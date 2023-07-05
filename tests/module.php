<?php

use Gentry\Gentry\Wrapper;

$inout = new Codger\Generate\FakeInOut;
Codger\Generate\Recipe::setInOut($inout);

/** Test module recipe */
return function () use ($inout) : Generator {
    $inout->expect('Y');
    $inout->expect('Y');
    $inout->expect('Y');
    $inout->expect('Y');
    $inout->expect('Y');
    $inout->expect('Y');
    $inout->expect('Y');
    $inout->expect("\n");
    $recipe = new Wrapper(new Codger\Lodger\Module([
        'Foo', '--table=users', '--vendor=pgsql', '--database=codger_test', '--user=codger_test', '--pass=blarps', '--ornament',
    ]));
    $recipe->execute();
    $recipe->process();
    $output = $inout->flush();

    /** Creates a model */
    yield function () use ($output) {
        // Outputs a model
        assert(strpos($output, <<<EOT
namespace Foo;

use Ornament\Core;

class Model
EOT
        ) !== false);
    };
    
    /** Creates a view */
    yield function () use ($output) {
        assert(strpos($output, <<<EOT
namespace Foo;

class View extends \View
EOT
        ) !== false);
    };
    
    /** Creates a repository */
    yield function () use ($output) {
        assert(strpos($output, <<<EOT
class Repository extends DatabaseRepository
EOT
        ) !== false);
    };       
    /** Creates a template */
    yield function () use ($output) {
        assert(strpos($output, <<<EOT
{% extends 'template.html.twig' %}

{% block title %}List of foos{% endblock title %}
EOT
        ) !== false);
    };
};

