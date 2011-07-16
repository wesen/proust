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

function methodCallClosure($a, $name) {
  return function ($text = "") use ($a, $name) {
    //    echo "a: '$a'";
    print_r($a);
    //    echo $a->foobar();
    //    return $a->$name($text);
  };
}

class Context implements \ArrayAccess {
  protected $stack = null;
  protected $partialStack = null;
  
  public function __construct($_mustache) {
    $this->mustache = $_mustache;
    $this->stack = array($_mustache);
    $this->partialStack = array();
    $this->otag = null;
    $this->ctag = null;
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
    echo $str;
  }

  public function partial($name) {
    if (in_array($name, $this->partialStack)) {
      return "";
    }

    array_push($this->partialStack, $name);

    /* TODO check for already compiled partial with current ctag / otag */
    
    $m = $this->getMustacheInStack();
    $partial = $m->partial($name);

    // temporarily reset to default delimiters for immediate lambda return
    $ctag = $this->ctag;
    $otag = $this->otag;
    $this->ctag = $this->otag = null;
    $res = $this->render($partial);
    $this->ctag = $ctag;
    $this->otag = $otag;
    
    array_pop($this->partialStack);
    
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

  public function fetch($name, $evalDirectly = false, $default = '__raise') {
    foreach ($this->stack as $a) {
      /* avoid recursion */
      if (($a == $this->mustache) || ($a == $this)) {
        continue;
      }
      $res = null;

      if (($a instanceof \ArrayAccess) || (is_array($a))) {
        if (isset($a[$name])) {
          $res = $a[$name];
        } else if (isset($a[(string)$name])) {
          $res = $a[(string)$name];
        }
      } elseif (method_exists($a, $name)) {
        $res = function ($text = "") use ($a, $name) {
          return $a->$name($text);
        };
      }
      if ($res !== null) {
        if (is_callable($res) && $evalDirectly) {
          // temporarily reset to default delimiters for immediate lambda return
          $ctag = $this->ctag;
          $otag = $this->otag;
          $this->ctag = $this->otag = null;
          $str = $this->render($res());
          $this->ctag = $ctag;
          $this->otag = $otag;
          return $str;
        } else {
          return $res;
        }
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
