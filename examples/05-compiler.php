<?php

/* get code tokens */

require_once(dirname(__FILE__)."/../Proust.php");

$p = new Proust\Proust(array("templatePath" => dirname(__FILE__)."/templates/",
                             "compilerOptions" => array("beautify" => true)));
$p->partials = array("partial" => "{{#section}}{{bla}}{{/section}}");

$tpl =<<<'EOT'
{{#foo}}{{bla}}{{/foo}}
{{>partial}}
EOT;

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