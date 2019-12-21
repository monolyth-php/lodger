<?php

namespace Codger\Lodger;

use Codger\Php\{ Klass, Method };
use Codger\Generate\Language;

class DetailView extends View
{
    /** @var string */
    public $extends;

    /** @var string */
    public $template;

    public function __invoke(string $namespace)
    {
        $class = (require dirname(__DIR__).'/view/Recipe.php')->call($this, "$namespace\\Detail", $extends);
        $thing = Language::convert($namespace, Language::TYPE_VARIABLE);
        if (!isset($template)) {
            $template = Language::convert($namespace, Language::TYPE_PATH).'/Detail/template.html.twig';
        }
        $class->defineProperty($thing, null, 'public', "@var $namespace\\Model")
            ->defineProperty('template', $template, 'protected', '@var string')
            ->defineProperty("{$thing}Repository", null, 'protected', "@var $namespace\\Repository")
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
            ->output(getcwd().'/src/'.Language::convert($namespace, Language::TYPE_PATH).'/Detail/View.php')
            ->setDoccomment("A detail view for $thing");
        if (isset($template)) {
            $class->delegate('sensi/codger-improse-view@detail/template', $template, $thing);
        }
        return $class;
    }
}

