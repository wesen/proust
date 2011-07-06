<?php

/*
 * String Scanner implementation
 *
 * (c) July 2011 - Manuel Odendahl - wesen@ruinwesen.com
 */

class StringScanner {
  function StringScanner($str) {
    $this->string = $str;
    $this->pos = 0;
    $this->length = strlen($str);
  }

  /** Resets the string scanner to the start position. **/
  function reset() {
    $this->pos = 0;
  }

  /** Returns the whole string. **/
  function getString() {
    return $this->string;
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
};

?>