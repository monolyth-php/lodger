<?php

namespace Codger\Lodger;

use Codger\Php\{ Klass, Composer };
use Codger\Generate\Language;
use Monolyth\Lodger\AccessesDatabase;
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
    use AccessesDatabase;

    /** @var string */
    public $table;
    
    /** @var bool */
    public $ornament = false;

    /** @var bool */
    public $skipPrefill = false;

    public function __invoke(string $name) : void
    {
        $this->name = Language::convert($name, Language::TYPE_PHP_NAMESPACE);
        if (!$this->skipPrefill && !isset($this->table)) {
            $this->checkTable();
        }
        if (strlen($this->name)) {
            $this->setNamespace($this->name);
        }
        $this->setName('Model')
            ->output(Language::convert($name, Language::TYPE_PATH).'/Model.php');
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

    private function prefillProperties() : void
    {
        $pdo = $this->getPdoFromSuppliedCredentials();
        switch ($this->vendor) {
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

