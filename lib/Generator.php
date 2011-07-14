<?php

/*
 * Template PHP Compiler
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

namespace Mustache;

class Generator {
  public function __construct() {
  }

  public function compile($tokens) {
    return "ob_start();\n".$this->compile_sub($tokens)."\nreturn ob_get_clean();\n";
  }

  public static function escape($str) {
    return str_replace("'", "\\'", $str);
  }

  public static function isAssoc($a) {
    $a = array_keys($a); return ($a != array_keys($a));
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
      return "echo '".self::escape($tokens[1])."';";
      break;

    case ":mustache":
      $method = "on_".substr($tokens[1], 1);
      if (method_exists($this, $method)) {
        return call_user_func_array(array($this, $method), array_slice($tokens, 2));
      } else {
        throw new \Exception("Unhandled mustache expression ".$tokens[1]);
      }
      break;

    default:
      throw new \Exception("Unhandled expression ".$tokens[0]);
      break;
    }
    return "";
  }

  public function on_section($name, $content) {
    $code = $this->compile_sub($content);
    $name = self::escape($name);
    $res = <<<EOD
\$f = function () use (\$ctx) { $code };
\$v = \$ctx['$name'];
if (\$v || (\$v === 0)) {
  if (\$v === true) {
    \$f();
  } else if (is_callable(\$v)) {
    ob_start(); \$f(); \$s = ob_get_clean();
    echo \$v(\$s);
  } else {
    if (!(is_array(\$v) || \$v instanceof \\Traversable) || Mustache\Generator::isAssoc(\$v)) {
      \$v = array(\$v);
    }
    foreach (\$v as \$_v) {
      \$ctx->push(\$_v);
      \$f();
      \$ctx->pop();
    }
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

  public function on_partial($name) {
    return "\$ctx->partial('$name');";
  }

  public function on_utag($name) {
    return "echo \$ctx['$name'];";
  }

  public function on_etag($name) {
    return "echo htmlspecialchars(\$ctx['$name']);";
  }
}

?>
