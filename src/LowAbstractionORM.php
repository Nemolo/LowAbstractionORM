<?php

namespace LowAbstractionORM;

use LowAbstractionORM\adapterso\cache\DummyCacheAdapter;
use LowAbstractionORM\factories\CRepoFactory;
use LowAbstractionORM\utils\ConfigHandler;

final class LowAbstractionORM {

    /**
     * @var IDBAdapter
     */
    private $dbAdapter;
    private $entityFactory;
    private $repoFactory;
    private $configHandler;

    protected $cacheAdapter;

    public function __construct(array $config)
    {
        $this->configHandler = new ConfigHandler($config);
        $this->dbAdapter = $this->configHandler->getDbAdapterInstance();
        $this->cacheAdapter = $this->configHandler->getCacheAdapterInstance();
    }

    private function setup($config) {
        //verify debug
        //rework all
        //verify cache configuration
        //get config from cache
        //fetch config paths for entities etc
    }

    public function repoFactory() {
        if(!$this->repoFactory) {
            $this->createRepoFactory();
        }
        return $this->repoFactory;
    }

    public function dbAdapter() {
        return $this->dbAdapter;
    }

    public function cacheAdapter() {
        return $this->cacheAdapter;
    }

    protected function createRepoFactory() {
        $this->repoFactory = new CRepoFactory($this);
    }
}