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
    /** @var string $table */
    public $table;

    /** @var string $vendor */
    public $vender;

    /** @var string $database */
    public $database;

    /** @var string $user */
    public $user;

    /** @var string $pass */
    public $pass;

    /** @var string bool */
    public $repository = false;

    /** @var string bool */
    public $model = false;

    /** @var string bool */
    public $listing = false;

    /** @var string bool */
    public $detail = false;

    /** @var string bool */
    public $crud = false;

    /** @var string bool */
    public $sass = false;

    /** @var string bool */
    public $form = false;

    public function __invoke(string $name)
    {
        $this->setTwigEnvironment(new Environment(new FilesystemLoader(__DIR__)));
        $namespaceName = Language::convert($name, Language::TYPE_PHP_NAMESPACE);
        if ($this->repository) {
            $this->delegate('Codger\Lodger\Repository', $namespaceName);
        }
        if ($this->model) {
            $this->delegate('Codger\Lodger\Model', $namespaceName, $this->table, $this->vendor, $this->database, $this->user, $this->pass);
        }
        if ($this->listing) {
            $this->delegate('Codger\Lodger\Listing\View', $namespaceName);
        }
        if ($this->detail) {
            $this->delegate('Codger\Lodger\Detail\View', $namespaceName);
        }
        if ($this->crud) {
            $this->delegate('Codger\Lodger\Controller', $namespaceName);
        }
        if ($this->sass) {
            $this->delegate('Codger\Lodger\Sass', $namespaceName);
        }
        if ($this->form) {
            $this->delegate('Codger\Lodger\Form', $namespaceName, $table, $vendor, $database, $user, $pass);
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

