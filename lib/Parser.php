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
    $this->lastLine = $pos["line"];
    $stripped_line = trim($this->lastLine);
    $stripped_column = $this->column - (strlen($this->lastLine) - strlen(ltrim($this->lastLine)));
    $whitespace = str_pad('', $stripped_column);
    $foo = <<<EOD
$this->message
  Line $this->lineno
    $stripped_line
    ${whitespace}^
EOD;

parent::__construct($foo, 0, NULL);
  }
};

class Parser {
  /** after these tags, all whitespace will be skipped. **/
  static $SKIP_WHITESPACE_BEFORE = array('/', '!', '=');
  static $SKIP_WHITESPACE = array('#', '^', '/', '!', '=', '>');

  /** lines where only these tags are present should be removed. **/
  static $STANDALONE_LINES = array('=', '!');

  /** allowed content in a tag name. **/
  static $ALLOWED_CONTENT = '(\w|[\?\!\/\_\-])*';

  /** These type of tags allow any content, the rest only allow ALLOWED_CONTENT. **/
  static $ANY_CONTENT = array('!', '=');

  /** opening and closing tag delimiters. These may be changed at runtime. **/
  public $otag = "{{";
  public $ctag = "}}";

  protected $linePos;
  protected $sections;
  protected $result;
  
  public function __construct(array $options = array()) {
    /* nothing special happens here. */
  }

  public function compile($src) {
    debug_log("Starting parsing", 'PARSER');
    $this->sections = array();
    $this->result = array(":multi");
    $this->startOfLine = true;
    $this->scanner = new \StringScanner($src);

    while (!$this->scanner->isEos()) {
      if (!$this->scanTags()) {
        $this->scanText();
      }
    }

    /* there still are opened sections */
    if (count($this->sections) > 0) {
      $section = array_pop($this->sections);
      throw new SyntaxError("Unclosed section ".$section["section"], $section["position"]);
    }

    debug_log("Parsing result: ".print_r($this->result, true), 'PARSER');

    return $this->result;
  }

  /** Find {{mustaches}} and add them to the result array. **/
  public function scanTags() {
    debug_log("scanning '".$this->scanner->rest()."'", 'PARSER');
    if (!$this->scanner->scan("(\s*)".\StringScanner::escape($this->otag))) {
      return;
    }

    $whitespace = $this->scanner[1];
    
    /* always have this start on a fresh line. */
    /* Since {{= rewrite ctag, we store the ctag which should be used when parsing this specific tag. **/
    $currentCtag = $this->ctag;
    $type = $this->scanner->scan("[#^\/=!<>^{&]");

    $this->scanner->skip("\s*");

    if (in_array($type, self::$ANY_CONTENT)) {
      $r = "\s*".(\StringScanner::escape($type))."?".(\StringScanner::escape($currentCtag));
      $content = $this->scanner->scanUntilExclusive($r);
    } else {
      $content = $this->scanner->scan(self::$ALLOWED_CONTENT);
    }

    debug_log("scanned tag $type$content", 'PARSER');

    if (empty($content)) {
      throw new SyntaxError("Illegal content in tag", $this->getPosition());
    }

    if (!in_array($type, self::$SKIP_WHITESPACE_BEFORE)) {
      if (!empty($whitespace)) {
        debug_log("adding beginning of line whitespace: '$whitespace'", 'PARSER');
        array_push($this->result, array(":static", $whitespace));
      }
    }

    switch ($type) {
    case '#':
      $block = array(":multi");
      array_push($this->result, array(":mustache", ":section", $content, &$block));
      array_push($this->sections, array("section" => $content,
                                        "position" => $this->getPosition(),
                                        "result" => &$this->result));
      $this->result = &$block;
      break;

    case '^':
      $block = array(":multi");
      array_push($this->result, array(":mustache", ":inverted_section", $content, &$block));
      array_push($this->sections, array("section" => $content,
                                        "position" => $this->getPosition(),
                                        "result" => &$this->result));
      $this->result = &$block;
      break;

    case '/':
      $section = array_pop($this->sections);

      if ($section === null) {
        throw new SyntaxError("Closing unopened section $content", $this->getPosition());
      }
      
      if ($section["section"] !== $content) {
        throw new SyntaxError("Unclosed section ".$section["section"], $this->getPosition());
      }
      
      $this->result = &$section["result"];
      break;

    case '!':
      /* ignore comments */
      break;

    case '=':
      $separators = explode(' ', $content);
      $this->otag = $separators[0];
      $this->ctag = $separators[1];
      array_push($this->result, array(":mustache", ":tag_change", $this->otag, $this->ctag));
      break;

    case '<':
    case '>':
      array_push($this->result, array(":mustache", ":partial", $content));
      break;

    case '{':
    case '&':
      if ($type == "{") {
        $type = "}"; // for balancing purposes
      }
      array_push($this->result, array(":mustache", ":utag", $content));
      break;

    default:
      array_push($this->result, array(":mustache", ":etag", $content));
      break;
    }

    $this->scanner->skip("\s+");
    if ($type) {
      $this->scanner->skip(\StringScanner::escape($type));
    }

    $close = $this->scanner->scan(\StringScanner::escape($currentCtag));
    if (!$close) {
      throw new SyntaxError("Unclosed tag", $this->getPosition());
    }

    if (in_array($type, self::$SKIP_WHITESPACE)) {
      if ($this->startOfLine) {
        debug_log("skipping whitespace at: '".$this->scanner->checkUntil('[^\v]+')."'", 'PARSER');
        $res = $this->scanner->skip('\h*\r?\n?');
        debug_log("skipped : $res, rest: '".$this->scanner->rest()."'", 'PARSER');
      }
    } else {
    }
  }

  /** Try to find static text. **/
  public function scanText() {
    /* XXX split here */
    $text = $this->scanner->scanUntilExclusive(\StringScanner::escape($this->otag));
    
    if ($text === null) {
      /* Couldn't find any otag, which means the rest is just static text. */
      $text = $this->scanner->rest();
      $this->scanner->clear();
    } else {
      if (!empty($text)) {
        $pos = strrpos($text, "\n");
        if ($pos !== false) {
          /* backtrack to position of last newline */
          $backtrack = strlen($text) - $pos - 1;
          $text = substr($text, 0, $pos + 1);
          $this->scanner->pos -= $backtrack;
          debug_log("start of line", 'PARSER');
          $this->startOfLine = true;
        } else {
          $this->startOfLine = false;
        }
      } else {
        $this->startOfLine = true;
      }
    }
    
    if (!empty($text)) {
      debug_log("add text '".print_r($text, true)."'", 'PARSER');
      array_push($this->result, array(":static", $text));
    }
  }

  /** Returns array("lineno" => .., "column" => .., "line" => ..); **/
  public function getPosition() {
    $rest = rtrim($this->scanner->checkUntil('\r?\n'));
    $parsed = $this->scanner->getScanned();
    $lines = explode("\n", $parsed);
    
    return array("lineno" => count($lines),
                 "column" => strlen(end($lines)),
                 "line" => end($lines).$rest);
  }

}

?>