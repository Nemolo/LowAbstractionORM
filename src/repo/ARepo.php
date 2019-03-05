<?php

namespace LowAbstractionORM\repo;

use LowAbstractionORM\IDBAdapter;
use LowAbstractionORM\IRepo;

abstract class ARepo implements IRepo
{
    private $connection;

    const DATE_FORMAT = 'Y-m-d';
    const DATETIME_FORMAT = 'Y-m-d H:i:s';
    const TIME_FORMAT = 'H:i:s';
    const UNIX_TIMESTAMP_FORMAT = 'U';

    protected $entityClass;

	/**
	 * ARepo constructor.
	 *
	 * @param Connection $connection
	 * @param null $entity
	 *
	 * @throws \Exception
	 */
    public final function __construct(IDBAdapter $connection, $entityClass = null)
    {
        $this->connection = $connection;
		if(!$this->entityClass) {
			$this->entityClass = $entityClass;
        }
        
        if(!$this->entityClass) {
            throw new \Exception('You have to specify Entity when you build a Repo');
        }
    }

    /**
     * @param $id
     * @return AEntity|null
     * @throws \Exception
     */
    public function getOneById($id) {
        /* @var AEntity $className*/
        $className = $this->entityClass;
        if($id === null) return null;
        if(count($className::KEYS) === 1) return $this->getOneByIds([$id]);
        throw new \Exception('Can\'t provide this entity with just one key');
    }

    /**
     * @param array $ids
     * @return AEntity|null
     * @throws \Exception
     */
    public function getOneByIds(array $ids)
    {
        if ($this->entityClass == NULL) {
            throw new \Exception('Wrong Repository setup');
        }
        /* @var AEntity $className*/
        $className = $this->entityClass;
        if (count($className::KEYS) < 1 || count($ids) != count($className::KEYS)) {
            throw new \Exception('Wrong Key Number for Entity, Expected ' . count($className::KEYS) . 'provided: ' . count($ids));
        }
        $table = $className::getTableName();
        $sql = "SELECT * FROM $table WHERE 1=1";
        $bind = [];
        foreach ($className::KEYS as $num=>$key) {
            $sql.= " AND `$key` = ?";
            if(isset($ids[$key])) $bind[] = $ids[$key];
            elseif(isset($ids[$num])) $bind[] = $ids[$num];
            else throw new \Exception('Wrong Key names for Entity Select');
        }
        return $this->getRow($sql, $bind, $this->entityClass);
    }

    /**
     * @param array $conditions
     * @return AEntity|null
     * @throws \Exception
     */
    public function getOneBy(array $conditions) {
        if($this->entityClass == NULL) {
            throw new \Exception('Wrong Repository setup');
        }
        /* @var AEntity $className*/
        $className = $this->entityClass;
        $table = $className::getTableName();
        $sql = "SELECT * FROM $table WHERE 1=1";
        foreach ($conditions as $key => $val) {
            if(is_numeric($key)) throw new \Exception('Associative array must be provided as conditions');
            $sql.= " AND `$key` = ?";

        }
        return $this->getRow($sql, array_values($conditions), $this->entityClass);
    }

	/**
	 * @param $sql
	 * @param $bind
	 *
	 * @return array
	 * @throws \Exception
	 */
    public function getBySql($sql, $bind) {
	    return $this->getRows($sql, $bind, $this->entityClass);
    }

	/**
	 * @param $sql
	 * @param $bind
	 *
	 * @return AEntity|null
	 * @throws \Exception
	 */
	public function getOneBySql($sql, $bind) {
		return $this->getRow($sql, $bind, $this->entityClass);
	}

    /**
     * @param array $conditions
     * @param null $limit
     * @return array
     * @throws \ReflectionException
     */
    public function getManyBy(array $conditions, $limit = null) {
        if($this->entityClass == NULL) {
            throw new \Exception('Wrong Repository setup');
        }
        /* @var AEntity $className*/
        $className = $this->entityClass;
        $table = $className::getTableName();
        $sql = "SELECT * FROM $table WHERE 1=1";
        foreach ($conditions as $key => $val) {
            if(is_numeric($key)) throw new \Exception('Associative array must be provided as conditions');
            $sql.= " AND `$key` = ?";
        }
        if(is_numeric($limit)) $sql.=" LIMIT $limit";
        return $this->getRows($sql, array_values($conditions), $this->entityClass);
    }

    /**
     * @param AEntity $entity
     * @return bool|string
     * @throws \Exception
     */
    public function insert(AEntity $entity) {
        if($this->entityClass == NULL) {
            throw new \Exception('Wrong Repository setup');
        }
        if($this->entityClass != get_class($entity)) {
            throw new \Exception('Wrong repository to Update');
        }
        $classVars = get_class_vars($entity);
        $keys = [];
        $bind = [];
        $point = [];
        foreach ($classVars as $name => $value) {
            if($entity::AUTO_INCREMENT == $name) {
                continue;
            }
            if(isset($entity->{$name})) {
                $keys[] = '`'.$name.'`';
                $value = $entity->{$name};
                if($value instanceof \DateTime) {
                	$bind[] = $value->format(self::DATETIME_FORMAT);
                } else {
	                $bind[] = $value;
                }

                $point[] = '?';
            }
        }
        $sql = "INSERT INTO ".$entity::getTableName()." (".implode(',',$keys).") VALUES (".implode(',',$point).")";
        $stmt = $this->pdo()->prepare($sql);
        if($stmt->execute($bind) || $stmt->errorCode() === '00000') {
        	if((new $this->entity)::AUTO_INCREMENT) {
		        return $this->pdo()->lastInsertId();
	        } else {
        		return true;
	        }
        } 
        throw new \Exception('Failed Insert');
    }

    /**
     * @param AEntity $entity
     * @return bool|string
     * @throws \Exception
     */
    public function update(AEntity $entity) {
        if($this->entityClass == NULL) {
            throw new \Exception('Wrong Repository setup');
        }
        if($this->entityClass != get_class($entity)) {
            throw new \Exception('Wrong repository to Update');
        }

        $whereSql = [];
        $whereBind = [];
        $setSql = [];
        $setBind = [];
        foreach ($entity::KEYS as $keyName) {
            $whereSql[] = "$keyName = ?";
            $whereBind[] = $entity->{$keyName};
        }
        foreach ($entity->getChangedProperties() as $name => $value) {
            $setSql[] = "$name = ?";
            $setBind[] = $entity->{$name};
        }
        if(count($whereSql) != count($entity::KEYS)) throw new \Exception('Won\'t update a class without all the keys');
        if(empty($setBind)) return 0;
        $sql = "UPDATE ".$entity::getTableName()." SET ".implode(',',$setSql)." WHERE ".implode('AND ',$whereSql);
        $stmt = $this->pdo()->prepare($sql);
        return $stmt->execute(array_merge($setBind,$whereBind));
    }

    /**
     * @param AEntity $entity
     * @return bool
     * @throws \Exception
     */
    public function delete(AEntity $entity) {
        if($this->entityClass == NULL) {
            throw new \Exception('Wrong Repository setup');
        }
        if($this->entityClass != get_class($entity)) {
            throw new \Exception('Wrong repository to Update');
        }

        $whereSql = [];
        $whereBind = [];

        foreach ($entity::KEYS as $keyName) {
            $whereSql[] = "$keyName = ?";
            $whereBind[] = $entity->{$keyName};
        }

        $sql = "DELETE FROM ".$entity::getTableName()." WHERE ".implode('AND ',$whereSql);
        $stmt = $this->pdo()->prepare($sql);
        return $stmt->execute(array_merge($whereBind));
    }



}
