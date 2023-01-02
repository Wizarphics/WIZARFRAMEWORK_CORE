<?php
/*
 * Copyright (c) 2022.
 * User: Fesdam
 * project: WizarFrameWork
 * Date Created: $file.created
 * 7/6/22, 7:06 AM
 * Last Modified at: 7/6/22, 7:06 AM
 * Time: 7:6
 * @author Wizarphics <Wizarphics@gmail.com>
 *
 */

namespace wizarphics\wizarframework\db;

use stdClass;
use wizarphics\wizarframework\Application;
use wizarphics\wizarframework\interfaces\ValidationInterface;
use wizarphics\wizarframework\Model;

abstract class DbModel extends Model
{
    protected Database $_db;
    public function __construct(?ValidationInterface $validator = null)
    {
        parent::__construct($validator);
        $this->_db = app()->db;
    }
    public function findOne($where)
    {
        $tableName = $this->tableName();
        $attributes = array_keys($where);
        $sql = implode(" AND ", array_map(fn ($attr) => "$attr = :$attr", $attributes));
        $SQL = "SELECT * FROM $tableName WHERE $sql";
        $statement = self::prepare("SELECT * FROM $tableName WHERE $sql");

        foreach ($where as $key => $item) {
            $statement->bindValue(":$key", $item);
        }
        $statement->execute();
        return $statement->fetchObject(static::class);
    }

    public function find(string|int $id): object|false
    {
        $tableName = $this->tableName();
        $result = $this->_db->where([$this->primaryKey() => $id])->get("*", [], $tableName, static::class);
        if ($result->count() > 0) {
            return $result->first();
        } else {
            return false;
        }
    }

    public function save(array|object|null $datas = null)
    {
        if ($datas != null) {
            $this->loadData($datas);
        }

        if (isset($this->{$this->primaryKey()})) {
            return $this->_doUpdate();
        }

        return $this->_doInsert();
    }

    /**
     * Update
     *
     * @return bool
     * 
     * Created at: 12/30/2022, 3:32:55 AM (Africa/Lagos)
     * @author     Wizarphics <wizarphics@gmail.com> 
     * @see       {@link https://wizarphics.com} 
     * @copyright Wizarphics 
     */
    protected function _doUpdate(): bool
    {
        $tableName = $this->tableName();
        $attributes = $this->attributes();
        $fieldsToUpdate = [];
        foreach ($attributes as $attribute) :

            $fieldsToUpdate[$attribute] = $this->{$attribute};

        endforeach;
        $result = $this->_db->where([
            $this->primaryKey() => $this->{$this->primaryKey()}
        ])->update($fieldsToUpdate, null, $tableName);

        return $result;
    }

    protected function _doInsert(): bool
    {

        $tableName = $this->tableName();
        $attributes = $this->attributes();
        $params = [];
        $params = array_map(fn ($attr) => ":$attr", $attributes);

        $SQL = "INSERT INTO $tableName (" . implode(',', $attributes) . ") VALUES(" . implode(',', $params) . ") ";

        $statement = self::prepare($SQL);
        foreach ($attributes as $attribute) {
            $statement->bindValue(":$attribute", $this->{$attribute});
        }

        return $statement->execute();
    }

    abstract public function tableName(): string;

    abstract public function attributes(): array;

    abstract public function primaryKey(): string;

    public static function prepare($sql)
    {
        return app()->db->prepare($sql);
    }
}
