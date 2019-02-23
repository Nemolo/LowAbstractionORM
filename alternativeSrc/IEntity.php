<?php

namespace LowAbstractionORM;



/**
 * Interface IEntity
 * @package bamboo\core\db\pandaorm\entities
 */
interface IEntity extends \Serializable, \JsonSerializable
{
	public function getIds();
    public function getClassName();
    public function getEntityName();
    public function getEntityTable();
    public function getPrimaryKeys();
    public function getOwnerFields();

	public function insert();
	public function update();
	public function delete();

	public function toArray();
}
