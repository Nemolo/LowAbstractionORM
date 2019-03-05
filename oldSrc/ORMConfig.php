<?php
/**
 * Created by PhpStorm.
 * User: tdlem
 * Date: 13/01/2019
 * Time: 12:21
 */

namespace LowAbstractionORM;


use LowAbstractionORM\entities\AEntity;
use LowAbstractionORM\entities\EntityConfig;
use LowAbstractionORM\exceptions\LowAbstractionORMException;

class ORMConfig {

    private $entities;

    /**
     * @param string|AEntity $entity
     * @return EntityConfig
     * @throws LowAbstractionORMException
     */
    public function entity($entity): EntityConfig {
        if(is_object($entity)) {
            $entity = get_class($entity);
        }
        if(!isset($this->entities)) {
            throw new LowAbstractionORMException("Entity Configuration not found");
        }
        return $this->entities[$entity];
    }
}