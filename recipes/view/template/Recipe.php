<?php

use Codger\Generate\Recipe;
use Codger\Generate\Language;

return function (string $path) : Recipe {
    $recipe = new class(new Twig_Environment(new Twig_Loader_Filesystem(dirname(__DIR__, 3).'/templates'))) extends Recipe {
        protected $template = 'generic.html.twig';
    };
    $recipe->output(getcwd()."/src/$path");
    return $recipe;
};

