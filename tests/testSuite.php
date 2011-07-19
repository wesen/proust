<?php

require_once(dirname(__FILE__).'/../vendor/simpletest/autorun.php');
require_once(dirname(__FILE__).'/../Proust.php');

class ProustTestSuite extends TestSuite {
  function ProustTestSuite() {
    $this->TestSuite('All Proust tests');
    $this->addFile(dirname(__FILE__)."/testStringScanner.php");
    $this->addFile(dirname(__FILE__)."/testHelpers.php");
    $this->addFile(dirname(__FILE__)."/testProust.php");
    $this->addFile(dirname(__FILE__)."/testContext.php");
    $this->addFile(dirname(__FILE__)."/testContextDot.php");
    $this->addFile(dirname(__FILE__)."/testParser.php");
    $this->addFile(dirname(__FILE__)."/testGenerator.php");
    //    $this->addFile(dirname(__FILE__)."/testSpec.php");
  }
};

?>