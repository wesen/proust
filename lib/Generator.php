<?php

/*
 * Template PHP Compiler - Code generator
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

namespace Mustache;

class Template {
  public function __construct($mustache = null) {
    if ($mustache === null) {
      $mustache = new \Mustache();
    }
    $this->mustache = $mustache;
    $this->context = $mustache->getContext();
  }

  public function __call($name, $arguments) {
    return $this->mustache->renderPartial($name);
  }
};

class Generator {
  public $mustache = null;
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
  
  public function __construct(array $options = array()) {
    $defaults = array("includePartialCode" => false,
                      "disableLambdas" => false,
                      "disableIndentation" => false,
                      "compileClass" => false,
                      "outputFunction" => "\$ctx->output",
                      "newlineFunction" => "\$ctx->newline()",
                      "mustache" => null);

    $options = array_merge($defaults, $options);
    object_set_options($this, $options, array_keys($defaults));

    if ($this->disableIndentation) {
      $this->outputFunction = 'echo';
      $this->newlineFunction = 'echo("\n")';
    }
  }

  /** Parse $code into a token array. **/
  public function getTokens($code) {
    $parser = new Parser();
    $tokens = $parser->parse($code, $this->mustache->getContext());
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

  public function compileClass($className, $codes) {
    $this->compileClass = true;
    $this->compiledMethods = array();
    $this->methodsToCompile = $codes;
    
    $res = "class $className extends Mustache\Template {\n";
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

    return $res;
  }

  /**
   * Compile the tokens with the given options. Original source should
   * be passed as $code for lambda sections to get the raw input.
   **/
  public function compile($tokens, $code = "", $options = array()) {
    $defaults = array("type" => "captured",
                      "name" => "f");
    $options = array_merge($defaults, $options);
    
    $compiledCode = "if (!isset(\$src)) { \$src = array(); }; ".
      "array_push(\$src, \n/* template source */\n'".self::escape($code)."'\n);\n".
      $this->compile_sub($tokens)."array_pop(\$src);\n";
    $compiledCodeCapture = "ob_start();\n".$compiledCode."return ob_get_clean();\n";

    switch ($options["type"]) {
    case "variable":
      return "\$".$options["name"]." = function (\$ctx) { $compiledCodeCapture };";
      
    case "function":
      return "function ".$options["name"]." (\$ctx) { $compiledCodeCapture };";
      
    case "method":
      return "function ".$options["name"]." (\$data = null) {\n".
        "  \$ctx = \$this->context; \$ctx->reset(\$data);\n".
        "  $compiledCodeCapture\n".
        "}\n";

    case "captured":
      return $compiledCodeCapture;

    case "raw":
    default:
      return $compiledCode;
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
  public function compile_sub($tokens) {
    switch ($tokens[0]) {
    case ":multi":
      {
        // hack closures
        $foo = $this;
        $arr = array_map(function ($token) use ($foo) {
            return $foo->compile_sub($token);
          },
          array_slice($tokens, 1));
        return join("\n", $arr);
      }
      break;

    case ":static":
      return $this->outputFunction."('".self::escape($tokens[1])."');";
      break;

    case ":mustache":
      $method = "on_".substr($tokens[1], 1);
      if (method_exists($this, $method)) {
        return call_user_func_array(array($this, $method), array_slice($tokens, 2));
      } else {
        throw new \Exception("Unhandled mustache expression ".$tokens[1]);
      }
      break;

    case ":newline":
      return $this->newlineFunction.";\n";
      break;

    default:
      throw new \Exception("Unhandled expression ".$tokens[0]);
      break;
    }
    return "";
  }

  public function on_section($name, $content, $start, $end) {
    $code = $this->compile_sub($content);
    $name = self::escape($name);
    $functionName = "__section_".self::functionName($name);
    $len = $end - $start;

    $iterationSection = "\$$functionName = function () use (\$ctx) { $code };

if (is_array(\$v) || \$v instanceof \\Traversable) {
  if (Mustache\Generator::isAssoc(\$v)) {
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
      return "/* section $name */
\$v = \$ctx['$name'];
$iterationSection
/* end section $name */
";
    } else {
      return "/* section $name */
\$v = \$ctx['$name'];
if (is_callable(\$v)) {
  Mustache\\Context::PushContext(\$ctx);
  ".$this->outputFunction."(\$ctx->render(\$v(substr(end(\$src), $start, $len))));
  Mustache\\Context::PopContext(\$ctx);
} else {
  $iterationSection
}
/* end section $name */
";
    }
  }

  public function on_inverted_section($name, $content) {
    $code = $this->compile_sub($content);
    $name = self::escape($name);
    return "/* inverted section $name */\n\$v = \$ctx['$name']; if (!\$v && (\$v !== 0)) { $code }\n";
  }

  public function on_partial($name, $indentation) {
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
          $m = $this->mustache;
          $code = $m->getPartial($name);
          array_push($this->methodsToCompile, array($name, $code));
        }
      return $str;
    } else {
      $m = $this->mustache;
      $ctx = $m->getContext();
      if ($ctx->isPartialRecursion($name)) {
        if ($this->compileClass) {
          /* add partial to be compiled */
          $code = $m->getPartial($name);
          array_push($this->methodsToCompile, array($name, $code));
        }
        /* revert to normal partial call. */
        return $str;
      }
      
      $ctx->pushPartial($name, $indentation);
      $code = $m->getPartial($name);
      $res = $this->compileCode($code, array("type" => "raw"));
      $ctx->popPartial($name);

      return "/* partial included code $name */\n".
        "\$ctx->pushPartial('$name', '$indentation');\n".
        $res."\n".
        "\$ctx->popPartial('$name');\n".
        "/* end partial include $name */\n";
    }
  }

  public function on_utag($name) {
    if ($this->disableLambdas) {
      return $this->outputFunction."(\$ctx->fetch('$name', false, null));";
    } else {
      return $this->outputFunction."(\$ctx->fetch('$name', true, null));";
    }
  }

  public function on_etag($name) {
    if ($this->disableLambdas) {
      return $this->outputFunction."(htmlspecialchars(\$ctx->fetch('$name', false, null)));";
    } else {
      return $this->outputFunction."(htmlspecialchars(\$ctx->fetch('$name', true, null)));";
    }
  }

  public function on_tag_change($otag, $ctag) {
    return "\$ctx->setDelimiters('$otag', '$ctag');";
  }
}

?>
