<?php

namespace Codger\Lodger;

use Codger\Php\Klass;
use Codger\Php\Composer;
use Codger\Generate\Language;

/**
 * Default view generation recipe.
 */
class View extends Klass
{
    public string $template;

    /**
     * Pass the namespace to place this view in. The special namespace 'global'
     * resolves to... we'll, you've guessed it.
     *
     * @param string $namespace
     * @return void
     */
    public function __invoke(string $namespace) : void
    {
        $isGlobal = $namespace == 'global';
        if (!isset($this->extends) && !$isGlobal) {
            $this->extendsClass('\View');
        }
        if (!$isGlobal) {
            $namespace = Language::convert($namespace, Language::TYPE_PHP_NAMESPACE);
        }
        if (!isset($this->template)) {
            $this->template = Language::convert($namespace, Language::TYPE_PATH).'/template.html.twig';
        }
        if (!$isGlobal) {
            $this->output(Language::convert($namespace, Language::TYPE_PATH).'/View.php')
                ->defineProperty('template', function ($property) {
                    $property->setDefault($this->template)
                        ->setVisibility('protected')
                        ->setDoccomment('@var string');
                });
        } else {
            $this->output('View.php')
                ->defineProperty('template', function ($property) {
                    $property->setDefault('template.html.twig')
                        ->setVisibility('protected')
                        ->setDoccomment('@var string');
                })
                ->isAbstract();
        }
        if (!$isGlobal) {
            $this->setNamespace($namespace);
        }
        if (isset($this->extends)) {
            $this->extendsClass($this->extends);
        }
        $this->setName('View');
    }
}

