<?php

/*
 * Mustache PHP Compiler
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

namespace Mustache;

/**
 * A ContextMissException is raised whenever a ta's target can not be
 * found in the current context is Mustache->raiseOnContextMiss is set
 * to true.
 *
 * By default, it is not raised.
 **/
class ContextMissException extends \Exception {
};

class Context implements \ArrayAccess {
  protected $stack = null;
  protected $partialStack = null;

  /* Keep a stack of global contexts for lambdas */
  static $GLOBAL_CONTEXT = array();

  public static function GetContext() {
    return self::$GLOBAL_CONTEXT[0];
  }

  public static function PushContext($ctx) {
    array_unshift(self::$GLOBAL_CONTEXT, $ctx);
  }

  public static function PopContext() {
    array_pop(self::$GLOBAL_CONTEXT);
  }

  /* actual context implementation */
  public function __construct($_mustache) {
    $this->mustache = $_mustache;
    $this->stack = array($_mustache);
    $this->partialStack = array();
    $this->otag = null;
    $this->ctag = null;
    $this->indentation = "";
    $this->isNewline = true;
  }

  public function getMustacheInStack() {
    foreach ($this->stack as $_s) {
      if ($_s instanceof \Mustache) {
        return $_s;
      }
    }
    return null;
  }

  public function push($new) {
    array_unshift($this->stack, $new);
    return $this;
  }

  public function pop() {
    array_shift($this->stack);
    return $this;
  }

  public function output($str) {
    if ($this->isNewline) {
      echo $this->indentation;
      $this->isNewline = false;
    }
    echo $str;
  }

  public function newline() {
    $this->isNewline = true;
    echo "\n";
  }

  public function isPartialRecursion($name) {
    foreach ($this->partialStack as $partial) {
      if ($partial["name"] == $name) {
        return true;
      }
    }
    return false;
  }
  
  public function pushPartial($name, $indentation) {
    if (count($this->partialStack) > 30) {
      /* max recursion reached, returning warning string. */
      return "Mustache: max recursion level reached\n";
    }
    
    array_push($this->partialStack, array("name" => $name,
                                          "indentation" => $this->indentation,
                                          "otag" => $this->otag,
                                          "ctag" => $this->ctag));
    $this->indentation .= $indentation;
    $this->ctag = $this->otag = null;
  }

  public function popPartial($name) {
    $partial = array_pop($this->partialStack);
    if ($partial["name"] != $name) {
      throw new Exception("Wrong partial stack ordering, ".$partial["name"]," should be $name");
    }
    $this->indentation = $partial["indentation"];
    $this->ctag = $partial["ctag"];
    $this->otag = $partial["otag"];
  }

  public function partial($name, $indentation) {
    $this->pushPartial($name, $indentation);

    // temporarily reset to default delimiters for immediate lambda return
    $m = $this->getMustacheInStack();
    $res = $m->renderPartial($name, $this);

    $this->popPartial($name);
    
    return $res;
  }

  public function setDelimiters($otag, $ctag) {
    $this->otag = $otag;
    $this->ctag = $ctag;
  }

  public function render($string) {
    $m = $this->getMustacheInStack();
    return $m->render($string, $this);
  }

  public function __fetch($a, $name) {
    debug_log("fetching '".print_r($name, true)."' from ".print_r($a, true), 'CONTEXT');
    if (is_a($a, "Mustache\Context")) {
      return $a->fetch($name);
    }
    
    if (($a instanceof \ArrayAccess) || (is_array($a))) {
      if (array_key_exists($name, $a)) {
        return $a[$name];
      } else if (array_key_exists((string)$name, $a)) {
        return $a[(string)$name];
      }
    } elseif (is_object($a)) {
      $vars = get_object_vars($a);
      if (array_key_exists($name, $vars)) {
        return $vars[$name];
      } else if (method_exists($a, $name)) {
        $res = function ($text = "") use ($a, $name) {
          return $a->$name($text);
        };
        return $res;
      }
    }

    throw new ContextMissException("Can't find $name in ".print_r($a, true));
  }

  public function fetch($name, $evalDirectly = false, $default = '__raise') {
    if ($name === ".") {
      return $this->stack[0];
    }
    
    $list = explode(".", $name);
    debug_log("fetching ".print_r($list, true), 'CONTEXT');

    $res = null;

    $found = false;
    
    foreach ($this->stack as $a) {
      /* avoid recursion */
      if (($a == $this->mustache) || ($a == $this)) {
        continue;
      }

      $res = null;

      $found = false;
      try {
        foreach ($list as $part) {
          if ($part === "") {
            /* XXX syntax error */
            return null;
          }
          $res = $this->__fetch($a, $part);
          $a = $res;
        }
        $found = true;
      } catch (ContextMissException $e) {
        $found = false;
      }
      
      if ($found) {
        break;
      }
    }

    if ($found) {
      if (is_callable($res) && $evalDirectly) {
        // temporarily reset to default delimiters for immediate lambda return
        $ctag = $this->ctag;
        $otag = $this->otag;
        $this->ctag = $this->otag = null;
        Context::PushContext($this);
        $str = $this->render($res());
        Context::PopContext();
        $this->ctag = $ctag;
        $this->otag = $otag;
        return $str;
      } else {
        return $res;
      }
    }

    if (($default == '__raise') || $this->getMustacheInStack()->raiseOnContextMiss) {
      throw new ContextMissException("Can't find $name in ".print_r($this->stack, true));
    } else {
      return $default;
    }
  }

  /***************************************************************************
   *
   * Implement array access
   *
   ***************************************************************************/

  function offsetExists( $offset ) {
    try {
      $this->fetch($offset);
      return true;
    } catch (ContextMissException $e) {
      return false;
    }
  }

  function offsetGet ( $offset ) {
    return $this->fetch($offset, false, null);
  }

  /** Add a value to the context. **/
  function offsetSet ( $offset ,  $value ) {
    $this->push(array($offset => $value));
  }

  function offsetUnset ( $offset ) {
    throw new Exception("Can't remove from Context, use pop() instead");
  }  
};

?>
