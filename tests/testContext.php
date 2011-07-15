<?php
/*
 * Mustache PHP Compiler - Test the Mustache class
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

require_once(dirname(__FILE__)."/../vendor/simpletest/autorun.php");
require_once(dirname(__FILE__)."/../Mustache.php");

class Foobar {
  public function a() {
    return 5;
  }

  public function c() {
    return 7;
  }
};

class TestContext extends UnitTestCase {
  function assertStartsWith($str, $prefix) {
    $this->assertEqual(substr($str, 0, strlen($prefix)), $prefix);
  }

  function setUp() {
    $this->m = new Mustache();
    $this->ctx = $this->m->getContext();
  }
  
  function testNoValue() {
    $ctx = $this->ctx;
    
    try {
      $this->m->raiseOnContextMiss = true;
      $res = $ctx['foo'];
      $this->assertFalse(true);
    } catch (Mustache\ContextMissException $e) {
      $this->assertStartsWith($e->getMessage(), "Can't find foo");
    }

    try {
      $res = $ctx->fetch('foo');
      $this->assertFalse(true);
    } catch (Mustache\ContextMissException $e) {
      $this->assertStartsWith($e->getMessage(), "Can't find foo");
    }
  }

  function testRaise() {
    $ctx = $this->ctx;

    $this->m->raiseOnContextMiss = true;
    try {
      $res = $ctx->fetch("foo", "default");
      $this->assertFalse(true);
    } catch (Mustache\ContextMissException $e) {
      $this->assertStartsWith($e->getMessage(), "Can't find foo");
    }

    $this->m->raiseOnContextMiss = false;
    $res = $ctx->fetch("foo", false, "default");
    $this->assertEqual($res, "default");
  }

  function testDefault() {
    $ctx = $this->ctx;

    $res = $ctx->fetch("foo", false, "default");
    $this->assertEqual($res, "default");
  }

  function testSingleValue() {
    $ctx = $this->ctx;

    $ctx['bla'] = 1;
    $this->assertEqual($ctx['bla'], 1);
  }

  function testTwoValues() {
    $ctx = $this->ctx;
    $ctx->push(array("foo" => "bar",
                     "blorg" => "bar2"));
    $this->assertEqual($ctx["foo"], "bar");
    $this->assertEqual($ctx["blorg"], "bar2");
  }

  function testPushPop() {
    $ctx = $this->ctx;
    $ctx->push(array("foo" => "bar",
                     "blorg" => "bar2"));
    $this->assertEqual($ctx["foo"], "bar");
    $this->assertEqual($ctx["blorg"], "bar2");

    $ctx->push(array("foo" => "bla"));
    $this->assertEqual($ctx["foo"], "bla");
    $this->assertEqual($ctx["blorg"], "bar2");

    $ctx->push(array("blorg" => "bar3"));
    $this->assertEqual($ctx["foo"], "bla");
    $this->assertEqual($ctx["blorg"], "bar3");

    $ctx->pop();
    $this->assertEqual($ctx["foo"], "bla");
    $this->assertEqual($ctx["blorg"], "bar2");

    $ctx->pop();
    $this->assertEqual($ctx["foo"], "bar");
    $this->assertEqual($ctx["blorg"], "bar2");
  }

  function testMethod() {
    $ctx = $this->ctx;

    $ctx->push(new Foobar());
    $res = $ctx['a'];
    $this->assertEqual($res(), 5);
    $res = $ctx['c'];
    $this->assertEqual($res(), 7);
  }

  function testRecursion() {
    $ctx = $this->ctx;
    $ctx->push(array("foo" => "bar"));
    $this->assertEqual($ctx["foo"], "bar");

    $ctx->push($ctx);
    $this->assertEqual($ctx["foo"], "bar");

    $ctx->push($ctx);
    $this->assertEqual($ctx["foo"], "bar");

    $ctx2 = new Mustache\Context($this->m);
    $ctx2->push(array("foo" => "blorg"));
    $ctx->push($ctx2);
    $this->assertEqual($ctx["foo"], "blorg");

    $ctx->push($ctx);
    $this->assertEqual($ctx["foo"], "blorg");

    $ctx->push($this->m);
    $this->assertEqual($ctx["foo"], "blorg");

    $ctx->pop();
    $ctx->pop();
    $ctx->pop();
    $this->assertEqual($ctx["foo"], "bar");
  }

  function testPartials() {
    /* XXX */
  }

};

?>
