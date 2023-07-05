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
class Repository
{
EOT
        ) !== false);
    };

    /** We get an `all` method */
    yield function () use ($result) {
        assert(strpos($result, <<<EOT
    public function all() : array
    {
EOT
        ) !== false);
    };

    /** We get a `find` method */
    yield function () use ($result) {
        assert(strpos($result, <<<EOT
    public function find(int \$id) :? Model
    {
EOT
        ) !== false);
    };

    /** We get a `save` method */
    yield function () use ($result) {
        assert(strpos($result, <<<EOT
    public function save(Model \$model) :? string
    {
EOT
        ) !== false);
    };

    /** We get a `delete` method */
    yield function () use ($result) {
        assert(strpos($result, <<<EOT
    public function delete(Model \$model) :? string
    {
EOT
        ) !== false);
    };
};

