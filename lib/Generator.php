<?php

/*
 * Template PHP Compiler
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

namespace Mustache;

class Generator {
  public $compileToVar = null;
  public $compileToFunction = null;
  public $compileToMethod = null;
  public $includePartialCode = false;
  public $context = null;
  
  public function __construct(array $options = array()) {
    $defaults = array("compileToVar" => null,
                      "compileToFunction" => null,
                      "compileToMethod" => null,
                      "includePartialCode" => false,
                      "context" => null);

    $options = array_merge($defaults, $options);
    object_set_options($this, $options, array_keys($defaults));
  }

  public function getTokens($code) {
    $parser = new Parser();
    $tokens = $parser->parse($code, $this->context);
    return $tokens;
  }
  
  public function compileCode($code) {
    $tokens = $this->getTokens($code);
    return $this->compile($tokens, $code);
  }

  public function compile($tokens, $code = "") {
    $compiledCode = "\$src = '".self::escape($code)."'; ob_start();\n".$this->compile_sub($tokens)."\nreturn ob_get_clean();\n";

    if ($this->compileToVar !== null) {
      return "\$".$this->compileToVar." = function (\$ctx) { $compiledCode };";
    }
    if ($this->compileToFunction !== null) {
      return "function ".$this->compileToFunction." (\$ctx) { $compiledCode };";
    }
    if ($this->compileToMethod !== null) {
      return "function ".$this->compileToFunction." () { \$ctx = \$this->context; $compiledCode };";
    }

    return $compiledCode;
  }

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
      return "\$ctx->output('".self::escape($tokens[1])."');";
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
      return "\$ctx->newline();";
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
    $res = <<<EOD

/* section $name */
\$v = \$ctx['$name'];
if (is_callable(\$v)) {
  Mustache\\Context::PushContext(\$ctx);
  \$ctx->output(\$ctx->render(\$v(substr(\$src, $start, $len))));
  Mustache\\Context::PopContext(\$ctx);
} else {
  \$$functionName = function () use (\$ctx) { $code };

  if (is_array(\$v) || \$v instanceof \\Traversable) {
    if (Mustache\Generator::isAssoc(\$v)) {
      \$v = array(\$v);
    }
    foreach (\$v as \$_v) {
      \$ctx->push(\$_v);
      \$$functionName();
      \$ctx->pop();
    }
  } else if (\$v) {
    \$$functionName();
  }
}
EOD;
return $res;
  }

  public function on_inverted_section($name, $content) {
    $code = $this->compile_sub($content);
    $name = self::escape($name);
    return "\$v = \$ctx['$name']; if (!\$v && (\$v !== 0)) { $code }";
  }

  public function on_partial($name, $indentation) {
    return "\$ctx->output(\$ctx->partial('$name', '$indentation'));";
  }

  public function on_utag($name) {
    return "\$ctx->output(\$ctx->fetch('$name', true, null));";
  }

  public function on_etag($name) {
    return "\$ctx->output(htmlspecialchars(\$ctx->fetch('$name', true, null)));";
  }

  public function on_tag_change($otag, $ctag) {
    return "\$ctx->setDelimiters('$otag', '$ctag');";
  }
}

?>
