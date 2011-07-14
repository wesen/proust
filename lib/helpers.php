<?php

/*
 * Mustache PHP Compiler - helper functions
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

function get_class_name($object = null) {
  if (!is_object($object) && !is_string($object)) {
    return false;
  }
    
  $class = explode('\\', (is_string($object) ? $object : get_class($object)));
  return $class[count($class) - 1];
}

?>