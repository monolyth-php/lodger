<?php

use Codger\Generate\Recipe;

return function (string $path, string $thing) : Recipe {
    $recipe = new class(new Twig_Environment(new Twig_Loader_Filesystem(dirname(__DIR__, 3).'/templates'))) extends Recipe {
        protected $template = 'detail.html.twig';
    };
    $recipe
        ->set('type', strtolower($thing))
        ->set('thing', $thing)
        ->output(getcwd()."/src/$path");
    return $recipe;
};

