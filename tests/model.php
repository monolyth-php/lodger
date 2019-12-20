<?php

use Gentry\Gentry\Wrapper;

$recipe = include 'recipes/model/Recipe.php';
$inout = Wrapper::createObject(Codger\Generate\FakeInOut::class);
Codger\Generate\Recipe::setInOut($inout);
putenv("CODGER_DRY=1");

/** Test model recipe */
return function () use ($recipe, $inout) : Generator {
    $bootstrap = new Codger\Generate\Bootstrap('model');
    /** The recipe should make us a basic model according to our parameters */
    yield function () use ($recipe, $inout, $bootstrap) {
        $bootstrap->resetOptions();
        $bootstrap->setOptions(['^prefill', '^ornament']);
        $result = $recipe->call($bootstrap, 'User')->render();
        assert(strpos($result, <<<EOT
namespace User;

class Model
{}
EOT
        ) !== false);
    };
    
    /** The recipe should make us a model using a db table as a base */
    yield function () use ($recipe, $inout, $bootstrap) {
        $db = new PDO('pgsql:dbname=codger_test', 'codger_test', 'blarps');
        $db->exec(file_get_contents(dirname(__DIR__).'/info/fixture.sql'));
        $bootstrap->resetOptions();
        $bootstrap->setOptions(['prefill', '^ornament']);
        $result = $recipe->call($bootstrap, 'Users', 'users', 'pgsql', 'codger_test', 'blarps')->render();
        assert(strpos($result, <<<EOT
namespace Users;

class Model
{
    public \$id;
    /** @var string */
    public \$username;
    /** @var string */
    public \$password;
    /** @var string */
    public \$description;
}
EOT
        ) !== false);
    };
    
    /** It can also add Ornament to our model */
    yield function () use ($recipe, $inout, $bootstrap) {
        $bootstrap->resetOptions();
        $db = new PDO('pgsql:dbname=codger_test', 'codger_test', 'blarps');
        $db->exec(file_get_contents(dirname(__DIR__).'/info/fixture.sql'));
        $result = $recipe->call($bootstrap, 'Users', 'users', 'pgsql', 'codger_test', 'blarps')->render();
        assert(strpos($result, <<<EOT
namespace Users;

use Ornament\Core;

class Model
{
    use Core\Model;

    public \$id;
    /** @var string */
    public \$username;
    /** @var string */
    public \$password;
    /** @var string */
    public \$description;
}
EOT
        ) !== false);
    };
};

