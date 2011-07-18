<?php

/* get code tokens */

require_once(dirname(__FILE__)."/../Proust.php");

$p = new Proust\Proust(array("templatePath" => dirname(__FILE__)."/templates/"));
$p->partials = array("partial" => "{{#section}}{{bla}}{{/section}}");

$tpl =<<<'EOT'
{{#foo}}{{bla}}{{/foo}}
{{>partial}}
EOT;

echo "Tokens:\n-------\n\n";
var_dump($p->getTokens($tpl));

echo "\n\nTemplate tokens:\n----------------\n\n";
var_dump($p->getTemplateTokens("section1"));

echo "\n\nFile tokens:\n----------------\n\n";
var_dump($p->getFileTokens(dirname(__FILE__)."/templates/partial.mustache"));

?>