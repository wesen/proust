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
    $this->p = new Mustache\Parser();
    $this->g = new Mustache\Generator();
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
    $this->assertEqual(eval($res), "foo'bar'bazfloz");

    $res = $this->g->compile(array(":static", '$foo() blabla\'\'";\n'));
    $this->assertEqual(eval($res), '$foo() blabla\'\'";\n');
  }

  function testSection() {
    $res = $this->g->compile(array(":section", "foo", array(":static", "bla")));
    print_r($res);
  }
};

?>
