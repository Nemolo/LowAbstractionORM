<?php

namespace LowAbstractionORM\factories;

use LowAbstractionORM\LowAbstractionORM;

/**
 * Class CRepoFactory
 * @package LowAbstractionORM\factories
 */
class CRepoFactory
{
	private $repos = [];
    private $ormInstance;

	public function __construct(LowAbstractionORM $ormInstance)
    {
        $this->ormInstance = $ormInstance;
    }

    /**
     * @return bool
     */
	public function beginTransaction() {
	    \Monkey::app()->dbAdapter->beginTransaction();
	    \Monkey::app()->cacheService->getCache('entities')->beginTransaction();
	    return true;
    }

    /**
     * @return bool
     * @throws \bamboo\core\exceptions\BambooDBALException
     */
    public function inTransaction() {
        return \Monkey::app()->dbAdapter->hasTransaction() && \Monkey::app()->cacheService->getCache('entities')->inTransaction();
    }

    /**
     * @return bool
     * @throws \bamboo\core\exceptions\BambooDBALException
     */
    public function commit() {
        return \Monkey::app()->dbAdapter->commit() && \Monkey::app()->cacheService->getCache('entities')->commit();
    }

    /**
     * @return bool
     * @throws \bamboo\core\exceptions\BambooDBALException
     */
    public function rollback() {
        return \Monkey::app()->dbAdapter->rollBack() && \Monkey::app()->cacheService->getCache('entities')->rollBack();
    }

    /**
     * @param $entityName
     * @param bool $lang
     * @return mixed
     */
	public function create($entityName, $lang = true)
	{
		if (!isset($this->repos[$entityName.$lang])) {
			$em = \Monkey::app()->entityManagerFactory->create($entityName, $lang);
			$name = "\\bamboo\\domain\\repositories\\C" . $em->getEntityName() . "Repo";
			/**
			 * Generic Repository if no specific one is defined
			 */
			if (!class_exists($name)) {
				$name = "\\bamboo\\core\\db\\pandaorm\\repositories\\CRepo";
			}
			$this->repos[$entityName.$lang] = new $name($em, \Monkey::app());
		}
		return $this->repos[$entityName.$lang];
	}
}