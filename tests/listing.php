<?php

use Gentry\Gentry\Wrapper;
use Codger\Lodger\Listing\{ View, Template };

$inout = new Codger\Generate\FakeInOut;
Codger\Generate\Recipe::setInOut($inout);

/** Test list recipe */
return function () : Generator {
    $this->beforeEach(function () use (&$dir) {
        $dir = getcwd();
        chdir(getcwd().'/tmp');
        file_put_contents(getcwd().'/composer.json', <<<EOT
{
    "name": "codger/dummy",
    "description": "Dummy config.json",
    "type": "project",
    "license": "MIT",
    "authors": [
        {
            "name": "Marijn Ophorst",
            "email": "marijn@monomelodies.nl"
        }
    ],
    "require": {}
}
EOT
        );
    });
    $this->afterEach(function () use (&$dir) {
        chdir($dir);
    });

    /** Creates a template */
    yield function () {
        $recipe = new Wrapper(new Template(['Foo/template.html.twig', 'foo']));
        $recipe->execute();
        $result = $recipe->render();
        assert(strpos($result, <<<EOT
{% extends 'template.html.twig' %}

{% block title %}List of foos{% endblock title %}

{% block content %}
    <ul>
        {% for foo in foos %}
            <li><a href="{{ url('foo-details', {id: foo.id}) }}">{{ foo.id }}</a></li>
        {% endfor %}
    </ul>
{% endblock content %}
EOT
        ) !== false);
    };
    
    /** Creates a view */
    yield function () {
        $recipe = new Wrapper(new View(['Foo']));
        $recipe->execute();
        $result = $recipe->render();
        assert(strpos($result, <<<EOT
namespace Foo;

class View extends \View
{
    /** @var array */
    public \$foos;

    /** @var string */
    protected \$template = 'Foo/template.html.twig';

    /** @var Foo\Repository */
    protected \$fooRepository;

    public function __construct()
    {
        parent::__construct();
        \$this->inject(function (\$fooRepository) {});
        \$this->foos = \$this->fooRepository->all();
    }
}
EOT
        ) !== false);
    };
};

