Proust - Mustache template compiler for PHP
----------------------------------------

Proust is a PHP compiler for mustache templates
(http://mustache.org). It started as a port of the Ruby compiler for
mustache templates, but was further extended for mustache v1.1.2 spec
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

Proust evaluates


Command line interface
----------------------

Proust comes with a command line interface to generate and inspect
mustache templates.