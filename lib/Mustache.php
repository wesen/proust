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
  protected $raiseOnContextMiss = false;

  protected $templateName = null;
  protected $templatePath = ".";
  protected $templateExtension = "mustache";
  protected $templateFile = null;

  protected $context = null;
  protected $template = null;
    
  public function __construct() {
    $this->templateName = null;
    $this->templatePath = ".";
    $this->templateExtension = "mustache";
    $this->raiseOnContextMiss = false;

    $this->templateFile = null;
    $this->context = null;
    $this->template = null;
  }

  /***************************************************************************
   *
   * Getters and Setters
   *
   ***************************************************************************/

  /** template path **/
  public function getTemplatePath() {
    return $this->templatePath;
  }

  public function setTemplatePath($path) {
    $this->templatePath = $path;
    $this->template = null;
  }

  /** template path **/
  public function getTemplateExtension() {
    return $this->templateExtension;
  }

  public function setTemplateExtension($extension) {
    $this->templateExtension = $extension;
    $this->template = null;
  }
  
  /** template name **/
  public function getTemplateName() {
    if ($this->templateName == null) {
      $this->templateName = Mustache::underscore(get_class_name($this));
    }
    return $this->templateName;
  }

  public function setTemplateName($name) {
    $this->templateName = $name;
    $this->template = null;
  }

  /** template file **/
  public function getTemplateFile() {
    if ($this->templateFile == null) {
      $this->templateFile = $this->getTemplatePath()."/".$this->getTemplateName().".".$this->getTemplateExtension();
    }
    return $this->templateFile;
  }

  public function setTemplateFile($file) {
    $this->templateFile = $file;
    $this->template = null;
  }

  /** template itself **/
  public function getTemplate() {
    if ($this->template == null) {
      $this->template = new Mustache\Template(file_get_contents($this->templateFile));
    }
    return $this->template;
  }

  public function setTemplate($template) {
    if (is_string($template)) {
      $this->template = new Mustache\Template($template);
    } else {
      $this->template = $template;
    }
  }

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
    if ($data == null) {
      $data = $this->getTemplate();
    } else {
      if (is_string($data)) {
        $data = new Mustache\Template($data);
      }
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
    return $this->render($this->partial($name), $context);
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