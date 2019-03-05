<?php

namespace LowAbstractionORM;

use LowAbstractionORM\entities\AEntity;
use LowAbstractionORM\factories\CRepoFactory;
use LowAbstractionORM\repositories\ARepo;
use LowAbstractionORM\utils\ConfigHandler;

final class LowAbstractionORM {

    /**
     * @var IDBAdapter
     */
    private $dbAdapter;
	protected $cacheAdapter;
	/**
	 * @var ARepo array
	 */
	protected $repos = [];

    private $configHandler;



    public function __construct(array $config)
    {
        $this->configHandler = new ConfigHandler($config);
        $this->dbAdapter = $this->configHandler->getDbAdapterInstance();
        $this->cacheAdapter = $this->configHandler->getCacheAdapterInstance();
    }

	/**
	 * @param $className
	 *
	 * @return ARepo
	 */
    public function getRepo($className): ARepo {
        if(!isset($this->repos[$className])) {
        	$repoName = $className::REPO;
            $this->repos[$className] = new $repoName($this->dbAdapter);
        }
        return $this->repos[$className];
    }

	/**
	 * @return IDBAdapter
	 */
    public function dbAdapter(): IDBAdapter {
        return $this->dbAdapter;
    }

	/**
	 * @return ICacheAdapter
	 */
    public function cacheAdapter(): ICacheAdapter {
        return $this->cacheAdapter;
    }
}