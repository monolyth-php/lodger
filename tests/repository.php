<?php

use Gentry\Gentry\Wrapper;

putenv("CODGER_DRY=1");

$recipe = include 'recipes/repository/Recipe.php';
$inout = new Codger\Generate\FakeInOut;
Codger\Generate\Recipe::setInOut($inout);

/** Test repository recipe */
return function () use ($recipe, $inout) : Generator {
    /** Create a Foo repository with all expected operations */
    yield function () use ($recipe, $inout) {
        $result = $recipe('Foo')->render();
        assert(strpos($result, 'namespace Foo;') !== false);
        
        /** Find method */
        assert(strpos($result, <<<EOT
    public function find(int \$id) :? Model
    {
        try {
            return \$this->adapter->selectFrom('foo')
                ->where('id = ?', \$id)
                ->fetchObject(Model::class);
        } catch (SelectException \$e) {
            return null;
        }
    }
EOT
        ) !== false);
        
        /** Save method */
        assert(strpos($result, <<<EOT
    public function save(Model &\$model) :? string
    {
        \$data = [];
        \$reflection = new ReflectionObject(\$model);
        foreach (\$reflection->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED & ~ReflectionProperty::IS_STATIC) as \$property) {
            \$property->setAccessible(true);
            if (\$property->name != 'id' && (isset(\$model->id) || !is_null(\$property->getValue(\$model)))) {
                \$data[\$property->name] = \$property->getValue(\$model);
            }
        }
        try {
            if (isset(\$model->id)) {
                \$this->adapter->updateTable('foo')
                    ->where('id = ?', \$model->id)
                    ->execute(\$data);
                \$model = \$this->find(\$model->id);
            } else {
                \$this->adapter->insertInto('foo')
                    ->execute(\$data);
                \$model = \$this->find(\$this->adapter->lastInsertId('foo'));
            }
            return null;
        } catch (InsertException \$e) {
            return 'insert';
        } catch (UpdateException \$e) {
            return 'update';
        }
    }
EOT
        ) !== false);

        /** Delete method */
        assert(strpos($result, <<<EOT
    public function delete(Model &\$model) :? string
    {
        try {
            \$this->adapter->deleteFrom('foo')
                ->where('id = ?', \$model->id)
                ->execute();
            \$model = null;
            return null;
        } catch (DeleteException \$e) {
            return 'database';
        }
    }
EOT
        ) !== false);
    };
};

