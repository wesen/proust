<?php

/*
 * Mustache PHP Compiler
 *
 * This is a straight port of the ruby mustache compiler.
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

require_once(dirname(__FILE__).'/Context.php');
require_once(dirname(__FILE__).'/Template.php');
require_once(dirname(__FILE__).'/helpers.php');

class Mustache implements \ArrayAccess {
  public $raiseOnContextMiss = false;

  public $templatePath = ".";
  public $templateExtension = "mustache";

  public $context = null;

  public $partials = array();

  public function __construct($options = array()) {
    $options = array_merge(array("templatePath" => ".",
                                 "templateExtension" => "mustache",
                                 "cacheDir" => "./.mustache.cache/",
                                 "raiseOnContextMiss" => false,
                                 "context" => null),
                           $options);
    object_set_options($this, $options, array("templatePath",
                                              "templateExtension",
                                              "cacheDir",
                                              "raiseOnContextMiss",
                                              "context"));
  }

  /***************************************************************************
   *
   * caching methods
   *
   ***************************************************************************/

  public function ensureCacheDirectoryExists() {
    if (!is_dir($this->cacheDir)) {
      if (!mkdir($this->cacheDir, 0777, true)) {
        throw new Exception("could not create cache directory ".$this->cacheDir);
      }
    }
  }
    
  /***************************************************************************
   *
   * Getters and Setters
   *
   ***************************************************************************/

  /** context **/
  public function getContext() {
    if ($this->context == null) {
      $this->context = new Mustache\Context($this);
    } 
    return $this->context;
  }

  /***************************************************************************
   *
   * Mustache methods
   *
   ***************************************************************************/

  public function render($data = null, $context = null) {
    if (is_string($data)) {
      $data = new Mustache\Template($data);
    } else {
      // when not a string, return directly, don't try to parse
      return $data;
    }

    if ($context == null) {
      return $data->render($this->getContext());
    } else {
      try {
        $this->getContext()->push($context);
        return $data->render($this->getContext());
      } catch (Exception $e) {
        $this->getContext()->pop();
        throw $e;
      }
    }
  }

  /**
   * Given a file name and an optional context, attemps to load and
   * render the file as a template.
   **/
  public function renderFile($name, $context = array()) {
    $data = $this->partial($name);
    return $this->render($data, $context);
  }

  /**
   * Given a name, attemps to read a file and return the contents as a
   * string. The file is not rendered, so it might contain
   * {{mustaches}}.
   **/
  public static function __partial($name) {
    if (file_exists($name)) {
      return file_get_contents($name);
    } else {
      return "";
    }
  }

  /**
   * Override this in your subclass if you want to do fun things like
   * reading templates from a database. It will be rendered by the
   * context, so all you need to do is return a string.
   **/
  public function partial($name) {
    if (array_key_exists($name, $this->partials)) {
      return $this->partials[$name];
    } else {
      return static::__partial($this->templatePath."/".$name.".".$this->templateExtension);
    }
  }

  /***************************************************************************
   *
   * Array access methods are mapped to context object
   *
   ***************************************************************************/
  /**
   * Implements the array access methods.
   **/
  function offsetExists( $offset ) {
    return $this->getContext()->offsetExists($offset);
  }

  function offsetGet ( $offset ) {
    return $this->getContext()->offsetGet($offset);
  }

  function offsetSet ( $offset ,  $value ) {
    return $this->getContext()->offsetSet($offset, $value);
  }

  function offsetUnset ( $offset ) {
    return $this->getContext()->offsetUnSet($offset, $value);
  }  

}

?>