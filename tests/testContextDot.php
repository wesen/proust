<?php
/*
 * Proust - Mustache PHP Compiler - Test the Context lookup for dot names
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

require_once(dirname(__FILE__)."/../vendor/simpletest/autorun.php");
require_once(dirname(__FILE__)."/../Proust.php");

class TestContextDot extends UnitTestCase {
  function setUp() {
    $this->m = new Proust\Proust();
    $this->ctx = $this->m->getContext();
  }

  function testDotFail() {
    $ctx = $this->ctx;
    $res = $ctx->fetch("a.b", false, null);
    $this->assertNull($res);

    $res = $ctx->fetch(".b", false, null);
    $this->assertNull($res);
  }

  function testDotSimpleFail() {
    $ctx = $this->ctx;
    $ctx->push(array("a" => "b",
                     "d" => array("a" => "foo",
                                  "b" => "bar"),
                     "c" => array(1, 2, 3)));

    $res = $ctx->fetch("a.b", false, null);
    $this->assertNull($res);

    $res = $ctx->fetch("c.", false, null);
    $this->assertEqual($res, null);

    $res = $ctx->fetch("d..a", false, null);
    $this->assertEqual($res, null);
  }

  function testDotSimpleSuccess() {
    $ctx = $this->ctx;
    $ctx->push(array("a" => "b",
                     "d" => array("a" => "foo",
                                  "b" => "bar"),
                     "c" => array(1, 2, 3)));
    
    $res = $ctx->fetch("d.a", false, null);
    $this->assertEqual($res, "foo");

    $res = $ctx->fetch("d.b", false, null);
    $this->assertEqual($res, "bar");
    
    $res = $ctx->fetch("c.0", false, null);
    $this->assertEqual($res, 1);
  }

  function testDotSimpleNested() {
    $ctx = $this->ctx;
    $ctx->push(array("a" => "b",
                     "d" => array("a" => "foo",
                                  "b" => "bar",
                                  "c" => array("foo" => "bar")),
                     "c" => array(1, 2, 3)));
    
    $res = $ctx->fetch("d.c.foo", false, null);
    $this->assertEqual($res, "bar");
  }
  
};

?>
