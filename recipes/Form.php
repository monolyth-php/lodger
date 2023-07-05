<?php

namespace Codger\Lodger;

use Codger\Php\{ Klass, Composer };
use Codger\Generate\Language;
use Monolyth\Disclosure\{ Container, NotFoundException };
use Twig\{ Environment, Loader\FilesystemLoader };
use Monolyth\Lodger\AccessesDatabase;
use PDO;

/**
 * Generate a Formulaic form.
 */
class Form extends Klass
{
    use AccessesDatabase;

    public string $table;

    public bool $skipPrefill = false;

    public function __invoke(string $name) : void
    {
        $name = Language::convert($name, Language::TYPE_PHP_NAMESPACE);
        if (!isset($this->table)) {
            $this->table = Language::convert($name, Language::TYPE_TABLE);
            $this->ask("Which table should we use? [{$this->table}]", function (string $answer) : void {
                if (strlen($answer)) {
                    $this->table = $answer;
                }
            });
        }
        if (!$this->skipPrefill) {
            $pdo = $this->getPdoFromSuppliedCredentials();
        }
        $this->setNamespace($name);
        $this->setName('Form')
            ->output(Language::convert($name, Language::TYPE_PATH).'/Form.php');
        $composer = new Composer;
        if (!$this->skipPrefill) {
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
            $lines = [];
            $namespaces = ['Monolyth\Formulaic\Label'];
            $addType = function (string $type) : string {
                $this->usesNamespace[] = "Monolyth\\Formulaic\\$type";
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
            $this->addMethod('__construct', function ($method) use ($lines) {
                $method->setDoccomment(<<<EOT
Constructor.

@return void
EOT
                );
                $code = implode("\n", $lines);
                return $code;
            });
        }
        $this->usesNamespaces('Monolyth\Formulaic\Post', ...array_unique($this->usesNamespace));
    }
}

