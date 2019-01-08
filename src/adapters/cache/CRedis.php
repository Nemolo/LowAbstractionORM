<?php

namespace LowAbstractionORM\adapters\cache;

use LowAbstractionORM\ICacheAdapter;

/**
 * Class CRedis
 * @package LowAbstractionORM\adapters\cache
 */
class CRedis implements ICacheAdapter
{
    /**
     * @var \Redis
     */
    protected $redis;
    protected $prefix;
    protected $config;
    private $inTransaction = false;

    public function __construct($config = [])
    {

    }

    /**
     * @param $host
     * @param int $port
     * @param $db
     * @return bool
     * @throws BambooException
     */
    public function addServer($host, $port = 6379, $db = 1)
    {
        $res = $this->redis->connect($host, $port);
        if (!$res) throw new BambooException('Failed to connect to cache service');
        else {
            $this->redis->select($db);
            return true;
        }
    }

    public function reconnect()
    {
        $this->redis = new \Redis();
        $this->addServer($this->config['host'] ?? 'localhost', $this->config['port'] ?? 6379, $this->config['db'] ?? 1);
    }

    /**
     * @return mixed
     */
    public function getPrimitive()
    {
        return $this->redis;
    }

    /**
     * @return string
     */
    public function ping()
    {
        return $this->redis->ping();
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        try {
            return $this->redis && $this->redis->ping() == '+PONG';
        } catch (\Throwable $e) {
        }
        return false;
    }

    /**
     * @param $key
     * @return mixed
     * FIXME GENERATE ERROR IN CASE OF AUTOMATIC TRANSACTION
     */
    public function get($key)
    {
        if ($this->inTransaction()) return false;
        $value = $this->redis->get($this->calcKey($key));
        return $value === false || $value instanceof \Redis ? false : unserialize($value);
    }

    /**
     * @return bool
     */
    public function inTransaction()
    {
        return $this->inTransaction;
    }

    protected function calcKey($key)
    {
        if (strpos($key, $this->getPrefix()) !== 0) {
            $key = $this->getPrefix() . $key;
        }
        return $key;
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param $keys
     * @return mixed
     * FIXME add app name to keys
     */
    public function mget($keys)
    {
        $res = $this->redis->mget($keys);
        foreach ($res as $key => $val) {
            //todo
        }
        return;
    }

    /**
     * @param $key
     * @param $value
     * @param null $expire
     * @return bool
     */
    public function add($key, $value, $expire = null)
    {
        return $this->set($this->calcKey($key), $value, $expire);
    }

    /**
     * @param $key
     * @param $value
     * @param null $expire
     * @return bool
     */
    public function set($key, $value, $expire = null)
    {
        if (is_null($expire) || $expire == 0) {
            return $this->redis->set($this->calcKey($key), serialize($value));
        } else {
            return $this->redis->set($this->calcKey($key), serialize($value), $expire);
        }

    }

    /**
     * @param $key
     * @return bool
     */
    public function delete($key)
    {
        $this->redis->delete($this->calcKey($key));
        return true;
    }

    /**
     * @param int $delay
     * @return bool
     */
    public function flush($delay = 0)
    {
        return $this->redis->flushDB();
    }

    /**
     * @return string
     */
    public function getStats()
    {
        return $this->redis->info();
    }

    /**
     * @return array
     */
    public function getAllKeys()
    {
        return $this->redis->getKeys('.*');
    }

    /**
     * @param $key
     * @return mixed
     */
    public function lpop($key)
    {
        return unserialize($this->redis->lPop($this->calcKey($key)));
    }

    /**
     * @param $key
     * @param $val
     * @return int
     */
    public function lpush($key, $val)
    {
        return $this->redis->lPush($this->calcKey($key), serialize($val));
    }

    /**
     * @param $key
     * @param $val
     * @param int $num
     * @return int
     */
    public function lrem($key, $val, $num = null)
    {
        return $this->redis->lRem($this->calcKey($key), serialize($val), $num);
    }

    /**
     * @param $key
     * @return int
     */
    public function llen($key)
    {
        return $this->redis->lLen($this->calcKey($key));
    }

    /**
     * @param $key
     * @return mixed
     */
    public function rpop($key)
    {
        return unserialize($this->redis->rPop($this->calcKey($key)));
    }

    /**
     * @param $key
     * @param $val
     * @return int
     */
    public function rpush($key, $val)
    {
        return $this->redis->rPush($this->calcKey($key), serialize($val));
    }

    /**
     * @param $key
     * @param $start
     * @param $end
     * @return array
     */
    public function lrange($key, $start, $end)
    {
        return $this->redis->lRange($this->calcKey($key), $start, $end);
    }

    /**
     * @return \Redis
     */
    public function beginTransaction()
    {
        $this->inTransaction = true;
        return $this->redis->multi();
    }

    /**
     *
     */
    public function commit()
    {
        $this->inTransaction = false;
        $this->redis->exec();
        return true;
    }

    /**
     *
     */
    public function rollback()
    {
        $this->inTransaction = false;
        $this->redis->discard();
        return true;
    }

    /**
     *
     */
    public function __destruct()
    {
        try {
            $this->redis->close();
        } catch (\Throwable $e) {
            logDestructError($e);
        }

    }
}