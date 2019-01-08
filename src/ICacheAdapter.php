<?php

namespace LowAbstractionORM;

/**
 * Interface IEntity
 * @package bamboo\core\db\pandaorm\entities
 */
interface ICacheAdapter
{
    public function get($key);
    public function set($key, $value, $expire = 0);
    public function add($key, $value, $expire = 0);
    public function delete($key);
    public function flush();
}