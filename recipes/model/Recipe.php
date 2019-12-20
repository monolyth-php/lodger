<?php

use Codger\Php\Klass;
use Codger\Php\Composer;
use Codger\Generate\Language;
use Monolyth\Disclosure\Container;
use Monolyth\Disclosure\NotFoundException;

if (file_exists(getcwd().'/src/dependencies.php')) {
    require_once(getcwd().'/src/dependencies.php');
}

/**
 * Generate a model. Inspects the database to guesstimate properties. Options
 * can be `prefill` or `ornament`. Use a caret (^) preceding the option to
 * negate it. The default is to use both prefilling and ornament.
 */
return function (string $name = null, string $table = null, string $vendor = null, string $database = null, string $user = null, string $pass = null, string ...$options) : Klass {
    if (is_null($pass) && !is_null($user)) {
        $pass = $user;
        $user = $database;
    }
    $this->defaults('ornament', 'prefill');
    $twig = new Twig_Environment(new Twig_Loader_Filesystem(__DIR__));
    $class = new Klass($twig);
    if (!isset($name)) {
        $class->ask("What namespace shall we put this in?", function (string $answer) use (&$name) : void {
            $name = $answer;
        });
    }
    while (!isset($table)) {
        if (isset($name)) {
            $table = strtolower(str_replace('\\', '_', $name));
        } else {
            $class->ask("Which table should we use?", function (string $answer) use (&$table) : void {
                if (strlen($answer)) {
                    $table = $answer;
                }
            });
        }
    }
    if (!$this->hasOption('prefill')) {
        $class->options("Prefill properties from database?", ['y' => 'Yes', 'n' => 'No'], function (string $answer) use (&$options) : void {
            $options[] = $answer == 'y' ? 'prefill' : '^prefill';
        });
    }
    if ($this->askedFor('prefill')) {
        if (!isset($vendor)) {
            $class->options("What database vendor is used?", ['m' => 'MySQL', 'p' => 'PostgreSQL'], function (string $v) use (&$vendor) {
                $vendor = $v;
            });
            switch ($vendor) {
                case 'p':
                    $vendor = "pgsql";
                    break;
                case 'm':
                    $vendor = "mysql";
                    break;
            }
        }
        if (class_exists(Container::class)) {
            try {
                $container = new Container;
                $env = $container->get('env');
                $database = $env->db['name'];
                $user = $env->db['user'];
                $pass = $env->db['pass'];
            } catch (NotFoundException $e) {
            }
        }
        if (!isset($database)) {
            $class->ask('Name of the database?', function (string $n) use (&$database) : void {
                $database = $n;
            });
        }
        if (!isset($user)) {
            $class->ask('User?', function (string $u) use (&$user) : void {
                $user = $u;
            });
        }
        if (!isset($pass)) {
            $class->ask('Password?', function (string $p) use (&$pass) : void {
                $pass = $p;
            });
        }
        try {
            $pdo = new PDO("$vendor:dbname=$database", $user, $pass);
        } catch (PDOException $e) {
            fwrite(STDERR, "Fatal: permission denied to $database for user $user with password $pass.\n");
            exit(1);
        }
    }
    if (strlen($name)) {
        $class->setNamespace($name);
    }
    $class->setName('Model')
        ->output(getcwd().'/src/'.Language::convert($name, Language::TYPE_PATH).'/Model.php');
    if ($this->askedFor('ornament')) {
        $composer = new Composer;
        $class->usesNamespaces('Ornament\\Core')
            ->usesTraits('Core\\Model');
        $composer->addDependency('ornament/core');
    }
    if ($this->askedFor('prefill')) {
        switch ($vendor) {
            case 'pgsql':
                $stmt = $pdo->prepare(
                    "SELECT
                        column_name,
                        column_default,
                        is_nullable,
                        data_type column_type,
                        udt_name _type,
                        character_maximum_length
                    FROM INFORMATION_SCHEMA.COLUMNS
                        WHERE TABLE_CATALOG = ? AND TABLE_SCHEMA = 'public' AND TABLE_NAME = ?
                        ORDER BY ORDINAL_POSITION ASC");
                break;
        }
        $stmt->execute([$database, $table]);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            $type = null;
            switch ($column['column_type']) {
                case 'bigint': $type = 'int'; break;
                case 'text': case 'timestamp': case 'timestamp with time zone': $type = 'string'; break;
                case 'boolean': $type = 'bool'; break;
                case 'float': case 'double precision': $type = 'float'; break;
                case 'ARRAY': $type = 'array'; break;
            }
            $class->defineProperty($column['column_name'], null, 'public', isset($type) ? "@var $type" : null);
        }
    }
    return $class;
};

