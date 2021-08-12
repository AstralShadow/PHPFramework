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
    private static array $referencedLists = [];
    private static array $cache = [];
    private static array $initialized = [];
    private int $id;
    private static int $cacheUsed = 0;
    private static int $entitiesLoaded = 0;

    /**
     * Populates $referencedFrom
     * Includes all referenced classes (autoloader)
     * @return void
     */
    public static function init(): void {
        $class = get_called_class();
        if (in_array($class, self::$initialized)){
            return;
        }
        self::$initialized[] = $class;
        self::$cache[$class] = [];
        self::$referencedLists[$class] = [];

        $entity = new \ReflectionClass($class);
        $isEntityFilter = function (\ReflectionProperty $property){
            if ($property->isStatic()){
                return false;
            }
            $type = $property->getType();
            if (!isset($type) || $type->isBuiltin()){
                return false;
            }

            return isEntity($type->getName());
        };
        $flags = \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PUBLIC;
        $properties = array_filter($entity->getProperties($flags), $isEntityFilter);

        foreach ($properties as $property){
            $typeName = $property->getType()->getName();
            if (!isset(self::$referencedLists[$typeName])){
                self::$referencedLists[$typeName] = [];
            }
            self::$referencedLists[$typeName][] = $property;
        }
    }

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

        $class = get_called_class();
        self::$cache[$class][(int) $this->getId()] = &$this;
    }

    /**
     * Sets properties of this object from given associative array
     * @param array $data
     * @return void
     */
    private function morph(array $data): void {
        self::$entitiesLoaded++;
        $entity = new \ReflectionClass($this);
        $flags = \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PUBLIC;
        $properties = $entity->getProperties($flags);
        foreach ($properties as $property){
            $name = $property->getName();
            if ($property->isStatic() || !isset($data[$name])){
                continue;
            }
            $type = $property->getType();
            $typeName = $type->getName();

            if (!$type->isBuiltin()){
                if ($typeName == 'DateTime'){
                    $data[$name] = new \DateTime($data[$name]);
                    $this->$name = $data[$name];
                    continue;
                }

                if (isEntity($typeName)){
                    $class = new \ReflectionClass($typeName);
                    $parent = $class->getParentClass();
                    if ($parent == new \ReflectionClass(get_class())){
                        $className = $class->getName();
                        $data[$name] = $className::get($data[$name]);
                    }
                }
            }
            $this->$name = $data[$name];
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
     * @return void
     */
    public function save(): void {
        $dbh = self::getPDO();
        $entity = new \ReflectionClass($this);
        $tableName = $entity->getStaticPropertyValue("tableName");
        $idName = $entity->getStaticPropertyValue("idName");

        $properties = $this->getDefinedProperties();

        $data = self::resolveReferences($properties);
        $keys = array_keys($data);

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
     * Reloads object from database.
     * It's not recursive.
     * @return void
     */
    public function load(): void {
        $dbh = self::getPDO();
        $class = get_called_class();
        $tableName = $class::$tableName;
        $idName = $class::$idName;

        $statement = $dbh->prepare(<<<EOF
            SELECT *
            FROM $tableName 
            WHERE $idName = :id;
        EOF);
        $id = $this->getId();
        $statement->bindParam(":id", $id);
        $statement->execute();

        $data = $statement->fetch(\PDO::FETCH_ASSOC);
        if ($data){
            $this->morph($data);
        }
    }

    /**
     * Returns an object by id
     * @param int $id
     * @return Entity
     */
    public static function &get(int $id): ?Entity {
        $dbh = self::getPDO();
        $class = get_called_class();
        $tableName = $class::$tableName;
        $idName = $class::$idName;

        if (isset(self::$cache[$class][(int) $id])){
            self::$cacheUsed++;
            return self::$cache[$class][(int) $id];
        }

        $statement = $dbh->prepare(<<<EOF
            SELECT *
            FROM $tableName 
            WHERE $idName = :id;
        EOF);
        $statement->bindParam(":id", $id);
        $statement->execute();

        $data = $statement->fetch(\PDO::FETCH_ASSOC);
        if (!$data){
            return null;
        }
        $entity = new \ReflectionClass($class);
        $object = $entity->newInstanceWithoutConstructor();
        $object->id = $data[$idName];
        $object->morph($data);

        self::$cache[$class][(int) $id] = &$object;

        return $object;
    }

    /**
     * Returns an object
     * @param array $conditions
     * @return Entity[]
     */
    public static function find(array $conditions): array {
        $dbh = self::getPDO();
        $class = get_called_class();
        $tableName = $class::$tableName;
        $idName = $class::$idName;

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

        $objects = [];
        foreach ($statement as $object){
            $objects[] = &self::get($object[0]);
        }
        return $objects;
    }

    /**
     * Deletes object from database by id
     * @param int $id
     * @return void
     */
    public static function delete(int $id): void {
        $dbh = self::getPDO();
        $class = get_called_class();
        $tableName = $class::$tableName;
        $idName = $class::$idName;

        $statement = $dbh->prepare(<<<EOF
            DELETE FROM $tableName
            WHERE $idName = :id;
        EOF);

        $statement->bindParam(':id', $id);
        $statement->execute();

        if (isset(self::$cache[$class][(int) $id])){
            unset(self::$cache[$class][(int) $id]);
        }
    }

    /**
     * Returns defined properties as associative array
     * @return array
     */
    private function getDefinedProperties(): array {
        $entity = new \ReflectionObject($this);
        $flags = \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PUBLIC;
        $properties = $entity->getProperties($flags);
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

    /**
     * Prints statistics about database and cache usage.
     * @return void
     */
    public static function printDebugStats(): void {
        echo "Loaded entities: " . self::$entitiesLoaded . " entities <br />\n";
        echo "Cache used: " . self::$cacheUsed . " times <br />\n";
    }

    /**
     * 
     * @param string $name
     * @param array $arguments
     */
    public function __call(string $name, array $arguments) {
        $traces = $this->getReferenceTraces();
        foreach ($traces as [$property, $trace]){
            if ($trace->getArguments()[0] !== $name){
                continue;
            }
            $targetClass = $property->class;
            $propertyName = $property->getName();
            $condition = [];
            if (isset($arguments[0]) && is_array($arguments[0])){
                $condition = $arguments[0];
            }
            $condition[$propertyName] = $this;

            return $targetClass::find($condition);
        }
    }

    /**
     * Returns list of traceable references.
     * This function is only for debugging.
     * @return array
     */
    public static function listReferenceTraces(): array {
        $name = get_called_class();
        $traces = $name::getReferenceTraces();
        $info = [];
        foreach ($traces as [$property, $trace]){
            $traceName = $trace->getArguments()[0];
            $className = $property->class;
            $shortName = $property->getDeclaringClass()->getShortName();
            $propertyName = $property->getName();

            $info[$traceName] = "Performs find() operation on $className";
            $info[$traceName] .= " where $shortName::$propertyName == \$this";
        }
        return $info;
    }

    private static function getReferenceTraces(): array {
        $name = get_called_class();
        $references = self::$referencedLists[$name];
        $response = [];
        foreach ($references as $property){
            $class = $property->getDeclaringClass();
            $name = $class->getShortName();
            $trace = null;
            foreach ($property->getAttributes() as $attribute){
                if (strpos($attribute->getName(), "traceable") !== false){
                    $trace = $attribute;
                    continue;
                }
            }
            if (!isset($trace)){
                continue;
            }
            $response[] = [$property, $trace];
        }
        return $response;
    }

}
