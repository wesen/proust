<?php
/*
 * Mustache PHP Compiler - Test the Generator class
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

require_once(dirname(__FILE__)."/../vendor/simpletest/autorun.php");
require_once(dirname(__FILE__)."/../Mustache.php");

class TestGenerator extends UnitTestCase {
  public function setUp() {
    $this->m = new Mustache\Mustache();
    $this->p = new Mustache\Parser();
    $this->g = new Mustache\Generator();
    $this->c = $this->m->getContext();
  }
  
  function testStringStatic() {
    $ctx = $this->c;
    $res = $this->g->compile(array(":static", "foo"));
    $this->assertEqual(eval($res), "foo");

    $res = $this->g->compile(array(":static", "foo'bar'"));
    $this->assertEqual(eval($res), "foo'bar'");

    $res = $this->g->compile(array(":static", '$foo'));
    $this->assertEqual(eval($res), '$foo');

    $res = $this->g->compile(array(":multi",
                                   array(":multi",
                                         array(":static", "foo'bar'"),
                                         array(":static", "baz")),
                                   array(":static", "flog")));
    $this->assertEqual(eval($res), "foo'bar'bazflog");
  }

  function testStatic() {
    $ctx = $this->c;
    $res = $this->g->compile(array(":static", "foo"));
    $this->assertEqual(eval($res), "foo");

    $res = $this->g->compile(array(":multi",
                                   array(":multi",
                                         array(":static", "foo'bar'"),
                                         array(":static", "baz")),
                                   array(":static", "flog")));
    $this->assertEqual(eval($res), "foo'bar'bazflog");

    $res = $this->g->compile(array(":static", '$foo() blabla\'\'";\n'));
    $foo = eval($res);
    $this->assertEqual($foo, '$foo() blabla\'\'";\n');
  }

  function testSection() {
    $ctx = $this->c;
    $res = $this->g->compile(array(":mustache", ":section", "foo", array(":static", "bla"), 0, 0));
    $ctx = $this->c;
    $this->assertEqual(eval($res), "");

    $ctx->push(array("foo" => true));
    $this->assertEqual(eval($res), "bla");
    $ctx->pop();

    $ctx->push(array("foo" => false));
    $this->assertEqual(eval($res), "");
    $ctx->pop();

    $ctx->push(array("foo" => array()));
    $this->assertEqual(eval($res), "");
    $ctx->pop();
  }

  function testSectionLambda() {
    
  }

  function testInvertedSection() {
    $ctx = $this->c;
    $res = $this->g->compile(array(":mustache", ":inverted_section", "foo", array(":static", "bla")));
    $ctx = $this->c;
    $this->assertEqual(eval($res), "bla");

    $ctx->push(array("foo" => true));
    $this->assertEqual(eval($res), "");
    $ctx->pop();

    $ctx->push(array("foo" => false));
    $this->assertEqual(eval($res), "bla");
    $ctx->pop();

    $ctx->push(array("foo" => array()));
    $this->assertEqual(eval($res), "bla");
    $ctx->pop();
  }

  function testEtag() {
    $ctx = $this->c;
    $ctx->push(array("foo" => "_foo_",
                     "bla" => "_bla_"));
    $res = $this->g->compile(array(":multi",
                                   array(":mustache", ":etag", "foo"),
                                   array(":mustache", ":etag", "bla")));
    $str = eval($res);
    $this->assertEqual($str, "_foo__bla_");
  }

  function testIteration() {
    $arr = array("foo" => "_foo_",
                 "bla" => "_bla_");
    $ctx = $this->c;
    $ctx->push(array("array" => $arr));
    $res = $this->g->compile(array(":mustache", ":section", "array", array(":multi",
                                                                           array(":mustache", ":etag", "bla"),
                                                                           array(":mustache", ":etag", "foo")
                                                                           ),
                                   0, 0));
    $str = eval($res);
    $this->assertEqual($str, "_bla__foo_");
  }
};

?>
