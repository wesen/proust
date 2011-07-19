<?php

/*
 * Proust - Mustache PHP Compiler - Code generator
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 *
 * This class compiles a parsed mustache template to PHP code. It can
 * take different compiler options.
 *
 * - includePartialCode -> partials are directly compiled and included in the output PHP code
 * - disableLambdas -> no function or method calls in the output
 * - disableIndentation -> no indenting of partials, makes for faster output
 */

namespace Proust;


class Template {
  public function __construct($proust = null) {
    if ($proust === null) {
      $proust = new \Proust();
    }
    $this->proust = $proust;
    $this->context = $proust->getContext();
  }

  public function __call($name, $arguments) {
    return $this->proust->renderPartial($name);
  }
};

class TokenWalker {
  public static $defaults = array("errorOnUnhandled" => false);
  
  public function __construct(array $options = array()) {
    $this->options = array_merge(self::$defaults, $options);
    object_set_options($this, $this->options, array_keys($options));
  }

  public function dispatch($type, $args, $tokens) {
    $method = "on_$type";
    if (method_exists($this, $method)) {
      return call_user_func_array(array($this, $method), $args);
    } else {
      return $this->__default($tokens);
    }
  }

  /**
   * Given an array of tokens, convert them into php code. In
   * particular, there are three types of expression we are concerned
   * with:
   *
   *  :multi -> mixed bag of :static, :mustache and whatever
   *
   *  :static -> normal HTML, the stuff outside of {{mustaches}}.
   *
   *  :mustache -> any mustache tag, from sections to partials
   **/
  public function walk($tokens) {
    //    debug_print_backtrace();
    switch ($tokens[0]) {
    case ":multi":
      return $this->dispatch("multi", array(array_slice($tokens, 1)), $tokens);

    case ":static":
      return $this->dispatch("static", array($tokens[1]), $tokens);

    case ":mustache":
      return $this->dispatch(substr($tokens[1], 1), array_slice($tokens, 2), $tokens);

    case ":newline":
      return $this->dispatch("newline", array(), $tokens);

    default:
      return $this->__default($tokens);
    }

    return "";
  }

  public function __default($tokens) {
    if ($this->errorOnUnhandled) {
      throw new \Exception("Unhandled expression ".$tokens[0]);
    }
  }

  public function on_multi($tokens) {
    foreach ($tokens as $token) {
      $this->walk($token);
    }
  }

  public function on_section($name, $content, $start, $end) {
    $this->walk($content);
  }

  public function on_inverted_section($name, $content) {
    $this->walk($content);
  }
};

class IdentityWalker extends TokenWalker {
  public function __construct(array $options = array()) {
    parent::__construct(array_merge(self::$defaults, $options));
  }

  public function recurse($tokens) {
    $foo = $this;
    $res = array_map(function ($x) use ($foo) { return $foo->walk($x); }, $tokens);
    return $res;
  }
  
  public function on_multi($tokens) {
    $arr = $this->recurse($tokens);
    array_unshift($arr, ":multi");
    return $arr;
  }

  public function on_section($name, $content, $start, $end) {
    return array(":mustache", ":section", "$name", $this->recurse($content), $start, $end);
  }

  public function on_inverted_section($name, $content) {
    return array(":mustache", ":inverted_section", "$name", $this->recurse($content));
  }

  public function __default($tokens) {
    return $tokens;
  }
}

class IndentationRemover extends IdentityWalker {
  public function __construct(array $options = array()) {
    parent::__construct(array_merge(self::$defaults, $options));
  }

  public function on_multi($tokens) {
    $len = count($tokens);
    if ($len == 0) {
      return array(":multi");
    }
    
    $res = array($tokens[0]);

    for ($i = 1; $i < $len; $i++) {
      $token = $this->walk($tokens[$i]);
      $end = count($res);
      $last = &$res[$end -1];
      switch ($token[0]) {
      case ":static":
        if ($last[0] == ":static") {
          $last[1] = $last[1].$token[1];
        } else {
          array_push($res, $token);
        }
        break;

      case ":newline":
        if ($last[0] == ":static") {
          $last[1] = $last[1]."\n";
        } else {
          array_push($res, array(":static", "\n"));
        }
        break;
        
      default:
        array_push($res, $token);
        break;
      }
    }

    array_unshift($res, ":multi");
    return $res;
  }
    
}

class Generator extends TokenWalker {
  public static $defaults = array("includePartialCode" => false,
                                  "disableLambdas" => false,
                                  "disableIndentation" => false,
                                  "compileClass" => false,
                                  "outputFunction" => "\$ctx->output",
                                  "newlineFunction" => "\$ctx->newline()",
                                  "beautify" => false,
                                  "proust" => null,
                                  "errorOnUnhandled" => true);
  public $proust = null;
  public $includePartialCode = false;
  public $disableLambdas = false;
  public $disableObject = false;

  /***************************************************************************
   *
   * Helper functions
   *
   ***************************************************************************/
  public static function escape($str) {
    return str_replace(array("\\", "'"),
                       array("\\\\", "\\'"),
                       $str);
  }

  public static function functionName($name) {
    $name = preg_replace('/[^a-zA-Z0-9]/', '_', $name);
    return $name;
  }

  public static function isAssoc($array) {
    foreach (array_keys($array) as $k => $v) {
      if ($k !== $v)
        return true;
    }
    return false;
  }

  /***************************************************************************
   *
   * Constructor
   *
   ***************************************************************************/
  public function __construct(array $options = array()) {
    parent::__construct(array_merge(self::$defaults, $options));

    if ($this->disableIndentation) {
      $this->outputFunction = 'echo';
      $this->newlineFunction = 'echo("\n")';
    }

  }

  /** Parse $code into a token array. **/
  public function getTokens($code) {
    $parser = new Parser();
    $tokens = $parser->parse($code, $this->proust->getContext());
    return $tokens;
  }

  /* Compile the code with the given options */
  public function compileCode($code, $options = array()) {
    $tokens = $this->getTokens($code);
    if ($tokens === array(":multi")) {
      return "";
    }
    return $this->compile($tokens, $code, $options);
  }

  public function beautifyString($str) {
    if ($this->beautify) {
      if (!@include_once 'PHP/Beautifier.php') {
        return $str;
      }
      
      $o_b = new \PHP_Beautifier();
      $o_b->setBeautify(true);
      $o_b->addFilter('ListClassFunction');
      $o_b->setIndentChar(' ');
      $o_b->setIndentNumber(2);
      $o_b->setNewLine("\n");
      $o_b->setInputString("<? $str ?>");
      $o_b->process();
      $res = $o_b->get();
      $res = preg_replace('/^\<\?\s+/', '', $res);
      $res = preg_replace('/\s+\?\>$/', '', $res);
      return $res;
    } else {
      return $str;
    }
  }

  /* compiles the given methods to a class. */
  public function compileClass($className, $codes) {
    $this->compileClass = true;
    $this->compiledMethods = array();
    $this->methodsToCompile = $codes;

    $prevBeautify = $this->beautify;
    $this->beautify = false;
    
    $res = "class $className extends Proust\Template {\n";
    while (count($this->methodsToCompile) > 0) {
      $method = array_pop($this->methodsToCompile);
      $name = $method[0];
      $code = $method[1];

      if (in_array($name, $this->compiledMethods)) {
        continue;
      }

      $res .= "/* method for $name */\n";
      array_push($this->compiledMethods, $name);
      $res .= $this->compileCode($code, array("type" => "method",
                                              "name" => "$name"));
      $res .= "\n";
    }

    $res .= "};\n";

    $this->beautify = $prevBeautify;
    
    return $this->beautifyString($res);
    // return $res;
  }

  /**
   * Compile the tokens with the given options. Original source should
   * be passed as $code for lambda sections to get the raw input.
   **/
  public function compile($tokens, $code = "", $options = array()) {
    $defaults = array("type" => "captured",
                      "name" => "f");
    $options = array_merge($defaults, $options);

    if ($this->disableIndentation) {
      $c = new IndentationRemover();
      $tokens = $c->walk($tokens);
    }

    $this->codeLines = array();
    $this->walk($tokens);
    $compiledCode = implode("\n", $this->codeLines);

    /* we have been called by a parent compiler */
    if ($options["type"] == "sub") {
      $res = $compiledCode;
      goto compile_return;
    }
    
    if (!$this->disableLambdas) {
      $compiledCode = "if (!isset(\$src)) { \$src = array(); }; ".
        "array_push(\$src, \n/* template source */\n'".self::escape($code)."'\n);\n".
        $compiledCode."array_pop(\$src);\n";
    }
    $compiledCodeCapture = "ob_start();\n".$compiledCode."return ob_get_clean();\n";

    switch ($options["type"]) {
    case "variable":
      $res = "\$".$options["name"]." = function (\$ctx) { ".$compiledCodeCapture." };";
      break;
      
    case "function":
      $res = "function ".$options["name"]." (\$ctx) { ".$compiledCodeCapture." };";
      break;
      
    case "method":
      $res = "function ".$options["name"]." (\$data = null) {\n".
        "  \$ctx = \$this->context; \$ctx->reset(\$data);\n".
        "  ".$compiledCodeCapture."\n".
        "}\n";
      break;

    case "captured":
      $res = $compiledCodeCapture;
      break;

    case "raw":
    default:
      $res = $compiledCode;
    break;
    }
    
  compile_return:
    return $this->beautifyString($res);
  }

  public function subCompile($tokens) {
    $c = new Generator($this->options);
    return $c->compile($tokens, "", array("type" => "sub"));
  }

  public function pushLine($str) {
    array_push($this->codeLines, $str);
  }

  public function on_static($text) {
    $this->pushLine($this->outputFunction."('".self::escape($text)."');");
  }
    
  public function on_newline() {
    $this->pushLine($this->newlineFunction.";");
  }

  public function on_section($name, $content, $start, $end) {
    $code = $this->subCompile($content);
    $name = self::escape($name);
    $functionName = "__section_".self::functionName($name);
    $len = $end - $start;

    $iterationSection = "\$$functionName = function () use (\$ctx) { $code };

if (is_array(\$v) || \$v instanceof \\Traversable) {
  if (Proust\Generator::isAssoc(\$v)) {
    \$ctx->push(\$v);
    \$$functionName();
    \$ctx->pop();
  } else {
    foreach (\$v as \$_v) {
      \$ctx->push(\$_v);
      \$$functionName();
      \$ctx->pop();
    }
  }
} else if (\$v) {
  \$$functionName();
}";
    
    if ($this->disableLambdas) {
      $res = "/* section $name */
\$v = \$ctx['$name'];
$iterationSection
/* end section $name */
";
    } else {
      $res = "/* section $name */
\$v = \$ctx['$name'];
if (is_callable(\$v)) {
  Proust\\Context::PushContext(\$ctx);
  ".$this->outputFunction."(\$ctx->render(\$v(substr(end(\$src), $start, $len))));
  Proust\\Context::PopContext(\$ctx);
} else {
  $iterationSection
}
/* end section $name */
";
    }

    $this->pushLine($res);
  }

  public function on_inverted_section($name, $content) {
    $code = $this->subCompile($content);
    $name = self::escape($name);
    $this->pushLine("/* inverted section $name */\n\$v = \$ctx['$name']; if (!\$v && (\$v !== 0)) { $code }\n");
  }
  
  public function on_partial($name, $indentation) {
    $ctx = $this->proust->getContext();
    
    if ($this->compileClass) {
      // use echo here because we already handled indentation in the partial itself
      $str = "\$ctx->pushPartial('$name', '$indentation');\n".
        "echo (\$this->".self::functionName($name)."());\n".
        "\$ctx->popPartial('$name');\n".
        "/* end partial include $name */\n";
    } else {
      $str = $this->outputFunction."(\$ctx->partial('$name', '$indentation'));\n";
    }
    
    if (!$this->includePartialCode) {
      if ($this->compileClass) {
        /* add partial to be compiled */
        $m = $this->proust;
        $code = $m->getPartial($name);
        array_push($this->methodsToCompile, array($name, $code));
      }
      $this->pushLine($str);
      return;
    } else {
      $m = $this->proust;
      $ctx = $m->getContext();

      if ($ctx->isPartialRecursion($name)) {
        if ($this->compileClass) {
          /* add partial to be compiled */
          $code = $m->getPartial($name);
          array_push($this->methodsToCompile, array($name, $code));
        }
        /* revert to normal partial call. */
        $this->pushLine($str);
        return;
      }

      $ctx->pushPartial($name, $indentation);
      $code = $m->getPartial($name);
      $c = new Generator($this->options);
      $res = $c->compileCode($code, array("type" => "raw"));
      $ctx->popPartial($name);

      $str = "/* partial included code $name */\n".
        "\$ctx->pushPartial('$name', '$indentation');\n".
        $res."\n".
        "\$ctx->popPartial('$name');\n".
        "/* end partial include $name */\n";
      $this->pushLine($str);
    }
  }

  public function on_utag($name) {
    if ($this->disableLambdas) {
      $res = $this->outputFunction."(\$ctx->fetch('$name', false, null));";
    } else {
      $res = $this->outputFunction."(\$ctx->fetch('$name', true, null));";
    }
    $this->pushLine($res);
  }

  public function on_etag($name) {
    if ($this->disableLambdas) {
      $res = $this->outputFunction."(htmlspecialchars(\$ctx->fetch('$name', false, null)));";
    } else {
      $res = $this->outputFunction."(htmlspecialchars(\$ctx->fetch('$name', true, null)));";
    }
    $this->pushLine($res);
  }

  public function on_tag_change($otag, $ctag) {
    $res = "\$ctx->setDelimiters('$otag', '$ctag');";
    $this->pushLine($res);
  }
}

?>
