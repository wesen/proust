<?php

require_once(dirname(__FILE__).'/../vendor/simpletest/autorun.php');
require_once(dirname(__FILE__).'/../Mustache.php');

class MustacheTestSuite extends TestSuite {
  function MustacheTestSuite() {
    $this->TestSuite('All Mustache tests');
    $this->addFile(dirname(__FILE__)."/testStringScanner.php");
    $this->addFile(dirname(__FILE__)."/testHelpers.php");
    $this->addFile(dirname(__FILE__)."/testMustache.php");
    $this->addFile(dirname(__FILE__)."/testContext.php");
    $this->addFile(dirname(__FILE__)."/testParser.php");
    $this->addFile(dirname(__FILE__)."/testGenerator.php");
    //    $this->addFile(dirname(__FILE__)."/testSpec.php");
  }
};

?>