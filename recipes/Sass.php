<?php

namespace Codger\Lodger;

use Codger\Generate\{ Recipe, Language };
use Twig\{ Environment, Loader\FilesystemLoader };

class Sass extends Recipe
{
    protected $_template = 'sass.html.twig';

    public function __invoke(string $namespace) : void
    {
        $this->setTwigEnvironment(new Environment(new FilesystemLoader(dirname(__DIR__).'/templates')));
        $this->set('id', Language::convert($namespace, Language::TYPE_CSS_IDENTIFIER))
            ->output(Language::convert($namespace, Language::TYPE_PATH).'/_style.scss');
    }
};

