<?php

namespace LowAbstractionORM\adapters\cache;

use LowAbstractionORM\ICacheAdapter;

/**
 * Class CMemcached
 * @package LowAbstractionORM\adapters\cache
 */
class CMemcached implements ICacheAdapter
{
	protected $memcached;
	protected $prefix;

	public function __construct($config = []) {

	}

	public function getPrefix()
	{
		return $this->prefix;
	}

	/**
	 * @param $host
	 * @param $port
	 * @param $weight
	 * @return bool
	 */
	public function addServer($host,$port,$weight){
		return $this->memcached->addServer($host,$port,$weight);
	}

	/**
	 * @param $key
	 * @return mixed
	 */
	public function get($key)
	{
		$value = $this->memcached->get($this->calcKey($key));
		return $value == false ? $value : unserialize($value);
	}

	/**
	 * @param $keys
	 * @return mixed
	 * FIXME add app name to keys
	 */
	public function mget($keys)
	{
		$res = $this->memcached->getMulti($keys);
		foreach($res as $key => $val){
			$res[$key] = unserialize($this->memcached);
		}
		return ;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param null $expire
	 * @return bool
	 */
	public function set($key, $value, $expire = null)
	{
		return $this->memcached->set($this->calcKey($key),serialize($value),$expire);
	}

	/**
	 * @param $key
	 * @param $value
	 * @param null $expire
	 * @return bool
	 */
	public function add($key, $value, $expire = null)
	{
		return $this->memcached->add($this->calcKey($key),serialize($value),$expire);
	}

	/**
	 * @param $key
	 * @return bool
	 */
	public function delete($key)
	{
		return $this->memcached->delete($this->calcKey($key));
	}

	/**
	 * @param int $delay
	 * @return bool
	 */
	public function flush($delay = 0)
	{
		return $this->memcached->flush($delay);
	}

	/**
	 * @return array
	 */
	public function getStats()
	{
		return $this->memcached->getStats();
	}

	/**
	 * @return array
	 */
	public function getAllKeys()
	{
		return $this->memcached->getAllKeys();
	}
}