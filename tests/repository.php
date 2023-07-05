<?php

use Gentry\Gentry\Wrapper;

$inout = new Codger\Generate\FakeInOut;
Codger\Generate\Recipe::setInOut($inout);

/** Test repository recipe */
return function () : Generator {
    $recipe = new Wrapper(new Codger\Lodger\Repository(['Foo', '--table=foo']));
    $recipe->execute();
    $result = $recipe->render();

    /** We are in the correct namespace */
    yield function () use ($result) {
        assert(strpos($result, 'namespace Foo;') !== false);
    };

    /** We have the correct general class definition */
    yield function () use ($result) {
        assert(strpos($result, <<<EOT
use Sensimedia\Supportery\DatabaseRepository;

class Repository extends DatabaseRepository
{
EOT
        ) !== false);
    };

    /** We get an `all` method shorthand */
    yield function () use ($result) {
        assert(strpos($result, <<<EOT
    public function all() : array
    {
        return \$this->list(\$this->select());
    }
EOT
        ) !== false);
    };

    /** We get a `find` method shorthand */
    yield function () use ($result) {
        assert(strpos($result, <<<EOT
    public function find(int \$id) :? Model
    {
        return \$this->findByIdentifier(\$id);
    }
EOT
        ) !== false);
    };
};

