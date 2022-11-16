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

namespace app\core\db;
use app\core\Application;
use app\core\Model;

abstract class DbModel extends Model
{
    public static function findOne($where)
    {
        $tableName = (new static)->tableName();
        $attributes = array_keys($where);
        $sql = implode(" AND ", array_map(fn($attr) => "$attr = :$attr", $attributes));
        $SQL = "SELECT * FROM $tableName WHERE $sql";
        $statement = self::prepare("SELECT * FROM $tableName WHERE $sql");

        foreach ($where as $key => $item) {
            $statement->bindValue(":$key", $item);
        }
        $statement->execute();
        return $statement->fetchObject(static::class);
    }

    public function save()
    {
        $tableName = $this->tableName();
        $attributes = $this->attributes();
        $params = [];
        $params = array_map(fn($attr) => ":$attr", $attributes);

        $SQL = "INSERT INTO $tableName (" . implode(',', $attributes) . ") VALUES(" . implode(',', $params) . ") ";

        $statement = self::prepare($SQL);
        foreach ($attributes as $attribute) {
            $statement->bindValue(":$attribute", $this->{$attribute});
        }

        $statement->execute();
        return true;
    }

    abstract public function tableName(): string;

    abstract public function attributes(): array;

    abstract public function primaryKey():string;

    public static function prepare($sql)
    {
        return Application::$app->db->pdo->prepare($sql);
    }
}