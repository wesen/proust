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
    return "ob_start();\n".$this->compile_sub($tokens)."\nreturn ob_end_clean();\n";
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
      return "echo '".addslashes($tokens[1])."';";
      break;

    case ":mustache":
      $method = "on_".substr($tokens[1], 1);
      call_user_method_array($method, $this, array_slice($tokens, 2));
      break;

    default:
      throw new \Exception("Unhandled expression ".$tokens[0]);
      break;
    }
    return "";
  }

  public function on_section($name, $content) {
    $code = $this->compile($content);
    $name = addslashes($name);
    return <<<EOD
      \$f = function () { $code };
      if ((\$v = \$ctx['$name']) !== null) {
        if (\$v == true) {
          echo \$f();
        } else if (is_callable(\$v)) {
          echo \$v(\$f());
        } else {
          foreach (\$v as \$_v) {
            \$ctx->push(\$_v);
            echo \$f();
            \$ctx->pop();
          }
        }
      }
EOD;
  }

  public function on_inverted_section($name, $content) {
  }

  public function on_partial($name) {
  }

  public function on_utag($name) {
  }

  public function on_etag($name) {
  }
}

?>
