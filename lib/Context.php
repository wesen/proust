<?php

/*
 * Proust - Mustache PHP Compiler - Context object
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 *
 * The context object implements context lookup and runtime partial evaluation.
 */

namespace Proust;

/**
 * A ContextMissException is raised whenever a ta's target can not be
 * found in the current context is Proust->raiseOnContextMiss is set
 * to true.
 *
 * By default, it is not raised.
 **/
class ContextMissException extends \Exception {
};

class Context implements \ArrayAccess {
  public $stack = null;
  public $partialStack = null;

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
  public function __construct($_proust = null) {
    $this->proust = $_proust;
    $this->reset();
  }

  /***************************************************************************
   *
   * Context lookup methods
   *
   ***************************************************************************/

  /* add a new context object to the context stack */
  public function push($new) {
    if ($this === $new) {
      // push empty array to avoid recursion
      $new = array();
    }
    array_unshift($this->stack, $new);
    return $this;
  }

  /* remove the top context object */
  public function pop() {
    array_shift($this->stack);
    return $this;
  }

  public function reset($data = null) {
    if ($data !== null) {
      $this->stack = array($data);
    } else {
      $this->stack = array();
    }

    $this->partialStack = array();
    $this->otag = null;
    $this->ctag = null;
    $this->indentation = "";
    $this->isNewline = true;
  }

  /* fetch a single path component from object $a */
  public function __fetch($a, $name, &$found) {
    $found = true;
    if (($a instanceof \ArrayAccess) || (is_array($a))) {
      if (isset($a[$name])) {
        return $a[$name];
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

    $found = false;
    return null;
  }

  /* fetch a value from the context stack, splitting dot notation. */
  public function fetch($name, $evalDirectly = false, $default = '__raise') {
    /* new syntax for iteration */
    if ($name === ".") {
      return $this->stack[0];
    }

    /* explode the dot notation */
    $list = explode(".", $name);

    $res = null;
    $found = false;
    
    foreach ($this->stack as $a) {
      $res = null;
      $found = false;
      foreach ($list as $part) {
        if ($part === "") {
          /* XXX syntax error */
          return null;
        }
        $res = $this->__fetch($a, $part, $found);
        if (!$found) {
          continue 2;
        }
        
        $a = $res;
      }

      if ($found) {
        break;
      }
    }

    if ($found) {
      /* evaluate the lambda directly if it is a tag. */
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

    /* raise an exception only if requested. */
    if (($default == '__raise') || ($this->proust &&  $this->proust->raiseOnContextMiss)) {
      throw new ContextMissException("Can't find $name");
    } else {
      return $default;
    }
  }
  
  /***************************************************************************
   *
   * output functions
   *
   ***************************************************************************/

  /* output a string to the output, taking care of indentation */
  public function output($str) {
    if ($this->isNewline) {
      echo $this->indentation;
      $this->isNewline = false;
    }
    echo $str;
  }

  /* output a newline to the output, taking care of indentation */
  public function newline() {
    $this->isNewline = true;
    echo "\n";
  }

  /***************************************************************************
   *
   * partial evaluation at runtime
   *
   ***************************************************************************/

  /* check if a partial is included recursively (ok for runtime eval, bad when compiling). */
  public function isPartialRecursion($name) {
    foreach ($this->partialStack as $partial) {
      if ($partial["name"] == $name) {
        return true;
      }
    }
    return false;
  }

  /* add a partial to the partial stack */
  public function pushPartial($name, $indentation) {
    if (count($this->partialStack) > 30) {
      /* max recursion reached, returning warning string. */
      return "Proust: max recursion level reached\n";
    }
    
    array_push($this->partialStack, array("name" => $name,
                                          "indentation" => $this->indentation,
                                          "otag" => $this->otag,
                                          "ctag" => $this->ctag));
    $this->indentation .= $indentation;
    $this->ctag = $this->otag = null;
  }

  /* remove the top partial, doing some sanity checking. */
  public function popPartial($name) {
    $partial = array_pop($this->partialStack);
    if ($partial["name"] != $name) {
      throw new \Exception("Wrong partial stack ordering, ".$partial["name"]." should be $name");
    }
    $this->indentation = $partial["indentation"];
    $this->ctag = $partial["ctag"];
    $this->otag = $partial["otag"];
  }

  /* render a partial at runtime. */
  public function partial($name, $indentation) {
    $this->pushPartial($name, $indentation);

    // temporarily reset to default delimiters for immediate lambda return
    $m = $this->proust;
    if ($m) {
      $res = $m->renderPartial($name, $this);
    } else {
      $res = "";
    }

    $this->popPartial($name);
    
    return $res;
  }

  /* set the context delimiters */
  public function setDelimiters($otag, $ctag) {
    $this->otag = $otag;
    $this->ctag = $ctag;
  }

  /* render a string at runtime. */
  public function render($string) {
    $m = $this->proust;
    if ($m) {
      return $m->render($string, $this);
    } else {
      return "";
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

class ContextNoObjects extends Context {
  /* fetch a single path component from object $a */
  public function __fetch($a, $name, &$found) {
    if (($a instanceof \ArrayAccess) || (is_array($a))) {
      if (isset($a[$name])) {
        $found = true;
        return $a[$name];
      }
    }

    $found = false;
    return null;
  }
}

?>
