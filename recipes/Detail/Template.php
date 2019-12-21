<?php

namespace Codger\Lodger\Detail;

use Codger\Generate\Recipe;
use Twig\{ Environment, Loader\FilesystemLoader };

class Template extends Recipe
{
    protected $_template = 'detail.html.twig';

    public function __invoke(string $path, string $thing) : void
    {
        $this->setTwigEnvironment(new Environment(new FilesystemLoader(dirname(__DIR__, 2).'/templates')));
        $this
           ->set('type', strtolower($thing))
            ->set('thing', $thing)
            ->output(getcwd()."/src/$path");
    }
}

