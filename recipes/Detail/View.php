<?php

namespace Codger\Lodger\Detail;

use Codger\Lodger;
use Codger\Php\{ Klass, Method };
use Codger\Generate\Language;

class View extends Lodger\View
{
    public string $extends;

    public string $template;

    public function __invoke(string $namespace) : void
    {
        parent::__invoke("$namespace\\Detail");
        $thing = Language::convert($namespace, Language::TYPE_VARIABLE);
        $this
            ->defineProperty($thing, function ($property) use ($namespace) {
                $property->setDoccomment("@var $namespace\\Model");
            })
            ->defineProperty("{$thing}Repository", function ($property) use ($namespace) {
                $property->setVisibility('protected')
                    ->setDoccomment("@var $namespace\\Repository");
            })
            ->usesNamespaces('Monolyth\Frontal')
            ->addMethod('__construct', function(int $id) {}, function (Method $method) use ($thing) : string {
                $method->setDoccomment(<<<EOT
@var int \$id The id of the $thing to display
@return void
@throws Monolyth\\Frontal\\Exception if no $thing with \$id was found
EOT
                );
                return <<<EOT
parent::__construct();
\$this->inject(function (\${$thing}Repository) {});
if (!(\$this->$thing = \$this->{$thing}Repository->find(\$id))) {
    throw new Frontal\\Exception(404, "$thing with id \$id not found");
}
EOT;
            })
            ->setDoccomment("A detail view for $thing");
    }
}

