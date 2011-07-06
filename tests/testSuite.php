<?php

require_once('../vendor/simpletest/autorun.php');

require_once('../Mustache.php');

class MustacheTestSuite extends TestSuite {
  function MustacheTestSuite() {
    $this->TestSuite('All Mustache tests');
    $this->addFile("tests/testStringScanner.php");
  }
};

?>