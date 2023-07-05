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
            ->usesNamespaces(
                'Sensimedia\\Supportery\\DatabaseRepository',
            )
            ->extendsClass('DatabaseRepository')
            ->addMethod('all', function () : array {}, function (Method $method) : string {
                return <<<EOT
return \$this->list(\$this->select());
EOT;
            })
            ->addMethod('find', function (int $id) :? \Model {}, function (Method $method) : string {
                return <<<EOT
return \$this->findByIdentifier(\$id);
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

