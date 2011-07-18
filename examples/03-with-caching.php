<?php

/* example with partials, caching and some compiler options */

require_once(dirname(__FILE__)."/../Proust.php");

$p = new Proust\Proust(array("enableCache" => true,
                             "cacheDir" => dirname(__FILE__)."/.proust.cache/",
                             "templatePath" => dirname(__FILE__)."/templates/",
                             "disableObjects" => "true",
                             "compilerOptions" => array("disableLambdas" => true,
                                                        // "disableIndentation" => true,
                                                        "includePartialCode" => true
                                                        )));

$data = array("foo" => array("x" => 1,
                             "y" => 2,
                             "z" => array(1, 2, 3, 4)));
echo $p->renderTemplate("section1", $data);

echo "\nIndentation disabled:\n";

$p->compilerOptions["disableIndentation"] = true;
echo $p->renderTemplate("section1", $data);

echo "\nWith explicit partials:\n";
$p->compilerOptions["disableIndentation"] = false;
$p->partials = array("partial2" => "{{foo.y}}");
echo $p->renderTemplate("section1", $data);

echo "\nShow caching in effect:\n";
$p->partials = array("partial2" => "NEW VERSION: {{foo.y}}");
echo $p->renderTemplate("section1", $data);

echo "\nAfter clearCache:\n";
$p->clearCache();
echo $p->renderTemplate("section1", $data);

?>