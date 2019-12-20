<?php

use Gentry\Gentry\Wrapper;

putenv("CODGER_DRY=1");
$recipe = include 'recipes/list/Recipe.php';
$inout = Wrapper::createObject(Codger\Generate\FakeInOut::class);
Codger\Generate\Recipe::setInOut($inout);

/** Test list recipe */
return function () use ($inout, $recipe) : Generator {
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
    yield function () use ($inout, $recipe) {
        $recipe->call('Foo');
        $result = $inout->flush();
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
    yield function () use ($inout, $recipe) {
        $result = $recipe->call('Foo')->render();
        assert(strpos($result, <<<EOT
namespace Foo;

class View extends \View
{
    /** @var string */
    protected \$template = 'Foo/template.html.twig';
    /** @var array */
    public \$foos;
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

