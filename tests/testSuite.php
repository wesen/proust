<?php

require_once(dirname(__FILE__).'/../vendor/simpletest/autorun.php');
require_once(dirname(__FILE__).'/../Mustache.php');

class MustacheTestSuite extends TestSuite {
  function MustacheTestSuite() {
    $this->TestSuite('All Mustache tests');
    $this->addFile("tests/testStringScanner.php");
    $this->addFile("tests/testMustache.php");
    $this->addFile("tests/testContext.php");
  }
};

?>