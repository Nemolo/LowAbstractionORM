<?php

namespace LowAbstractionORM\adapters\cache;

use LowAbstractionORM\ICacheAdapter;


/**
 * Class CDummyCache
 * @package bamboo\core\cache
 */
class DummyCacheAdapter implements ICacheAdapter
{
	public function get($key)
	{
		return null;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param null $expire
	 * @return bool
	 */
	public function set($key, $value, $expire = null)
	{
		return true;
	}

	/**
	 * @param $key
	 * @param $value
	 * @param null $expire
	 * @return bool
	 */
	public function add($key, $value, $expire = null)
	{
		return true;
	}

	/**
	 * @param $key
	 * @return bool
	 */
	public function delete($key)
	{
		return true;
	}

	/**
	 * @param int $delay
	 * @return bool
	 */
	public function flush($delay = 0)
	{
		return true;
	}
}