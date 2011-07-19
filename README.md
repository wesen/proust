Proust - Mustache template compiler for PHP
===========================================

Proust is a PHP compiler for [mustache](http://mustache.github.com/)
templates. It started as a port of the Ruby compiler for mustache
templates, but was further extended for mustache v1.1.2 spec
compliance and PHP-specific extensions. It also allows a number of
compiler parameters to customize and optimize compiled templates.

Proust also comes with a port of the Ruby StringScanner object which
could prove useful on its own.

Overview
--------

Proust takes mustache templates as input (either one or more
templates, either as strings or as templates), and generates PHP code
for them. Partials can be passed dynamically or by using a template
directory which the compiler will use to look them up. The compiled
code can be cached dynamically and on disk by writing out php files.

Quick example
-------------

A quick example:

```php
<?php

/* simple hello world */

require_once(dirname(__FILE__)."/../Proust.php");

$p = new Proust\Proust();
echo $p->render("Hello {{planet}}\n", array("planet" => "World"));

?>
```

Mustache template
-----------------

The canonical Mustache template, with a php class:

```php
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
```

Proust options
--------------

Now on to the more compiler specific aspects of Proust. Proust can
cache the output of the compiler in a cache directory. These files can
later be reused by including their contents. Proust does this
automatically when caching is enabled. It recompiles templates when
the original template file is newer than the cached version. It also
hashes the compiler options and ctag and otag to avoid conflicts.

This example also shows the multiple compiler options:

* *disableObjects* - disable the possibility to use objects (and their
   methods and variables) at runtime. Makes for a slightly quicker
   context lookup. This is not a compiler option, but rather a runtime
   option.
* *disableLambdas* - don't allow for lambdas (or object methods) on
   the context stack. Makes the resulting code smaller and slightly
   quicker.
* *disableIndentation* - don't indent partials (which is not necessary
   for HTML for example). Makes for faster output, as there is no need
   to keep track of the indentation level, and plain "echo" can be
   used.
* *includePartialCode* - include partial code directly where a partial
   is called. Makes for faster (and bigger) code.

```php
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
```

Tokenized output
-----------------

To get a glimpse at the compilation results, you can use the getTokens
(with their variants getTemplateTokens and getFileTokens), as well as
the compile (and compileTemplate and compileFile) methods.

```php
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
```

Compiler output
---------------

The compiler output can be beautified by installing the PHP_Beautifyer
PEAR extension, and setting the compiler option "beautify" to
true. This is not entirely tested, and is a bit brittle (it happened
to break a huge class compilation, but that has been "avoided" for now).

```php
<?php

/* compile some templates */

require_once(dirname(__FILE__)."/../Proust.php");

$p = new Proust\Proust(array("templatePath" => dirname(__FILE__)."/templates/"));
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
```

Class Generation
----------------

Proust can also generate a class from templates and partials. The
class can then be used to render the templates (one method per
template or partial.

```php
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
```

Command line interface
----------------------

Proust comes with a command line interface to compile and inspect
mustache templates.

```
$ php Proust.php  -h
Usage:

 Proust.php [-o outputfile] [-p partialDir] [-i] [-e] [-t] [-h] [-j json] -- inputfiles...

   -o outputfile : store php in this file
   -t            : print token array
   -h            : this information
   -p path       : set template path
   -e            : evaluate templates
   -j json       : parse json file and pass as context to evaluation
   -c name       : compile to class name
   --disable-lambdas : disable lambdas for compilation
   --disable-indentation : disable indentation for compilation
   --include-partials : include partials directly as code
   --beautify     : beautify generated code
```

Known Issues
------------

* Beware of caching and the compiler option "includePartialCode". When
  partials are changed, the compiled code won't be updated, and still
  contain the all partials. If you want to include dynamic partials as
  well (even more volatile), please set the compiler options
  "includeDynamicPartials" to true.

See Also
--------

 * [mustache specification](https://github.com/mustache/spec)
 * [Readme for the Ruby Mustache implementation](http://github.com/defunkt/mustache/blob/master/README.md).
 * [mustache(1)](http://mustache.github.com/mustache.1.html) and [mustache(5)](http://mustache.github.com/mustache.5.html) man pages.
