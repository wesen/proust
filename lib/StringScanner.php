<?php

/*
 * String Scanner implementation
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

class StringScanner implements \ArrayAccess
{
  function StringScanner($str) {
    $this->string = $str;
    $this->pos = 0;
    $this->length = strlen($str);
    $this->matches = array();
  }

  /** Resets the string scanner to the start position. **/
  function reset() {
    $this->pos = 0;
  }

  /** Returns the next char and advances the read pointer. **/
  function getChar() {
    if ($this->pos < $this->length) {
      return $this->string[$this->pos++];
    } else {
      return null;
    }
  }

  /** Returns true if the scanner is at the beginning of the string. **/
  function isBol() {
    return ($this->pos == 0);
  }
  
  /** Returns true if the scanner is at the end of the string. **/
  function isEos() {
    return ($this->pos >= $this->length);
  }

  /** Appends the string to the end of the string scanner string. Does not affect the scan position. **/
  function concat($str) {
    $this->string .= $str;
    $this->length += strlen($str);
  }

  /** Extracts a string without advancing the scan pointer. **/
  function peek($len) {
    return substr($this->string, $this->pos, $len);
  }

  /** Returns the rest of the string. **/
  function rest() {
    return substr($this->string, $this->pos);
  }

  /** Return the size of the rest of the string. **/
  function getRestSize() {
    return max(0, $this->length - $this->pos);
  }

  /***************************************************************************
   *
   * Matching functions
   *
   ***************************************************************************/

  /**
   * Test wether the given pattern is matched from the current scan
   * pointer. Returns the length of the match, or null. The scan pointer
   * is not advanced.
   **/
  function isMatch($re) {
    $string = $this->rest();
    $res = preg_match("/^$re/", $string, $this->matches, PREG_OFFSET_CAPTURE);
    if ($res == 0) {
      $this->match_length = null;
      return null;
    } else {
      $this->match_length = strlen($this[0]);
      return $this->match_length;
    }
  }

  function getMatched() {
    return $this[0];
  }

  function wasMatched() {
    return $this[0] != null;
  }

  function getMatchedSize() {
    return $this->match_length;
  }

  function getPostMatch() {
    if ($this->wasMatched()) {
      return substr($this->string, $this->pos + $this->match_length);
    } else {
      return null;
    }
  }

  function getPreMatch() {
    if ($this->wasMatched()) {
      return substr($this->string, 0, $this->pos);
    } else {
      return null;
    }
  }

  /**
   * Tries to match with pattern at the current position. If there's a
   * match, the scanner advances the scan pointer and returns the
   * matched string. Otherwise, the scanner returns null.
   **/
  function scan($re) {
    return $this->scanFull($re, true, true);
  }

  /**
   * Scans the string *until* the pattern is matched. Returns the
   * substring up to and including the end of the match, advancing the
   * scan pointer to that location. If there is no match, null is
   * returned.
   **/
  function scanUntil($re) {
    return $this->searchFull($re, true, true);
  }

  /**
   * Test whether the pattern is matched at the current
   * position. Returns the matched string if $returnStringP is true,
   * advances the scan pointer if $advanceScanPointerP is true.
   *
   * Affects the match register.
   **/
  function scanFull($re, $returnStringP = false, $advanceScanPointerP = false) {
    $res = $this->isMatch($re);
    if ($res) {
      if ($advanceScanPointerP) {
        $this->pos += $this->match_length;
      }
      if ($returnStringP) {
        return $this[0];
      } else {
        return $res > 0;
      }
    } else {
      return null;
    }
  }

  /**
   * Scan the string *until* the pattern is matched. Returns the
   * matched string if $returnStringP is true, otherwise returns the
   * number of bytes advanced. Advances the string pointer if $advanceScanPointerP is true.
   *
   * Affects the match register.
   **/
  function searchFull($re, $returnStringP = false, $advanceScanPointerP = false) {
    $string = $this->rest();
    $res = preg_match("/$re/", $string, $this->matches, PREG_OFFSET_CAPTURE);
    if ($res) {
      $start_pos = $this->matches[0][1];
      $this->match_length = strlen($this[0]);

      if ($advanceScanPointerP) {
        $this->pos += $start_pos + $this->match_length;
      }

      if ($returnStringP) {
        return substr($string, 0, $start_pos + $this->match_length);
      } else {
        return $start_pos + $this->match_length;
      }
    } else {
      return null;
    }
  }

  /**
   * Attempts to skip over the given pattern beginning with the scan
   * pointer. If it matches, the scan pointer is advanced to the end
   * of the match, and the length of the match is returned. Otherwise,
   * null is returned.
   **/
  function skip($re) {
  }
  
  /**
   * This returns the value that scan would return, without advancing
   * the scan pointer. The match register is affected though.
   **/
  function check($re) {
  }

  /**
   * This returns the value that scan_until would return, without
   * advancing the scan pointer. The match register is affected
   * though.
   **/
  function checkUntil($re) {
  }

  /**
   * Looks ahead to see if the pattern exists anywhere in the string,
   * without advancing the scan pointer. This predicates whether a
   * scan_until will return a value.
   **/
  function doesExist($re) {
  }
  

  /**
   * Set the scan pointer to the previous position. Only one previous
   * position is remembered, and it changes with each scanning
   * operation.
   **/
  function unScan() {
  }

  /**
   * Implements the array access methods.
   **/
  function offsetExists( $offset ) {
    return ($offset == 0) || isset($this->matches[$offset]);
  }

  function offsetGet ( $offset ) {
    if (isset($this->matches[$offset])) {
      return $this->matches[$offset][0];
    } else {
      return null;
    }
  }

  function offsetSet ( $offset ,  $value ) {
    throw new Exception('StringScanner is readonly');
  }

  function offsetUnset ( $offset ) {
    throw new Exception('StringScanner is readonly');
  }
};

?>