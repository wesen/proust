<?php
/*
 * Mustache PHP Compiler - Test the Mustache class
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

require_once(dirname(__FILE__)."/../vendor/simpletest/autorun.php");
require_once(dirname(__FILE__)."/../Mustache.php");

class TestParser extends UnitTestCase {
  public function setUp() {
    $this->p = new Mustache\Parser();
  }
  
  public function testEmpty() {
    $res = $this->p->compile("");
    $this->assertEqual($res, array(":multi"));
  }

  public function testComment() {
    $res = $this->p->compile("{{!foobar comment}}");
    $this->assertEqual($res, array(":multi"));
    $res = $this->p->compile("{{!foobar comment}}{{!foobar comment}}");
    $this->assertEqual($res, array(":multi"));
  }

  public function testRawText() {
    $res = $this->p->compile("foo");
    $this->assertEqual($res, array(":multi", array(":static", "foo")));

    $res = $this->p->compile("{{!comment}}foo");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "foo")));
    $res = $this->p->compile("{{!comment}}foo{{!comment}}");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "foo")));
  }

  public function testSimpleMustache() {
    $res = $this->p->compile("{{foo}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":etag", "foo")));
  }

  public function testTwoTags() {
    $res = $this->p->compile("{{foo}}{{bla}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":etag", "foo"),
                                   array(":mustache", ":etag", "bla")));
  }

  public function testTwoTagsComment() {
    $res = $this->p->compile("{{foo}}{{!comment}}{{bla}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":etag", "foo"),
                                   array(":mustache", ":etag", "bla")));
  }

  public function testTagText() {
    $res = $this->p->compile("text{{foo}}");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "text"),
                                   array(":mustache", ":etag", "foo")));

    $res = $this->p->compile("text{{foo}}text");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "text"),
                                   array(":mustache", ":etag", "foo"),
                                   array(":static", "text")
                                   ));

    $res = $this->p->compile("text{{foo}}{{bla}}text");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "text"),
                                   array(":mustache", ":etag", "foo"),
                                   array(":mustache", ":etag", "bla"),
                                   array(":static", "text")
                                   ));

    $res = $this->p->compile("text{{foo}}text2{{bla}}text");
    $this->assertEqual($res, array(":multi",
                                   array(":static", "text"),
                                   array(":mustache", ":etag", "foo"),
                                   array(":static", "text2"),
                                   array(":mustache", ":etag", "bla"),
                                   array(":static", "text")
                                   ));
  }
};

?>
