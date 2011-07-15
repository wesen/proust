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
      array_walk_recursive($yaml, function (&$x) {
          if (is_numeric($x)) {
            /* XXX hack around spyc */
            $x = (float)$x;
          } else if (is_string($x)) {
            $x = stripcslashes($x);
          }
        });
      $yaml["name"] = $name;
      $i = 0;
      foreach ($yaml["tests"] as &$test) {
        if (array_key_exists("lambda", $test["data"])) {
          $code = "return function (\$text = \"\") { ".$test["data"]["lambda"]["php"]." };";
          $test["data"]["lambda"] = eval($code);
        }
        $test["method_name"] = "$name"."_".$i;
        $this->tests[$name."_$i"] = $test;
        $i++;
      }
      $this->specs[$name] = $yaml;
      
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
    $msg = "Specification error: ".$test["desc"]."\n".
      "Got :\n------\n*".print_r($res, true)."*\n------\n".
      "Expected :\n------\n*".print_r($test["expected"], true)."*\n------\n".
      "Template: \n------\n*".print_r($test["template"], true)."*\n-------\n";
    $msg = str_replace('%', '%%', $msg);
    
    $this->assertEqual($res, $test["expected"], $msg);
    $this->tearDown();
  }

  public function runSpec($spec) {
    $tests = $spec["tests"];
    $this->reporter->paintGroupStart($spec["name"], count($tests));
    $i = 0;
    foreach ($tests as $test) {
      $this->reporter->paintMethodStart($spec["name"]."_$i");
      $this->runTest($test);
      $this->reporter->paintMethodEnd($spec["name"]."_$i");
      $i++;
    }
    $this->reporter->paintGroupEnd($spec["name"]);
  }

  function fail($message = "Fail") {
    if (! isset($this->reporter)) {
      trigger_error('Can only make assertions within test methods');
    }
    $tests = $this->reporter->getTestList();
    $name = end($tests);
    $test = $this->tests[$name];
    parent::fail($message);
    return false;
  }

  public function getTests() {
    $res = array_merge(array_keys($this->specs), array_keys($this->tests));
    return $res;
  }
};

?>



