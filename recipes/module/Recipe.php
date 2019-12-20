<?php

use Codger\Generate\Recipe;
use Codger\Generate\Language;

/**
 * Generate a Monolyth module as commonly implemented by Sensi, based on the
 * $name passed in. Names can be passed as a path (`foo/bar`) or a namespace
 * (`Foo\\Bar`).
 */
return function (string $name, string $table = null, string $vendor = null, string $database = null, string $user = null, string $pass = null, ...$options) : Recipe {
    $twig = new Twig_Environment(new Twig_Loader_Filesystem(__DIR__));
    $module = new class($twig) extends Recipe {
        public function wrap(string $question, string $task, ...$options)
        {
            $this->options($question, ['Y' => 'Yes', 'n' => 'No'], function (string $answer) use ($task, $options) {
                if ($answer == 'Y') {
                    $this->delegate($task, ...$options);
                }
            });
        }
    };
    $namespaceName = Language::convert($name, Language::TYPE_PHP_NAMESPACE);
    $module->wrap("Add a repository?", 'sensi/codger-repository@repository', $namespaceName);
    $module->wrap("Add a model?", 'sensi/codger-model@model', $namespaceName, $table, $vendor, $database, $user, $pass, ...$options);
    $module->wrap("Add a list view?", 'sensi/codger-improse-view@list', $namespaceName);
    $module->wrap("Add a detail view?", 'sensi/codger-improse-view@detail', $namespaceName);
    $module->wrap("Add a CRUD controller?", 'sensi/codger-monolyth-module@controller', $namespaceName);
    $module->wrap("Add SASS?", 'sensi/codger-monolyth-module@sass', $namespaceName);
    $module->wrap("Add a form?", 'sensi/codger-form@form', $namespaceName, $table, $vendor, $database, $user, $pass);
    $route = Language::convert($name, Language::TYPE_URL);
    $module->info(<<<EOT
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
    return $module;
};

