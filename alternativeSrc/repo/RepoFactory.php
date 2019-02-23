<?php

namespace SincAppSviluppo\domain;


use SincAppSviluppo\domain\repo\UserProfileRepo;

/**
 * Class RepoFactory
 * @package data
 *
 * @property UserProfileRepo $userProfileRepo
 */
class RepoFactory {

    private $connection;
    private $repos = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param $key
     * @return ARepo
     * @throws \Exception
     */
    public function __get($key) {
        return $this->getRepo($key);
    }

	/**
	 * @param $entityName
	 *
	 * @return ARepo
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
    public function getRepo($entityName): ARepo {
        if(!isset($this->repos[$entityName])) {
            $entityReflectionclass = new \ReflectionClass($entityName);
            if($entityReflectionclass->getParentClass()->getName() != AEntity::class)
                throw new \Exception('Can\'t provide a repo to something that is not an Entity');
            
            $repoClass = $entityReflectionclass->getConstant('REPO');
            $this->repos[$entityName] = new $repoClass($this->connection, $entityName);
        }
        return $this->repos[$entityName];
    }
}