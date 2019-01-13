<?php

namespace LowAbstractionORM;

/**
 * Interface ICacheAdapter
 * @package LowAbstractionORM
 */
interface ICacheAdapter
{
    public function get($key);
    public function set($key, $value, $expire = 0);
    public function add($key, $value, $expire = 0);
    public function delete($key);
    public function flush();
}