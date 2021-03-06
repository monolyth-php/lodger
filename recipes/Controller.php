<?php

namespace Codger\Lodger;

use Codger\Php\Klass;
use Codger\Php\Method;
use Codger\Generate\Language;

class Controller extends Klass
{
    public function __invoke(string $namespace) : void
    {
        $namespace = Language::convert($namespace, Language::TYPE_PHP_NAMESPACE);
        $varname = Language::convert($namespace, Language::TYPE_VARIABLE);
        $this->setNamespace($namespace)
            ->usesNamespaces('Monolyth\Disclosure\Injector')
            ->setName('Controller')
            ->usesTraits('Injector')
            ->defineProperty("{$varname}Repository", null, 'private', "@var {$namespace}Repository")
            ->addMethod('__construct', function () use ($varname) : string {
                return <<<EOT
    \$this->inject(function (\${$varname}Repository) {});

EOT;
            })
            ->addMethod('create', function () :? string {}, function (Method $method) use ($namespace, $varname) : string {
                $method->setDoccomment(
                    <<<EOT
    Create a new model of type {$namespace}\\Model.

    @return null|string
EOT
                );
                return <<<EOT
    \$model = new Model;
    foreach (\$_POST as \$key => \$value) {
        \$model->\$key = \$value;
    }
    return \$this->{$varname}Repository->save(\$model);

EOT;
            })
            ->addMethod('update', function (int $id) :? string {}, function (Method $method) use ($varname) : string {
                $method->setDoccomment(
                    <<<EOT
    Update the model with the given \$id.

    @param int \$id
    @return null|string
EOT
                );
                return <<<EOT
    \$form = new Form;
    if (\$model = \$this->{$varname}Repository->find(\$id)) {
        \$form->bind(\$model);
        return \$this->{$varname}Repository->save(\$model);
    } else {
        return 'not found';
    }

EOT;
            })
            ->addMethod('delete', function (int $id) :? string {}, function (Method $method) use ($varname) : string {
                $method->setDoccomment(
                    <<<EOT
    Delete the model with the given \$id.

    @param int \$id
    @return null|string
EOT
                );
                return <<<EOT
    if (\$model = \$this{$varname}Repository->find(\$id)) {
        return \$this->{$varname}Repository->delete(\$model);
    } else {
        return 'not found';;
    }

EOT;
            })
            ->output(Language::convert($namespace, Language::TYPE_PATH).'/Controller.php');
    }
}

