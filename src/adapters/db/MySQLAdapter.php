<?php

namespace LowAbstractionORM\adapters;

use LowAbstractionORM\IDBAdapter;

/**
 * Class MySQLAdapter
 * @package LowAbstractionORM\adapters
 */
class MySQLAdapter implements IDBAdapter
{
	/** @var \PDO */
	protected $connection;

	/** @var \PDOStatement */
	protected $statement;

	/** @var int */
	protected $fetchMode = \PDO::FETCH_ASSOC;


	protected $map;

	protected $transaction = false;


	protected $queryCount = 0;
	protected $queries = [];
	protected $isProfiling = false;
	/**
	 * @return bool
	 */
	public function isConnected()
	{
		return (bool)$this->connection;
	}

	/**
	 * Reconnect
	 * @return $this|mixed|CMySQLAdapter
	 * @throws BambooDBALException
	 */
	public function reconnect()
	{
		$this->disconnect();

		return $this->connect();
	}

	/**
	 * Disconnect
	 */
	public function disconnect()
	{
	    try {
            $this->statement = null;
            $this->connection = null;
        } catch (\Throwable $e) {
	        if(strStr($e->getMessage(), 'send of 9 bytes failed with errno=32 Broken pipe') !== false ) return;
	        throw $e;
        }
	}

	/**
	 * @return int
	 */
	public function queryCount()
	{
		return $this->queryCount;
	}

	/**
	 * Create new Connection or returns existing one;
	 * @return $this|mixed
	 * @throws BambooDBALException
	 */
	public function connect()
	{
		if ($this->connection) {
			return $this;
		}

		$connString = $this->getComponentOption('engine') . ':host=' . $this->getComponentOption('host') . ';dbname=' . $this->getComponentOption('name') . ";charset=utf8";

		try {
		    $options = [
                \PDO::ATTR_TIMEOUT => 3,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::MYSQL_ATTR_FOUND_ROWS => true,
                \PDO::ATTR_PERSISTENT => false
            ];
			$this->connection = new \PDO($connString, $this->getComponentOption('user'), $this->getComponentOption('pass'),$options);
			//$this->connection->setAttribute(\PDO::ATTR_TIMEOUT, 3);
			//$this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			//$this->connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            //$this->connection->setAttribute(\PDO::MYSQL_ATTR_FOUND_ROWS, false);
		} catch (\PDOException $e) {
			throw new BambooDBALException('Could not connect to server', [], 100, $e);
		}

		return $this;
	}

	/**
	 * @param null $fetchStyle
	 * @param int $column
	 *
	 * @return array
	 * @throws BambooDBALException
	 */
	public function fetchAll($fetchStyle = null, $column = 0)
	{
		if ($fetchStyle == null) {
			$fetchStyle = $this->fetchMode;
		}

		try {
			return $fetchStyle === \PDO::FETCH_COLUMN
				? $this->getStatement()->fetchAll($fetchStyle, $column)
				: $this->getStatement()->fetchAll($fetchStyle);
		} catch (\PDOException $e) {
			throw new BambooDBALException($e->getMessage(), [], 410, $e);
		}
	}

	/**
	 * @return \PDOStatement
	 * @throws BambooDBALException
	 */
	public function getStatement()
	{
		if ($this->statement === null) {
			throw new BambooDBALException("No PDOStatement object set");
		}

		return $this->statement;
	}

	/**
	 * @param string $table
	 * @param array $bind
	 * @param string $limit
	 * @param string $orderBy
	 * @param string $boolOperator
	 * @return $this|mixed
	 * @throws BambooDBALException
	 */
	public function select($table, array $bind = [], $limit = null, $orderBy = null, $boolOperator = 'AND')
	{
		$where = [];
		foreach ($bind as $col => $value) {
			$i = 0;
			$inClause = [];
			unset($bind[$col]);
			if (is_array($value)) {
				foreach ($value as $orVal) {
					$bind[":" . $col . "_" . $i] = $orVal;
					$inClause[] = ":" . $col . "_" . $i;
					$i++;
				}
				$where[] = $col . " IN (" . implode(',', $inClause) . ")";
			} elseif(is_null($value)) {
				$where[] = $col. ' is null ';
			} else {
				$bind[":" . $col] = $value;
				$where[] = '`'.$col.'`' . " = :" . $col;
			}
		}

		$sql = "SELECT * FROM `" . $table . "` " . (($where) ? " WHERE " . implode(" " . $boolOperator . " ", $where) : " ") . " {$orderBy} {$limit} ";

		$this->prepare($sql)->execute($bind);

		return $this;
	}

	/**
	 * @param array $parameters
	 * @return $this|mixed
	 * @throws BambooDBALException
	 */
	public function execute(array $parameters = [])
	{
		try {
			$time = microtime();
			$this->getStatement()->execute($parameters);
			$time = microtime() - $time;
			$this->queryCount++;
		} catch (\PDOException $e) {
            /*try {
                \Monkey::dump('error while executing statement');
                \Monkey::dump($this->statement->queryString);
                \Monkey::dump($parameters);
            } catch (\Throwable $e) {} */

			throw new BambooDBALException($e->getMessage(), [], 210, $e);
		}
		//TODO FIX STATISTICS TO ALWAYS WORK
		if(isset($this->isProfiling) && $this->isProfiling) {
			try {
				$this->queries[] = [trim($this->getStatement()->queryString),json_encode($parameters),$time];
			} catch (\Throwable $e) {}
		}

		return $this;
	}

	/**
	 * @param string $sql
	 * @param array $options
	 *
	 * @return $this
	 * @throws BambooDBALException
	 */
	public function prepare($sql, array $options = [])
	{
		try {
			$this->statement = $this->connection()->prepare($sql, $options);
		} catch (\PDOException $e) {
            try{
                //\Monkey::dump('error while preparing statement');
                //\Monkey::dump($sql);
            } catch (\Throwable $e) {}
            $debug = $e->getMessage();
			throw new BambooDBALException($e->getMessage(), [], 200, $e);
		} catch (\Throwable $e) {
			throw new BambooDBALException("Database offline");
		}

		return $this;
	}

	public function connection()
	{
	    if (is_null($this->connection)) $this->connect();
		if (is_null($this->connection)) throw new BambooDBALException('Connection to db Failed', [], 101);

		return $this->connection;
	}

    /**
     * @param $table
     * @param array $bind
     * @param string $boolOperator
     * @return mixed
     * @throws BambooDBALException
     */
	public function selectCount($table, array $bind = [], $boolOperator = 'AND')
	{
		$where = [];
		if ($bind) {
			foreach ($bind as $col => $value) {
				unset($bind[$col]);
				$bind[":" . $col] = $value;
				$where[] = $col . " = :" . $col;
			}
		}
        $sql = "SELECT COUNT(*) AS conto FROM `" . $table . (($bind) ? "` WHERE " . implode(" " . $boolOperator . " ", $where) : "` ");

		$this->prepare($sql)->execute($bind);

		return $this->fetch()['conto'];
	}

	/**
	 * @param null $fetchStyle
	 * @param null $cursorOrientation
	 * @param null $cursorOffset
	 *
	 * @return mixed
	 * @throws BambooDBALException
	 */
	public function fetch($fetchStyle = null, $cursorOrientation = null, $cursorOffset = null)
	{
		if ($fetchStyle == null) {
			$fetchStyle = $this->fetchMode;
		}

		try {
			return $this->getStatement()->fetch($fetchStyle, $cursorOrientation, $cursorOffset);
		} catch (\PDOException $e) {
			throw new BambooDBALException($e->getMessage(), [], 400, $e);
		}
	}

	/**
	 * @param string $table
	 * @param array $bind
	 * @param bool $delayed
	 * @param bool $ignore
	 * @return int
	 * @throws BambooDBALException
	 */
	public function insert($table, array $bind, $delayed = false, $ignore = false)
	{
		if($delayed === true) {
			$insert = "INSERT DELAYED INTO";
		} elseif($ignore === true ) {
			$insert = "INSERT IGNORE INTO";
		} else {
			$insert = "INSERT INTO";
		}
		$insert.= " `" . $table . "`";

		if ($this->isAssoc($bind)) {
			$keys = [];
			foreach (array_keys($bind) as $key) {
				$keys[] = "`" . $key . "`";
			}
			$keys = implode(", ", $keys);
			$values = implode(", :", array_keys($bind));
			foreach ($bind as $col => $value) {
				unset($bind[$col]);
				if (!is_object($value) && !is_array($value) && !is_null($value)) {
					$bind[":" . $col] = "" . $value . "";
				} elseif(is_null($value)) {
					$bind[":" . $col] = null;
				}
			}
			$sql = $insert ." (" . $keys . ") VALUES (:" . $values . ")";

			return (int)$this->prepare($sql)->execute($bind)->getLastInsertId();
		}
		$values = [];
		foreach ($bind as $val) {
			$values[] = '?';
		}
		$sql = $insert ." VALUES (" . implode(',', $values) . ")";

		return (int)$this->prepare($sql)->execute($bind)->getLastInsertId();
	}

	protected function isAssoc($arr)
	{
		return array_keys($arr) !== range(0, count($arr) - 1);
	}

    /**
     * @param null $name
     * @return string
     * @throws BambooDBALException
     */
	public function getLastInsertId($name = null)
	{
		return $this->connection()->lastInsertId($name);
	}

	/**
	 * @param $table
	 * @param array $bind
	 * @param array $where
	 * @param string $boolOperator
	 * @param bool $named
	 * @return int
	 * @throws BambooDBALException
	 */
	public function update($table, array $bind, array $where, $boolOperator = 'AND', $named = false, $cacheVerified = false)
	{
		$whereCondition = [];

		if ((bool)$named) {
			if ($where) {
				foreach ($where as $col => $value) {
					$whereCondition[] = "`" . $col . "` = ?";
				}
				$where = array_values($where);
			}
			$set = [];
			foreach ($bind as $col => $value) {
				if (!is_object($value) && !is_array($value)) {
					$set[] = "`" . $col . "` = ?";
				}
			}
			$bind = array_values($bind);
			$bind = array_merge($bind, $where);
		} else {
			if ($where) {
				foreach ($where as $col => $value) {
					unset($where[$col]);
					$where[":" . $col] = $value;
					$whereCondition[] = "`" . $col . "` = :" . $col;
				}
			}
			$set = [];
			foreach ($bind as $col => $value) {
				unset($bind[$col]);
				if (!is_object($value) && !is_array($value)) {
					$bind[":" . $col] = $value;
					$set[] = "`" . $col . "` = :" . $col;
				}
			}
			$bind = $bind + $where;
		}
		$sql = "UPDATE `" . $table . "` SET " . implode(", ", $set) . (($where) ? " WHERE " . implode(" " . $boolOperator . " ", $whereCondition) : " ");

		if (!$cacheVerified) {
			$this->app->eventManager->triggerEvent('DBUpdate', ["table" => $table, "parameters" => $bind, 'source' => 'update']);
		}

		return $this->prepare($sql)->execute($bind)->countAffectedRows();
	}

	/**
	 * @return int
	 * @throws BambooDBALException
	 */
	public function countAffectedRows()
	{
		try {
			return $this->getStatement()->rowCount();
		} catch (\PDOException $e) {
			throw new BambooDBALException($e->getMessage(), [], 300, $e);
		}
	}

    /**
     * @param $table
     * @param array $bind
     * @param string $boolOperator
     * @param bool $cacheVerified
     * @return int|mixed
     * @throws BambooDBALException
     */
	public function delete($table, array $bind, $boolOperator = 'AND', $cacheVerified = false)
	{
		$where = [];

		if ($bind) {
			foreach ($bind as $col => $value) {
				unset($bind[$col]);
				$bind[] = $value;
				$where[] = $col . " = ?";
			}
		}

		$sql = "DELETE FROM" . " `" . $table . "` " . (($where) ? " WHERE " . implode(" " . $boolOperator . " ", $where) : " ");
		if (!$cacheVerified) {
			$this->app->eventManager->triggerEvent('DBUpdate', ["table" => $table, "parameters" => $bind, 'source' => 'delete']);
		}

		return $this->prepare($sql)->execute($bind)->countAffectedRows();
	}

	/**
	 * @param $query
	 * @param array $bind
	 * @param bool $cacheVerified
	 * @return $this
	 * @throws BambooDBALException
	 */
	public function query($query, array $bind, $cacheVerified = false)
	{
		if (!$cacheVerified && preg_match('/^(UPDATE|DELETE)/ui', $query) > 0) {
            \Monkey::app()->cacheService->getCache('entities')->flush();
			//$this->app->eventManager->triggerEvent('DBUpdate', ["table" => "", "parameters" => [], 'query' => $query, 'source' => 'query']);
		}

		return $this->prepare($query)->execute($bind);
	}

    /**
     * @return bool|mixed
     * @throws BambooDBALException
     */
	public function beginTransaction()
	{
		if ($this->connection()->inTransaction()) {
			return true;
		} else {
			return $this->connection()->beginTransaction();
		}
	}

    /**
     * @return bool|mixed
     * @throws BambooDBALException
     */
	public function commit()
	{
		if ($this->hasTransaction()) {
			return $this->connection()->commit();
		} else return true;

	}

    /**
     * @return bool
     * @throws BambooDBALException
     */
	public function hasTransaction()
	{
		return $this->connection()->inTransaction();
	}

    /**
     * @return bool|mixed
     * @throws BambooDBALException
     */
	public function rollBack()
	{
		if ($this->hasTransaction()) {
			return $this->connection()->rollBack();
		} else return false;
	}

    /**
     * @param $table
     * @param string $mode
     * @return $this|mixed
     * @throws BambooDBALException
     */
	public function lock($table, $mode = 'WRITE')
	{
		return $this->prepare("LOCK TABLES $table $mode")->execute();
	}

    /**
     * @return $this|mixed
     * @throws BambooDBALException
     */
	public function unlock()
	{
		return $this->prepare('UNLOCK TABLES')->execute();
	}

	/**
	 * @return string
	 */
	public function time()
	{
		$dateTime = new \DateTime();

		return $dateTime->format('Y-m-d H:i:s');
	}

	/**
	 * @param $string
	 * @param int $paramType
	 * @return mixed
	 */
	public function quote($string, $paramType = \PDO::PARAM_STR)
	{
		return $this->connection->quote($string,$paramType);
	}

    /**
     *
     */
	public function __destruct()
	{
	    try {
            $this->rollBack();
            if(isset($this->isProfiling) && $this->isProfiling) {
                try {
                    foreach ($this->queries as $bind) {
                        $statement = $this->connection()->prepare("INSERT DELAYED INTO QueryStatistics (queryString,parameters,microtime) values (?,?,?)");
                        $statement->execute($bind);
                    }
                } catch (\Throwable $e){}
            }
            $this->disconnect();
        } catch (\Throwable $e) {
            logDestructError($e);
        }

	}
}