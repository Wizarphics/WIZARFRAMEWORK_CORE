<?php


namespace wizarphics\wizarframework;

use InvalidArgumentException;
use RuntimeException;
use wizarphics\wizarframework\db\Database;

class Schema
{

    /**
     * CREATE TABLE keys flag
     *
     * Whether table keys are created from within the
     * CREATE TABLE statement.
     *
     * @var bool
     */
    protected $createTableKeys = false;
    protected string $createTableStr;
    public Database $db;

    /**
     * List of fields.
     *
     * @var array
     */
    protected $fields = [];

    /**
     * List of keys.
     *
     * @var array
     */
    protected $keys = [];

    /**
     * DEFAULT value representation in CREATE/ALTER TABLE statements
     *
     * @var false|string
     */
    protected $default = ' DEFAULT ';

    /**
     * List of unique keys.
     *
     * @var array
     */
    protected $uniqueKeys = [];

    /**
     * List of primary keys.
     *
     * @var array
     */
    protected $primaryKeys = [];

    /**
     * List of foreign keys.
     *
     * @var array
     */
    protected $foreignKeys = [];

    public function __construct()
    {
        $this->db = Application::$app->db;
        $this->createTableStr =  "%s %s (%s\n)";
    }

    public function addField($field): self
    {
        if (is_string($field)) {
            if ($field === 'id') {
                $this->addField([
                    'id' => [
                        'type'           => 'INT',
                        'constraint'     => 9,
                        'auto_increment' => true,
                    ],
                ]);
                $this->addKey('id', true);
            } else {
                if (strpos($field, ' ') === false) {
                    throw new InvalidArgumentException('Field information is required for that operation.');
                }

                $fieldName = explode(' ', $field, 2)[0];
                $fieldName = trim($fieldName, '`\'"');

                $this->fields[$fieldName] = $field;
            }
        }

        if (is_array($field)) {
            foreach ($field as $idx => $f) {
                if (is_string($f)) {
                    $this->addField($f);
                    continue;
                }

                if (is_array($f)) {
                    $this->fields = array_merge($this->fields, [$idx => $f]);
                }
            }
        }

        return $this;
    }

    public function addKey(array|string $key, bool $primary = false, bool $unique = false): self
    {
        if ($primary) {
            foreach ((array) $key as $one) {
                $this->primaryKeys[] = $one;
            }
        } else {
            $this->keys[] = $key;

            if ($unique) {
                $this->uniqueKeys[] = count($this->keys) - 1;
            }
        }

        return $this;
    }


    public function addPrimaryKey($key)
    {
        return $this->addKey($key, true);
    }

    public function addUniqueKey($key)
    {
        return $this->addKey($key, false, true);
    }


    public function addForeignKey($fieldName = '', string $tableName = '', $tableField = '', string $onUpdate = '', string $onDelete = '')
    {
        $fieldName  = (array) $fieldName;
        $tableField = (array) $tableField;
        $errorNames = [];

        foreach ($fieldName as $name) {
            if (!isset($this->fields[$name])) {
                $errorNames[] = $name;
            }
        }

        if ($errorNames !== []) {
            $errorNames[0] = implode(', ', $errorNames);

            // throw new DatabaseException(lang('Database.fieldNotExists', $errorNames));
        }

        $this->foreignKeys[] = [
            'field'          => $fieldName,
            'referenceTable' => $tableName,
            'referenceField' => $tableField,
            'onDelete'       => strtoupper($onDelete),
            'onUpdate'       => strtoupper($onUpdate),
        ];

        return $this;
    }

    public function createTable(string $table, bool $ifNotExists = false, array $attributes = [])
    {
        if ($table === '') {
            throw new InvalidArgumentException('A table name is required for that operation.');
        }

        // TODO: $table = $this->db->DBPrefix . $table;

        if ($this->fields === []) {
            throw new RuntimeException('Field information is required.');
        }

        // If table exists lets stop here
        if ($ifNotExists === true && $this->db->tableExists($table)) {
            $this->reset();

            return true;
        }

        $sql = $this->_createTable($table, $attributes);

        // dd($sql);
        

        if (($result = $this->db->query($sql)) !== false) {

            // Most databases don't support creating indexes from within the CREATE TABLE statement
            if (!empty($this->keys)) {
                for ($i = 0, $sqls = $this->_processIndexes($table), $c = count($sqls); $i < $c; $i++) {
                    $this->db->query($sqls[$i]);
                }
            }
        }

        $this->reset();

        return $result;
    }

    protected function _createTable(string $table, array $attributes)
    {
        $columns = $this->_processFields(true);

        for ($i = 0, $c = count($columns); $i < $c; $i++) {
            $columns[$i] = ($columns[$i]['_literal'] !== false) ? "\n\t" . $columns[$i]['_literal']
                : "\n\t" . $this->_processColumn($columns[$i]);
        }

        $columns = implode(',', $columns);

        $columns .= $this->_processPrimaryKeys($table);
        $columns .= $this->_processForeignKeys($table);

        if ($this->createTableKeys === true) {
            $indexes = $this->_processIndexes($table);
            if (is_string($indexes)) {
                $columns .= $indexes;
            }
        }

        return sprintf(
            $this->createTableStr . '%s',
            'CREATE TABLE',
            $table,
            $columns,
            $this->_createTableAttributes($attributes)
        );
    }

    protected function _createTableAttributes(array $attributes): string
    {
        $sql = '';

        foreach (array_keys($attributes) as $key) {
            if (is_string($key)) {
                $sql .= ' ' . strtoupper($key) . ' ' .$attributes[$key];
            }
        }

        return $sql;
    }

    /**
     * Process fields
     */
    protected function _processFields(bool $createTable = false): array
    {
        $fields = [];

        foreach ($this->fields as $key => $attributes) {
            if (!is_array($attributes)) {
                $fields[] = ['_literal' => $attributes];

                continue;
            }

            $attributes = array_change_key_case($attributes, CASE_UPPER);

            if ($createTable === true && empty($attributes['TYPE'])) {
                continue;
            }

            if (isset($attributes['TYPE'])) {
                $this->_attributeType($attributes);
            }

            $field = [
                'name'           => $key,
                'new_name'       => $attributes['NAME'] ?? null,
                'type'           => $attributes['TYPE'] ?? null,
                'length'         => '',
                'unsigned'       => '',
                'null'           => '',
                'unique'         => '',
                'default'        => '',
                'auto_increment' => '',
                '_literal'       => false,
            ];

            if (isset($attributes['TYPE'])) {
                $this->_attributeUnsigned($attributes, $field);
            }

            if ($createTable === false) {
                if (isset($attributes['AFTER'])) {
                    $field['after'] = $attributes['AFTER'];
                } elseif (isset($attributes['FIRST'])) {
                    $field['first'] = (bool) $attributes['FIRST'];
                }
            }

            $this->_attributeDefault($attributes, $field);

            if (isset($attributes['NULL'])) {
                if ($attributes['NULL'] === true) {
                    $field['null'] = empty($this->null) ? '' : ' ' . $this->null;
                } else {
                    $field['null'] = ' NOT NULL';
                }
            } elseif ($createTable === true) {
                $field['null'] = ' NOT NULL';
            }

            $this->_attributeAutoIncrement($attributes, $field);
            $this->_attributeUnique($attributes, $field);

            if (isset($attributes['COMMENT'])) {
                $field['comment'] = $this->db->escape($attributes['COMMENT']);
            }

            if (isset($attributes['TYPE']) && !empty($attributes['CONSTRAINT'])) {
                if (is_array($attributes['CONSTRAINT'])) {
                    $attributes['CONSTRAINT'] = $this->db->escape($attributes['CONSTRAINT']);
                    $attributes['CONSTRAINT'] = implode(',', $attributes['CONSTRAINT']);
                }

                $field['length'] = '(' . $attributes['CONSTRAINT'] . ')';
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Process column
     */
    protected function _processColumn(array $field): string
    {
        return $field['name']
            . ' ' . $field['type'] . $field['length']
            . $field['unsigned']
            . $field['default']
            . $field['null']
            . $field['auto_increment']
            . $field['unique'];
    }

    /**
     * Performs a data type mapping between different databases.
     */
    protected function _attributeType(array &$attributes)
    {
        // Reset field lengths for data types that don't support it
        if (isset($attributes['CONSTRAINT']) && stripos($attributes['TYPE'], 'int') !== false) {
            $attributes['CONSTRAINT'] = null;
        }

        switch (strtoupper($attributes['TYPE'])) {
            case 'MEDIUMINT':
                $attributes['TYPE'] = 'INTEGER';
                $attributes['UNSIGNED'] = false;
                break;

            case 'INTEGER':
                $attributes['TYPE'] = 'INT';
                break;

            case 'ENUM':
                $attributes['TYPE'] = 'TEXT';
                $attributes['CONSTRAINT'] = null;
                break;

            case 'TIMESTAMP':
                $attributes['TYPE'] = 'DATETIME';
                break;

            case 'BOOLEAN':
                $attributes['TYPE'] = 'BIT';
                break;

            default:
                break;
        }
    }

    /**
     * Depending on the unsigned property value:
     *
     *    - TRUE will always set $field['unsigned'] to 'UNSIGNED'
     *    - FALSE will always set $field['unsigned'] to ''
     *    - array(TYPE) will set $field['unsigned'] to 'UNSIGNED',
     *        if $attributes['TYPE'] is found in the array
     *    - array(TYPE => UTYPE) will change $field['type'],
     *        from TYPE to UTYPE in case of a match
     */
    protected function _attributeUnsigned(array &$attributes, array &$field)
    {
        if (empty($attributes['UNSIGNED']) || $attributes['UNSIGNED'] !== true) {
            return;
        }

        // Reset the attribute in order to avoid issues if we do type conversion
        $attributes['UNSIGNED'] = false;

        if (is_array($this->unsigned)) {
            foreach (array_keys($this->unsigned) as $key) {
                if (is_int($key) && strcasecmp($attributes['TYPE'], $this->unsigned[$key]) === 0) {
                    $field['unsigned'] = ' UNSIGNED';

                    return;
                }

                if (is_string($key) && strcasecmp($attributes['TYPE'], $key) === 0) {
                    $field['type'] = $key;

                    return;
                }
            }

            return;
        }

        $field['unsigned'] = ($this->unsigned === true) ? ' UNSIGNED' : '';
    }

    protected function _attributeDefault(array &$attributes, array &$field)
    {
        if ($this->default === false) {
            return;
        }

        if (array_key_exists('DEFAULT', $attributes)) {
            if ($attributes['DEFAULT'] === null) {
                $field['default'] = empty($this->null) ? '' : $this->default . $this->null;

                // Override the NULL attribute if that's our default
                $attributes['NULL'] = true;
                $field['null']      = empty($this->null) ? '' : ' ' . $this->null;
            } elseif ($attributes['DEFAULT'] instanceof RawSql) {
                $field['default'] = $this->default . $attributes['DEFAULT'];
            } else {
                $field['default'] = $this->default . $this->db->escape($attributes['DEFAULT']);
            }
        }
    }

    protected function _attributeUnique(array &$attributes, array &$field)
    {
        if (! empty($attributes['UNIQUE']) && $attributes['UNIQUE'] === true) {
            $field['unique'] = ' UNIQUE';
        }
    }

    protected function _attributeAutoIncrement(array &$attributes, array &$field)
    {
        if (! empty($attributes['AUTO_INCREMENT']) && $attributes['AUTO_INCREMENT'] === true
            && stripos($field['type'], 'int') !== false
        ) {
            $field['auto_increment'] = ' AUTO_INCREMENT';
        }
    }

    protected function _processPrimaryKeys(string $table): string
    {
        $sql = '';

        for ($i = 0, $c = count($this->primaryKeys); $i < $c; $i++) {
            if (! isset($this->fields[$this->primaryKeys[$i]])) {
                unset($this->primaryKeys[$i]);
            }
        }

        if ($this->primaryKeys !== []) {
            $sql .= ",\n\tCONSTRAINT " . ('pk_' . $table)
                    . ' PRIMARY KEY(' . implode(', ', ($this->primaryKeys)) . ')';
        }

        return $sql;
    }

    protected function _processIndexes(string $table)
    {
        $sqls = [];

        for ($i = 0, $c = count($this->keys); $i < $c; $i++) {
            $this->keys[$i] = (array) $this->keys[$i];

            for ($i2 = 0, $c2 = count($this->keys[$i]); $i2 < $c2; $i2++) {
                if (! isset($this->fields[$this->keys[$i][$i2]])) {
                    unset($this->keys[$i][$i2]);
                }
            }

            if (count($this->keys[$i]) <= 0) {
                continue;
            }

            if (in_array($i, $this->uniqueKeys, true)) {
                $sqls[] = 'ALTER TABLE ' . ($table)
                    . ' ADD CONSTRAINT ' . ($table . '_' . implode('_', $this->keys[$i]))
                    . ' UNIQUE (' . implode(', ', ($this->keys[$i])) . ')';

                continue;
            }

            $sqls[] = 'CREATE INDEX ' . ($table . '_' . implode('_', $this->keys[$i]))
                . ' ON ' . ($table)
                . ' (' . implode(', ', ($this->keys[$i])) . ')';
        }

        return $sqls;
    }

    protected function _processForeignKeys(string $table): string
    {
        $sql = '';

        $allowActions = [
            'CASCADE',
            'SET NULL',
            'NO ACTION',
            'RESTRICT',
            'SET DEFAULT',
        ];

        foreach ($this->foreignKeys as $fkey) {
            $nameIndex            = $table . '_' . implode('_', $fkey['field']) . '_foreign';
            $nameIndexFilled      =($nameIndex);
            $foreignKeyFilled     = implode(', ',($fkey['field']));
            $referenceTableFilled =($fkey['referenceTable']);
            $referenceFieldFilled = implode(', ',($fkey['referenceField']));

            $formatSql = ",\n\tCONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s(%s)";
            $sql .= sprintf($formatSql, $nameIndexFilled, $foreignKeyFilled, $referenceTableFilled, $referenceFieldFilled);

            if ($fkey['onDelete'] !== false && in_array($fkey['onDelete'], $allowActions, true)) {
                $sql .= ' ON DELETE ' . $fkey['onDelete'];
            }

            if ($fkey['onUpdate'] !== false && in_array($fkey['onUpdate'], $allowActions, true)) {
                $sql .= ' ON UPDATE ' . $fkey['onUpdate'];
            }
        }

        return $sql;
    }

    /**
     * Resets table creation vars
     */
    public function reset()
    {
        $this->fields = $this->keys = $this->uniqueKeys = $this->primaryKeys = $this->foreignKeys = [];
    }
}
