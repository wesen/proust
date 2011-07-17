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
  
  public function __construct($_data) {
    $this->data = $_data;
  }

  public function render($context) {
    debug_log("current context ".print_r($context, true), 'EVALUATION');
    $this->code = "\$src = '".Generator::escape($this->data)."'; ".$this->compile($this->data, $context);
    debug_log("template code ".print_r($this->code, true), 'COMPILER');
    $this->compiled = eval("return function (\$ctx) { ".$this->code." };");

    $f = $this->compiled;
    $res = $f($context);
    debug_log("template evaluation: ".print_r($res, true), 'COMPILER');
    return $res;
  }

  public function compile($src = null, $context = null) {
    if ($src == null) {
      $src = $this->data;
    }
    $generator = new Generator();
    $tokens = $this->getTokens($src, $context);
    return $generator->compile($tokens);
  }

  public function getTokens($src = null, $context = null) {
    if ($src == null) {
      $src = $this->data;
    }
    $parser = new Parser();
    if ($context !== null) {
      /* weird mixture of evaluation context and compilation context, but so it is. */
      if ($context->otag !== null) {
        $parser->otag = $context->otag;
      }
      if ($context->ctag !== null) {
        $parser->ctag = $context->ctag;
      }
    }
    return $parser->compile($src);
  }
};

?>