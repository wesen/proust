<?php

/* simple hello world */

require_once(dirname(__FILE__)."/../Proust.php");

$p = new Proust\Proust();
echo $p->render("Hello {{planet}}\n", array("planet" => "World"));

?>