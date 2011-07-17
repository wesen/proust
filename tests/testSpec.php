<?php
/*
 * Mustache PHP Compiler - Test the Mustache specification
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

require_once(dirname(__FILE__)."/../vendor/simpletest/autorun.php");
require_once(dirname(__FILE__)."/../Mustache.php");
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
    //    ini_set('xdebug.show_exception_trace', 1);

    parent::__construct();
    $this->specs = array();
    $this->tests = array();
    $parser = new sfYamlParser();

    $m = new Mustache(array("enableCache" => true,
                            "cacheDir" => dirname(__FILE__)."/spec.cache"));
    $m->clearCache();
    
    foreach (glob(SPEC_DIR."*.yml") as $file) {
      $name = str_replace(".yml", "", basename($file));
      $contents = file_get_contents($file);
      /* hack around sfyaml */
      $contents = str_replace("!code", "", $contents);
      
      $yaml = $parser->parse($contents);
      /*
      array_walk_recursive($yaml, function (&$x) {
          if (is_numeric($x)) {
          // XXX hack around spyc
          $x = (float)$x;
          } else if (is_string($x)) {
          $x = stripcslashes($x);
          }
          });
      */
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

  public function runTestWithMustache($m, $test, $info) {
    /* special hack for one global variable */
    global $calls;
    $calls = 0;
    $this->setUp();
    if (array_key_exists("partials", $test)) {
      $m->partials = $test["partials"];
    }
    try {
      $res = $m->render($test["template"], $test["data"]);
    } catch (Exception $e) {
      throw $e;
    }
      
    $msg = "$info\nSpecification error: ".$test["desc"]."\n".
      "Got :\n------\n*".print_r($res, true)."*\n------\n".
      "Expected :\n------\n*".print_r($test["expected"], true)."*\n------\n".
      "Template: \n------\n*".print_r($test["template"], true)."*\n-------\n";
    $msg = str_replace('%', '%%', $msg);
    
    $this->assertEqual($res, $test["expected"], $msg);
    $this->tearDown();
  }
  
  public function runTest($test) {
    $m = new Mustache(array("enableCache" => false));
    $this->runTestWithMustache($m, $test, "NORMAL");

    /* run again with include partials */
    $m = new Mustache(array("enableCache" => false,
                            "compilerOptions" => array("includePartialCode" => true)));
    $this->runTestWithMustache($m, $test, "INCLUDE PARTIALS");
    
    /* test with disabled lambdas when test is not for lambdas */
    if (!preg_match("/lambdas/", $test["method_name"])) {
      $m = new Mustache(array("enableCache" => false,
                              "compilerOptions" => array("includePartialCode" => true,
                                                         "disableLambdas" => true)));
      $this->runTestWithMustache($m, $test, "DISABLED LAMBDAS");
    }

    if (!preg_match("/partials/", $test["method_name"])) {
      $m = new Mustache(array("enableCache" => false,
                              "compilerOptions" => array("disableIndentation" => true)));
      $this->runTestWithMustache($m, $test, "DISABLED INDENTATION");
    }
      
    
    /* test caching */
    $m = new Mustache(array("enableCache" => true,
                            "cacheDir" => dirname(__FILE__)."/spec.cache"));

    $this->runTestWithMustache($m, $test, "CACHE ENABLED, FIRST RUN");
    $m->resetContext();
    $this->runTestWithMustache($m, $test, "CACHE ENABLED, SECOND RUN");

    $m = new Mustache(array("enableCache" => true,
                            "cacheDir" => dirname(__FILE__)."/spec.cache"));
    $this->runTestWithMustache($m, $test, "CACHE ENABLED, THIRD RUN");
    $m->resetContext();
    $this->runTestWithMustache($m, $test, "CACHE ENABLED, FOURTH RUN");

    /* test caching with different compiler options */
    if (!preg_match("/lambdas/", $test["method_name"])) {
      $m = new Mustache(array("enableCache" => true,
                              "cacheDir" => dirname(__FILE__)."/spec.cache",
                              "compilerOptions" => array("includePartialCode" => true,
                                                         "disableLambdas" => true)));
      $this->runTestWithMustache($m, $test, "CACHE ENABLED, FIRST RUN, WITH OPTIONS");
      $m->resetContext();
      $this->runTestWithMustache($m, $test, "CACHE ENABLED, SECOND RUN, WITH OPTIONS");
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



