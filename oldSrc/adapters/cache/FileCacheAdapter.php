<?php

namespace LowAbstractionORM\adapters\cache;

use LowAbstractionORM\ICacheAdapter;

/**
 * Class CFileCache
 * @package LowAbstractionORM\adapters\cache
 */
class FileCacheAdapter implements ICacheAdapter
{
	protected $prefix;
	protected $fileName;
	protected $cache;
	protected $changed = false;

	public function __construct($config) {

	}

	public function getPrefix()
	{
		return $this->prefix;
	}

    /**
     * @param $host
     * @param $port
     * @return bool
     */
	public function addServer($host,$port){
		if($host == "__default__") $host = __DIR__."/../../temp/";
		if(!file_exists($host.$port)) {
			$this->fileName = $host.$port;
			$this->cache = [];
			return true;
		}elseif(is_readable($host.$port)) {
			$this->fileName = $host.$port;
			$this->cache = unserialize(file_get_contents($this->fileName));
			return true;
		} else return false;
	}

	/**
	 * @param $key
	 * @return mixed
	 */
	public function get($key)
	{
	    $key = $this->calcKey($key);
		if(isset($this->cache[$key])) {
			if(($this->cache[$key]['expire'] == 0 || $this->cache[$key]['expire'] > time())) return $this->cache[$key]['value'];
			else unset($this->cache[$key]);
		} return false;
	}

	/**
	 * @param $keys
	 * @return mixed
	 * FIXME add app name to keys
	 */
	public function mget($keys)
	{
		return;
		$res = $this->memcached->getMulti($keys);
		foreach($res as $key => $val){
			$res[$key] = unserialize($this->memcached);
		}
		return ;
	}

	private function _set($key,$value,$expire) {
        $this->changed = true;
        $this->cache[$key]['value'] = $value;
        if(!is_numeric($expire) && $expire != 0) {
            $this->cache[$key]['expire'] = time() + $expire;
        } else {
            $this->cache[$key]['expire'] = 0;
        }
        return true;
    }
	/**
	 * @param $key
	 * @param $value
	 * @param int $expire
	 * @return bool
	 */
	public function set($key, $value, $expire = 0)
	{
	    $key = $this->calcKey($key);
        $this->_set($key,$value,$expire);
	}

	/**
	 * @param $key
	 * @param $value
	 * @param null $expire
	 * @return bool
	 */
	public function add($key, $value, $expire = null)
	{
	    $key = $this->calcKey($key);
		if($this->get($key) == false) {
			return $this->_set($key,$value,$expire);
		} else {
			return false;
		}
	}

	/**
	 * @param $key
	 * @return bool
	 */
	public function delete($key)
	{
	    $key = $this->calcKey($key);
		if(isset($this->cache[$key])) {
			$this->changed = true;
			unset($this->cache[$key]);
		}
		return true;
	}

	/**
	 * @param int $delay
	 * @return bool
	 */
	public function flush($delay = 0)
	{
		$this->changed = true;
		$this->cache = [];
		$this->__destruct();
		return true;
	}

	/**
	 * @return array
	 * //todo implement this
	 */
	public function getStats()
	{
		return ['chiavi'=>count($this->cache)];
	}

	/**
	 * @return array
	 */
	public function getAllKeys()
	{
		return array_keys($this->cache);
	}

	public function __destruct()
	{
		if($this->changed) {
			if(!file_exists($this->fileName)){
				touch($this->fileName);
				chmod($this->fileName, 0777);
                //TODO perchèèèèèèèèèèèèèèè
                //hown($this->fileName,'apache');
			}
			if(is_writable($this->fileName) === true) {
				$f = fopen($this->fileName,"w");
				$bytes = fwrite($f,serialize($this->cache));
				fclose($f);
				if($bytes != false) return true;
			}
			throw new RedPandaConfigException('Could not write file');
		}
		return false;
	}
}