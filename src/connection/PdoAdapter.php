<?php

namespace SincAppSviluppo\domain;

use LowAbstractionORM\IDBAdapter;

abstract class PdoAdapter implements IDBAdapter {
	/** @var \PDO $pdo */
	protected $pdo;
	protected $options;

	/**
	 * Connection constructor.
	 *
	 * @param array $options
	 */
	public function __construct( array $options ) {
		$this->options = $options;
	}

	abstract function getPdo(): \PDO;

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
	 *
	 * @return AEntity|null
	 * @throws \Exception
	 */
	protected function getRow( $query, array $data = [], $className = null ) {
		if ( $className ) {
			$res = $this->query( $query, $data, false, \PDO::FETCH_CLASS, $className );
		} else {
			$res = $this->query( $query, $data, false );
		}
		if ( $res ) {
			return $res;
		} else {
			return null;
		}
	}

	/**
	 * @param $query
	 * @param array $data
	 * @param null $className
	 *
	 * @return array
	 * @throws \Exception
	 */
	protected function getRows( $query, array $data = [], $className = null ) {
		if ( $className ) {
			return $this->query( $query, $data, true, \PDO::FETCH_CLASS, $className );
		} else {
			return $this->query( $query, $data );
		}
	}

	public static function build( array $config ): IDBAdapter {
		return new static( $config );
	}

	public function test(): bool {
		$res = $this->query( "SELECT 1 as re", [], false );
		if ( $res['re'] === 1 ) {
			return true;
		}
		throw new \Exception( 'Problems with connection' );
	}

	public function connect() {
		$this->getPdo();
	}

	public function disconnect() {
		$this->commit();
		$this->pdo = null;
	}

	public function query( $sql, array $bind = [], $all = true, $fetchMode = \PDO::FETCH_ASSOC, $fetchArgs = null ) {
		$stmt = $this->getPdo()->prepare( $sql );
		if ( $stmt->execute( $bind ) ) {
			if ( $all ) {
				return $stmt->fetchAll( $fetchMode, $fetchArgs );
			} else {
				return $stmt->fetch( $fetchMode, $fetchArgs );
			}
		} else {
			throw new \Exception( implode( " - ", $stmt->errorInfo() ), $stmt->errorCode() );
		}
	}
}
