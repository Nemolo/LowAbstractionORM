<?php
/**
 * Created by PhpStorm.
 * User: tdlem
 * Date: 13/01/2019
 * Time: 12:21
 */

namespace LowAbstractionORM\utils;

use LowAbstractionORM\adapters\cache\RedisCacheAdapter;
use LowAbstractionORM\adapters\cache\DummyCacheAdapter;
use LowAbstractionORM\adapters\MySQLAdapter;
use LowAbstractionORM\exceptions\ORMConfigException;
use LowAbstractionORM\ICacheAdapter;
use LowAbstractionORM\ORMConfig;

class ConfigHandler
{
    /**
     * @var ORMConfig
     */
    protected $parsedConfig;

    public function __construct(array $config)
    {
        $this->validateConfig($config);
    }

    public function normalizeConfig(array $config): array {
        $baseConfig = [
            'IS_DEBUG' => false,
            'APP_ROOT' => null,
            'DB_CONN' => [
                'ADAPTER' => null,
                'ENGINE' => 'mysql',
                'HOST' => 'localhost',
                'USER' => 'root',
                'PASS' => '',
                'PORT' => 3306,
                'NAME' => null
            ],
            'CACHE_CONN' => [
                'ADAPTER' => null,
                'ENGINE' => 'redis',
                'HOST' => 'localhost',
                'PORT' => 6379,
                'PREFIX' => 'LowAbstractionORM_'
            ],
            'ENTITIES' => [

            ],
            'MAPS' => [

            ],
            'TEMP_FOLDER' => $config['APP_ROOT'].DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, ['var', 'laORM'])
        ];

        return array_merge($baseConfig, $config);
    }

    public function validateConfig(array $config) {
        //todo validate config or throw exception?
        return true;
    }

    protected function parseConfig(array $config) {

        $config = $this->normalizeConfig($config);

        if($config['CACHE_CONN']) {

            switch ($config['CACHE_CONN']['ENGINE']) {
                case 'redis': {
                    $config['CACHE_ADAPTER'] = new RedisCacheAdapter();
                    break;
                }
                case 'file': {
                    $config['CACHE_ADAPTER'] = new \Redis('blabla');
                    break;
                }
                default: {
                    $config['CACHE_ADAPTER'] = new DummyCacheAdapter();
                }
            }
        }
        /** @var ICacheAdapter $cacheAdapter */
        $cacheAdapter = $config['CACHE_ADAPTER'];
        if(!$config['IS_DEBUG']) {
            // do not fetch
            $parsedConfig = $cacheAdapter->get('LowAbstractionORMConfig');
            if($parsedConfig) {
                $this->parsedConfig = $parsedConfig;
                return;
            }
        }

        $this->createParsedConfig($config);

        foreach ($config as $key => $value) {
            switch (strtoupper($key)) {
                case 'IS_DEBUG':
                    if(!is_bool($value)) throw new ORMConfigException('IS_DEBUG');
            }
        }
    }

    protected function createParsedConfig(array $config) {
        //todo create config class? useful?
    }

    protected function fetchParsedConfig(array $config) {
        //todo fetch from disk/cache, useful function?
    }

    protected function saveParsedConfig(array $config) {
        //todo save parsed config, useful function?
    }

    public function config(): ORMConfig {
        return $this->parsedConfig;
    }
}