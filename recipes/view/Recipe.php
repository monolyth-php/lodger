<?php

use Codger\Php\Klass;
use Codger\Php\Composer;
use Codger\Generate\Language;

/**
 * Default view generation recipe. Pass `global` as a namespace to, eh, not use
 * a namespace.
 */
return function (string $namespace, string $extends = '\View', string $template = null, string $uses = null, string $recipe = 'view/template') : Klass {
    $isGlobal = strtolower($namespace) == 'global';
    $class = new Klass(new Twig_Environment(new Twig_Loader_Filesystem(__DIR__)));
    if (!$isGlobal) {
        $namespace = Language::convert($namespace, Language::TYPE_PHP_NAMESPACE);
        $class->output(getcwd().'/src/'.Language::convert($namespace, Language::TYPE_PATH).'/View.php');
        $class->defineProperty('template', $template, 'protected', '@var string');
    } else {
        $class->output(getcwd().'/src/View.php')
            ->isAbstract();
    }
    if (strtolower($namespace) != 'global') {
        $class->setNamespace($namespace);
    }
    $class->extendsClass($extends)
        ->setName('View');
    if (!is_null($uses)) {
        $class->usesNamespaces($uses);
    }
    if (isset($template)) {
        $class->delegate("sensi/codger-improse-view@$recipe", $template);
    }
    return $class;
};

