<?php

/*
 * Mustache PHP Compiler
 *
 * This is a straight port of the ruby mustache compiler.
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

class Mustache {
  public function __construct() {
    $this->templatePath = ".";
    $this->templateExtension = "mustache";
    $this->raiseOnContextMiss = false;
  }

  public function render() {
  }

  /**
   * Given a file name and an optional context, attemps to load and
   * render the file as a template.
   **/
  public function renderFile($name, $context = array()) {
  }

  /**
   * Given a name, attemps to read a file and return the contents as a
   * string. The file is not rendered, so it might contain
   * {{mustaches}}.
   **/
  public static function __partial($name) {
    return file_get_contents($name);
  }

  /**
   * Override this in your subclass if you want to do fun things like
   * reading templates from a database. It will be rendered by the
   * context, so all you need to do is return a string.
   **/
  public function partial($name) {
    return static::__partial($name);
  }

  /** Memoization array for camelcasing. **/
  protected static $__camelizeHash = array();

  /**
   * camelcases a name to get a class name.
   **/
  public static function classify($name) {
    if (array_key_exists($name, self::$__camelizeHash)) {
      return self::$__camelizeHash[$name];
    }

    $orig = $name;
    
    $name = str_replace(array('-', '_'), ' ', $name); 
    $name = ucwords($name); 
    $name = ucfirst(str_replace(' ', '', $name));
    
    self::$__camelizeHash[$orig] = $name;
    
    return $name; 
  }

  protected static $__uncamelizeHash = array();

  /**
   * Uncamelizes a string.
   **/
  public static function underscore($name) {
    if (array_key_exists($name, self::$__uncamelizeHash)) {
      return self::$__uncamelizeHash[$name];
    }

    $orig = $name;

    $name = lcfirst($name);
    $name = preg_replace("/([A-Z])/e", '"_".lcfirst("$1")', $name);
    self::$__uncamelizeHash[$orig] = $name;
    return $name;
  }
}

?>