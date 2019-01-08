<?php

namespace LowAbstractionORM;

use LowAbstractionORM\adapterso\cache\CDummyCache;
use LowAbstractionORM\factories\CRepoFactory;

final class LowAbstractionORM {

    /**
     * @var IDBAdapter
     */
    private $dbAdapter;
    private $entityFactory;
    private $repoFactory;

    protected $cacheAdapter;

    public function __construct(array $config, ICacheAdapter $cacheAdapter = null)
    {
        $this->setup($config);
        if(!$cacheAdapter && !$this->cacheAdapter) {
            $this->cacheAdapter = new CDummyCache();
        }
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