<?php
/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: $file.created
 * 7/5/22, 11:36 AM
 * Last Modified at: 7/5/22, 11:36 AM
 * Time: 11:36
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */

namespace wizarphics\wizarframework\db;

use PDO;
use wizarphics\wizarframework\Application;

class Database
{

    private static $_instance = null;
    public PDO $_pdo;
    private \PDOStatement|bool $_qeury;
    private bool $_error = false;
    private array $_results;
    private int $_count = 0;

    private static array $_where = array();

    private static array $_whereValues = array();

    /**
     * Class constructor.
     */
    private function __construct(array $config)
    {
        try {
            $dsn = $config['dsn'] ?? '';
            $user = $config['user'] ?? '';
            $password = $config['password'] ?? '';
            $this->_pdo = new PDO($dsn, $user, $password);
            $this->_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->_pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_CLASS);
            // echo 'Database connection established.';
        } catch (\PDOException $e) {
            Application::$app->handleExceptions($e);
        }
    }

    public static function &getInstance(array $config)
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new static($config);
        }

        self::resest();

        return self::$_instance;
    }

    public function runQuery($sql, $params = array(), string|false $fetchClass = false)
    {
        $this->_error = false;
        if ($this->_qeury = $this->_pdo->prepare($sql)) {
            if (count($params) > 0) {
                if (array_is_list($params)) {
                    $key = 1;
                    foreach ($params as $param) {
                        $this->_qeury->bindValue($key, $param);
                        $key++;
                    }
                } else {
                    foreach ($params as $key => $params) {
                        $this->_qeury->bindValue($key, $params);
                    }
                }
            }

            if ($this->_qeury->execute()) {
                if ($fetchClass != false) {
                    $this->_qeury->setFetchMode(PDO::FETCH_CLASS, $fetchClass);
                    $this->_results = $this->_qeury->fetchAll();
                } else {
                    $this->_results = $this->_qeury->fetchAll(PDO::FETCH_OBJ);
                }
                $this->_count = $this->_qeury->rowCount();
            } else {
                $this->_error = true;
            }
        }

        $this->resest();

        return $this;
    }

    public function where(array|string $key, $value = null)
    {
        if (is_string($key)) {
            $where = array($key => $value);
        } else {
            $where = $key;
        }

        $this->_where($where);
        return $this;
    }

    private function _action($action, $table, $where = [], $fetchClass = false)
    {
        if (count($where) > 0) {
            $this->_where($where);
        };


        $whereString = $this->_getWhereString();
        $values = static::$_whereValues ?: [];

        if (!is_null($whereString) && is_string($whereString)) {
            $sql = "{$action} FROM `{$table}` WHERE {$whereString};";
        } else {
            $sql = "{$action} FROM `{$table}`;";
        }
        if (!$this->runQuery($sql, $values, $fetchClass)->error()) {
            return $this;
        }

        return false;
    }


    private function _where(array $where)
    {
        static::$_where[] = $this->_buildWhere($where);
    }

    private function _getWhereString(): string|null
    {
        return count(static::$_where) > 0 ? join(' AND ', static::$_where) : null;
    }

    private function _buildWhere($where)
    {
        if (count($where) === 3) {
            $field = $where[0];
            $operator = $where[1];
            $value = ($where[2]);
            $pointer = ":$field";
            // print '<pre>';
            // var_dump(get_defined_vars());
            // print '</pre>';
            // exit;
        } else {
            $field = key($where);
            $value = (end($where));
            $operator = '=';
            $pointer = ":$field";
        }
        $operators = ['=', '<', '>', '<=', '>='];
        if (!in_array($operator, $operators)) {
            $operator = '=';
        }

        // var_dump($where, $field, key($where));
        // exit;

        static::$_whereValues[$field] = $value;

        return "{$field} {$operator} {$pointer}\r\n";
    }

    public function get($select = "*", $where = [], $table = "", $fetchClass = false)
    {
        return $this->_action("SELECT $select", $table, $where, $fetchClass);
    }

    public function delete(array $where, string $table)
    {
        return $this->_action("DELETE", $table, $where);
    }

    public function error()
    {
        return $this->_error;
    }

    public function count()
    {
        return $this->_count;
    }

    public function insert(array $fields, string $table): bool
    {
        if (count($fields)) {
            $keys = array_keys($fields);
            $values = '';
            $x = 1;
            $values = implode(', ', array_map(fn ($key) => ":$key", $keys));
            $sql = "INSERT INTO {$table} (`" . implode('`, `', $keys) . "`) VALUES ({$values});";

            // print $sql;

            if (!$this->runQuery($sql, $fields)->error()) {
                return true;
            }
        }
        return false;
    }

    public function update(array $fields, string|int|null $id, string $table)
    {
        if (count($fields)) {
            if ($id != null) {
                $this->_where(['id' => $id]);
            }

            $set = '';
            $x = 1;

            $where = $this->_getWhereString();

            $set = implode(', ', array_map(fn ($key) => "$key = :$key", array_keys($fields)));

            $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
            if (!$this->runQuery($sql, array_merge($fields, self::$_whereValues))->error()) {
                return true;
            }
        }
        return false;
    }

    public function results()
    {
        return $this->_results;
    }

    public function first()
    {
        return $this->results()[0];
    }

    protected static function resest()
    {
        static::$_where = array();
        static::$_whereValues = array();
    }

    public function applyMigrations()
    {
        $this->createMigrationsTable();
        $appliedMigrations = $this->getAppliedMigrations();

        $newMigrations = [];


        assert(defined('MIGRATION_PATH'), 'Constant MIGRATION_PATH is not defined');
        $files = scandir(MIGRATION_PATH);

        $toApplyMigrations = array_diff($files, $appliedMigrations);
        foreach ($toApplyMigrations as $migration) {
            if ($migration === '.' || $migration === '..' || $migration == 'index.html' || $migration == '.gitkeep' || $migration == '.htaccess') {
                continue;
            }

            require_once MIGRATION_PATH . $migration;

            $className = pathinfo($migration, PATHINFO_FILENAME);
            $instance = new $className();
            $this->log("Applying migration $migration");
            $instance->up();
            $this->log("Applied migration $migration");
            $newMigrations[] = $migration;
        }

        if (!empty($newMigrations)) {
            $this->saveMigrations($newMigrations);
        } else {
            $this->log("All migrations are applied");
        }
    }

    public function createMigrationsTable()
    {
        if ($this->tableExists('migrations') == false) {
            $sql = "CREATE TABLE migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )ENGINE=INNODB;
            ";
            $this->_pdo->exec($sql);
        }
    }

    public function getAppliedMigrations()
    {
        $sql = "SELECT migration FROM migrations";
        $statement = $this->_pdo->prepare($sql);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public function saveMigrations(array $migrations)
    {
        $str = implode(",", array_map(fn ($m) => "('$m')", $migrations));

        $sql = "INSERT INTO migrations (migration) VALUES $str";
        $statement = $this->_pdo->prepare($sql);
        $statement->execute();
    }

    public function prepare($sql)
    {
        return $this->_pdo->prepare($sql);
    }

    public function query($sql)
    {
        return $this->_pdo->query($sql);
    }

    public function tableExists($table)
    {
        $st = $this->prepare("SHOW TABLES");
        $st->execute();
        $tables = $st->fetchAll(PDO::FETCH_UNIQUE);
        return (array_key_exists($table, $tables));
    }

    protected function log($message)
    {
        echo '[' . date('Y-m-d H:i:s') . '] - ' . $message . PHP_EOL;
    }
}
