<?php

namespace LowAbstractionORM;

/**
 * Interface IDBAdapter
 * @package bamboo\core\db\pandaorm\adapter
 */
interface IDBAdapter
{

    public static function build(array $config): self;

    /** @return mixed */
    public function connect();

    /** @return mixed */
    public function disconnect();

    /**
     * @param string $sql
     * @param array $options
     *
     * @return mixed
     */
    public function prepare($sql, array $options = array());

    /**
     * @param array $parameters
     *
     * @return mixed
     */
    public function execute(array $parameters = array());

    /**
     * @param null $fetchStyle
     * @param null $cursorOrientation
     * @param null $cursorOffset
     *
     * @return mixed
     */
    public function fetch($fetchStyle = null, $cursorOrientation = null, $cursorOffset = null);

    /**
     * @param null $fetchStyle
     * @param int  $column
     *
     * @return mixed
     */
    public function fetchAll($fetchStyle = null, $column = 0);

    /**
     * @param string $table
     * @param array $bind
     * @param string $limit
     * @param string $orderBy
     * @param string $boolOperator
     * @return mixed
     */
    public function select($table, array $bind = array(), $limit = "", $orderBy = "", $boolOperator = 'AND');

    /**
     * @param $table
     * @param array $bind
     * @param string $boolOperator
     *
     * @return mixed
     */
    public function selectCount($table, array $bind, $boolOperator = "AND");

    /**
     * @param string $table
     * @param array $bind
     *
     * @return int
     */
    public function insert($table, array $bind);

    /**
     * @param $table
     * @param array $bind
     * @param array $where
     * @param string $boolOperator
     * @return int
     */
    public function update($table, array $bind, array $where, $boolOperator = 'AND');

    /**
     * @param $table
     * @param array $bind
     * @param string $boolOperator
     * @return mixed
     */
    public function delete($table, array $bind, $boolOperator = 'AND');

    /**
     * @param $sql
     * @param array $bind
     * @return mixed
     */
    public function query($sql, array $bind);

    /**
     * @param $table
     * @param string $mode
     * @return mixed
     */
    public function lock($table, $mode = 'WRITE');

    /**
     * @return mixed
     */
    public function unlock();

	/**
	 * @return mixed
	 */
	public function beginTransaction();

	/**
	 * @return mixed
	 */
	public function commit();

	/**
	 * @return mixed
	 */
	public function rollback();
}