<?php

/* render and call a template class */

require_once(dirname(__FILE__)."/../Proust.php");

$p = new Proust\Proust(array("templatePath" => dirname(__FILE__)."/templates/",
                             "compilerOptions" => array("beautify" => true)));
$p->partials = array("partial" => "{{#section}}{{bla}}{{/section}}\n");

$tpl =<<<'EOT'
{{#foo}}{{bla}}{{/foo}}
{{>partial}}
EOT;

$tpl2 =<<<'EOT'
{{#foo}}{{>section1}}{{/foo}}
EOT;

echo "\n\n\nClass:\n-----\n\n";
$code = $p->compileClass("TestClass", array(array("main", $tpl),
                                            array("foobar", $tpl2)));
echo $code;
echo "\n\n";

eval($code);

$test = new TestClass($p);
echo "\n\n\nMethod main():\n---------------\n\n";
echo $test->main(array("foo" => array("bla" => "Hello world"),
                       "section" => array("bla" => "Partial hello world")));

echo "\n\n\nMethod foobar():\n----------------\n\n";
echo $test->foobar(array("foo" => array("x" => 1,
                                        "y" => 2,
                                        "z" => array(1, 2, 3, 4)),
                         "section" => array("bla" => "Partial hello world")));


?>