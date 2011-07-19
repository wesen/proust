<?php

/*
 * Proust - Mustache PHP Compiler - Test the helpers
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

require_once(dirname(__FILE__)."/../vendor/simpletest/autorun.php");
require_once(dirname(__FILE__)."/../Proust.php");

class Test {
  public function __construct() {
    $this->bla = 2;
  }
}

class TestHelpers extends UnitTestCase {
  public function testIsAssoc() {
    $a = array(1, 2, 3, 4);
    $this->assertFalse(Proust\Generator::isAssoc($a));

    $a = array("foo", "bla", "hicks");
    $this->assertFalse(Proust\Generator::isAssoc($a));

    $a = array("foo" => "bla");
    $this->assertTrue(Proust\Generator::isAssoc($a));

    $a = array();
    $this->assertFalse(Proust\Generator::isAssoc($a));
    
    $a = array(0 => 1, 2 => 3, 5 => 7);
    $this->assertTrue(Proust\Generator::isAssoc($a));

    $a = array(1 => 0, 0 => 2, 2 => 3);
    $this->assertTrue(Proust\Generator::isAssoc($a));
  }

  public function objectSetOptions() {
    $t = new Test();
    objectSetOptions($t, array("foo" => 1));
    $this->assertEqual($t->foo, 1);
    $this->assertEqual($t->bla, 2);

    $t = new Test();
    objectSetOptions($t, array("foo" => 1, "bla" => 3));
    $this->assertEqual($t->foo, 1);
    $this->assertEqual($t->bla, 3);
  }
};

?>