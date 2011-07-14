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
    $this->m = new Mustache();
    $this->p = new Mustache\Parser();
    $this->g = new Mustache\Generator();
    $this->c = $this->m->getContext();
  }
  
  function testStringStatic() {
    $res = $this->g->compile_sub(array(":static", "foo"));
    $this->assertEqual($res, "echo 'foo';");

    $res = $this->g->compile_sub(array(":static", "foo'bar'"));
    $this->assertEqual($res, "echo 'foo\\'bar\\'';");

    $res = $this->g->compile_sub(array(":static", '$foo'));
    $this->assertEqual($res, 'echo \'$foo\';');

    $res = $this->g->compile_sub(array(":multi",
                                   array(":multi",
                                         array(":static", "foo'bar'"),
                                         array(":static", "baz")),
                                   array(":static", "flog")));
    $this->assertEqual($res, "echo 'foo\\'bar\\'';\necho 'baz';\necho 'flog';");
  }

  function testStatic() {
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
    $res = $this->g->compile(array(":mustache", ":section", "foo", array(":static", "bla")));
    $ctx = $this->c;
    print_r($res);
    echo "eval: ".eval($res)."\n";
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

  function testInvertedSection() {
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
};

?>
