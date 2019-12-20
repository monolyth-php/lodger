<?php

use Codger\Php\Klass;
use Codger\Generate\Language;

return function (string $namespace, string $extends = '\View', string $template = null) : Klass {
    $class = (require dirname(__DIR__).'/view/Recipe.php')($namespace, $extends);
    $repo = Language::convert($namespace, Language::TYPE_VARIABLE);
    $things = Language::pluralize($repo);
    if (!isset($template)) {
        $template = Language::convert($namespace, Language::TYPE_PATH).'/template.html.twig';
    }
    $class->defineProperty($things, null, 'public', '@var array')
        ->defineProperty('template', $template, 'protected', '@var string')
        ->defineProperty("{$repo}Repository", null, 'protected', "@var $namespace\\Repository")
        ->addMethod('__construct', function () use ($things, $repo) : string {
            return <<<EOT
parent::__construct();
\$this->inject(function (\${$repo}Repository) {});
\$this->{$things} = \$this->{$repo}Repository->all();
EOT;
        })
        ->output(getcwd().'/src/'.Language::convert($namespace, Language::TYPE_PATH).'/View.php');
    if (isset($template)) {
        $class->delegate('sensi/codger-improse-view@list/template', $template, $repo);
    }
    return $class;
};

