<?php

use Codger\Generate\Recipe;
use Codger\Generate\Language;

return function (string $namespace) : Recipe {
    $recipe = new class(new Twig_Environment(new Twig_Loader_Filesystem(dirname(__DIR__, 2).'/templates'))) extends Recipe {
        protected $template = 'sass.html.twig';
    };
    $recipe->set('id', Language::convert($namespace, Language::TYPE_CSS_IDENTIFIER))
        ->output(getcwd().'/src/'.Language::convert($namespace, Language::TYPE_PATH).'/_style.scss');
    return $recipe;
};

