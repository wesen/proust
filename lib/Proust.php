<?php

/*
 * Proust - Mustache PHP Compiler
 *
 * This is a relatively straight port of the ruby mustache compiler.
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

namespace Proust;

require_once(dirname(__FILE__).'/Context.php');
require_once(dirname(__FILE__).'/Parser.php');
require_once(dirname(__FILE__).'/Generator.php');
require_once(dirname(__FILE__).'/helpers.php');

class Proust implements \ArrayAccess {
  public $raiseOnContextMiss = false;
  public $templatePath = ".";
  public $templateExtension = "mustache";
  public $enableCache = true;
  public $cacheDir = "";
  public $compilerOptions = array();

  public $context = null;

  public $partials = array();
  public $codeCache = array();
  public $phpCache = array();
  public $templateCache = array();

  public function __construct($options = array()) {
    $defaults = array("templatePath" => ".",
                      "templateExtension" => "mustache",
                      "cacheDir" => null,
                      "raiseOnContextMiss" => false,
                      "context" => null,
                      "enableCache" => false,
                      "disableObjects" => false,
                      "partials" => array(),
                      "compilerOptions" => array());
    $options = array_merge($defaults, $options);
    object_set_options($this, $options, array_keys($defaults));
    
    if ($this->cacheDir == null) {
      $this->cacheDir = $this->templatePath."/.proust_cache";
    }
    if ($this->enableCache) {
      $this->ensureCacheDirectoryExists();
    }
  }

  /***************************************************************************
   *
   * caching methods
   *
   ***************************************************************************/

  public function ensureCacheDirectoryExists() {
    if (!is_dir($this->cacheDir)) {
      if (!mkdir($this->cacheDir, 0777, true)) {
        throw new \Exception("could not create cache directory ".$this->cacheDir);
      }
    }
  }

  /* Store ctag, otag and compiler options as part of the cache name */
  public function getTagString($context) {
    if (!is_a($context, "Context")) {
      $context = $this->getContext();
    }
    $res = print_r($context->otag, true)."_".print_r($context->ctag, true);
    foreach ($this->compilerOptions as $k => $v) {
      $res .= "_".$k."_".$v."_";
    }
    return $res;
  }

  /* get the filename of the actual file. */
  public function getCacheFilename($filename) {
    $cachefile = preg_replace('/[\/\\\ \.]/', '_', $filename);
    $cachefile = $this->cacheDir."/$cachefile.proust_cache";
    return $cachefile;
  }

  /* Get the cache string for a file. */
  public function getFileCodeCacheKey($filename, $context) {
    if (file_exists($filename)) {
      $mtime = filemtime($filename);
      $size = filesize($filename);
      return "file $filename $mtime $size ".$this->getTagString($context);
    } else {
      throw new \Exception("could not find file $filename");
    }
  }

  /* Get the cache index for a code fragment. */
  public function getCodeCacheKey($code, $context) {
    $name = "code ".sha1($code)." ".$this->getTagString($context);
    return $name;
  }
    
  /* cache a code string, along with context information. Returns the evaluated function.. */
  public function getCachedCode($code, $context) {
    $name = $this->getCodeCacheKey($code, $context);

    if (array_key_exists($name, $this->codeCache)) {
      return $this->codeCache[$name];
    }

    if (!$this->enableCache) {
      $php = $this->compile($code, $context);
      eval($php);
      return $f;
    }

    $f = null;
    $cachefile = $this->getCacheFilename($name);
    if (file_exists($cachefile)) {
      include($cachefile);
    } else {
      $php = $this->compile($code, $context);
      file_put_contents($cachefile, "<?php $php ?>");
      eval($php);
    }

    $this->codeCache[$name] = $f;

    return $f;
  }

  /* cache a file, along with context information. Returns the evaluated function. */
  function getCachedFile($filename, $context = null) {
    $name = $this->getFileCodeCacheKey($filename, $context);
    if (array_key_exists($name, $this->codeCache)) {
      return $this->codeCache[$name];
    }

    if (!$this->enableCache) {
      $php = $this->compileFile($filename, $context);
      eval($php);
      return $f;
    }

    $cachefile = $this->getCacheFilename($name);
    $f = null;
    if (file_exists($cachefile)) {
      include($cachefile);
    } else {
      $php = $this->compileFile($filename, $context);
      file_put_contents($cachefile, "<?php $php ?>");
      eval($php);
    }
    $this->codeCache[$name] = $f;
    return $f;
  }

  /* clears the complete cache */
  function clearCache() {
    foreach (glob($this->cacheDir."/*.proust_cache") as $file) {
      unlink($file);
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
      if ($this->disableObjects) {
        $this->context = new ContextNoObjects($this);
      } else {
        $this->context = new Context($this);
      }
    } 
    return $this->context;
  }

  public function resetContext() {
    $this->context = null;
  }
  
  /***************************************************************************
   *
   * Proust methods
   *
   ***************************************************************************/

  /**
   * Given a string, compile and render the string.
   **/
  public function render($code = null, $context = null) {
      // when not a string, return directly, don't try to parse
    if (!is_string($code)) {
      return $code;
    }

    if (is_string($code)) {
      $f = $this->getFunction($code, $context);
      return $this->evalTemplate($f, $context);
    }
  }

  /**
   * Given a file name and an optional context, attemps to load and
   * render the file as a template.
   **/
  public function renderTemplate($name, $context = null) {
    $f = $this->getTemplateFunction($name, $context);
    return $this->evalTemplate($f, $context);
  }

  public function renderFile($filename, $context = null) {
    $f = $this->getFileFunction($filename, $context);
    return $this->evalTemplate($f, $context);
  }

  /* Get the function to render the code. */
  public function getFunction($code, $context = null) {
    return $this->getCachedCode($code, $context);
  }

  public function getTemplateFunction($name, $context = null) {
    $filename = $this->templatePath."/".$name.".".$this->templateExtension;
    return $this->getCachedFile($filename, $context);
  }

  public function getFileFunction($filename, $context = null) {
    return $this->getCachedFile($filename, $context);
  }
  
  /**
   * Override this in your subclass if you want to do fun things like
   * reading templates from a database.
   **/
  public function renderPartial($name, $context = null) {
    if (array_key_exists($name, $this->partials)) {
      $res = $this->render($this->partials[$name], $context);
    } else {
      $filename = $this->templatePath."/".$name.".".$this->templateExtension;
      if (file_exists($filename)) {
        $res = $this->renderFile($filename, $context);
      } else {
        $res = "";
      }
    }
    return $res;
  }

  /* returns true if the partial is a file. */
  public function isPartialStatic($name) {
    if (array_key_exists($name, $this->partials)) {
      return false;
    } else {
      return true;
    }
  }
  
  /* get the content of a partial. */
  public function getPartial($name) {
    if (array_key_exists($name, $this->partials)) {
      return $this->partials[$name];
    } else {
      $filename = $this->templatePath."/".$name.".".$this->templateExtension;
      if (file_exists($filename)) {
        return file_get_contents($filename);
      } else {
        return "";
      }
    }
  }
  

  /* evaluates a compiled template, along with the given context. */
  public function evalTemplate($f, $context = null) {
    $ctx = $this->getContext();
    
    if ($context == null) {
      return $f($ctx);
    } else {
      try {
        if ($ctx == $context) {
          return $f($ctx);
        }
        $ctx->push($context);
        $res = $f($ctx);
        $ctx->pop();
        return $res;
      } catch (\Exception $e) {
        $ctx->pop();
        throw $e;
      }
    }
  }

  /***************************************************************************
   *
   * Compiler interface
   *
   ***************************************************************************/
  public function compileFile($filename, $context = null) {
    $name = $this->getFileCodeCacheKey($filename, $context);
    if (array_key_exists($name, $this->phpCache)) {
      return $this->phpCache[$name];
    }
    $code = file_get_contents($filename);
    $php = $this->compile($code, $context);
    $this->phpCache[$name] = $php;
    return $php;
  }

  public function compileTemplate($name, $context) {
    $filename = $this->templatePath."/".$name.".".$this->templateExtension;
    return $this->compileFile($filename, $context);
  }

  /* Get the tokenized version of $code. */
  public function getTokens($code, $context = null) {
    $parser = new Parser();
    $tokens = $parser->parse($code, $context);
    return $tokens;
  }

  public function getFileTokens($filename, $context = null) {
    $code = file_get_contents($filename);
    return $this->getTokens($code, $context);
  }

  public function getTemplateTokens($name, $context = null) {
    $filename = $this->templatePath."/".$name.".".$this->templateExtension;
    return $this->getFileTokens($filename, $context);
  }

  /* Compile the code, passing the options to the compiler. */
  public function compile($code, $context = null, $options = array()) {
    $name = $this->getCodeCacheKey($code, $context);

    if (array_key_exists($name, $this->phpCache)) {
      return $this->phpCache[$name];
    }
    
    $compilerOptions = $this->compilerOptions;
    $compilerOptions["proust"] = $this;

    $options = array_merge(array("type" => "variable",
                                 "name" => "f"),
                           $options);

    $generator = new Generator($compilerOptions);
    $php = $generator->compileCode($code, $options);
    $this->phpCache[$name] = $php;

    return $php;
  }

  /* Compiles the given methods to a class, looking up partials as needed. */
  public function compileClass($name, $methods) {
    $compilerOptions = $this->compilerOptions;
    $compilerOptions["proust"] = $this;

    $generator = new Generator($compilerOptions);
    return $generator->compileClass($name, $methods);
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