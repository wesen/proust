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

Features:

Usage
-----

A quick example:

```php
<?php

/* simple hello world */

require_once(dirname(__FILE__)."/../Proust.php");

$p = new Proust\Proust();
echo $p->render("Hello {{planet}}\n", array("planet" => "World"));

?>
```

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

echo "\n\n";

$p->compilerOptions["disableIndentation"] = true;
echo $p->renderTemplate("section1", $data);

?>
```

Command line interface
----------------------

Proust comes with a command line interface to generate and inspect
mustache templates.

Caching
-------

Known Issues
------------

  * None :)

See Also
--------

 * [mustache specification](https://github.com/mustache/spec)
 * [Readme for the Ruby Mustache implementation](http://github.com/defunkt/mustache/blob/master/README.md).
 * [mustache(1)](http://mustache.github.com/mustache.1.html) and [mustache(5)](http://mustache.github.com/mustache.5.html) man pages.
