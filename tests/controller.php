<?php

use Gentry\Gentry\Wrapper;

/** Controller recipe */
return function () : Generator {
    /** Controller recipe makes us a controller */
    yield function () {
        $recipe = new Wrapper(new Codger\Lodger\Controller(['Foo']));
        $recipe->execute();
        $result = $recipe->render();
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

