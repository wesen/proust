<?php
/*
 * Proust - Mustache PHP Compiler - Test the Mustache specification
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

require_once(dirname(__FILE__)."/../vendor/simpletest/autorun.php");
require_once(dirname(__FILE__)."/../Proust.php");
require_once(dirname(__FILE__)."/../vendor/yaml/lib/sfYamlParser.php");



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
    $parser = new sfYamlParser();

    $m = new Proust\Proust(array("enableCache" => true,
                                 "cacheDir" => dirname(__FILE__)."/spec.cache",
                                 "compilerOptions" => array("beautify" => false,
                                                            "includeDynamicPartials" => true)));
    $m->clearCache();

    $methods = array();
    
    foreach (glob(SPEC_DIR."*.yml") as $file) {
      $name = str_replace(".yml", "", basename($file));
      $contents = file_get_contents($file);
      /* hack around sfyaml */
      $contents = str_replace("!code", "", $contents);
      
      $yaml = $parser->parse($contents);
      $yaml["name"] = $name;
      $i = 0;
      foreach ($yaml["tests"] as &$test) {
        if (array_key_exists("lambda", $test["data"])) {
          $code = "return function (\$text = \"\") { ".$test["data"]["lambda"]["php"]." };";
          $test["data"]["lambda"] = eval($code);
        }
        $name = preg_replace('/[^a-zA-Z0-9]/', '_', $name);
        $test["method_name"] = "$name"."_".$i;

        array_push($methods, array($test["method_name"], $test["template"]));
        $this->tests[$name."_$i"] = $test;
        $i++;
      }
      $this->specs[$name] = $yaml;
    }

    $classCode = $m->compileClass("Specs", $methods);
    file_put_contents("/tmp/specClass.php", $classCode);
    eval($classCode);
    $m = new Proust\Proust(array("enableCache" => false));
    $this->obj = new Specs($m);
  }

  public function setUp() {
    /* special hack for one global variable */
    global $calls;
    $calls = 0;
  }    

  function createInvoker() {
    return new SimpleErrorTrappingInvoker(
                                          new SimpleExceptionTrappingInvoker(new SpecInvoker($this)));
  }

  public function runTestWithObject($test) {
    $this->setUp();
    $methodName = $test["method_name"];

    if (array_key_exists("partials", $test)) {
      $this->obj->proust->partials = $test["partials"];
    }

    $res = $this->obj->$methodName($test["data"]);
    $info = "CLASS CALLING";
    
    $msg = "$info\nSpecification error: ".$test["desc"]."\n".
      "Got :\n------\n*".print_r($res, true)."*\n------\n".
      "Expected :\n------\n*".print_r($test["expected"], true)."*\n------\n".
      "Template: \n------\n*".print_r($test["template"], true)."*\n-------\n";
    $msg = str_replace('%', '%%', $msg);
    
    $this->assertEqual($res, $test["expected"], $msg);
    $this->tearDown();
  }
  
  public function runTestWithProust($m, $test, $info) {
    $this->setUp();
    if (array_key_exists("partials", $test)) {
      $m->partials = $test["partials"];
    }
    $res = $m->render($test["template"], $test["data"]);
      
    $msg = "$info\nSpecification error: ".$test["desc"]."\n".
      "Got :\n------\n*".print_r($res, true)."*\n------\n".
      "Expected :\n------\n*".print_r($test["expected"], true)."*\n------\n".
      "Template: \n------\n*".print_r($test["template"], true)."*\n-------\n";
    $msg = str_replace('%', '%%', $msg);
    
    $this->assertEqual($res, $test["expected"], $msg);
    $this->tearDown();
  }
  
  public function runTest($test) {
    $m = new Proust\Proust(array("enableCache" => false));
    $this->runTestWithProust($m, $test, "NORMAL");

    /* run again with include partials */
    $m = new Proust\Proust(array("enableCache" => false,
                            "compilerOptions" => array("includePartialCode" => true)));
    $this->runTestWithProust($m, $test, "INCLUDE PARTIALS");

    /* run again with beautify */
    $m = new Proust\Proust(array("enableCache" => false,
                                 "compilerOptions" => array("beautify" => true)));
    $this->runTestWithProust($m, $test, "BEAUTIFY");

    
    /* run again with no objects */
    $m = new Proust\Proust(array("enableCache" => false,
                                     "disableObjects" => true,
                                     "compilerOptions" => array("includePartialCode" => true)));
    $this->runTestWithProust($m, $test, "DISABLE OBJECTS");
    
    
    /* test with disabled lambdas when test is not for lambdas */
    if (!preg_match("/lambdas/", $test["method_name"])) {
      $m = new Proust\Proust(array("enableCache" => false,
                              "compilerOptions" => array("disableLambdas" => true)));
      $this->runTestWithProust($m, $test, "DISABLED LAMBDAS");
    }

    $this->runTestWithObject($test);
        
    if (!preg_match("/partials/", $test["method_name"])) {
      $m = new Proust\Proust(array("enableCache" => false,
                              "compilerOptions" => array("disableIndentation" => true)));
      $this->runTestWithProust($m, $test, "DISABLED INDENTATION");
    }
      
    
    /* test caching */
    $m = new Proust\Proust(array("enableCache" => true,
                            "cacheDir" => dirname(__FILE__)."/spec.cache"));

    $this->runTestWithProust($m, $test, "CACHE ENABLED, FIRST RUN");
    $m->resetContext();
    $this->runTestWithProust($m, $test, "CACHE ENABLED, SECOND RUN");

    $m = new Proust\Proust(array("enableCache" => true,
                            "cacheDir" => dirname(__FILE__)."/spec.cache"));
    $this->runTestWithProust($m, $test, "CACHE ENABLED, THIRD RUN");
    $m->resetContext();
    $this->runTestWithProust($m, $test, "CACHE ENABLED, FOURTH RUN");

    /* test caching with different compiler options */
    if (!preg_match("/lambdas/", $test["method_name"])) {
      $m = new Proust\Proust(array("enableCache" => true,
                              "cacheDir" => dirname(__FILE__)."/spec.cache",
                              "compilerOptions" => array("includePartialCode" => true,
                                                         "disableLambdas" => true)));
      $this->runTestWithProust($m, $test, "CACHE ENABLED, FIRST RUN, DISABLED LAMBDAS, INCLUDE PARTIALS");
      $m->resetContext();
      $this->runTestWithProust($m, $test, "CACHE ENABLED, FIRST RUN, DISABLED LAMBDAS, INCLUDE PARTIALS");
    }
    
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

  public function run($reporter) {
    $context = SimpleTest::getContext();
    $context->setTest($this);
    $context->setReporter($reporter);
    $this->reporter = $reporter;
    $started = false;
    foreach ($this->specs as $spec) {
      foreach ($spec["tests"] as $test) {
        $method = $test["method_name"];
        if ($reporter->shouldInvoke($spec["name"], $method)) {
          if (! $started) {
            $reporter->paintCaseStart($this->getLabel());
            $started = true;
          }
          $invoker = $this->reporter->createInvoker($this->createInvoker());
          $invoker->before($method);
          $invoker->invoke($method);
          $invoker->after($method);
        }
      }
    }
    if ($started) {
      $reporter->paintCaseEnd($this->getLabel());
    }
    unset($this->reporter);
    $context->setTest(null);
    return $reporter->getStatus();
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
    $res = array_merge(array(), array_keys($this->tests));
    return $res;
  }
};

?>



