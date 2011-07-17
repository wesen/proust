<?php
/*
 * Mustache PHP Compiler - Test the Mustache class
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

require_once(dirname(__FILE__)."/../vendor/simpletest/autorun.php");
require_once(dirname(__FILE__)."/../Mustache.php");

function __filename($file) {
  return dirname(__FILE__).'/files/'.$file;
}


require_once(dirname(__FILE__).'/classDefs.php');

class TestMustache extends UnitTestCase {
  function setUp() {
    $this->m = new Mustache\Mustache(array("templatePath" => dirname(__FILE__)."/files/"));
    $this->m->clearCache();
  }

  function tearDown() {
    $this->m->clearCache();
  }
  
  function testSetters() {
    $res = new Mustache\Mustache();
    $this->assertEqual($res->templatePath, ".");
    $this->assertEqual($res->templateExtension, "mustache");
  }

  function testRender() {
    $m = $this->m;
    
    $res = $m->renderTemplate("token1");
    $this->assertEqual($res, "foo\n");
    /* try again for precompiled version. */
    $res = $m->renderTemplate("token1");
    $this->assertEqual($res, "foo\n");

    $res = $m->renderTemplate("token2");
    $this->assertEqual($res, "\n");
    /* try again for precompiled version. */
    $res = $m->renderTemplate("token2");
    $this->assertEqual($res, "\n");
  }

  function testRenderSection() {
    $m = $this->m;
    $res = $m->renderTemplate("section1");
    $this->assertEqual($res, "\n");
    $res = $m->renderTemplate("section1", array("foo" => array("bla" => "bla")));
    $this->assertEqual($res, "bla\n");

    $res = $m->renderTemplate("section1", array("foo" => array(array("bla" => "1 "),
                                                           array("bla" => "2 "),
                                                           array("bla" => "3 "),
                                                           array("bla" => "4 "),
                                                           array("bla" => "5 ")
                                                           )));
    $this->assertEqual($res, "1 2 3 4 5 \n");

    /* test reloading cached stuff */
    $m = new Mustache\Mustache(array("templatePath" => dirname(__FILE__)."/files/"));
    $res = $m->renderTemplate("section1");
    $this->assertEqual($res, "\n");
    $res = $m->renderTemplate("section1", array("foo" => array("bla" => "bla")));
    $this->assertEqual($res, "bla\n");

    $res = $m->renderTemplate("section1", array("foo" => array(array("bla" => "1 "),
                                                           array("bla" => "2 "),
                                                           array("bla" => "3 "),
                                                           array("bla" => "4 "),
                                                           array("bla" => "5 ")
                                                           )));
    $this->assertEqual($res, "1 2 3 4 5 \n");
  }

  function testRenderSectionNoCache() {
    $m = $this->m;
    $m->enableCache = false;
    $res = $m->renderTemplate("section1");
    $this->assertEqual($res, "\n");
    $res = $m->renderTemplate("section1", array("foo" => array("bla" => "bla")));
    $this->assertEqual($res, "bla\n");

    $res = $m->renderTemplate("section1", array("foo" => array(array("bla" => "1 "),
                                                           array("bla" => "2 "),
                                                           array("bla" => "3 "),
                                                           array("bla" => "4 "),
                                                           array("bla" => "5 ")
                                                           )));
    $this->assertEqual($res, "1 2 3 4 5 \n");

    /* test reloading cached stuff */
    $m = new Mustache\Mustache(array("templatePath" => dirname(__FILE__)."/files/"));
    $res = $m->renderTemplate("section1");
    $this->assertEqual($res, "\n");
    $res = $m->renderTemplate("section1", array("foo" => array("bla" => "bla")));
    $this->assertEqual($res, "bla\n");

    $res = $m->renderTemplate("section1", array("foo" => array(array("bla" => "1 "),
                                                           array("bla" => "2 "),
                                                           array("bla" => "3 "),
                                                           array("bla" => "4 "),
                                                           array("bla" => "5 ")
                                                           )));
    $this->assertEqual($res, "1 2 3 4 5 \n");
  }
  
  function testLambdaContext() {
    $m = $this->m;
    $res = $m->render("{{foo}}",
                      array("a" => 42,
                            "foo" => function () { $ctx = Mustache\Context::GetContext(); return $ctx['a']; }));
    $this->assertEqual($res, 42);

    $res = $m->render("{{#foo}}{{a}}{{/foo}}",
                      array("a" => 42,
                            "foo" => function ($text) { return $text; }));
    $this->assertEqual($res, 42);

    $res = $m->render("{{#foo}}{{a}}{{/foo}}",
                      array("a" => 42,
                            "foo" => function ($text) { return $text.$text; }));
    $this->assertEqual($res, 4242);

    $res = $m->render("{{#foo}}{{a}}{{/foo}}",
                      array("b" => 42,
                            "a" => 100,
                            "c" => "{{b}}",
                            "foo" => function ($text) { $ctx = Mustache\Context::GetContext(); $c = $ctx['c']; return $text.$c.$text; }));
    $this->assertEqual($res, 10042100);
    
  }

};

?>
