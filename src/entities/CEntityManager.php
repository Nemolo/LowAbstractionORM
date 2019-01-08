<?php

namespace LowAbstractionORM\entities;
use LowAbstractionORM\exceptions\LowAbstractionORMException;
use LowAbstractionORM\IDBAdapter;
use LowAbstractionORM\IEntity;
use LowAbstractionORM\IEntityConfig;
use LowAbstractionORM\IEntityManager;

/**
 * Class CEntityManager
 * @package LowAbstractionORM\entities
 */
class CEntityManager implements IEntityManager
{
    /**
     * @var IEntityConfig
     */
    private $properties;

    /**
     * @var IDBAdapter
     */
    private $adapter;

    /**
     * CEntityManager constructor.
     *
     * @param $properties
     * @param IDBAdapter $adapter
     * @param $entityName
     * @param CLang|null $lang
     * @throws RedPandaORMInvalidEntityException
     */
    public function __construct(IEntityConfig $properties, IDBAdapter $adapter)
    {
        $this->adapter = $adapter;
        $this->properties = $properties;
    }

    /**
     * @param string $limit
     * @param string $orderBy
     * @return IEntity[]
     */
    public function findAll($limit = null, $orderBy = null)
    {
        return $this->findBy(array(),$limit,$orderBy);
    }

    /**
     * @param array $condition
     * @param null $limit
     * @param null $orderBy
     * @param string $boolOperator
     * @return IEntity[]
     */
    public function findBy(array $condition, $limit = null, $orderBy = null, $boolOperator = 'AND')
    {
        try {
            $entity = $this->getNewEntity();
            if($this->matchKeys($entity,$condition)){
                $collection = [];
                if(!is_null($one = $this->findOne($condition))) $collection[] = $one;
            } else {
                $collection = $this->loadMany($this->adapter->select($entity->getEntityTable(),$condition,$limit,$orderBy,$boolOperator)->getStatement());
            }

            return $collection;
        } catch (LowAbstractionORMException $e) {
            throw $e;
        }
    }

    /**
     * @param AEntity $entity
     * @param array $condition
     * @return bool
     */
    protected function matchKeys(AEntity $entity,array $condition)
    {
        foreach($entity->getPrimaryKeys() as $key ){
            if(!isset($condition[$key])) return false;
        }
        return true;
    }

    /**
     * @param array $condition
     * @param null $orderBy
     * @param string $boolOperator
     * @return null|IEntity
     */
    public function findOneBy(array $condition, $orderBy = null, $boolOperator = 'AND')
    {
        $a = $this->findBy($condition," LIMIT 1 ",$orderBy,$boolOperator);
        if($a->isEmpty()) return null;
        else return $a->getFirst();
    }

    /**
     * @return int
     */
    public function getCacheExpiration()
    {
        if(is_null($this->cacheExpiration)){
            $this->cacheExpiration = 0;
            $this->cacheExpiration = \Monkey::app()->cfg()->fetch('miscellaneous','cacheEntityExpiration') ? \Monkey::app()->cfg()->fetch('miscellaneous','cacheEntityExpiration') : 0;
            $this->cacheExpiration = !empty($this->properties) && $this->properties->fetch('entity','cacheExpiration') ? $this->properties->fetch('entity','cacheExpiration') : $this->cacheExpiration;
        }
        return $this->cacheExpiration;
    }

    /**
     * @param array $ids
     * @return AEntity|\Exception|null|\Throwable
     */
    public function findOne(array $ids)
    {
        try {
            $entity = $this->getNewEntity();
            $entity->setIds($ids);
            return $this->fillOne($entity);
        } catch (BambooEntityNotFoundException $e) {
            return null;
        }
    }

    /**
     * @param AEntity $entity
     * @return AEntity|\Exception|null|\Throwable
     */
    public function fillOne(AEntity $entity)
    {
	    if($entity->isCacheable()){
	        try {
                $e = \Monkey::app()->cacheService->getCache('entities')->get($entity->getHashKey());
                if($e instanceof $entity){
                    $e->setEntityManager($this);
                    return $e;
                }
            } catch (\Throwable $e) {
                \Monkey::app()->applicationWarning('EntityManager',
                    'failed to retrive cache',
                    'failed to retrive cache for entity: '.$entity->getEntityTable().' ids: '.$entity->printId(),$e);
            }
		    $entity = $this->loadOne($this->adapter->select($entity->getEntityTable(), $entity->getIds())->getStatement());
		    if(is_null($entity)) return null;

            $this->cache($entity);
		    return $entity;
	    }
	    return $this->loadOne($this->adapter->select($entity->getEntityTable(), $entity->getIds())->getStatement());
    }

    /**
     * @param AEntity $entity
     */
    public function cache(AEntity $entity) {
        if($entity->isCacheable()) {
            \Monkey::app()->cacheService->getCache('entities')->add($entity->getHashKey(),$entity, $this->getCacheExpiration());
        }
    }

    /**
     * @param AEntity $entity
     */
    public function unCache(AEntity $entity) {
        \Monkey::app()->cacheService->getCache('entities')->delete($entity->getHashKey());
    }

    /**
     * @param AEntity $entity
     */
    public function reCache(AEntity $entity) {
        if ($entity->isCacheable()) {
            \Monkey::app()->cacheService->getCache('entities')->set($entity->getHashKey(),$entity,$this->getCacheExpiration());
        }
    }

    /**
     * @param $field
     * @return bool|null
     */
    public function isDefined($field)
    {
        if ($this->properties === null) {
            return false;
        }

        $children = $this->properties->fetch("entity", "children");
        $selected = null;
        foreach($children as $child){
            if ($child->name == $field) {
                $selected = $child;
                continue;
            }
        }

        if ($selected == null) {
            return false;
        }

        return $selected;
    }

    /**
     * @param $key
     * @param IEntity $entity
     * @param bool $ignoreLang
     * @return CObjectCollection|AEntity|IEntity|null
     * @throws RedPandaORMException
     */
    public function findChild($key,IEntity $entity,$ignoreLang = false)
    {
        $selected = $this->isDefined($key);

        if ($selected === false) {
            throw new RedPandaORMException('Undefined relationship type for entity %s while looking for key %s', [$entity->getEntityName(),$key]);
        }

        switch ($selected->type) {
            case 'OneToOne':
                return $this->findOneChild($entity, $selected, !$ignoreLang && isset($selected->lang) ? $selected->lang : false );
                break;
            case 'OneToMany':
                return $this->findManyChild($entity, $selected, !$ignoreLang && isset($selected->lang) ? $selected->lang : false );
                break;
            case 'ManyToMany':
                return $this->findManyToManyChild($entity, $selected, !$ignoreLang && isset($selected->lang) ? $selected->lang : false );
                break;
            case 'OneToOneCustom':
                return $this->findOneCustomChild($entity, $selected);
                break;
            case 'OneToManyCustom':
                return $this->findManyCustomChild($entity, $selected);
                break;
            default:
                throw new RedPandaORMException('Missing relationship information for %s', [$selected->name]);
                break;
        }
    }

    /**
     * @param $sql
     * @param array $bind
     * @return CObjectCollection
     * @throws RedPandaORMException
     */
    public function findBySql($sql,$bind = array())
    {
        $col = new CObjectCollection();
        foreach($this->adapter->query($sql, $bind)->fetchAll() as $singleId){
            $col->add($this->findOne($singleId));
        }
        return $col;
    }

    /**
     * @param $sql
     * @param array $bind
     * @return int
     * @throws RedPandaORMException
     */
    public function findCountBySql($sql,$bind = array())
    {
        $res = $this->adapter->query($sql,$bind)->fetch();
        $i = 0;
        $count = -1;
        if(!$res) return 0;
        foreach($res as $val){
            $count = $val;
            $i++;
        }
        if($i>1) {
            throw new RedPandaORMException('Error in custom query: expected 1 result, found [%s]',[$i]);
        }
        return $count;
    }

    /**
     * @param $sql
     * @param array $bind
     * @return mixed
     */
    public function query($sql,$bind = array())
    {
        return $this->adapter->query($sql, $bind);
    }

    /**
     * @param $entity
     * @param $child
     * @param bool|false $lang
     * @return null|IEntity
     */
    private function findOneChild($entity,$child,$lang = false)
    {
        try {
            $conditions = array();
            foreach($child->childKeys as $seq=>$key)
            {
                if(!isset($entity->{$child->parentKeys[$seq]})) return null;
                $conditions[$key] = $entity->{$child->parentKeys[$seq]};
            }
            if($lang && $this->lang){
                $conditions['langId'] = $this->lang->getId();
            }
        } catch(\Throwable $e){
            return null;
        }
        return \Monkey::app()->entityManagerFactory->create($child->entity,$this->lang)->findOneBy($conditions);
    }

    /**
     * @param $entity
     * @param $child
     * @param bool $lang
     * @return CObjectCollection
     */
    private function findManyChild($entity,$child,$lang = false)
    {
        try{
            $conditions = array();
            foreach($child->childKeys as $seq=>$key) {
                $conditions[$key] = $entity->{$child->parentKeys[$seq]};
            }
            if($lang && $this->lang) {
                $conditions['langId'] = $this->lang->getId();
            }
        }catch(\Throwable $e){
            return new CObjectCollection();
        }
        return \Monkey::app()->entityManagerFactory->create($child->entity,$this->lang)->findBy($conditions);
    }

    /**
     * @param $entity
     * @param $child
     * @param bool $lang
     * @return CObjectCollection
     */
    private function findManyToManyChild($entity, $child, $lang = false)
    {
        try {
            /** @var CEntityManager $cEm */
            $cEm = \Monkey::app()->entityManagerFactory->create($child->entity,$this->lang);
            $childE = $cEm->getEmptyEntity();
            $select = [];
            foreach($child->childKeys as $key) {
                $select[] = "tableChild.".$key;
            }
            if($lang === true && $this->lang != false) {
                $select[] = "tableChild.langId";
            }
            $sql = "SELECT ".implode(',',array_unique($select)).
	                " FROM `".ucfirst($childE->getEntityTable())."` tableChild , `".ucfirst($child->joinOn->table)."` tableJoin
	                WHERE 1=1 ";
            foreach ($child->joinOn->childKeys as $seq=>$key) {
                $sql.=" AND tableJoin.".$key." = tableChild.".$child->childKeys[$seq]." ";
            }

            foreach($child->joinOn->parentKeys as $seq=>$key) {
                $sql.=" AND tableJoin.".$child->joinOn->parentKeys[$seq]." = :".$child->joinOn->parentKeys[$seq];
            }

            $parentKeys = array();
            foreach($child->parentKeys as $seq=>$key){
                if(!isset($entity->{$key}) || empty($entity->{$key})){
                    return new CObjectCollection();
                }
                $parentKeys[] = $entity->{$key};
            }

            if($lang === true && $this->lang != false) {
                $sql.= " AND tableChild.langId = ".$this->lang->getId()." ";
            }

            if(isset($child->joinOn->orderBy) && !empty($child->joinOn->orderBy)) {
                $sql.= " ORDER BY tableJoin.".$child->joinOn->orderBy." ";
            }
        } catch (BambooEntityNotFoundException $e) {
            return new CObjectCollection();
        } catch (\Throwable $e){
            return new CObjectCollection();
        }
        return $cEm->findBySql($sql,$parentKeys);
    }

    /**
     * @param $entity
     * @param $child
     * @return AEntity|null
     */
    public function findOneCustomChild($entity, $child)
    {
        return $this->findManyCustomChild($entity,$child)->getFirst();
    }

    /**
     * @usage the query written in the map must select the ids of the child entity
     * @param $entity
     * @param $child
     * @return CObjectCollection
     */
    public function findManyCustomChild($entity, $child)
    {
        $params = [];
        foreach($child->parentKeys as $key) {
            $params[] = $entity->{$key};
        }
        try {
            return \Monkey::app()->entityManagerFactory->create($child->entity,false)->findBySql($child->query,$params);
        } catch (BambooDBALException $e) {
            return new CObjectCollection();
        }

    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @param null $entityName
     * @return string
     */
    public function getEntityClass($entityName = null) {
        return $this->entityPath . ($entityName ?? $this->getEntityName());
    }

    /**
     * @param $entityName
     * @return AEntity
     * @throws BambooEntityNotFoundException
     */
    private function getNewEntity($entityName = null)
    {
        if(!class_exists($this->getEntityClass($entityName))) throw new BambooEntityNotFoundException('Required Entity not found: %s', [$this->getEntityClass($entityName)]);
        $className = $this->getEntityClass($entityName);
        $entity = new $className();

        return $entity;
    }

    /**
     * @param bool $createFields
     * @return AEntity
     */
    public function getEmptyEntity($createFields = false)
    {
        $e = $this->getNewEntity();
        $e->setEntityManager($this);
        if ($createFields) $e->readOwnerFields();
        return $e;
    }

    /**
     * @param \PDOStatement $statement
     * @return CObjectCollection
     */
    private function loadMany(\PDOStatement $statement)
    {
        /** @var CMySQLAdapter $dbAdapter */
        $temp = $statement->fetchAll(\PDO::FETCH_CLASS,$this->getEntityClass(),[$this]);
        $collection = new CObjectCollection();
        foreach ($temp as $t) {
            $collection->add($t);
        }
        return $collection;
    }

    /**
     * @param \PDOStatement $statement
     * @return mixed|null
     */
    private function loadOne(\PDOStatement $statement){
        /** @var CMySQLAdapter $dbAdapter */
        $entity = $statement->fetchObject($this->getEntityClass(),[$this]);
        return $entity ? $entity : null;
    }

    /**
     * @param IEntity $entity
     * @return mixed
     * @throws BambooORMReadOnlyException
     * @throws BambooORMInvalidEntityException
     */
    public function delete(IEntity $entity)
    {
        $new = $this->getEmptyEntity();
        if(!$entity instanceof $new){
            throw new BambooORMInvalidEntityException("Invalid instance of entity while performing delete");
        }
        if (!$entity->isReadOnly()) {
            $this->unCache($entity);
            return $this->adapter->delete($new->getEntityTable(),$entity->getIds(),'AND',true);
        } else {
            throw new BambooORMReadOnlyException("Entity %s is read only",[$entity->getEntityName()]);
        }
    }

    /**
     * @param IEntity $entity
     * @return int
     * @throws BambooORMInvalidEntityException
     * @throws BambooORMReadOnlyException
     */
    public function insert(IEntity $entity)
    {
        $new = $this->getEmptyEntity();
        if(!$entity instanceof $new){
            throw new BambooORMInvalidEntityException("Invalid instance of entity while performing delete");
        }
        $fields = [];

        foreach ($entity->toArray() as $key =>$field) {
            if(is_object($field) || is_array($field)) continue;
            else $fields[$key] = $field;
        }
        if (!$entity->isReadOnly()) {
            return $this->adapter->insert($entity->getEntityTable(), $fields);
        } else {
            throw new BambooORMReadOnlyException("Entity %s is read only",[$entity->getEntityName()]);
        }
    }

    /**
     * @param IEntity $entity
     * @return int
     * @throws BambooORMReadOnlyException
     * @throws BambooORMInvalidEntityException
     */
    public function update(IEntity $entity)
    {
        $new = $this->getEmptyEntity();
        if(!$entity instanceof $new){
            throw new BambooORMInvalidEntityException("Invalid instance of entity while performing update");
        }
        if (!$entity->isReadOnly()) {
            $fields = [];
	        $raw = $entity->toArray();
            foreach ($entity->getOwnerFields() as $field) {
                $fields[$field] = $raw[$field];
            }

            foreach ($entity->getPrimaryKeys() as $val) {
                unset($fields[$val]);
            }

            $this->reCache($entity);
            return $this->adapter->update($entity->getEntityTable(), $fields, $entity->getIds(), 'AND', false, true);
        } else {
            throw new BambooORMReadOnlyException("Entity %s is read only",[$entity->getEntityName()]);
        }
    }

    /**
     * @param CLang $lang
     */
    public function setLang($lang = null)
    {
        if($lang instanceof CLang){
            $this->lang = $lang;
        }
    }

    /**
     * @return CLang|bool|null
     */
    public function getLang()
    {
        return $this->lang;
    }
}