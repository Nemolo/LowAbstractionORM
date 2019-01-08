<?php
namespace LowAbstractionORM\repositories;

use LowAbstractionORM\entities\AEntity;
use LowAbstractionORM\entities\CEntityManager;
use LowAbstractionORM\IEntity;
use LowAbstractionORM\IRepo;

/**
 * Class ARepo
 * @package bamboo\core\db\pandaorm\repositories
 *
 * @author Bambooshoot Team <emanuele@bambooshoot.agency>
 *
 * @copyright (c) Bambooshoot snc - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 *
 * @date 10/02/2016
 * @since 1.0
 */
abstract class ARepo implements IRepo
{
    /**
     * @var array
     */
    protected $em = [];

    /**
     * @var AApplication
     */
    protected $app;

    /**
     * @param CEntityManager $em
     * @param AApplication $application
     */
    public function __construct(CEntityManager $em, AApplication $application)
    {
        $this->em = $em;
        $this->app = $application;
    }

    /**
     * @return array
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * @param array $id
     * @param string $entity
     * @return AEntity|null
     */
    public function findOne(array $id)
    {
        return $this->em()->findOne($id);
    }

    /**
     * @return array|CEntityManager
     */
    public function em()
    {
        return $this->em;
    }

    /**
     * @param $string
     * @return \bamboo\core\db\pandaorm\entities\AEntity
     */
    public function findOneByStringId($string)
    {
        $sample = $this->getEmptyEntity();
        if ($sample->readId($string)) {
            return $this->em()->fillOne($sample);
        } else return null;
    }

    /**
     * @return AEntity
     */
    public function getEmptyEntity()
    {
        return $this->em()->getEmptyEntity();
    }

    /**
     * @param array $conditions
     * @param string $orderBy
     * @return null|AEntity
     */
    public function findOneBy(array $conditions, $orderBy = "")
    {
        return $this->findBy($conditions, "LIMIT 0,1", $orderBy)->getFirst();
    }

    /**
     * @param array $condition
     * @param $limit
     * @param $orderBy
     * @param $entity
     * @return \bamboo\core\base\CObjectCollection
     */
    public function findBy(array $condition, $limit = "", $orderBy = "")
    {
        return $this->em()->findBy($condition, $limit, $orderBy);
    }

    /**
     * @param string $limit
     * @param string $orderBy
     * @return mixed
     */
    public function findAll($limit = "", $orderBy = "")
    {
        return $this->em()->findAll($limit, $orderBy);
    }

    /**
     * @param $sql
     * @param array $bind
     * @return CObjectCollection
     */
    public function findBySql($sql, $bind = [])
    {
        return $this->em()->findBySql($sql, $bind);
    }

    /**
     * @param $sql
     * @param array $bind
     * @return AEntity|null
     */
    public function findOneBySql($sql,$bind = [])
    {
        return $this->findBySql($sql, $bind)->getFirst();
    }

    /**
     * @param IEntity $entity
     * @return int
     * @throws \bamboo\core\exceptions\RedPandaORMInvalidEntityException
     */
    public function delete(IEntity $entity)
    {
        return $entity->delete();
    }

    /**
     * @param IEntity $entity
     * @return int
     * @throws \bamboo\core\exceptions\RedPandaORMInvalidEntityException
     */
    public function update(IEntity $entity)
    {
        return $entity->update();
    }

    /**
     * @param IEntity $entity
     * @return int
     * @throws \bamboo\core\exceptions\RedPandaORMInvalidEntityException
     */
    public function insert(IEntity $entity)
    {
        return $entity->insert();
    }

    /**
     * @param array $limit
     * @param array $orderBy
     * @param array $params
     * @param array $args
     * @return CObjectCollection
     * @throws RedPandaPaginationException
     * @throws RedPandaRepositoryException
     */
    public function listBy($method, array $limit, array $orderBy, array $params, array $args)
    {
        if ($method == null || empty($method)) {
            throw new RedPandaRepositoryException('invalid data fetch method');
        }

        if (empty($limit)) {
            $limit = array(0, 30);
        }

        if (isset($limit[0])) {
            if (!is_numeric($limit[0])) {
                throw new RedPandaPaginationException('invalid limit value (not int)');
            }
            if ($limit[0] < 0) {
                throw new RedPandaPaginationException('invalid limit value ( <0 )');
            }
        }

        if (isset($limit[1])) {
            if (!is_numeric($limit[1])) {
                throw new RedPandaPaginationException('invalid limit value (not int)');
            }
            if ($limit[1] < 1) {
                throw new RedPandaPaginationException('invalid limit value ( < 1 )');
            }
        } else {
            $limit[1] = 30;
        }

        if (is_callable(array($this, 'listBy' . $method))) {
            $objc = $this->{'listBy' . $method}($limit, $orderBy, $params, $args);
        } else {
            throw new RedPandaPaginationException('method listBy' . $method . ' not found in Repository');
        }

        if ($objc instanceof CObjectCollection) {
            $objc->rewind();
            return $objc;
        } else {
            return $objc;
        }
    }

    /**
     * @param $method
     * @param array $params
     * @param array $args
     * @return bool
     * @throws RedPandaRepositoryException
     */
    public function fetchEntityBy($method, array $params, array $args)
    {
        if ($method == null || empty($method)) {
            throw new RedPandaRepositoryException('invalid data fetch method');
        }

        if (is_callable(array($this, 'fetchEntityBy' . $method))) {
            return $this->{'fetchEntityBy' . $method}($params, $args);
        } else {
            return false;
        }
    }

    /**
     * @param array $params
     * @param array $args
     * @return mixed
     * @throws RedPandaPaginationException
     * @throws RedPandaRepositoryException
     */
    public function fetchValueBy($method, array $params, array $args)
    {
        if ($method == null || empty($method)) {
            throw new RedPandaRepositoryException('invalid data fetch method');
        }
        if (is_callable(array($this, 'fetchValueBy' . $method))) {
            return $this->{'fetchValueBy' . $method}($params, $args);
        } else {
            return false;
        }
    }

    /**
     * @param $lister
     * @param array $params
     * @return mixed
     * @throws RedPandaRepositoryException
     */
    public function countBy($lister, array $params)
    {
        if ($lister == null || empty($lister)) {
            throw new RedPandaRepositoryException('invalid data fetch method');
        }

        $objc = $this->{'countBy' . $lister}($params);

        if ($objc instanceof CObjectCollection) {
            $objc->rewind();
            return $objc;
        } else {
            return $objc;
        }
    }

    /**
     * TODO implement PDO
     * @param array $orderBy
     * @return string
     */
    protected function orderBy(array $orderBy)
    {
        if (empty($orderBy)) {
            return " ";
        }

        foreach ($orderBy as $key => $val) {
            if(is_object($val)) $val = (array) $val;
            $orderBy[$key] = trim(($val['field'] . " " . $val['order']));
        }

        return (empty($orderBy) ? " " : "ORDER BY " . implode(',', $orderBy));
    }

    /**
     * TODO implement PDO
     * @param array $limit
     * @return string
     */
    protected function limit(array $limit)
    {
        return " LIMIT " . $limit[0] . "," . $limit[1];
    }

    protected function getEntity($objOrString) {
        $nameRepo = substr(str_replace('Repo', '', get_class($this)), 2);
        $R = \Monkey::app()->repoFactory->create($nameRepo);
        if (is_object($objOrString)) {
            $nameObj = substr(get_class($objOrString), 2);
            if ($nameRepo !== $nameObj)
                throw new BambooException('The given Entity must have the same Name of the used Repo');
        } elseif (is_string($objOrString) || is_numeric($objOrString)) {
            return $R->findOneByStringId($objOrString);
        } elseif (is_array($objOrString)) {
            $oc = $R->findBy($objOrString);
            if (1 < $oc->count()) throw new BambooException('Given parameters refer to more than one Entity');
            return $oc->getFirst();
        }
        throw new BambooException('Not recognized data');
    }

    /**
     * @param array $limit
     * @param array $orderBy
     * @param array $params
     * @param array $args
     * @param string $entity
     * @return mixed
     */
    public function listByAll(array $limit, array $orderBy, array $params, array $args)
    {
        return $this->em()->findAll($this->limit($limit), $this->orderBy($orderBy));
    }
}