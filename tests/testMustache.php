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

class FoobarBlorg extends Mustache {
};

function mustacheForFile($file) {
  $m = new Mustache();
  $m->setTemplateFile(dirname(__FILE__)."/files/$file");
  return $m;
}

class TestMustache extends UnitTestCase {

  function testPartial() {
    $m = new Mustache();
    $res = $m->partial(__filename("testPartial.mustache"));
    $this->assertEqual($res, "partial {{mustache}}\n");
  }

  function testClassify() {
    $res = Mustache::classify("foobar");
    $this->assertEqual($res, "Foobar");

    $res = Mustache::classify("foobar_blorg");
    $this->assertEqual($res, "FoobarBlorg");

    $res = Mustache::classify("foobar_blorg_bla");
    $this->assertEqual($res, "FoobarBlorgBla");
  }

  function testUnderscore() {
    $res = Mustache::underscore("foobar");
    $this->assertEqual($res, "foobar");

    $res = Mustache::underscore("Foobar");
    $this->assertEqual($res, "foobar");

    $res = Mustache::underscore("FoobarBlorg");
    $this->assertEqual($res, "foobar_blorg");

    $res = Mustache::underscore("FoobarBlorgBla");
    $this->assertEqual($res, "foobar_blorg_bla");
  }

  function testClassName() {
    $res = new Mustache();
    $this->assertEqual($res->getTemplateName(), "mustache");

    $res = new FoobarBlorg();
    $this->assertEqual($res->getTemplateName(), "foobar_blorg");

    $res = new FooBar\Bla\FoobarBlorgBla();
    $this->assertEqual($res->getTemplateName(), "foobar_blorg_bla");
  }

  function testSetters() {
    $res = new Mustache();
    $this->assertEqual($res->getTemplatePath(), ".");
    $this->assertEqual($res->getTemplateName(), "mustache");
    $this->assertEqual($res->getTemplateExtension(), "mustache");
    $this->assertEqual($res->getTemplateFile(), "./mustache.mustache");

    $res = new FoobarBlorg();
    $this->assertEqual($res->getTemplatePath(), ".");
    $this->assertEqual($res->getTemplateName(), "foobar_blorg");
    $this->assertEqual($res->getTemplateExtension(), "mustache");
    $this->assertEqual($res->getTemplateFile(), "./foobar_blorg.mustache");
  }

  function testTokens() {
    $m = mustacheForFile("token1.mustache");
    $tpl = $m->getTemplate();
    $res = $tpl->getTokens();
    $this->assertEqual($res, array(":multi", array(":static", "foo\n")));

    $m = mustacheForFile("token2.mustache");
    $tpl = $m->getTemplate();
    $res = $tpl->getTokens();
    $this->assertEqual($res, array(":multi", array(":mustache", ":etag", "foo"), array(":static", "\n")));
  }

  function testRender() {
    $m = mustacheForFile("token1.mustache");
    $res = $m->render();
    $this->assertEqual($res, "foo\n");
    /* try again for precompiled version. */
    $res = $m->render();
    $this->assertEqual($res, "foo\n");

    $m = mustacheForFile("token2.mustache");
    $res = $m->render();
    $this->assertEqual($res, "\n");
    /* try again for precompiled version. */
    $res = $m->render();
    $this->assertEqual($res, "\n");

    $res = $m->render(null, array("foo" => "foo"));
    $this->assertEqual($res, "foo\n");
    $res = $m->render(null, array("foo" => "bla"));
    $this->assertEqual($res, "bla\n");
  }

  function testRenderSection() {
    $m = mustacheForFile("section1.mustache");
    $res = $m->render();
    $this->assertEqual($res, "");
    $res = $m->render(null, array("foo" => array("bla" => "bla")));
    $this->assertEqual($res, "bla");

    $res = $m->render(null, array("foo" => array(array("bla" => "1 "),
                                                 array("bla" => "2 "),
                                                 array("bla" => "3 "),
                                                 array("bla" => "4 "),
                                                 array("bla" => "5 ")
                                                 )));
    $this->assertEqual($res, "1 2 3 4 5 ");
    
  }

  function testPartial() {
    /* XXX */
  }
};

?>
