<?php

namespace LowAbstractionORM;

use SincAppSviluppo\domain\PdoAdapter;

class MySqlAdapter extends PdoAdapter {

	/**
	 * @return \PDO
	 * @throws \Exception
	 */
	public function getPdo(): \PDO {
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
}
