<?php

use Gentry\Gentry\Wrapper;

putenv("CODGER_DRY=1");
$recipe = include 'recipes/controller/Recipe.php';

/** Controller recipe */
return function () use ($recipe) : Generator {
    /** Controller recipe makes us a controller */
    yield function () use ($recipe) {
        $result = $recipe('Foo')->render();
        assert(strpos($result, <<<EOT
namespace Foo;

use Monolyth\\Disclosure\\Injector;

class Controller
{
EOT
        ) !== false);
        assert(strpos($result, 'function create(') !== false);
        assert(strpos($result, 'function update(') !== false);
        assert(strpos($result, 'function delete(') !== false);
    };
};

