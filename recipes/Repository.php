<?php

namespace Codger\Lodger;

use Codger\Php\Klass;
use Codger\Php\Method;
use Codger\Generate\Language;

class Repository extends Klass
{
    /** @var string */
    public $table;

    public function __invoke(string $name) : void
    {
        if (!isset($name)) {
            $this->ask("What namespace shall we put this in?", function (string $answer) use (&$name) : void {
                $name = $answer;
            });
        }
        $name = Language::convert($name, Language::TYPE_PHP_NAMESPACE);
        $var = Language::convert($name, Language::TYPE_VARIABLE);
        while (!isset($this->table)) {
            if (isset($name)) {
                $this->table = strtolower(str_replace('\\', '_', $name));
            }
            $this->ask("Which table should we use? [{$this->table}]", function (string $answer) : void {
                if (strlen($answer)) {
                    $this->table = $answer;
                }
            });
        }
        $this->setNamespace($name)
            ->setName('Repository')
            ->usesNamespaces(
                'Monolyth\\Disclosure\\Injector',
                'Quibble\\Query\\{ SelectException, InsertException, UpdateException, DeleteException }',
                'ReflectionObject',
                'ReflectionProperty',
                'PDO'
            )
            ->usesTraits('Injector')
            ->defineProperty('adapter', null, 'private')
            ->addMethod('__construct', function (Method $method) : string {
                return <<<EOT
\$this->inject(function (\$adapter) {});
EOT;
            })
            ->addMethod('all', function () : array {}, function (Method $method) : string {
                return <<<EOT
try {
    return \$this->adapter->selectFrom('{$this->table}')
        ->fetchAll(PDO::FETCH_CLASS, Model::class);
} catch (SelectException \$e) {
    return [];
}
EOT;
            })
            ->addMethod('find', function (int $id) :? \Model {}, function (Method $method) : string {
                return <<<EOT
try {
    return \$this->adapter->selectFrom('{$this->table}')
        ->where('id = ?', \$id)
        ->fetchObject(Model::class);
} catch (SelectException \$e) {
    return null;
}
EOT;
            })
            ->addMethod('save', function (\Model &$model) :? string {}, function (Method $method) : string {
                return <<<EOT
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
        \$this->adapter->updateTable('{$this->table}')
            ->where('id = ?', \$model->id)
            ->execute(\$data);
        \$model = \$this->find(\$model->id);
    } else {
        \$this->adapter->insertInto('{$this->table}')
            ->execute(\$data);
        \$model = \$this->find(\$this->adapter->lastInsertId('{$this->table}'));
    }
    return null;
} catch (InsertException \$e) {
    return 'insert';
} catch (UpdateException \$e) {
    return 'update';
}
EOT;
            })
            ->addMethod('delete', function (\Model &$model) :? string {}, function (Method $method) : string {
                return <<<EOT
try {
    \$this->adapter->deleteFrom('{$this->table}')
        ->where('id = ?', \$model->id)
        ->execute();
    \$model = null;
    return null;
} catch (DeleteException \$e) {
    return 'database';
}
EOT;
            })
            ->output(getcwd().'/src/'.Language::convert($name, Language::TYPE_PATH).'/Repository.php')
            ->info(<<<EOT
Don't forget to register a dependency for this repository, e.g.

\$container->register(function (&\${$var}Repository) {
    \${$var}Repository = new $name\\Repository;
});
EOT
        );
    }
}

