<?php

namespace LowAbstractionORM\adapters;

use LowAbstractionORM\exceptions\DbAdapterException;
use LowAbstractionORM\exceptions\ORMConfigException;
use LowAbstractionORM\IDBAdapter;

/**
 * Class MySQLAdapter
 * @package LowAbstractionORM\adapters
 */
class MySQLAdapter implements IDBAdapter
{

    /** @var \PDO */
    protected $connection;

    /** @var int */
    protected $fetchMode = \PDO::FETCH_ASSOC;

    protected $transaction = false;

    protected $queryCount = 0;
    protected $queries = [];
    protected $dbTime = 0;
    protected $isProfiling = false;

    private $config;

    public static function build(array $config): IDBAdapter
    {
        return new self($config);
    }


    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @return bool
     * @throws ORMConfigException
     */
    public function test(): bool
    {
        $res = $this->connection()->query("SELECT 1")->execute();
        return $res == 1;
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        return (bool)$this->connection;
    }

    /**
     * @return bool|\PDO
     * @throws ORMConfigException
     * @throws \Throwable
     */
    public function reconnect()
    {
        $this->disconnect();
        return $this->connect();
    }

    /**
     * @return bool|void
     * @throws \Throwable
     */
    public function disconnect()
    {
        try {
            $this->connection = null;
        } catch (\Throwable $e) {
            if (strStr($e->getMessage(), 'send of 9 bytes failed with errno=32 Broken pipe') !== false) return;
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
     * @return bool|\PDO
     * @throws ORMConfigException
     */
    public function connect()
    {
        $connString = $this->config['ENGINE'] . ':host=' . $this->config['HOST'] . ';dbname=' . $this->config['NAME'] . ";charset=utf8";

        try {
            $options = [
                \PDO::ATTR_TIMEOUT => 3,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_EMULATE_PREPARES => false,
                \PDO::MYSQL_ATTR_FOUND_ROWS => true,
                \PDO::ATTR_PERSISTENT => false
            ];
            $this->connection = new \PDO($connString, $this->config['USER'], $this->config['PASS'], $options);
            //$this->connection->setAttribute(\PDO::ATTR_TIMEOUT, 3);
            //$this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            //$this->connection->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            //$this->connection->setAttribute(\PDO::MYSQL_ATTR_FOUND_ROWS, false);
        } catch (\PDOException $e) {
            throw new ORMConfigException('Could not connect to server', 100, $e);
        }
        return $this->connection;
    }

    /**
     * @return bool|\PDO
     * @throws ORMConfigException
     */
    protected function connection()
    {
        if ($this->connection) {
            return $this->connection;
        }
        return $this->connect();
    }

    /**
     * @param $sql
     * @param array $options
     * @return bool|\PDOStatement
     * @throws ORMConfigException
     */
    protected function prepare($sql, array $options = [])
    {
        try {
            return $this->connection()->prepare($sql, $options);
        } catch (\PDOException $e) {
            throw $e;
        }
    }

    /**
     * @param \PDOStatement $statement
     * @param $params
     * @param array $options
     * @return \PDOStatement
     * @throws DbAdapterException
     */
    protected function execute(\PDOStatement $statement, $params, $options = [])
    {
        if ($statement->execute($params)) {
            return $statement;
        } else {
            throw new DbAdapterException('Could not execute statement', $statement->errorCode(), $statement->errorInfo());
        }
    }

    /**
     * @param \PDOStatement $statement
     * @param array $options
     * @return array
     * @throws DbAdapterException
     */
    protected function fetchAll(\PDOStatement $statement, $options = [])
    {
        if (isset($options['FETCH_STYLE'])) {
            $fetchStyle = $options['FETCH_STYLE'];
        } else {
            $fetchStyle = $this->fetchMode;
        }
        switch ($fetchStyle) {
            case \PDO::FETCH_ASSOC:
            case \PDO::FETCH_BOTH:
                return $statement->fetchAll($fetchStyle);
            case \PDO::FETCH_COLUMN:
                return $statement->fetchAll($fetchStyle, $options['FETCH_ARGUMENT'] ?? 0);
            default:
                throw new DbAdapterException('unknown fetch_style: ' . $fetchStyle);
        }
    }

    /**
     * @param \PDOStatement $statement
     * @param $options
     * @return mixed
     * @throws DbAdapterException
     */
    protected function fetch(\PDOStatement $statement, $options = [])
    {
        if (isset($options['FETCH_STYLE'])) {
            $fetchStyle = $options['FETCH_STYLE'];
        } else {
            $fetchStyle = $this->fetchMode;
        }
        switch ($fetchStyle) {
            case \PDO::FETCH_ASSOC:
            case \PDO::FETCH_BOTH:
                return $statement->fetch($fetchStyle);
            case \PDO::FETCH_COLUMN:
                return $statement->fetch($fetchStyle, $options['FETCH_ARGUMENT'] ?? 0);
            default:
                throw new DbAdapterException('unknown fetch_style: ' . $fetchStyle);
        }
    }

    protected function isAssoc($arr)
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * @param string $table
     * @param array $bind
     * @param null $limit
     * @param null $orderBy
     * @param string $boolOperator
     * @return array|mixed
     * @throws DbAdapterException
     * @throws ORMConfigException
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
            } elseif (is_null($value)) {
                $where[] = $col . ' is null ';
            } else {
                $bind[":" . $col] = $value;
                $where[] = '`' . $col . '`' . " = :" . $col;
            }
        }

        $sql = "SELECT * FROM `" . $table . "` " . (($where) ? " WHERE " . implode(" " . $boolOperator . " ", $where) : " ") . " {$orderBy} {$limit} ";

        $stmt = $this->prepare($sql);
        $stmt = $this->execute($stmt, $bind);
        return $this->fetchAll($stmt);
    }

    /**
     * @param $table
     * @param array $bind
     * @param string $boolOperator
     * @return mixed
     * @throws DbAdapterException
     * @throws ORMConfigException
     */
    public function selectCount($table, array $bind = [], $boolOperator = 'AND')
    {
        $where = [ "1 = 1"];
        if ($bind) {
            foreach ($bind as $col => $value) {
                unset($bind[$col]);
                $bind[":" . $col] = $value;
                $where[] = "`$col` = :$col";
            }
        }
        $sql = "SELECT COUNT(*) AS conto FROM $table WHERE ". implode(" " . $boolOperator . " ", $where);

        $stmt = $this->prepare($sql);
        $this->execute($stmt, $bind);
        return $this->fetch($stmt)['conto'];
    }


    /**
     * @param string $table
     * @param array $bind
     * @param bool $delayed
     * @param bool $ignore
     * @return int|string
     * @throws DbAdapterException
     * @throws ORMConfigException
     */
    public function insert($table, array $bind, $delayed = false, $ignore = false)
    {
        if ($delayed === true) {
            $insert = "INSERT DELAYED INTO";
        } elseif ($ignore === true) {
            $insert = "INSERT IGNORE INTO";
        } else {
            $insert = "INSERT INTO";
        }
        $insert .= " `" . $table . "`";

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
                } elseif (is_null($value)) {
                    $bind[":" . $col] = null;
                }
            }
            $sql = $insert . " (" . $keys . ") VALUES (:" . $values . ")";

            return (int)$this->prepare($sql)->execute($bind)->getLastInsertId();
        }
        $values = [];
        foreach ($bind as $val) {
            $values[] = '?';
        }
        $sql = $insert . " VALUES (" . implode(',', $values) . ")";

        $stmt = $this->prepare($sql);
        $this->execute($stmt, $bind);
        return $this->getLastInsertId();
    }



    /**
     * @param null $name
     * @return string
     * @throws ORMConfigException
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
     * @param bool $cacheVerified
     * @return int
     * @throws DbAdapterException
     * @throws ORMConfigException
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

        $stmt = $this->prepare($sql);
        $this->execute($stmt, $bind);
        return $stmt->rowCount();
    }

    /**
     * @param $table
     * @param array $bind
     * @param string $boolOperator
     * @param bool $cacheVerified
     * @return int|mixed
     * @throws DbAdapterException
     * @throws ORMConfigException
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

        $stmt = $this->prepare($sql);
        $this->execute($stmt, $bind);
        return $stmt->rowCount();
    }

    /**
     * @param $query
     * @param array $bind
     * @param array $stmtOptions
     * @param array $fetchOptions
     * @return bool|mixed
     * @throws ORMConfigException
     */
    public function query($query, array $bind, $stmtOptions = [], $fetchOptions = [])
    {
        return $this->prepare($query, $stmtOptions)->execute($bind);
    }

    /**
     * @return bool|mixed
     * @throws ORMConfigException
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
     * @throws ORMConfigException
     */
    public function commit()
    {
        if ($this->hasTransaction()) {
            return $this->connection()->commit();
        } else return true;

    }

    /**
     * @return bool
     * @throws ORMConfigException
     */
    public function hasTransaction()
    {
        return $this->connection()->inTransaction();
    }

    /**
     * @return bool|mixed
     * @throws ORMConfigException
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
     * @return bool|mixed
     * @throws ORMConfigException
     */
    public function lock($table, $mode = 'WRITE')
    {
        return $this->prepare("LOCK TABLES $table $mode")->execute();
    }

    /**
     * @return bool|mixed
     * @throws ORMConfigException
     */
    public function unlock()
    {
        return $this->prepare('UNLOCK TABLES')->execute();
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function time()
    {
        $dateTime = new \DateTime();

        return $dateTime->format('Y-m-d H:i:s');
    }

    /**
     * @param $string
     * @param int $paramType
     * @return string
     * @throws ORMConfigException
     */
    public function quote($string, $paramType = \PDO::PARAM_STR)
    {
        return $this->connection()->quote($string, $paramType);
    }

}