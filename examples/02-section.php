<?php

/* canonical mustache template */

require_once(dirname(__FILE__)."/../Proust.php");

$p = new Proust\Proust();

$tpl =<<<'EOD'
Hello {{name}}
You have just won ${{value}}!
{{#in_ca}}
Well, ${{taxed_value}}, after taxes.
{{/in_ca}}

EOD;

class Chris {
  public $name = "Chris";
  public $value = 10000;
  public $in_ca = true;

  public function taxed_value() {
    return $this->value - ($this->value * 0.4);
  }
}

echo $p->render($tpl, new Chris());

?>