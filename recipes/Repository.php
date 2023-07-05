<?php

namespace Codger\Lodger;

use Codger\Php\Klass;
use Codger\Php\Method;
use Codger\Generate\Language;

/**
 * Generate a Monolyth-style repository.
 */
class Repository extends Klass
{
    public string $table;

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
            ->defineProperty('adapter', null, 'private')
            ->addMethod('__construct', function (Method $method) : string {
                return <<<EOT
\$this->inject(function (\$adapter) {});
EOT;
            })
            ->addMethod('all', function () : array {}, function (Method $method) : string {
                return <<<EOT
throw new \\LogicException("This method is not yet implemented.");
EOT;
            })
            ->addMethod('find', function (int $id) :? \Model {}, function (Method $method) : string {
                return <<<EOT
EOT;
            })
            ->addMethod('save', function (\Model $model, array $includeFields = []) :? string {}, function (Method $method) : string {
                return <<<EOT
throw new \\LogicException("This method is not yet implemented.");
EOT;
            })
            ->addMethod('delete', function (\Model &$model) :? string {}, function (Method $method) : string {
                return <<<EOT
throw new \\LogicException("This method is not yet implemented.");
EOT;
            })
            ->output(Language::convert($name, Language::TYPE_PATH).'/Repository.php')
            ->info(<<<EOT
Don't forget to register a dependency for this repository, e.g.

\$container->register(function (&\${$var}Repository) {
    \${$var}Repository = new $name\\Repository;
});
EOT
        );
    }
}

