<?php

use Gentry\Gentry\Wrapper;
use Codger\Lodger\Model;

$inout = Wrapper::createObject(Codger\Generate\FakeInOut::class);
Codger\Generate\Recipe::setInOut($inout);

/** Test model recipe */
return function () : Generator {
    /** The recipe should make us a basic model according to our parameters */
    yield function () {
        $model = new Model(['User', '--skip-prefill']);
        $model->execute();
        $result = $model->render();
        assert(strpos($result, <<<EOT
namespace User;

class Model
{
}
EOT
        ) !== false);
    };
    
    /** The recipe should make us a model using a db table as a base */
    yield function () {
        $db = new PDO('pgsql:dbname=codger_test', 'codger_test', 'blarps');
        $db->exec(file_get_contents(dirname(__DIR__).'/info/fixture.sql'));
        $model = new Model(['User', '--vendor=pgsql', '--table=users', '--database=codger_test', '--user=codger_test', '--pass=blarps']);
        $model->execute();
        $result = $model->render();
        assert(strpos($result, <<<EOT
namespace User;

class Model
{
    /** @var int */
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
    yield function () {
        $db = new PDO('pgsql:dbname=codger_test', 'codger_test', 'blarps');
        $db->exec(file_get_contents(dirname(__DIR__).'/info/fixture.sql'));
        $model = new Model(['User', '--vendor=pgsql', '--table=users', '--database=codger_test', '--user=codger_test', '--pass=blarps', '--ornament']);
        $model->execute();
        $result = $model->render();
        assert(strpos($result, 'use Ornament\Core;') !== false);
        assert(strpos($result, 'use Core\Model;') !== false);
    };
};

