<?php

require_once("../vendor/simpletest/autorun.php");
require_once("../Mustache.php");

class TestStringScanner extends UnitTestCase {
  function testCreation() {
    $sc = new StringScanner("foo");
    $this->assertEqual($sc->wasMatched(), false);
    $this->assertEqual($sc->getMatched(), null);
    $this->assertEqual($sc->getPostMatch(), null);
    $this->assertEqual($sc->getPreMatch(), null);
    $this->assertEqual($sc->getMatchedSize(), 0);
    $this->assertEqual($sc[0], null);
    $this->assertEqual($sc[99], null);
    $this->assertEqual($sc->isBol(), true);
    $this->assertEqual($sc->pos, 0);
    $this->assertEqual($sc->rest(), "foo");
    $this->assertEqual($sc->isEos(), false);
    $this->assertEqual($sc->getRestSize(), 3);
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
    $sc = new StringScanner("foobarbaz");
    $res = $sc->scan("123");
    $this->assertEqual($res, null);
    $this->assertEqual($sc->rest(), "foobarbaz");

    $res = $sc->scan("foo");
    $this->assertEqual($res, "foo");
    $this->assertEqual($sc->rest(), "barbaz");

    $sc = new StringScanner("foobar blorg bla");
    $res = $sc->scan("\w+");
    $this->assertEqual($res, "foobar");
    $this->assertEqual($sc->rest(), " blorg bla");

    $res = $sc->scan("\s+");
    $this->assertEqual($res, " ");
    $this->assertEqual($sc->rest(), "blorg bla");
  }

  function testArrayAccess() {
    $sc = new StringScanner("foobar blorg bla");

    $res = $sc->scan("foo(\w+)(\s+)(\w+)g");
    $this->assertEqual($res, "foobar blorg");
    $this->assertEqual($sc->rest(), " bla");
    $this->assertEqual($sc[0], "foobar blorg");
    $this->assertEqual($sc[1], "bar");
    $this->assertEqual($sc[2], " ");
    $this->assertEqual($sc[3], "blor");
  }

  function testScanUntil() {
    $sc = new StringScanner("foobar blorg bla");

    $res = $sc->scanUntil("bar");
    $this->assertEqual($res, "foobar");
    $this->assertEqual($sc->pos, 6);
    $this->assertEqual($sc->rest(), " blorg bla");
    $this->assertEqual($sc->getMatched(), "bar");

    $res = $sc->scanUntil("\s+");
    $this->assertEqual($res, " ");
    $this->assertEqual($sc->getMatched(), " ");

    $res = $sc->scanUntil("(\w+) (\w+)");
    $this->assertEqual($res, "blorg bla");
    $this->assertTrue($sc->isEos());
    $this->assertEqual($sc->getMatched(), "blorg bla");
    $this->assertEqual($sc[0], "blorg bla");
    $this->assertEqual($sc[1], "blorg");
    $this->assertEqual($sc[2], "bla");

    $sc = new StringScanner("foobar blorg bla");
    $res = $sc->scanUntil("hihihi");
    $this->assertEqual($res, null);
    $this->assertEqual($sc->rest(), "foobar blorg bla");

    $res = $sc->scanUntil("bl[ab]");
    $this->assertEqual($res, "foobar blorg bla");
    $this->assertTrue($sc->isEos());
    $this->assertEqual($sc->getMatched(), "bla");
  }

  function testScanFull() {
    $sc = new StringScanner("foobar blorg bla");
    $res = $sc->scanFull("foo", false, false);
    $this->assertEqual($res, true);
    $this->assertEqual($sc->rest(), "foobar blorg bla");
    $this->assertEqual($sc->getMatched(), "foo");

    $res = $sc->scanFull("bla", false, false);
    $this->assertEqual($res, null);
    $this->assertEqual($sc->rest(), "foobar blorg bla");

    $res = $sc->scanFull("foobar", true, false);
    $this->assertEqual($res, "foobar");
    $this->assertEqual($sc->rest(), "foobar blorg bla");
    $this->assertEqual($sc->getMatched(), "foobar");

    $res = $sc->scanFull("\w+", true, true);
    $this->assertEqual($res, "foobar");
    $this->assertEqual($sc->rest(), " blorg bla");
    $this->assertEqual($sc->getMatched(), "foobar");
  }

  function testSearchFull() {
    $sc = new StringScanner("foobar blorg bla");

    $res = $sc->searchFull("hahaha", false, false);
    $this->assertEqual($res, null);

    $res = $sc->searchFull("bar", false, false);
    $this->assertEqual($res, 6);
    $this->assertEqual($sc->rest(), "foobar blorg bla");
    $this->assertEqual($sc->getMatched(), "bar");

    $res = $sc->searchFull("bar", true, false);
    $this->assertEqual($res, "foobar");
    $this->assertEqual($sc->rest(), "foobar blorg bla");
    $this->assertEqual($sc->getMatched(), "bar");

    $res = $sc->searchFull("(bar)(\s+)", true, true);
    $this->assertEqual($res, "foobar ");
    $this->assertEqual($sc->rest(), "blorg bla");
    $this->assertEqual($sc->getMatched(), "bar ");
    $this->assertEqual($sc[0], "bar ");
    $this->assertEqual($sc[1], "bar");
    $this->assertEqual($sc[2], " ");
  }

  function testSkip() {
    $sc = new StringScanner("foobar blorg bla");
    $res = $sc->skip("hihi");
    $this->assertEqual($res, null);
    $this->assertEqual($sc->rest(), "foobar blorg bla");

    $res = $sc->skip("\w+\s+");
    $this->assertEqual($res, 7);
    $this->assertEqual($sc->rest(), "blorg bla");
  }

  function testCheck() {
    $sc = new StringScanner("foobarbaz");
    $res = $sc->check("123");
    $this->assertEqual($res, null);
    $this->assertEqual($sc->rest(), "foobarbaz");

    $res = $sc->check("foo");
    $this->assertEqual($res, "foo");
    $this->assertEqual($sc->rest(), "foobarbaz");

    $sc = new StringScanner("foobar blorg bla");
    $res = $sc->check("\w+");
    $this->assertEqual($res, "foobar");
    $this->assertEqual($sc->rest(), "foobar blorg bla");

    $sc->pos += 6;
    $res = $sc->check("\s+");
    $this->assertEqual($res, " ");
    $this->assertEqual($sc->rest(), " blorg bla");
  }


  function testCheckUntil() {
    $sc = new StringScanner("foobar blorg bla");

    $res = $sc->checkUntil("bar");
    $this->assertEqual($res, "foobar");
    $this->assertEqual($sc->rest(), "foobar blorg bla");
    $this->assertEqual($sc->getMatched(), "bar");

    $sc->pos += 6;
    $res = $sc->checkUntil("\s+");
    $this->assertEqual($res, " ");
    $this->assertEqual($sc->rest(), " blorg bla");
    $this->assertEqual($sc->getMatched(), " ");

    $sc->pos += 1;
    $res = $sc->checkUntil("(\w+) (\w+)");
    $this->assertEqual($res, "blorg bla");
    $this->assertFalse($sc->isEos());
    $this->assertEqual($sc->rest(), "blorg bla");
    $this->assertEqual($sc->getMatched(), "blorg bla");
    $this->assertEqual($sc[0], "blorg bla");
    $this->assertEqual($sc[1], "blorg");
    $this->assertEqual($sc[2], "bla");

    $sc = new StringScanner("foobar blorg bla");
    $res = $sc->checkUntil("hihihi");
    $this->assertEqual($res, null);
    $this->assertEqual($sc->rest(), "foobar blorg bla");

    $res = $sc->checkUntil("bl[ab]");
    $this->assertEqual($res, "foobar blorg bla");
    $this->assertFalse($sc->isEos());
    $this->assertEqual($sc->rest(), "foobar blorg bla");
    $this->assertEqual($sc->getMatched(), "bla");
  }

  function testUnscan() {
    $sc = new StringScanner("foobar blorg bla");
    $res = $sc->scan("foo(bar) (\w+)");
    $this->assertEqual($res, "foobar blorg");
    $this->assertEqual($sc[0], "foobar blorg");
    $this->assertEqual($sc[1], "bar");
    $this->assertEqual($sc[2], "blorg");
    $this->assertEqual($sc->rest(), " bla");
    $this->assertEqual($sc->wasMatched(), true);

    $sc->unScan();
    $this->assertEqual($sc->pos, 0);
    $this->assertEqual($sc[0], null);
    
    try {
      $sc->unScan();
    } catch (Exception $e) {
      $this->assertEqual($e->getMessage(), "unScan failed, previous match had failed");
    }

    $res = $sc->isMatch("foo");
    $this->assertEqual($sc[0], "foo");

    $res = $sc->isMatch("foobar");
    $this->assertEqual($sc[0], "foobar");
    $sc->unScan();
    $this->assertEqual($sc[0], "foo");

    $res = $sc->scan("foobar ");
    $this->assertEqual($sc->rest(), "blorg bla");
    $sc->unScan();
    $this->assertEqual($sc->rest(), "foobar blorg bla");

  }
  
};

?>