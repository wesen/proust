<?php

require_once("../vendor/simpletest/autorun.php");
require_once("../Mustache.php");

class TestStringScanner extends UnitTestCase {
  function testCreation() {
    $sc = new StringScanner("");
  }

  function testGetChar() {
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

  function testGetString() {
    $sc = new StringScanner("foo");
    $this->assertEqual($sc->string, "foo");
  }

  function testReset() {
    $sc = new StringScanner("foo");
    $this->assertEqual($sc->getChar(), "f");
    $this->assertEqual($sc->getChar(), "o");
    $this->assertEqual($sc->getChar(), "o");
    $sc->reset();
    $this->assertEqual($sc->getChar(), "f");
    $this->assertEqual($sc->getChar(), "o");
    $this->assertEqual($sc->getChar(), "o");
  }

  function testConcat() {
    $sc = new StringScanner("foo");
    $this->assertEqual($sc->getChar(), "f");
    $this->assertEqual($sc->getChar(), "o");
    $this->assertEqual($sc->getChar(), "o");
    $this->assertTrue($sc->isEos());

    $sc->concat("bar");
    $this->assertFalse($sc->isEos());
    $this->assertEqual($sc->string, "foobar");
    $this->assertEqual($sc->getChar(), "b");
    $this->assertEqual($sc->getChar(), "a");
    $this->assertEqual($sc->getChar(), "r");
    $this->assertTrue($sc->isEos());
  }

  function testPeek() {
    $sc = new StringScanner("foobarbaz");
    $this->assertEqual($sc->peek(4), "foob");
    $this->assertEqual($sc->peek(0), "");
    $this->assertEqual($sc->peek(9), "foobarbaz");
    $this->assertEqual($sc->peek(10), "foobarbaz");
    $this->assertEqual($sc->peek(5), "fooba");

    $sc->pos = 3;
    $this->assertEqual($sc->peek(3), "bar");
    $this->assertEqual($sc->peek(9), "barbaz");
    $this->assertEqual($sc->peek(5), "barba");

    $sc->pos = 9;
    $this->assertEqual($sc->peek(3), "");
  }

  function testRest() {
    $sc = new StringScanner("foobarbaz");
    $this->assertFalse($sc->isEos());
    $rest = $sc->rest();
    $this->assertTrue($rest, "foobarbaz");
    $this->assertEqual($sc->getRestSize(), 9);

    $sc->pos = 3;
    $this->assertEqual($sc->rest(), "barbaz");
    $this->assertEqual($sc->getRestSize(), 6);
    $this->assertFalse($sc->isEos());

    $sc->pos = 9;
    $this->assertTrue($sc->isEos());
    $this->assertEqual($sc->getRestSize(), 0);
    $this->assertEqual($sc->rest(), "");

    $sc->pos = 11;
    $this->assertTrue($sc->isEos());
    $this->assertEqual($sc->getRestSize(), 0);
    $this->assertEqual($sc->rest(), "");
  }

  function testMatch() {
    $sc = new StringScanner("foobarbaz");
    $this->assertEqual($sc->isMatch("foo"), 3);
    $this->assertTrue($sc->wasMatched());
    $this->assertEqual($sc->getMatchedSize(), 3);
    $this->assertEqual($sc->getMatched(), "foo");
    $this->assertEqual($sc->getPreMatch(), "");
    $this->assertEqual($sc->getPostMatch(), "barbaz");
    
    $this->assertEqual($sc->isMatch("bar"), null);
    $this->assertFalse($sc->wasMatched());
    $this->assertEqual($sc->getMatchedSize(), null);
    $this->assertEqual($sc->getMatched(), null);
    $this->assertEqual($sc->getPreMatch(), null);
    $this->assertEqual($sc->getPostMatch(), null);
    
    $this->assertEqual($sc->isMatch("\w+"), 9);
    $this->assertTrue($sc->wasMatched());
    $this->assertEqual($sc->getMatchedSize(), 9);
    $this->assertEqual($sc->getMatched(), "foobarbaz");
    $this->assertEqual($sc->getPreMatch(), "");
    $this->assertEqual($sc->getPostMatch(), "");

    $sc->pos = 3;
    $this->assertEqual($sc->isMatch("foo"), null);
    $this->assertFalse($sc->wasMatched());
    $this->assertEqual($sc->getMatchedSize(), null);
    $this->assertEqual($sc->getMatched(), null);
    $this->assertEqual($sc->getPreMatch(), null);
    $this->assertEqual($sc->getPostMatch(), null);

    $this->assertEqual($sc->isMatch("bar"), 3);
    $this->assertTrue($sc->wasMatched());
    $this->assertEqual($sc->getMatchedSize(), 3);
    $this->assertEqual($sc->getMatched(), "bar");
    $this->assertEqual($sc->getPreMatch(), "foo");
    $this->assertEqual($sc->getPostMatch(), "baz");
    
    $this->assertEqual($sc->isMatch("\w+"), 6);
    $this->assertTrue($sc->wasMatched());
    $this->assertEqual($sc->getMatchedSize(), 6);
    $this->assertEqual($sc->getMatched(), "barbaz");
    $this->assertEqual($sc->getPreMatch(), "foo");
    $this->assertEqual($sc->getPostMatch(), "");

    $sc->pos = 9;
    $this->assertEqual($sc->isMatch("foo"), null);
    $this->assertEqual($sc->isMatch("bar"), null);
    $this->assertEqual($sc->isMatch("\w+"), null);
  }

  function testScan() {
  }
  
};

?>