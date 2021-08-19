<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Core;

use Core\Attributes\Table;
use Core\Attributes\PrimaryKey;
use Core\Attributes\Traceable;
use Core\Attributes\TraceLazyLoad;

/**
 * Base class for database models
 * You should define all types of all variables in your class
 *
 * @author azcraft
 */
abstract class Entity
{

    /**
     * Used for resolving traceable references
     * @var array
     */
    private static array $referencedLists = [];

    /**
     * Caching all objects
     * @var array
     */
    private static array $objectCache = [];

    /**
     * List of loaded entity classes 
     * @var array
     */
    private static array $initialized = [];

    /**
     * Caches table names. Used by getTableName()
     * @var array
     */
    private static array $tableNamesCache;

    /**
     * Caches primary keys names. Used by getPrimaryKeys()
     * @var array
     */
    private static array $primaryKeysCache;

    /**
     * Cache use count. (Debugging Stats)
     * @var int
     */
    private static int $cacheUsed = 0;

    /**
     * Loaded SQL Objects. (Debugging Stats) 
     * @var int
     */
    private static int $entitiesLoaded = 0;

    /**
     * Current object's id
     * @var int|array
     */
    private $id;

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
        self::$objectCache[$class] = [];
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
        $tableName = self::getTableName();
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

        if (defined("DEBUG_PRINT_QUERY_TYPES")){
            echo get_called_class() . " [SQL] insert element. <br />\n";
        }

        foreach ($keys as $key){
            $statement->bindParam($key, $data[$key]);
        }

        $statement->execute();
        $this->resolveId(true);

        $class = get_called_class();
        $id = $this->getId();
        if (!is_array($id)){
            $id = [$id];
        }
        $idHash = self::hashIds($id);
        self::$objectCache[$class][$idHash] = &$this;
    }

    /**
     * Sets id after inserting element
     * @return void
     */
    private function resolveId(bool $justInserted = false): void {
        if (isset($this->id)){
            return;
        }
        $keys = self::getPrimaryKeys();
        $defined = true;
        $values = [];
        foreach ($keys as $key){
            if (!isset($this->$key)){
                $defined = false;
                break;
            }
            $values[] = $this->$key;
        }
        if ($defined){
            $values = self::resolveReferences($values);
            $this->setId(...$values);
        } else if (count($keys) == 1 && $justInserted){
            $pdo = self::getPDO();
            $this->setId($pdo->lastInsertId());
        } else {
            throw new Exception("Can not resolve id of inserted element.");
        }
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
     * @return mixed|array
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Defines id of entity
     * @param mixed|array $id
     * @return void
     */
    protected function setId(...$id): void {
        if (count($id) == 1){
            $this->id = $id[0];
        } else {
            $this->id = $id;
        }
    }

    /**
     * Saves the object in the database
     * @throws Exception
     * @return void
     */
    public function save(): void {
        $dbh = self::getPDO();
        $entity = new \ReflectionClass($this);
        $tableName = self::getTableName();
        $idName = $entity->getStaticPropertyValue("idName");
        var_dump($tableName . "; " . $idName);

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

        if (defined("DEBUG_PRINT_QUERY_TYPES")){
            echo get_called_class() . " [SQL] save element. <br />\n";
        }

        $id = $this->getId();
        $statement->bindParam(':id', $id);

        foreach ($keys as $key){
            $statement->bindParam($key, $data[$key]);
        }

        $statement->execute();

        $this->setId($dbh->lastInsertId());
    }

    /**
     * Reloads object from database.
     * It's not recursive.
     * @return void
     */
    public function load(): void {
        $this->resolveId();
        $dbh = self::getPDO();
        $tableName = self::getTableName();
        $keys = self::getPrimaryKeys();
        $id = $this->getId();
        if (!is_array($id) && isset($id)){
            $id = [$id];
        }

        if (count($id) == 0 || count($id) != count($keys)){
            throw new Exception("Incorrect number of primary keys.");
        }

        $queryCondition = "$keys[0] = ?";
        for ($i = 1; $i < count($keys); $i++){
            $key = $keys[$i];
            $queryCondition .= " AND $key = ?";
        }

        $statement = $dbh->prepare(<<<EOF
            SELECT *
            FROM $tableName 
            WHERE $queryCondition;
        EOF);

        if (defined("DEBUG_PRINT_QUERY_TYPES")){
            echo get_called_class() . " [SQL] load element. <br />\n";
        }

        $statement->execute($id);

        $data = $statement->fetch(\PDO::FETCH_ASSOC);
        if ($data){
            $this->morph($data);
        }
    }

    /**
     * Returns an object by primary key
     * @param mixed|array $id
     * @return Entity
     */
    public static function &get(...$id): ?Entity {
        $dbh = self::getPDO();
        $className = get_called_class();
        $tableName = self::getTableName();
        $keys = self::getPrimaryKeys();

        if (count($id) == 0 || count($id) != count($keys)){
            throw new Exception("Incorrect number of primary keys.");
        }
        $id = self::resolveReferences($id);

        $idHash = self::hashIds($id);
        if (isset(self::$objectCache[$className][$idHash])){
            self::$cacheUsed++;
            return self::$objectCache[$className][$idHash];
        }

        $queryCondition = "$keys[0] = ?";
        for ($i = 1; $i < count($keys); $i++){
            $key = $keys[$i];
            $queryCondition .= " AND $key = ?";
        }

        $statement = $dbh->prepare(<<<EOF
            SELECT *
            FROM $tableName
            WHERE $queryCondition;
        EOF);
        if (defined("DEBUG_PRINT_QUERY_TYPES")){
            echo get_called_class() . " [SQL] get element. <br />\n";
        }
        $statement->execute($id);

        $data = $statement->fetch(\PDO::FETCH_ASSOC);
        if (!$data){
            $dummy = null;
            return $dummy;
        }
        $entity = new \ReflectionClass($className);
        $object = $entity->newInstanceWithoutConstructor();
        self::$objectCache[$className][$idHash] = &$object;
        $object->setId(...$id);
        $object->morph($data);

        return $object;
    }

    /**
     * Returns an object
     * @param array $conditions
     * @return Entity[]
     */
    public static function find(array $conditions): array {
        $dbh = self::getPDO();
        $tableName = self::getTableName();
        $primaryKeys = self::getPrimaryKeys();

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
            SELECT *
            FROM $tableName 
            $conditionString;
        EOF);

        if (defined("DEBUG_PRINT_QUERY_TYPES")){
            echo get_called_class() . " [SQL] find elements. <br />\n";
        }

        foreach ($keys as $key){
            $statement->bindParam($key, $data[$key]);
        }

        $statement->execute();

        $objects = [];
        $className = get_called_class();
        while ($data = $statement->fetch(\PDO::FETCH_ASSOC)){
            $id = [];
            foreach ($primaryKeys as $prim){
                $id[] = $data[$prim];
            }
            $idHash = self::hashIds($id);
            if (isset(self::$objectCache[$className][$idHash])){
                self::$cacheUsed++;
                $objects[] = &self::$objectCache[$className][$idHash];
                continue;
            }

            $entity = new \ReflectionClass($className);
            $object = $entity->newInstanceWithoutConstructor();
            self::$objectCache[$className][$idHash] = &$object;
            $object->setId(...$id);
            $object->morph($data);

            $objects[] = &$object;
        }

        return $objects;
    }

    /**
     * Deletes object from database by primary key
     * @param mixed|array $id
     * @return void
     */
    public static function delete(...$id): void {
        $dbh = self::getPDO();
        $class = get_called_class();
        $tableName = self::getTableName();
        $keys = self::getPrimaryKeys();

        if (count($id) == 1 && count($keys) > 1 && $id[0] instanceof self){
            self::delete(...$id[0]->getId());
            return;
        }

        if (!count($id) || count($keys) != count($id)){
            throw new Exception("Incorrect number of primary keys.");
        }

        $id = self::resolveReferences($id);

        $condition = "$keys[0] = ?";
        for ($i = 1; $i < count($keys); $i++){
            $condition .= " AND $keys[$i] = ?";
        }

        $statement = $dbh->prepare(<<<EOF
            DELETE FROM $tableName
            WHERE $condition;
        EOF);

        if (defined("DEBUG_PRINT_QUERY_TYPES")){
            echo get_called_class() . " [SQL] delete element. <br />\n";
        }

        $statement->execute($id);

        $idHash = self::hashIds($id);
        if (isset(self::$objectCache[$class][$idHash])){
            unset(self::$objectCache[$class][$idHash]);
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
                $properties[$key] = $value->getId();
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
     * Covers user-selected reference tracing functions
     * @param string $method
     * @param array $arguments
     */
    public function __call(string $method, array $arguments) {
        $className = get_called_class();

        $traces = $this->getReferenceTraces();
        if (!in_array($method, array_keys($traces))){
            self::loadLazyTrace($method);
            $traces = $this->getReferenceTraces();
        }

        if (isset($traces[$method])){
            $property = $traces[$method];
            $targetClass = $property->class;
            $propertyName = $property->getName();
            $condition = [];
            if (isset($arguments[0]) && is_array($arguments[0])){
                $condition = $arguments[0];
            }
            $condition[$propertyName] = $this;

            return $targetClass::find($condition);
        }

        throw new Exception("$className::$method is not initialized.");
    }

    /**
     * Loads methods named with TraceLazyLoad attribute on demand
     * @param string $method
     * @return void
     */
    private static function loadLazyTrace(string $method): void {
        $self = new \ReflectionClass(get_called_class());
        $attributes = $self->getAttributes();
        foreach ($attributes as $attribute){
            $instance = $attribute->newInstance();
            if (!($instance instanceof TraceLazyLoad)){
                continue;
            }
            if ($instance->contains($method)){
                $instance->load();
                return;
            }
        }
    }

    /**
     * Returns list of traceable references.
     * This function is only for debugging.
     * @return array
     */
    public static function listReferenceTraces(): array {
        $info = [];

        $self = new \ReflectionClass(get_called_class());
        $attributes = $self->getAttributes();
        foreach ($attributes as $attribute){
            $instance = $attribute->newInstance();
            if (!($instance instanceof TraceLazyLoad)){
                continue;
            }
            $class = $instance->getClassName();
            foreach ($instance->getMethods() as $method){
                $info[$method] = "Can be lazy loaded from $class";
            }
        }

        $traces = self::getReferenceTraces();
        foreach ($traces as $trace => $property){
            $className = $property->class;
            $shortName = $property->getDeclaringClass()->getShortName();
            $propertyName = $property->getName();

            $info[$trace] = "Performs find() operation on $className";
            $info[$trace] .= " where $shortName::$propertyName == \$this";
        }
        return $info;
    }

    /**
     * Returns array of pairs ($parameter, $method)
     * $parameter is the parameter that references this class.
     * $method is the name of the method that this class need to have
     * @return array
     */
    private static function getReferenceTraces(): array {
        $name = get_called_class();
        $references = self::$referencedLists[$name];
        $response = [];
        foreach ($references as $property){
            $class = $property->getDeclaringClass();
            $name = $class->getShortName();
            $trace = null;
            foreach ($property->getAttributes() as $attribute){
                $instance = $attribute->newInstance();
                if ($instance instanceof Traceable){
                    $trace = $instance->getName();
                    continue;
                }
            }
            if (!isset($trace)){
                continue;
            }
            $response[$trace] = $property;
        }
        return $response;
    }

    /**
     * Buffers and returns table name per called class
     * @param string $className
     * @return string
     * @throws Exception
     */
    private static function getTableName(string $className = null): string {
        $name = $className ?? get_called_class();
        if (isset(self::$tableNamesCache[$name])){
            return self::$tableNamesCache[$name];
        }

        $class = new \ReflectionClass($name);
        foreach ($class->getAttributes() as $attr){
            $instance = $attr->newInstance();
            if ($instance instanceof Table){
                $tableName = $instance->getTable();
                self::$tableNamesCache[$name] = $tableName;
                return $tableName;
            }
        }
        throw new Exception("Table name for $name is not defined");
    }

    /**
     * Buffers and returns primary keys per called class
     * @param string $className
     * @return array
     * @throws Exception
     */
    private static function getPrimaryKeys(string $className = null): array {
        $name = $className ?? get_called_class();
        if (isset(self::$primaryKeysCache[$name])){
            return self::$primaryKeysCache[$name];
        }

        $class = new \ReflectionClass($name);
        foreach ($class->getAttributes() as $attr){
            $instance = $attr->newInstance();
            if ($instance instanceof PrimaryKey){
                $keys = $instance->getKeys();
                if (count($keys)){
                    self::$primaryKeysCache[$name] = $keys;
                    return $keys;
                }
            }
        }
        throw new Exception("Primary keys for $name are not defined");
    }

    /**
     * Returns hash of array of resolved ids
     * @param array $ids
     * @return string
     */
    private static function hashIds(array $ids): string {
        foreach ($ids as $k => $id){
            $ids[$k] = (string) $id;
        }
        return serialize($ids);
    }

}
