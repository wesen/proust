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
  /** lines where only these tags are present should be removed. **/
  static $STANDALONE_LINES = array('=', '!', '#', '^', '/', '>');
  static $SECTION_TYPES = array('#', '^', '/');

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
    $this->result = array(":start");
    $this->startOfLine = true;
    $this->scanner = new \StringScanner($src);

    while (!$this->scanner->isEos()) {
      debug_log("scanTags (bol? ".$this->startOfLine.") rest '".$this->scanner->peek(10)."' otag: ".$this->otag, 'PARSER');
      $this->scanTags();
      debug_log("scanText (bol? ".$this->startOfLine.") rest '".$this->scanner->peek(10)."'", 'PARSER');
      $this->scanText();
    }

    /* check if there is an empty placeholder tag present */
    $prev = end($this->result);
    if (is_array($prev) && 
        ($prev[0] === ":standalone")) {
      array_pop($this->result);
    }
    
    /* there still are opened sections */
    if (count($this->sections) > 0) {
      $section = array_pop($this->sections);
      throw new SyntaxError("Unclosed section ".$section["section"], $section["position"]);
    }

    /* replace start token with multi now that we are done. */
    $this->result[0] = ":multi";

    debug_log("Parsing result: ".print_r($this->result, true), 'PARSER');
    return $this->result;
  }

  /** Find {{mustaches}} and add them to the result array. **/
  public function scanTags() {
    /* Read in the next tag. */
    if (!$this->scanner->scan(\StringScanner::escape($this->otag))) {
      return;
    }

    /* Since {{= rewrite ctag, we store the ctag which should be used when parsing this specific tag. **/
    $currentCtag = $this->ctag;
    $this->scanner->skip("\s*");

    $type = $this->scanner->scan("[#^\/=!<>^{&]");

    $this->scanner->skip("\s*");
    
    if (in_array($type, self::$ANY_CONTENT)) {
      $r = "\s*".(\StringScanner::escape($type))."?".(\StringScanner::escape($currentCtag));
      $content = $this->scanner->scanUntilExclusive($r);
    } else {
      $content = $this->scanner->scan(self::$ALLOWED_CONTENT);
    }

    /* read until end of tag. */
    $this->scanner->skip("\s+");
    if ($type) {
      $skipType = $type;
      if ($type == '{') {
        $skipType = '}';
      }
      debug_log("Skipping $skipType", 'PARSER');
      $this->scanner->skip(\StringScanner::escape($skipType));
    }

    $close = $this->scanner->scan(\StringScanner::escape($currentCtag));
    if (!$close) {
      if ($this->scanner->check(self::$ALLOWED_CONTENT.\StringScanner::escape($currentCtag))) {
        throw new SyntaxError("Illegal content in tag", $this->getPosition());
      } else {
        throw new SyntaxError("Unclosed tag with '".$this->scanner->peek(10)."'", $this->getPosition());
      }
    }
    
    debug_log("scanned tag $type$content", 'PARSER');

    if (empty($content)) {
      throw new SyntaxError("Illegal content in tag", $this->getPosition());
    }

    /* Whitespace handling */
    $indentation = "";
    
    $isStandalone = false;
    if (in_array($type, self::$STANDALONE_LINES)) {
      
      /* check if there is something else on this line */
      if ($this->scanner->scan('[\h\r]*') !== null) {
        if ($this->scanner->isEos() || // end of template
            ($this->scanner->peek(1) == "\n") // end of line
            ) {

          debug_log("rest of line is whitespace", 'PARSER');
          /* check if beginning of line is whitespace */
          $count = count($this->result);
          $prev = $this->result[$count - 1];
          $prev2 = array(":foo"); // fake for beginning of multi
          if ($count > 1) {
            $prev2 = $this->result[$count - 2];
          }
            
          debug_log("stack ".print_r($prev, true)." ".print_r($prev2, true), 'PARSER');

          if (($prev != ":multi") &&
              (($prev === null) // beginning of file = whitespace
               || ($prev === ":start")
               || ($prev[0] === ":newline") // new line
               || ($prev[0] === ":standalone") // new line

               || (($prev[0] === ":static") // static text
                   && ($prev2 === ":start" ||
                       $prev2[0] === ":newline") // at line beginning
                   && preg_match('/^\s*$/', $prev[1])) // is whitespace?
               )) {

            $isStandalone = true;
            if ($prev[0] === ":static") {
              $indentation = $prev[1];
              array_pop($this->result);
            } else if ($prev[0] === ":standalone") {
              array_pop($this->result);
            }

            /* when at the end of file on an empty line, remove previous newline when handling sections */
            if ($this->scanner->isEos() &&
                in_array($type, self::$SECTION_TYPES) &&
                ($indentation === "") &&
                (end($this->result) == array(":newline"))) {
              array_pop($this->result);
            }
            
            //            if ($type != '>') {
            //              $this->scanner->skip('\n?');
            //            }
              $this->scanner->skip('\n?');

            debug_log("standalone tag found, rest '".$this->scanner->peek(10)."'", 'PARSER');
          }
        }
      }

      if (!$isStandalone) {
        $this->scanner->unScan();
      }
    }

    /* check if there is an empty placeholder tag present */
    $prev = end($this->result);
    if (is_array($prev) &&
        ($prev[0] === ":standalone")) {
      array_pop($this->result);
    }

    /* parse tag type */

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
      debug_log("set separators: ".print_r($separators, true), 'PARSER');
      $this->otag = $separators[0];
      $this->ctag = $separators[1];
      array_push($this->result, array(":mustache", ":tag_change", $this->otag, $this->ctag));
      break;

    case '<':
    case '>':
      array_push($this->result, array(":mustache", ":partial", $content, $indentation));
      break;

    case '{':
    case '&':
      array_push($this->result, array(":mustache", ":utag", $content));
      break;

    default:
      array_push($this->result, array(":mustache", ":etag", $content));
      break;
    }

    debug_log("finish tag parsing $type$content", 'PARSER');

    if ($isStandalone) {
      array_push($this->result, array(":standalone")); // insert empty whitespace string for next tag
    }
  }

  public function lastStatic() {
    $count = count($this->result);
    if ($count > 0) {
      $elt = &$this->result[$count-1];
      if ($elt[0] === ":static") {
        return $elt;
      }
    }

    return null;
  }
  
  public function addStatic($text) {
    debug_log("add static '$text'", 'PARSER');
    $count = count($this->result);
    if ($count > 0) {
      $elt = &$this->result[$count-1];

      if ($elt[0] === ":static") {
        $elt[1] = $elt[1].$text;
        return;
      }
    }

    array_push($this->result, array(":static", $text));
  }

  public function stripWhitespace($stripNewline = false) {
    $count = count($this->result);
    if ($count > 0) {
      $elt = &$this->result[$count-1];
      if ($elt[0] === ":static") {
        debug_log("strip_whitespace on '".$elt[1]."'", 'PARSER');
        if ($stripNewline && preg_match('/\n$/', $elt[1])) {
          $elt[1] = preg_replace('/\r?\n/', '', $elt[1]);
        } else {
          $elt[1] = preg_replace('/\h*$/', '', $elt[1]);
        }
        if ($elt[1] == '') {
          array_pop($this->result);
        }
      }
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
    }

    if (!empty($text)) {
      $prev = end($this->result);
      if ($prev[0] === ":standalone") {
        array_pop($this->result);
      }
      foreach (preg_split('/(\n)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $str) {
        if ($str === "\n") {
          array_push($this->result, array(":newline"));
        } else {
          $this->addStatic($str);
        }
      }
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