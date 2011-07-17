<?php

/*
 * Mustache PHP Compiler
 *
 * This is a straight port of the ruby mustache compiler
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

define('MUSTACHE_PHP_VERSION_ID', '0.1');

require_once('lib/StringScanner.php');
require_once('lib/Mustache.php');

function usage() {
  print "Usage:\n\n";
  print " mustache.php [-o outputfile] [-p partialDir] [-t] [-h] inputfile\n\n";
  print "   -o outputfile : store php in this file\n";
  print "   -t            : print token array\n";
  print "   -h            : this information\n";
}


if (defined('STDIN')) {
  error_reporting(E_ALL);

  require_once('Console/Getopt.php');
  $o = new Console_Getopt();
  $argv = $o->readPHPArgv();

  /* check we are not included by someone */
  if (realpath($argv[0]) != realpath(__FILE__)) {
    return;
  }
  
  function filenameToFunctionName($filename){
    $name = basename($filename);
    $name = preg_replace('/\.[^\.]*$/', '', $name);
    $name = preg_replace('/[^a-zA-Z0-9]/', '_', $name);
    return $name;
  }
  
  function _getopt($opts, $name) {
    if (array_key_exists($name, $opts)) {
      return $opts[$name];
    } else {
      return null;
    }
  }
  
  $res = $o->getopt($argv, 'o:th');
  $opts = array();
  foreach ($res[0] as $foo) {
    $opts[$foo[0]] = ($foo[1] === null ? true : $foo[1]);
  }

  if (_getopt($opts, "h")) {
    usage();
    die();
  }
  
  $files = $res[1];
  $m = new Mustache(array("enableCache" => false));

  $code = "";
  foreach ($files as $file) {
    $tpl = file_get_contents($file);
    if (_getopt($opts, "t")) {
      $code .= "Tokens for $file:\n".print_r($m->getTokens($tpl), true)."\n";;
    } else {
      $code .= $m->compile($tpl, null, filenameToFunctionName($file))."\n";
    }
  }

  if (!_getopt($opts, 't')) {
    $code = "<?php $code ?>";
    if (_getopt($opts, 'o') !== null) {
      file_put_contents($opts['o'], $code);
      print "Written to ".$opts['o']."\n";
    } else {
      print $code;
    }
  } else {
    print $code;
  }
}

?>