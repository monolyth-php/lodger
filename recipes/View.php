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
    /** @var string */
    public $extends;

    /** @var string */
    public $template;

    /** @var array */
    public $uses = [];

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
        if (!isset($this->extends)) {
            $this->extends = '\View';
        }
        if (!$isGlobal) {
            $namespace = Language::convert($namespace, Language::TYPE_PHP_NAMESPACE);
        }
        if (!isset($this->template)) {
            $this->template = Language::convert($namespace, Language::TYPE_PATH).'/template.html.twig';
        }
        if (!$isGlobal) {
            $this->output(getcwd().'/src/'.Language::convert($namespace, Language::TYPE_PATH).'/View.php');
            $this->defineProperty('template', $this->template, 'protected', '@var string');
        } else {
            $this->output(getcwd().'/src/View.php')
                ->isAbstract();
        }
        if (!$isGlobal) {
            $this->setNamespace($namespace);
        }
        if ($this->extends) {
            $this->extendsClass($extends);
        }
        $this->setName('View');
        if ($this->uses) {
            $this->usesNamespaces(...$this->uses);
        }
    }
}

