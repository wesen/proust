<?php
/*
 * Proust - Mustache PHP Compiler - Test the Proust class
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

require_once(dirname(__FILE__)."/../vendor/simpletest/autorun.php");
require_once(dirname(__FILE__)."/../Proust.php");

function __filename($file) {
  return dirname(__FILE__).'/files/'.$file;
}

function proustForFile($file) {
  $m = new Proust();
  $m->setTemplateFile(dirname(__FILE__)."/files/$file");
  return $m;
}

class TestCaching extends UnitTestCase {
  function setUp() {
    $this->m = new Proust();
  }
};

?>
