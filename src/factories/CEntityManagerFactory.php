<?php

namespace LowAbstractionORM\factories;


/**
 * Class CEntityManagerFactory
 * @package bamboo\core\db\pandaorm\factories
 */
class CEntityManagerFactory
{
    protected $factories = [];

    /**
     * @param $entityName
     * @param bool $lang
     * @return mixed
     * @throws \Throwable
     */
    public function create($entityName, $lang = true)
    {
        $entityName = ucfirst($entityName);
        if (!isset($this->factories[$entityName . $lang])) {
            $properties = \Monkey::app()->cacheService->getCache('misc')->get($entityName.'Map');
            if($properties === false) {
                try {
                    $properties = null;
                    foreach (\Monkey::app()->installedModules() as $module) {
                        $location = $module . "/domain/entities/maps/" . $entityName . "Map.json";
                        if (is_readable($location)) {
                            $properties = new CConfig($location);
                            $properties->load(false);
                            break;
                        }
                    }
                } catch (\Throwable $e) {
                    throw $e;
                }
                \Monkey::app()->cacheService->getCache('misc')->set($entityName.'Map',$properties);
            }

            if ($lang instanceof CLang) {
                $this->factories[$entityName . $lang] = new CEntityManager($properties, \Monkey::app()->dbAdapter, $entityName, $lang);
            } else if ($lang === true) {
                $this->factories[$entityName . $lang] = new CEntityManager($properties, \Monkey::app()->dbAdapter, $entityName, \Monkey::app()->getLang());
            } else {
                $this->factories[$entityName . $lang] = new CEntityManager($properties, \Monkey::app()->dbAdapter, $entityName);
            }
        }

        return $this->factories[$entityName . $lang];
    }
}