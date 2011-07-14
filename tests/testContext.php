<?php
/*
 * Mustache PHP Compiler - Test the Mustache class
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

require_once(dirname(__FILE__)."/../vendor/simpletest/autorun.php");
require_once(dirname(__FILE__)."/../Mustache.php");

class TestContext extends UnitTestCase {
  function assertStartsWith($str, $prefix) {
    return strncmp($str, $prefix, strlen($prefix)) == 0;
  }

  function setUp() {
    $this->m = new Mustache();
    $this->ctx = $this->m->getContext();
  }
  
  function testNoValue() {
    $ctx = $this->ctx;
    
    try {
      $res = $ctx['foo'];
    } catch (Mustache\ContextMissException $e) {
      $this->assertStartsWith($e->getMessage(), "Can't find foo");
    }

    try {
      $res = $ctx->fetch('foo');
    } catch (Mustache\ContextMissException $e) {
      $this->assertStartsWith($e->getMessage(), "Can't find foo");
    }
  }

  function testDefault() {
    $ctx = $this->ctx;

    $res = $ctx->fetch("foo", "default");
    $this->assertEqual($res, "default");
  }

  function testSingleValue() {
    $ctx = $this->ctx;

    $ctx['bla'] = 1;
    $this->assertEqual($ctx['bla'], 1);
  }
};

?>
