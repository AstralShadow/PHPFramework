<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core;

/**
 * Base class for database models
 * You should define all types of all variables in your class;
 *
 * @author azcraft
 */
abstract class Entity
{

    protected static string $tableName;
    protected static string $idName = "id";
    private int $id;

    /**
     * Creates an object in the database
     * @throws Exception
     */
    public function __construct() {
        $dbh = self::getPDO();
        $entity = new \ReflectionClass($this);
        $tableName = $entity->getStaticPropertyValue("tableName");
        $properties = $this->getDefinedProperties();

        $data = self::resolveReferences($properties);
        $keys = array_keys($data);
        $prefixedKeys = array_map(function ($k){
            return ":$k";
        }, $keys);

        $statement_columns = implode(", ", $keys);
        $statement_values = implode(", ", $prefixedKeys);

        $statement = $dbh->prepare(<<<EOF
            INSERT INTO $tableName($statement_columns)
            VALUES ($statement_values);
        EOF);

        foreach ($keys as $key){
            $statement->bindParam($key, $data[$key]);
        }

        $statement->execute();

        $this->id = $dbh->lastInsertId();
    }

    /**
     * Sets properties of this object from given associative array
     * @param array $data
     * @return void
     */
    private function morph(array $data): void {
        $entity = new \ReflectionClass($this);
        $properties = $entity->getProperties();
        foreach ($properties as $property){
            $name = $property->getName();
            if ($property->isStatic() || !isset($data[$name])){
                continue;
            }
            $typeName = $property->getType()->getName();

            if ($typeName == 'DateTime'){
                $data[$name] = new \DateTime($data[$name]);
                $property->setValue($this, $data[$name]);
                continue;
            }

            if (class_exists($typeName)){
                $class = new \ReflectionClass($typeName);
                $parent = $class->getParentClass();
                if ($parent == new \ReflectionClass(get_class())){
                    $className = $class->getName();
                    $data[$name] = $className::get($data[$name]);
                }
            }
            $property->setValue($this, $data[$name]);
        }
    }

    /**
     * Returns the entity's id
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * Saves the object in the database
     * @throws Exception
     */
    public function save(): void {
        $dbh = self::getPDO();
        $entity = new \ReflectionClass($this);
        $tableName = $entity->getStaticPropertyValue("tableName");
        $idName = $entity->getStaticPropertyValue("idName");

        $properties = $this->getDefinedProperties();

        $data = self::resolveReferences($properties);
        $keys = array_keys($data);
        $prefixedKeys = array_map(function ($k){
            return ":$k";
        }, $keys);

        $set_clause = "";
        if (count($keys)){
            $conditions = array_map(function ($key){
                return "$key = :$key";
            }, $keys);
            $set_clause = implode(', ', $conditions);
        }

        $statement = $dbh->prepare(<<<EOF
            UPDATE $tableName
            SET $set_clause
            WHERE $idName = :id;
        EOF);

        $statement->bindParam(':id', $this->id);

        foreach ($keys as $key){
            $statement->bindParam($key, $data[$key]);
        }

        $statement->execute();

        $this->id = $dbh->lastInsertId();
    }

    /**
     * Returns an object by id
     * @param int $id
     * @return Entity
     */
    public static function get(int $id): Entity {
        $dbh = self::getPDO();
        $entity = new \ReflectionClass(get_called_class());
        $tableName = $entity->getStaticPropertyValue("tableName");
        $idName = $entity->getStaticPropertyValue("idName");

        $statement = $dbh->prepare(<<<EOF
            SELECT *
            FROM $tableName 
            WHERE $idName = :id;
        EOF);
        $statement->bindParam(":id", $id);
        $statement->execute();

        $data = $statement->fetch(\PDO::FETCH_ASSOC);
        $user = $entity->newInstanceWithoutConstructor();
        $user->id = $data[$idName];
        $user->morph($data);

        return $user;
    }

    /**
     * Returns an object
     * @param array $conditions
     * @return Entity[]
     */
    public static function find(array $conditions): array {
        $dbh = self::getPDO();
        $entity = new \ReflectionClass(get_called_class());
        $tableName = $entity->getStaticPropertyValue("tableName");
        $idName = $entity->getStaticPropertyValue("idName");

        $data = self::resolveReferences($conditions);
        $keys = array_keys($data);
        $conditionString = "";

        if (count($keys)){
            $conditions = array_map(function ($key){
                return "$key = :$key";
            }, $keys);
            $conditionString = "WHERE " . implode(' AND ', $conditions);
        }

        $statement = $dbh->prepare(<<<EOF
            SELECT $idName
            FROM $tableName 
            $conditionString;
        EOF);

        foreach ($keys as $key){
            $statement->bindParam($key, $data[$key]);
        }

        $statement->execute();

        $users = [];
        foreach ($statement as $user){
            $users[] = self::get($user[0]);
        }
        return $users;
    }

    public static function delete(int $id): void {
        $dbh = self::getPDO();
        $entity = new \ReflectionClass(get_called_class());
        $tableName = $entity->getStaticPropertyValue("tableName");
        $idName = $entity->getStaticPropertyValue("idName");

        $statement = $dbh->prepare(<<<EOF
            DELETE FROM $tableName
            WHERE $idName = :id;
        EOF);

        $statement->bindParam(':id', $id);
        $statement->execute();
    }

    /**
     * Returns defined properties as associative array
     * @return array
     */
    private function getDefinedProperties(): array {
        $entity = new \ReflectionObject($this);
        $properties = $entity->getProperties();
        $data = [];
        foreach ($properties as $property){
            if (!$property->isStatic()){
                $name = $property->getName();
                if (isset($this->$name)){
                    $data[$name] = $this->$name;
                }
            }
        }
        return $data;
    }

    /**
     * Replaces Entities with their id.
     * @param array $properties Output from getDefinedProperties.
     * @return array
     */
    private static function resolveReferences(array $properties): array {
        foreach ($properties as $key => $value){
            if ($value instanceof Entity){
                $properties[$key] = $value->id;
            }
            if ($value instanceof \DateTime){
                $properties[$key] = $value->format("Y-m-d H:i:s");
            }
        }

        return $properties;
    }

    /**
     * Returns the active PDO connection or throws exception
     * @return PDO
     */
    public static function getPDO(): \PDO {
        $dbh = Controller::getPDO();
        if ($dbh == null){
            throw new Exception("You may not use Entities without specifying database.");
        }
        return $dbh;
    }

}
