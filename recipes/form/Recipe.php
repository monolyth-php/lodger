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
 * Generate a Formulaic form as commonly implemented by Sensi.
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
    $class->setName('Form')
        ->output(getcwd().'/src/'.Language::convert($name, Language::TYPE_PATH).'/Form.php');
    $composer = new Composer;
    $class->usesNamespaces('Monolyth\\Formulaic\\Post');
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
    $lines = [];
    $namespaces = ['Monolyth\Formulaic\Label'];
    $addType = function (string $type) use (&$namespaces) : string {
        $namespaces[] = "Monolyth\\Formulaic\\$type";
        return $type;
    };
    foreach ($columns as $column) {
        if ($column['column_name'] == 'id') {
            // ID should never be included in a form
            continue;
        }
        $type = 'Text';
        $additional = '';
        $modifiers = '';
        switch ($column['column_type']) {
            case 'integer': case 'bigint': case 'int': case 'smallint': case 'float': case 'double precision':
                $type = $addType('Number');
                break;
            case 'boolean':
                $type = $addType('Checkbox');
                break;
            case 'character': case 'character varying': 
                $type = $addType('Text');
                break;
            case 'timestamp': case 'timestamp with time zone': case 'datetime':
                $type = $addType('Datetime');
                break;
            case 'date':
                $type = $addType('Date');
                break;
            default: $type = $addType('Textarea');
        }
        $hr = ucfirst($column['column_name']);
        if ($column['is_nullable'] == 'NO') {
            $modifiers .= '->isRequired()';
        }
        $lines[] = <<<EOT
\$this[] = new Label(_('$hr'), (new $type('{$column['column_name']}'$additional))$modifiers);
EOT;
    }
    $class->addMethod('__construct', function ($method) use ($lines) {
        $method->setDoccomment(<<<EOT
Constructor.

@return void
EOT
        );
        $code = implode("\n", $lines);
        return $code;
    });
    $class->usesNamespaces(...array_unique($namespaces));
    return $class;
};

