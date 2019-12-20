<?php

use Codger\Generate\Recipe;
use Codger\Generate\Language;

return function (string $project, string ...$modules) : Recipe {
    $twig = new Twig_Environment(new Twig_Loader_Filesystem(dirname(__DIR__, 2).'/templates'));
    $twig->addFilter(new Twig_SimpleFilter('normalize', function (string $module) : string {
        return strtolower(str_replace('\\', '-', $module));
    }));
    $recipe = new class($twig) extends Recipe {
        protected $template = 'base.html.twig';
    };
    $recipe->output(getcwd()."/src/template.html.twig");
    $recipe->set('project', $project);
    $recipe->set('modules', $modules);
    return $recipe;
};

