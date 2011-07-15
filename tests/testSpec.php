<?php
/*
 * Mustache PHP Compiler - Test the Mustache specification
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

require_once(dirname(__FILE__)."/../vendor/simpletest/autorun.php");
require_once(dirname(__FILE__)."/../vendor/spyc/spyc.php");
require_once(dirname(__FILE__)."/../Mustache.php");


define('SPEC_DIR', dirname(__FILE__)."/../spec/specs/");

class SpecInvoker extends SimpleInvoker {
  function __construct($obj) {
    parent::__construct($obj);
    $this->spec_case = $obj;
  }
  
  function invoke($method) {
    if (array_key_exists($method, $this->spec_case->specs)) {
      $this->spec_case->runSpec($this->spec_case->specs[$method]);
    } else if (array_key_exists($method, $this->spec_case->tests)) {
      $this->spec_case->runTest($this->spec_case->tests[$method]);
    }
  }
}

class TestSpec extends UnitTestCase {

  public function __construct() {
    parent::__construct();
    $this->specs = array();
    $this->tests = array();

    foreach (glob(SPEC_DIR."*.yml") as $file) {
      $name = str_replace(".yml", "", basename($file));
      $yaml = Spyc::YAMLLOAD($file);
      $this->specs[$name] = $yaml;
      
      $i = 0;
      foreach ($yaml["tests"] as $test) {
        $this->tests[$name."_$i"] = $test;
        $i++;
      }
    }
  }

  function createInvoker() {
    return new SimpleErrorTrappingInvoker(
                                          new SimpleExceptionTrappingInvoker(new SpecInvoker($this)));
  }
  
  public function loadSpec($file) {
    return Spyc::YAMLLoad(dirname(__FILE__)."/../spec/specs/$file.yml");
  }
  
  public function runTest($test) {
      $this->setUp();
      $m = new Mustache();
      if (array_key_exists("partials", $test)) {
        $m->partials = $test["partials"];
      }
      $res = $m->render($test["template"], $test["data"]);
      $this->assertEqual($res, $test["expected"],
                         "Specification error: *".$test["name"]."*: ".$test["desc"]."\n".
                         "Got :\n------\n".print_r($res, true)."\n------\n".
                         "Expected :\n------\n".print_r($test["expected"], true)."\n------\n".
                         "Template: \n------\n".print_r($test["template"], true)."\n-------\n");
      $this->tearDown();
  }

  public function runSpec($spec) {
    $tests = $spec["tests"];
    foreach ($tests as $test) {
      $this->runTest($test);
    }
  }

  function fail($message = "Fail") {
    if (! isset($this->reporter)) {
      trigger_error('Can only make assertions within test methods');
    }
    $tests = $this->reporter->getTestList();
    $name = end($tests);
    $test = $this->tests[$name];
    print "$message in test '$name' (".$test["name"].")\n";
    return false;
  }

  public function getTests() {
    $res = array_merge(array_keys($this->specs), array_keys($this->tests));
    //    echo "tests: ".print_r($res, true)."\n";
    return $res;
  }
};

?>



