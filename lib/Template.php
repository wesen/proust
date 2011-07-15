<?php

/*
 * Mustache PHP Compiler
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

namespace Mustache;

require_once(dirname(__FILE__).'/Parser.php');
require_once(dirname(__FILE__).'/Generator.php');

class Template {
  protected $data = "";
  protected $compiled = null;
  
  public function __construct($_data) {
    $this->data = $_data;
  }

  public function render($context) {
    if ($this->compiled == null) {
      $code = $this->compile($this->data);
      //      echo "code: ".print_r($code, true)."\n";
      $this->compiled = eval("return function (\$ctx) { $code };");
    }

    $f = $this->compiled;
    return $f($context);
  }

  public function compile($src = null) {
    if ($src == null) {
      $src = $this->data;
    }
    $generator = new Generator();
    $tokens = $this->getTokens($src);
    //    echo "tokens: ".print_r($tokens, true)."\n";
    return $generator->compile($tokens);
  }

  public function getTokens($src = null) {
    if ($src == null) {
      $src = $this->data;
    }
    $parser = new Parser();
    return $parser->compile($src);
  }
};

?>