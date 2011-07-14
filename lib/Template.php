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
      $this->compiled = $this->compile($this->data);
    }
    
    return $this->compiled($context);
  }

  public function compile($src = null) {
    if ($src == null) {
      $src = $this->data;
    }
    $generator = new Mustache\Generator();
    return $generator->compile($this->getTokens($src));
  }

  public function getTokens($src = null) {
    if ($src == null) {
      $src = $this->data;
    }
    $parser = new Mustache\Parser();
    return $parser->compile($src);
  }
};

?>