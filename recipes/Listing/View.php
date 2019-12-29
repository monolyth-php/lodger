<?php

namespace Codger\Lodger\Listing;

use Codger\Lodger;
use Codger\Generate\Language;

class View extends Lodger\View
{
    public function __invoke(string $namespace) : void
    {
        $this->setNamespace(Language::convert($namespace, Language::TYPE_PHP_NAMESPACE))
            ->set('name', 'View')
            ->extendsClass('\View');
        $repo = Language::convert($namespace, Language::TYPE_VARIABLE);
        $things = Language::pluralize($repo);
        if (!isset($this->template)) {
            $this->template = Language::convert($namespace, Language::TYPE_PATH).'/template.html.twig';
        }
        $this->defineProperty($things, function ($property) {
            $property->setVisibility('public')->setDoccomment('@var array');
        })
            ->defineProperty('template', function ($property) {
                $property->setDefault($this->template)
                    ->setVisibility('protected')
                    ->setDoccomment('@var string');
            })
            ->defineProperty("{$repo}Repository", function ($property) use ($namespace) {
                $property->setVisibility('protected')
                    ->setDoccomment("@var $namespace\\Repository");
            })
            ->addMethod('__construct', function () use ($things, $repo) : string {
                return <<<EOT
parent::__construct();
\$this->inject(function (\${$repo}Repository) {});
\$this->{$things} = \$this->{$repo}Repository->all();
EOT;
            })
            ->output(Language::convert($namespace, Language::TYPE_PATH).'/View.php');
    }
}

