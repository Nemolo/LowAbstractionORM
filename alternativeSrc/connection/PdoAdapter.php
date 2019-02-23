<?php

namespace SincAppSviluppo\domain;

use LowAbstractionORM\IDBAdapter;

abstract class PdoAdapter implements IDBAdapter {
    /** @var \PDO $pdo */
    private $pdo;

    private $options;

    /**
     * Connection constructor.
     * @param array $options
     */
    public function __construct(array $options) {
        $this->options = $options;
    }

    /**
     * @return \PDO
     * @throws \Exception
     */
    public function getPdo() {
        if(!isset($this->pdo)) {
            switch ($this->options['driver']) {
                case 'pdo_mysql': {
                    $connectionString = "mysql:host=".$this->options['host'].";dbname=".$this->options['dbname'];
                    $this->pdo = new \PDO($connectionString,$this->options['user'],$this->options['password']);
                    $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                    $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
                    break;
                }
                default:
                    throw new \Exception('Unhandled driver');
            }
        }
        return $this->pdo;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function beginTransaction() {
        return $this->getPdo()->beginTransaction();
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function commit() {
        return $this->getPdo()->commit();
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function rollBack() {
        return $this->getPdo()->rollBack();
    }

    /**
     * @param $query
     * @param array $data
     * @param null $className
     * @return AEntity|null
     * @throws \Exception
     */
    protected function getRow($query, array $data = [], $className = null)
    {
        $asd = $this->pdo()->prepare($query);
        $asd->execute($data);
        if ($className) $res = $asd->fetchObject($className);
        else $res = $asd->fetch(\PDO::FETCH_ASSOC);
        if ($res) return $res;
        else return null;
    }

    /**
     * @param $query
     * @param array $data
     * @param null $className
     * @return array
     * @throws \Exception
     */
    protected function getRows($query, array $data = [], $className = null)
    {
        $stmt = $this->pdo()->prepare($query);
        $stmt->execute($data);
        if ($className) return $stmt->fetchAll(\PDO::FETCH_CLASS, $className);
        else return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
