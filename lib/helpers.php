<?php

/*
 * Mustache PHP Compiler - helper functions
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

$DEBUG = array();
//array_push($DEBUG, 'PARSER');
// array_push($DEBUG, 'NOTICE');
// array_push($DEBUG, 'COMPILER');
// array_push($DEBUG, 'EVALUATION');
  
function get_class_name($object = null) {
  if (!is_object($object) && !is_string($object)) {
    return false;
  }
    
  $class = explode('\\', (is_string($object) ? $object : get_class($object)));
  return $class[count($class) - 1];
}

function objectSetOptions($obj, $options) {
  foreach ($options as $k => $v) {
    $obj->$k = $v;
  }
}

function debug_log($str, $level = "NOTICE") {
  global $DEBUG;
  if (in_array($level, $DEBUG)) {
    error_log($str);
  }
}

?>