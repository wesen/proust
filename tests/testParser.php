<?php
/*
 * Mustache PHP Compiler - Test the Parser class
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

require_once(dirname(__FILE__)."/../vendor/simpletest/autorun.php");
require_once(dirname(__FILE__)."/../Mustache.php");

class TestParser extends UnitTestCase {
  function assertStartsWith($str, $prefix) {
    $this->assertEqual(substr($str, 0, strlen($prefix)), $prefix);
  }

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

  public function testUnclosedTag() {
    try {
      $res = $this->p->compile("{{foo");
      $this->assertFalse(true);
    } catch (Mustache\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed tag");
      $this->assertTrue(true);
    }

    try {
      $res = $this->p->compile("{{foo{{bla}}");
      $this->assertFalse(true);
    } catch (Mustache\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed tag");
      $this->assertTrue(true);
    }

    try {
      $res = $this->p->compile("{{fo{o}}");
      $this->assertFalse(true);
    } catch (Mustache\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed tag");
      $this->assertTrue(true);
    }
    
  }

  public function testIllegalContent() {
    try {
      $res = $this->p->compile("{{ #fo#o}}");
      $this->assertFalse(true);
    } catch (Mustache\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Illegal content in tag");
      $this->assertTrue(true);
    }
  }

  public function testUnclosedSection() {
    try {
      $res = $this->p->compile("{{#foo}}");
      $this->assertFalse(true);
    } catch (Mustache\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed section foo");
      $this->assertTrue(true);
    }
    
    try {
      $res = $this->p->compile("{{#foo}}bla");
      $this->assertFalse(true);
    } catch (Mustache\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed section foo");
      $this->assertTrue(true);
    }

    try {
      $res = $this->p->compile("{{#foo}}{{#bla}}{{/bla}}");
      $this->assertFalse(true);
    } catch (Mustache\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed section foo");
      $this->assertTrue(true);
    }
    try {
      $res = $this->p->compile("{{#foo}}{{#foo}}{{/foo}}");
      $this->assertFalse(true);
    } catch (Mustache\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed section foo");
      $this->assertTrue(true);
    }

    try {
      $res = $this->p->compile("{{#foo}}{{#bla}}");
      $this->assertFalse(true);
    } catch (Mustache\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed section bla");
      $this->assertTrue(true);
    }

    try {
      $res = $this->p->compile("{{#foo}}{{/bla}}");
      $this->assertFalse(true);
    } catch (Mustache\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed section foo");
      $this->assertTrue(true);
    }

    try {
      $res = $this->p->compile("{{bla}}{{#foo}}{{/bla}}");
      $this->assertFalse(true);
    } catch (Mustache\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Unclosed section foo");
      $this->assertTrue(true);
    }
  }

  public function testSimpleSection() {
    $res = $this->p->compile("{{#foo}}{{/foo}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":section", "foo", array(":multi"))));

    $res = $this->p->compile("{{#foo}}text{{/foo}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":section", "foo", array(":multi",
                                                                               array(":static", "text")))));

    $res = $this->p->compile("{{#foo}}{{foo}}{{/foo}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":section", "foo", array(":multi",
                                                                               array(":mustache", ":etag", "foo")))));

    $res = $this->p->compile("{{^foo}}{{/foo}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":inverted_section", "foo", array(":multi"))));

    $res = $this->p->compile("{{^foo}}text{{/foo}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":inverted_section", "foo", array(":multi",
                                                                                        array(":static", "text")))));

    $res = $this->p->compile("{{^foo}}{{foo}}{{/foo}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":inverted_section", "foo", array(":multi",
                                                                                        array(":mustache", ":etag", "foo")))));
  }

  public function testNestedSection() {
    $res = $this->p->compile("{{#foo}}{{#bla}}{{/bla}}{{/foo}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":section", "foo", array(":multi",
                                                                               array(":mustache", ":section", "bla", array(":multi"))))));
  }

  public function testUnopenedSection() {
    try {
      $res = $this->p->compile("{{/foo}}");
      $this->assertFalse(true);
    } catch (Mustache\SyntaxError $e) {
      $this->assertStartsWith($e->getMessage(), "Closing unopened section foo");
      $this->assertTrue(true);
    }
  }

  public function testCtag() {
    $res = $this->p->compile("{{=[[ ]]}}[[foo]]text{{text}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":etag", "foo"),
                                   array(":static", "text{{text}}")));

    /* create new mustache to reset ctag and otag. */
    $this->p = new Mustache\Parser();
    $res = $this->p->compile("{{=[[ ]]}}[[foo]]text[[={{ }}]]{{text}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":etag", "foo"),
                                   array(":static", "text"),
                                   array(":mustache", ":etag", "text")));
  }

  public function testPartial() {
    $res = $this->p->compile("{{>partial}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":partial", "partial")));
    $res = $this->p->compile("{{<partial}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":partial", "partial")));
    $res = $this->p->compile("{{<partial/test}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":partial", "partial/test")));
  }

  public function testUtag() {
    $res = $this->p->compile("{{{fresh}}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":utag", "fresh")));
    $res = $this->p->compile("{{&unescaped_stuff}}");
    $this->assertEqual($res, array(":multi",
                                   array(":mustache", ":utag", "unescaped_stuff")));
  }

  public function testLong() {
    $res = $this->p->compile('Hello {{name}}
You have just won ${{value}}!
{{#in_ca}}
 Well, ${{taxed_value}}, after taxes.
{{/in_ca}}');
    $this->assertEqual($res,
                       array(":multi",
                             array(":static", "Hello "),
                             array(":mustache", ":etag", "name"),
                             array(":static", "\nYou have just won $"),
                             array(":mustache", ":etag", "value"),
                             array(":static", "!\n"),
                             array(":mustache", ":section", "in_ca",
                                   array(":multi",
                                         array(":static", "Well, $"),
                                         array(":mustache", ":etag", "taxed_value"),
                                         array(":static", ", after taxes.\n")))));
  }

};

?>
