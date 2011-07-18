<?php

/* get compiled code */

require_once(dirname(__FILE__)."/../Proust.php");

$p = new Proust\Proust();
$p->partials = array("partial" => "{{#section}}{{bla}}{{/section}}");

$tpl =<<<'EOT'
{{#foo}}{{bla}}{{/foo}}
{{>partial}}
EOT;

echo "Tokens:\n-------\n\n";
var_dump($p->getTokens($tpl));

echo "\n\n\nCode:\n-----\n\n";
echo $p->compile($tpl);
echo "\n\n";

$p->compilerOptions = array("includePartialCode" => true);
echo "\n\n\nCode with included partials:\n----------------------------\n\n";
echo $p->compile($tpl);
echo "\n\n";

$p->compilerOptions = array("disableLambdas" => true);
echo "\n\n\nCode with disabled lambdas:\n---------------------------\n\n";
echo $p->compile($tpl);
echo "\n\n";

$p->compilerOptions = array("disableIndentation" => true);
echo "\n\n\nCode with disabled indentation:\n-------------------------------\n\n";
echo $p->compile($tpl);
echo "\n\n";


?>