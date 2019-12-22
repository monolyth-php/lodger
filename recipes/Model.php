<?php

namespace Codger\Lodger;

use Codger\Php\Klass;
use Codger\Php\Composer;
use Codger\Generate\Language;
use Monolyth\Disclosure\Container;
use Monolyth\Disclosure\NotFoundException;
use PDO;

if (file_exists(getcwd().'/src/dependencies.php')) {
    require_once(getcwd().'/src/dependencies.php');
}

/**
 * Generate a model. Inspects the database to guesstimate properties. Database
 * credentials are supplied via the various options, but are optional if the
 * `skip-prefill` option is specified. Supply the `ornament` option to also
 * include the Ornament ORM decorator (optional, but super-handy).
 */
class Model extends Klass
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
    public $ornament = false;

    /** @var bool */
    public $skipPrefill = false;

    /** @var PDO */
    private $pdo;

    public function __invoke(string $name) : void
    {
        $this->name = Language::convert($name, Language::TYPE_PHP_NAMESPACE);
        if (!isset($this->table)) {
            $this->checkTable();
        }
        if (!$this->skipPrefill && !isset($this->vendor)) {
            $this->getVendor();
        }
        if (!$this->skipPrefill) {
            if (class_exists(Container::class)) {
                if (!$this->attemptCredentialsFromEnvy()) {
                    $this->checkDatabase();
                }
            } else {
                $this->checkDatabase();
            }
        }
        if (strlen($this->name)) {
            $this->setNamespace($this->name);
        }
        $this->setName('Model')
            ->output(getcwd().'/src/'.Language::convert($name, Language::TYPE_PATH).'/Model.php');
        if ($this->ornament) {
            $composer = new Composer;
            $this->usesNamespaces('Ornament\Core')
                ->usesTraits('Core\Model');
            $composer->addDependency('ornament/core');
        }
        if (!$this->skipPrefill) {
            $this->prefillProperties();
        }
    }

    private function checkTable() : void
    {
        $this->table = strtolower(str_replace('\\', '_', $this->name));
        $this->ask("Which table should we use? [{$this->table}]", function (string $answer) : void {
            if (strlen($answer)) {
                $this->table = $answer;
            }
        });
    }

    private function getVendor() : void
    {
        $this->options("What database vendor is used?", ['m' => 'MySQL', 'p' => 'PostgreSQL'], function (string $vendor) {
            switch ($vendor) {
                case 'p':
                    $this->vendor = "pgsql";
                    break;
                case 'm':
                    $this->vendor = "mysql";
                    break;
            }
        });
    }

    private function attemptCredentialsFromEnvy() : bool
    {
        try {
            $container = new Container;
            $env = $container->get('env');
            $this->database = $this->database ?? $env->db['name'];
            $this->user = $this->user ?? $env->db['user'];
            $this->pass = $this->pass ?? $env->db['pass'];
            return true;
        } catch (NotFoundException $e) {
            return false;
        } catch (ErrorException $e) {
            return false;
        }
    }

    private function checkDatabase() : void
    {
        if (!isset($this->database)) {
            $this->ask('Name of the database?', function (string $database) : void {
                $this->database = $database;
            });
        }
        if (!isset($this->user)) {
            $this->ask('User?', function (string $user) : void {
                $this->user = $user;
            });
        }
        if (!isset($this->pass)) {
            $this->ask('Password?', function (string $pass) : void {
                $this->pass = $pass;
            });
        }
        try {
            $this->pdo = new PDO("{$this->vendor}:dbname={$this->database}", $this->user, $this->pass);
        } catch (PDOException $e) {
            $this->error("Fatal: permission denied to {$this->database} for user {$this->user} with password {$this->pass}.\n");
            exit(6);
        }
    }

    private function prefillProperties() : void
    {
        switch ($this->vendor) {
            case 'pgsql':
                $stmt = $this->pdo->prepare(
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
        $stmt->execute([$this->database, $this->table]);
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $column) {
            $type = null;
            switch ($column['column_type']) {
                case 'bigint': case 'integer': $type = 'int'; break;
                case 'text': case 'timestamp': case 'timestamp with time zone': $type = 'string'; break;
                case 'boolean': $type = 'bool'; break;
                case 'float': case 'double precision': $type = 'float'; break;
                case 'ARRAY': $type = 'array'; break;
            }
            $this->defineProperty($column['column_name'], function ($property) use ($type) {
                if (isset($type)) {
                    $property->setDoccomment("@var $type");
                }
            });
        }
    }
}

