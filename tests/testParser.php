<?php
/*
 * Mustache PHP Compiler - Test the Mustache class
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

require_once(dirname(__FILE__)."/../vendor/simpletest/autorun.php");
require_once(dirname(__FILE__)."/../Mustache.php");

class TestParser extends UnitTestCase {
  public function setUp() {
    $this->p = new Mustache\Parser();
  }
  
  public function testEmpty() {
    $res = $this->p->compile("");
    $this->assertEqual($res, array(":multi"));
  }
};

?>
