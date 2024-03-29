<?php

namespace Codger\Lodger\Listing;

use Codger\Generate\Recipe;
use Codger\Generate\Language;
use Twig\{ Environment, Loader\FilesystemLoader };

class Template extends Recipe
{
    protected string $_template = 'list.html.twig';

    public function __invoke(string $path, string $thing) : void
    {
        $this->setTwigEnvironment(new Environment(new FilesystemLoader(dirname(__DIR__, 2).'/templates')));
        $this
            ->set('type', strtolower($thing))
            ->set('thing', $thing)
            ->set('things', Language::pluralize($thing))
            ->output($path);
    }
}

