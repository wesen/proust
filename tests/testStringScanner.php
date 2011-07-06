<?php

require_once("../vendor/simpletest/autorun.php");
require_once("../Mustache.php");

class TestStringScanner extends UnitTestCase {
  function testStringScannerCreation() {
    $sc = new StringScanner("");
  }

  function testStringScannerGetChar() {
    $sc = new StringScanner("foo");
    $this->assertTrue($sc->isBol());
    $this->assertFalse($sc->isEos());

    $this->assertEqual($sc->getChar(), "f");
    $this->assertEqual($sc->pos, 1);
    $this->assertFalse($sc->isBol());
    $this->assertFalse($sc->isEos());

    $this->assertEqual($sc->getChar(), "o");
    $this->assertEqual($sc->pos, 2);
    $this->assertFalse($sc->isBol());
    $this->assertFalse($sc->isEos());

    $this->assertEqual($sc->getChar(), "o");
    $this->assertEqual($sc->pos, 3);
    $this->assertFalse($sc->isBol());
    $this->assertTrue($sc->isEos());

    $this->assertEqual($sc->getChar(), null);
    $this->assertTrue($sc->isEos());

    $this->assertEqual($sc->getChar(), null);
    $this->assertTrue($sc->isEos());
  }

  function testStringScannerGetString() {
    $sc = new StringScanner("foo");
    $this->assertEqual($sc->getString(), "foo");
  }

  function testStringScannerReset() {
    $sc = new StringScanner("foo");
    $this->assertEqual($sc->getChar(), "f");
    $this->assertEqual($sc->getChar(), "o");
    $this->assertEqual($sc->getChar(), "o");
    $sc->reset();
    $this->assertEqual($sc->getChar(), "f");
    $this->assertEqual($sc->getChar(), "o");
    $this->assertEqual($sc->getChar(), "o");
  }
};

?>