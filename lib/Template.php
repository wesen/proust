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
    debug_log("current context ".print_r($context, true), 'COMPILER');
    debug_log("render template ".print_r($this->data, true).", compiled: ".print_r($this->compiled, true), 'COMPILER');
    if ($this->compiled == null) {
      $this->code = $this->compile($this->data);
      debug_log("template code ".print_r($this->code, true), 'COMPILER');
      $this->compiled = eval("return function (\$ctx) { ".$this->code." };");
    }

    $f = $this->compiled;
    $res = $f($context);
    debug_log("template evaluation: ".print_r($res, true), 'COMPILER');
    return $res;
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