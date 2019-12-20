<?php

use Gentry\Gentry\Wrapper;

putenv("CODGER_DRY=1");
$recipe = include 'recipes/detail/Recipe.php';
$inout = Wrapper::createObject(Codger\Generate\FakeInOut::class);
Codger\Generate\Recipe::setInOut($inout);

/** Test detail recipe */
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
        $recipe('Foo');
        $result = $inout->flush();
        assert(strpos($result, <<<EOT
{% extends 'template.html.twig' %}

{% block title %}Details of foo{% endblock title %}

{% block content %}
    <article>
        {% for property, value in foo %}
            {{ property }}: {{ value }}<br>
        {% endfor %}
    </article>
{% endblock content %}
EOT
        ) !== false);
    };
    
    /** Creates a view */
    yield function () use ($inout, $recipe) {
        $result = $recipe('Foo')->render();
        assert(strpos($result, <<<EOT
namespace Foo\Detail;

/** A detail view for foo */
class View extends \View
{
    /** @var string */
    protected \$template = 'Foo/Detail/template.html.twig';
    /** @var Foo\Model */
    public \$foo;
    /** @var Foo\Repository */
    protected \$fooRepository;

    /**
     * @var int \$id The id of the foo to display
     * @return void
     * @throws Monolyth\Frontal\Exception if no foo with \$id was found
     */
    public function __construct(int \$id)
    {
        parent::__construct();
        \$this->inject(function (\$fooRepository) {});
        if (!(\$this->foo = \$this->fooRepository->find(\$id))) {
            throw new Frontal\Exception(404, "foo with id \$id not found");
        }
    }
}
EOT
        ) !== false);
    };
};

