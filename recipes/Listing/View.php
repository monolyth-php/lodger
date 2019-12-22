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
        $this->defineProperty($things, null, 'public', '@var array')
            ->defineProperty('template', $this->template, 'protected', '@var string')
            ->defineProperty("{$repo}Repository", null, 'protected', "@var $namespace\\Repository")
            ->addMethod('__construct', function () use ($things, $repo) : string {
                return <<<EOT
parent::__construct();
\$this->inject(function (\${$repo}Repository) {});
\$this->{$things} = \$this->{$repo}Repository->all();
EOT;
            })
            ->output(getcwd().'/src/'.Language::convert($namespace, Language::TYPE_PATH).'/View.php');
    }
}

