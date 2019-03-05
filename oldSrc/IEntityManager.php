<?php

namespace LowAbstractionORM;

/**
 * Interface IEntityManager
 * @package bamboo\core\db\pandaorm\entities
 */
interface IEntityManager
{
    public function findAll($limit, $offset);
    public function findBy(array $condition, $limit, $offset);
    public function findOne(array $id);
    public function findBySql($sql,$bind = array());
    public function findCountBySql($sql,$bind = array());
    public function query($sql,$bind = array());
    public function getEntityName();
    public function getEmptyEntity();
    public function delete(IEntity $entity);
    public function insert(IEntity $entity);
    public function update(IEntity $entity);

}