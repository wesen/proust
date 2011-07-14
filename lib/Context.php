<?php

/*
 * Mustache PHP Compiler
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

namespace Mustache;

class Context implements \ArrayAccess {
  public function __construct() {
  }

  function offsetExists( $offset ) {
    return false;
  }

  function offsetGet ( $offset ) {
    return null;
  }

  function offsetSet ( $offset ,  $value ) {
  }

  function offsetUnset ( $offset ) {
  }  
}

?>
