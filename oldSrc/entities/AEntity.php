<?php

namespace LowAbstractionORM\entities;

use LowAbstractionORM\IEntity;

/**
 * Class AEntity
 * @package LowAbstractionORM\entities
 */
abstract class AEntity implements IEntity
{
    /**
     * We write this const here, so that they can be changed in extended class, but will only read those in config
     * builder, and only use the EntityConfig to read the fields; Really? is it fast?
     */
	const entityTable = self::class;
    const primaryKeys = ['id'];
    const autoIncrement = true;
    const readOnly = false;


    protected $ownersFields = [];
	protected $originalFields = [];
    protected $fields = [];
	protected $em;



    /**
     * AEntity constructor.
     * @param null $entityManager
     * @param array $fields
     */
	public function __construct($entityManager = null, $fields = [])
	{
		if (!isset($this->entityTable)) {
			$this->entityTable = ltrim(get_class($this), 'C');
		}

		if($entityManager instanceof CEntityManager) {
            $this->setEntityManager($entityManager);
        }

        if (!empty($fields)) {
            foreach ($fields as $name => $value) {
                $this->__set($name,$value);
            }
        }
        $this->ownersFields = array_keys($this->fields);
        $this->originalFields = $this->fields;
	}

	public function config() {
	    return $this->ormConfig->entity(self::class);
    }

    /**
     * @return array
     */
	public function getOwnerFields()
	{
		return $this->ownersFields;
	}

    /**
     * @return bool
     */
    public function isReadOnly()
    {
        return $this->readOnly;
    }

	/**
	 * @return bool|mixed
	 */
	public function isCacheable()
    {
		return isset($this->isCacheable) ?  $this->isCacheable : true;
	}

	public function cache() {
	    $this->em()->cache($this);
    }

	public function unCache() {
        $this->em()->unCache($this);
    }

    public function reCache() {
        $this->em()->reCache($this);
    }

    /**
     * @param IEntityManager $em
     */
	public function setEntityManager(IEntityManager $em)
	{
		$this->__set('em',$em);
	}

	/**
	 * @return CEntityManager
	 */
	public function em()
	{
        if($this->em == null) $this->setEntityManager(\Monkey::app()->entityManagerFactory->create($this->getEntityName()));
		return $this->em;
	}

	/**
	 * @param $name
	 * @return bool
	 */
	public function __isset($name)
    {
		try {
			return property_exists($this,$name) || array_key_exists($name,$this->fields) || ($this->em() && ($this->em()->isDefined($name) !== false));
		} catch(\Throwable $e) {}
		return false;
	}
	
    /**
     * @param $key
     * @return mixed
     * @throws RedPandaORMException
     * @throws \Exception
     */
	public function __get($key)
	{
		//$accessor = "get" . ucfirst($key); @check ucfirst non serve perchè i metodi sono case-insensitive
		$accessor = "get" . $key;
		if (method_exists($this, $accessor) && is_callable(array($this, $accessor))) {
			return $this->$accessor();
		}

		if (!array_key_exists($key,$this->fields) && array_search($key,$this->ownersFields) === false) {

            try {
	            if(is_null($this->em())) throw new RedPandaException('EntityManager not set for Entity');
				$field = $this->em()->findChild($key,$this);
			} catch (RedPandaORMException $e) {
                throw $e;
			} catch (RedPandaException $e) {
                throw $e;
			} catch (\Throwable $e) {
                throw new RedPandaORMException("Field \"%s\" could not be retrieved for \"%s\" entity yet, error: %s", [$key, get_class($this),$e->getMessage()],0,$e);
            }

			if ($field === false) {
				throw new RedPandaORMException("Field \"%s\" has not been set for \"%s\" entity yet.", [$key, get_class($this)],0);
			}

			$this->fields[$key] = $field;
		}

		return $this->fields[$key];
	}

	/**
	 * @return string
	 */
	public function getClassName()
	{
		return get_class($this);
	}

	/**
	 * @return string
	 */
	public function getEntityName()
	{
		$reflect = new \ReflectionClass($this);
		return substr($reflect->getShortName(), 1, strlen($reflect->getShortName()));
	}

	/**
	 * @return mixed
	 */
	public function getEntityTable()
	{
		return $this->entityTable;
	}

	/**
	 * @return array
	 */
	public function getPrimaryKeys()
	{
		return $this->primaryKeys;
	}

	/**
	 * @return bool
	 */
	public function hasData()
	{
		if (count($this->fields) > 0) {
			return true;
		}
		return false;
	}

	/**
	 * @return array
	 * @throws RedPandaException
	 */
	public function getIds()
	{
		try{
			$a = [];
			foreach($this->getPrimaryKeys() as $val){
				$a[$val] = $this->$val;
			}
			if(empty($a)) throw new RedPandaException('Primary Keys not specified');
			return $a;
		} catch (\Throwable $e){
			throw new RedPandaException('Ids not set',[],0,$e);
		}
	}

    /**
     * @param array $ids
     * @return bool
     * @throws RedPandaException
     */
    public function setIds(array $ids)
    {
        try{
            foreach($ids as $key=>$value){
                if(is_numeric($key)){
                    $this->{$this->getPrimaryKeys()[$key]} = $value;
                } elseif(is_string($key)){
                    $this->$key = $value;
                } else {
                    throw new \Exception('Not valid id setting in AEntity');
                }
            }
            return true;
        } catch (\Throwable $e){
            throw new RedPandaException('Wrong Ids Setting',[],0,$e);
        }
    }

    /**
     * @return string
     * @throws RedPandaException
     */
	public function printId() {
		return implode('-',$this->getIds());
	}

    /**
     * @return string
     */
    public function stringId() {
        return $this->printId();
    }

	/**
	 * @param $string
	 * @return bool
	 */
	public function readId($string) {
		try {
			$this->setIds(explode('-',$string,count($this->getPrimaryKeys())));
			return true;
		} catch (\Throwable $e) {
			return false;
		}
	}

    /**
     * @param null $algorithm
     * @return int|string
     * @throws RedPandaException
     */
	public function getHashKey($algorithm = null)
	{
        $hasKey = $this->getEntityTable()."::".implode('::',$this->getIds());
        switch($algorithm) {
            case null: return $hasKey;
            case 'md5': return md5($hasKey);
            case 'crc32': return crc32($hasKey);
            default: return $hasKey;
        }
	}

	/**
	 * @return array
	 */
	public function fullTreeToArray()
	{
		$array = [];
		foreach ($this->fields as $key => $value) {
			if (!is_object($value)) {
				$array[$key] = $value;
			} else if ($value instanceof CObjectCollection) {
				foreach ($value as $val){
					$array[$key][] = $val->fullTreeToArray();
				}
			} else {
				$array[$key] = $value->fullTreeToArray();
			}
		}

		return $array;
	}

	/**
	 * @return string BINARY
	 */
	public function serialize()
	{
		$r = [];
        //$r['fields'] = []; //TODO try like this
		foreach($this->ownersFields as $field){
			if(!isset($this->$field) || is_null($this->$field)) {
				$r['fields'][$field] = null;
			} else {
				$r['fields'][$field] = $this->$field;
			}
		}
		$r['ownersFields'] = $this->ownersFields;
		return serialize($r);
	}

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        $fields = [];
        foreach (array_keys($this->fields) as $key) {
            $fields[$key] = $this->$key;
        }
        return $fields;
    }

    /**
     * @param string $string
     */
	public function unserialize($string)
	{
		$r = unserialize($string);
		$this->ownersFields = $r['ownersFields'];
		foreach($r['fields'] as $key => $val){
			$this->fields[$key] = $val;
			$this->originalFields[$key] = $val;
		}
	}

    /**
     * @return string
     */
    public function froze() {
        $r = [];
        foreach($this->ownersFields as $field){
            if(!isset($this->$field) || is_null($this->$field)) {
                $r[$field] = null;
            } else {
                $r[$field] = $this->$field;
            }
        }
        return json_encode($r);
    }

    /**
     * @param $string
     * @return static
     * @throws \Throwable
     */
    public static function defrost($string) {
        $entity = new static(null,json_decode($string,true));
        $entity->setEntityManager(\Monkey::app()->entityManagerFactory->create($entity->getEntityTable()));
        return $entity;
    }

    /**
     * @return int
     * @throws BambooException
     */
	public function insert()
	{
		if(is_null($this->em())) {
			throw new BambooException('Classic should never happen exception, inserting on '.$this->entityTable);
		}
		return $this->em()->insert($this);
	}

    /**
     * @return int
     * @throws BambooException
     * @throws \bamboo\core\exceptions\BambooORMInvalidEntityException
     * @throws \bamboo\core\exceptions\BambooORMReadOnlyException
     */
	public function update()
	{
	    if(is_null($this->em())) {
			throw new BambooException('Classic should never happen exception, updating on '.$this->entityTable);
		}
		return $this->em()->update($this);
	}

	/**
	 * @return mixed
	 * @throws BambooException
	 * @throws \bamboo\core\exceptions\BambooORMInvalidEntityException
	 * @throws \bamboo\core\exceptions\BambooORMReadOnlyException
	 */
	public function delete()
	{
		if(is_null($this->em())) {
			throw new BambooException('Classic should never happen exception, deleting on '.$this->entityTable);
		}
		return $this->em()->delete($this);
	}

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->fields;
    }

    /**
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function __set($name, $value)
    {
        //$mutator = "set" . ucfirst(strtolower($name)$mutator = "set" . ucfirst(strtolower($name));); //@check ucfirst e strtolower non servolo perchè i metodi sono case-insensitive
        $mutator = "set".$name;
        if (method_exists($this, $mutator) && is_callable(array($this, $mutator))) {
            $this->$mutator($value);
        } else if(property_exists($this,$name)) {
            $this->$name = $value;
        } else {
            $this->fields[$name] = $value;
        }
        return $this;
    }

    /**
     * @param $name
     * @return $this
     * @throws BambooException
     */
    public function __unset($name)
    {
        if (!$this->__isset($name)) {
            throw new BambooException("Field \"$name\" has not been set for this entity yet.");
        }
        unset($this->fields[$name]);
        return $this;
    }

	/**
	 * @return array
	 */
	public function __debugInfo()
	{
		if(!isset($this->em) || empty($this->em())) return get_object_vars($this);
		$em = $this->em();
		unset($this->em);
		$x = get_object_vars($this);
		$this->setEntityManager($em);
		return $x;
	}

    /**
     * @param null $actionName
     * @param null $eventName
     * @param null $userId
     * @return CObjectCollection
     */
	public function getLogs($actionName = null, $eventValue = null, $eventName = null, $userId = null){
        $params = [
            'entityName' => $this->getEntityName(),
            'stringId' => $this->stringId()
        ];

        if($actionName) $params['actionName'] = $actionName;
        if($eventName) $params['eventName'] = $eventName;
        if($userId) $params['userId'] = $userId;
        if($eventValue) $params['eventValue'] = $eventValue;

        return \Monkey::app()->repoFactory->create('Log')->findBy($params);
    }

    /**
     * @return \bamboo\core\db\pandaorm\repositories\ARepo
     */
    public function getEntityRepo() {
	    return \Monkey::app()->repoFactory->create($this->getEntityName());
    }

    /**
     * Read the DB field from database
     */
    public function readOwnerFields() {
        $res = \Monkey::app()->dbAdapter->query("SHOW COLUMNS FROM ".$this->getEntityTable(), [])->fetchAll();
        foreach($res as $v) {
            $this->ownersFields[] = $v->Field;
        }
    }

    /**
     * insert the data, refresh the entity and return the new id if applicable
     * @return int
     */
    public function smartInsert() {
        $res = \Monkey::app()->dbAdapter->query("SHOW COLUMNS FROM `".$this->getEntityTable()."`", [],true)->fetchAll();
        $autoIncrementCol = null;
        $this->ownersFields = [];
        foreach($res as $v) {
            if(strstr($v['Extra'],'auto_increment')) $autoIncrementCol = $v['Field'];
            $this->ownersFields[] = $v['Field'];
        }
        $res = $this->insert();
        if(!is_null($autoIncrementCol)) {
            $this->$autoIncrementCol = $res;
        }
        $this->refresh();
        return $res;
    }

    /**
     * refresh the data in the entity, cleaning the dependencies;
     */
    public function refresh() {
        $fields = \Monkey::app()->dbAdapter->select($this->getEntityName(),$this->getIds())->fetch();
        $this->fields = $fields;
        $this->originalFields = $fields;
    }

    /**
     * check if the values of the entity are changed since first loaded
     * @return bool
     */
    public function isChanged() {
        foreach ($this->ownersFields as $field) {
            if($this->fields[$field] != $this->originalFields[$field]) return true;
        }
        return false;
    }
}