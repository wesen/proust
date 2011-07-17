<?php
/*
 * Mustache PHP Compiler - Test the Mustache class
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

require_once(dirname(__FILE__)."/../vendor/simpletest/autorun.php");
require_once(dirname(__FILE__)."/../Mustache.php");

function __filename($file) {
  return dirname(__FILE__).'/files/'.$file;
}

function mustacheForFile($file) {
  $m = new Mustache();
  $m->setTemplateFile(dirname(__FILE__)."/files/$file");
  return $m;
}

class TestCaching extends UnitTestCase {
  function setUp() {
    $this->m = new Mustache();
  }
};

?>
