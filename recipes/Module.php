<?php

namespace Codger\Lodger;

use Codger\Php\Recipe;
use Codger\Generate\Language;
use Twig\{ Environment, Loader\FilesystemLoader };

/**
 * Generate a Monolyth module as commonly implemented, based on the $name passed
 * Names can be passed as a path (`foo/bar`) or a namespace (`Foo\\Bar`).
 */
class Module extends Recipe
{
    /** @var string */
    public $table;

    /** @var string */
    public $vendor;

    /** @var string */
    public $database;

    /** @var string */
    public $user;

    /** @var string */
    public $pass;

    /** @var bool */
    public $repository = false;

    /** @var bool */
    public $model = false;

    /** @var bool */
    public $listing = false;

    /** @var bool */
    public $detail = false;

    /** @var bool */
    public $crud = false;

    /** @var bool */
    public $sass = false;

    /** @var bool */
    public $form = false;

    /** @var bool */
    public $skipPrefill = false;

    /** @var bool */
    public $ornament = false;

    public function __invoke(string $name)
    {
        $this->setTwigEnvironment(new Environment(new FilesystemLoader(__DIR__)));
        $namespaceName = Language::convert($name, Language::TYPE_PHP_NAMESPACE);
        if ($this->repository) {
            $this->delegate('Codger\Lodger\Repository', [$namespaceName]);
        }
        if ($this->model) {
            $arguments = [$namespaceName];
            foreach (['table', 'vendor', 'database', 'user', 'pass'] as $argument) {
                if (isset($this->$argument)) {
                    $arguments[] = "--$argument={$this->$argument}";
                }
            }
            foreach (['skipPrefill', 'ornament'] as $argument) {
                if ($this->$argument) {
                    $arguments[] = "--$argument";
                }
            }
            $this->delegate('Codger\Lodger\Model', $arguments);
        }
        if ($this->listing) {
            $this->delegate('Codger\Lodger\Listing\View', [$namespaceName]);
            $this->delegate('Codger\Lodger\Listing\Template', [Language::convert($name, Language::TYPE_PATH), Language::convert($name, Language::TYPE_VARIABLE)]);
        }
        if ($this->detail) {
            $this->delegate('Codger\Lodger\Detail\View', [$namespaceName]);
            $this->delegate('Codger\Lodger\Detail\Template', [Language::convert($name, Language::TYPE_PATH), Language::convert($name, Language::TYPE_VARIABLE)]);
        }
        if ($this->crud) {
            $this->delegate('Codger\Lodger\Controller', [$namespaceName]);
        }
        if ($this->sass) {
            $this->delegate('Codger\Lodger\Sass', [$namespaceName]);
        }
        if ($this->form) {
            $arguments = [$namespaceName];
            foreach (['table', 'vendor', 'database', 'user', 'pass'] as $argument) {
                if (isset($this->$argument)) {
                    $arguments[] = "--$argument={$this->$argument}";
                }
            }
            $this->delegate('Codger\Lodger\Form', $arguments);
        }
        $route = Language::convert($name, Language::TYPE_URL);
        $this->info(<<<EOT
    Add a route for this module to access it over HTTP, e.g.:

    \$router->when('/$route/', null, function () {
        \$router->when('/add/', '$name-add')
            ->get({$namespaceName}\\Add\\View::class)
            ->post(function (callable \$GET) use (\$router) {
                \$controller = new {$namespaceName}\\Controller;
                \$model = \$controller->create();
                if (\$model) {
                    return new RedirectResponse(\$router->generate('$name-detail', ['id' => \$model->id]));
                } else {
                    return \$GET;
                }
            });
        \$router->when("/(?'id'\d+)/", '$name-detail')
            ->get(function (int \$id) {
                return new {$namespaceName}\\Detail\\View(\$id);
            })
            ->post(function (int \$id, callable \$GET) use (\$router) {
                \$controller = new {$namespaceName}\\Controller;
                if (!(\$error = \$controller->update(\$id))) {
                    return new RedirectResponse(\$router->generate('$name-list'));
                } else {
                    return \$GET;
                }
            })
            ->delete(function (int \$id, callable \$GET) use (\$router) {
                \$controller = new {$namespaceName}\\Controller;
                if (!(\$error = \$controller->delete(\$id))) {
                    return new RedirectResponse(\$router->generate('$name-list'));
                } else {
                    return \$GET;
                }
            });
        \$router->when('/', '$name-list')
            ->get({$namespaceName}\\View::class);
    });
EOT
        );
    }
}

