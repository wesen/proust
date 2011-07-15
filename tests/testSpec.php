<?php
/*
 * Mustache PHP Compiler - Test the Mustache specification
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

require_once(dirname(__FILE__)."/../vendor/simpletest/autorun.php");
require_once(dirname(__FILE__)."/../vendor/spyc/spyc.php");
require_once(dirname(__FILE__)."/../Mustache.php");

class TestSpec extends UnitTestCase {
  public function loadSpec($file) {
    return Spyc::YAMLLoad(dirname(__FILE__)."/../spec/specs/$file.yml");
  }
  
  public function setUp() {
  }

  public function runSpec($spec) {
    $tests = $spec["tests"];
    foreach (array_slice($tests, 0, 5) as $test) {
      //      print_r($test);
      $m = new Mustache();
      if (array_key_exists("partials", $test)) {
        $m->partials = $test["partials"];
      }
      $res = $m->render($test["template"], $test["data"]);
      $this->assertEqual($res, $test["expected"],
                         "Specification error: *".$test["name"]."*: ".$test["desc"]."\n".
                         "Got :\n------\n".print_r($res, true)."\n------\n".
                         "Expected :\n------\n".print_r($test["expected"], true)."\n------\n");
    }
  }
  
  public function testComments() {
    $spec = $this->loadSpec("comments");
    $this->runSpec($spec);
  }

  public function testDelimiters() {
    $spec = $this->loadSpec("delimiters");
    $this->runSpec($spec);
  }

  public function testInterpolation() {
    $spec = $this->loadSpec("interpolation");
    $this->runSpec($spec);
  }

  public function testInverted() {
    $spec = $this->loadSpec("inverted");
    $this->runSpec($spec);
  }

  public function testPartials() {
    $spec = $this->loadSpec("partials");
    $this->runSpec($spec);
  }

  public function testSections() {
    $spec = $this->loadSpec("sections");
    $this->runSpec($spec);
  }

  public function testLambda() {
    $spec = $this->loadSpec("sections");
    $this->runSpec($spec);
  }
  
};

?>



