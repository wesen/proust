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

$tpl2 =<<<'EOT'
{{#foo}}{{>section1}}{{/foo}}
EOT;

echo "\n\n\nClass:\n-----\n\n";
echo $p->compileClass("TestClass", array(array("main", $tpl)));
echo "\n\n";

?>