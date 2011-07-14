<?php

/*
 * Template PHP Compiler
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

namespace Mustache;

require_once(dirname(__FILE__).'/StringScanner.php');

/**
 * The Parser is responsible for taking a string template and
 * converting it into an array of tokens and expression.
 *
 * It raises SyntaxError if there is anything it doesn't understand.
 **/

class SyntaxError extends \Exception {
  public function __construct($message, $pos) {
    $this->message = $message;
    $this->lineno = $pos["lineno"];
    $this->column = $pos["column"];
    $this->line = $pos["line"];
    $stripped_line = trim($this->line);
    $stripped_column = $this->column - (strlen($this->line) - strlen(ltrim($this->line)));
    $whitespace = str_pad('', $stripped_column);
    $foo = <<<EOD
$this->message
  Line $this->lineno
    $stripped_line
    ${whitespace}^
EOD;

    parent::__construct($foo);
  }
};

define('MULTI',    ":multi");
define('STATIC',   ":static");
define('MUSTACHE', ":mustache");

class Parser {
  /** after these tags, all whitespace will be skipped. **/
  static $SKIP_WHITESPACE = array('#', '^', '/');

  /** allowed content in a tag name. **/
  static $ALLOWED_CONTENT = '/(\w|[?!\/-])*/';

  /** These type of tags allow any content, the rest only allow ALLOWED_CONTENT. **/
  static $ANY_CONTENT = array('!', '=');

  /** opening and closing tag delimiters. These may be changed at runtime. **/
  public $otag = "{{";
  public $ctag = "}}";

  protected $sections;
  protected $result;
  
  public function __construct(array $options = array()) {
    /* nothing special happens here. */
  }

  public function compile($src) {
    $this->sections = array();
    $this->result = array(MULTI);
    $this->scanner = new \StringScanner($src);

    while (!$this->scanner->isEos()) {
      if (!$this->scanTags()) {
        $this->scanText();
      }
    }

    /* there still are opened sections */
    if (count($this->sections) > 0) {
      $section = array_pop($this->sections);
      throw new SyntaxError("Unclosed section ".$section["type"], $section["pos"]);
    }

    return $this->result;
  }
}

?>