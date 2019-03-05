<?php

namespace LowAbstractionOrm\entity;

use LowAbstractionORM\IEntity;

abstract class AEntity implements IEntity
{
    const TABLE = null;
    const REPO = Repo::class;
    const KEYS = ['id'];
    const AUTO_INCREMENT = 'id';
    const MASK = [];
    const RELATIONSHIPS = [];

    protected $relationship = [];
    private $savedCopy;

    const RELATIONSHIP_TO_ONE = "toone";
    const RELATIONSHIP_TO_MANY = "tomany";

    public function __construct($data = [])
    {
        foreach ($data as $key => $val) {
            $this->{$key} = $val;
        }
        $this->savedCopy = clone $this;
    }

    /**
     * @return null|string
     * @throws \ReflectionException
     */
    public static function getTableName()
    {
        if (static::TABLE !== null) return static::TABLE;
        else return strtolower((new \ReflectionClass(static::class))->getShortName());
    }

    /**
     * @param null $entityClass
     *
     * @return ARepo
     */
    protected function getRepo($entityClass = null)
    {
        /** @var RepoFactory $repoFactory */
        global $cnt;
        if (!$entityClass) $entityClass = static::class;
        return $cnt[RepoFactory::class]->getRepo($entityClass);
    }

    /**
     * @return bool|string
     * @throws \Exception
     */
    public function insert()
    {
        return $this->getRepo()->insert($this);
    }

    /**
     * @return bool
     */
    public function hasKeys()
    {
        foreach (static::KEYS as $key) {
            if (empty($this->$key)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getIds()
    {
        $keys = [];
        foreach (static::KEYS as $key) {
            if (empty($this->$key)) {
                throw new \Exception("$key not defined for object");
            }
            $keys[$key] = $this->$key;
        }
        return $keys;
    }

    /**
     * @return static|null
     * @throws \Exception
     */
    public function refresh()
    {
        if (!$this->hasKeys()) return null;
        return $this->getRepo()->getOneById($this->getIds());
    }

    /**
     * @return array
     */
    public function getChangedProperties()
    {
        $changed = [];
        foreach (get_object_vars($this->savedCopy) as $key => $val) {
            if ($key === "savedCopy") continue;
            if ($this->{$key} !== $val) {
                $changed[$key] = $val;
            }
        }
        return $changed;
    }

    /**
     * @return bool|string
     * @throws \Exception
     */
    public function update()
    {
        return $this->getRepo()->update($this);
    }

    /**
     * @return bool|string
     * @throws \Exception
     */
    public function delete()
    {
        return $this->getRepo()->delete($this);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return json_decode(json_encode($this), true);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        foreach (get_object_vars($this) as $key => $val) {
            if ($key === "savedCopy" || $val === null) continue;
            $res[$key] = $val;
        }
        foreach (static::MASK as $maskedField) {
            unset($res[$maskedField]);
        }
        return $res;
    }

    /**
     * @param array $data
     *
     * @return static
     */
    static function fromArray(array $data)
    {
        $a = new static();
        foreach ($data as $key => $val) {
            $a->{$key} = $val;
        }
        return $a;
    }

    /**
     * @param string $data
     *
     * @return static
     */
    static function jsonDeserialize(string $data)
    {
        return static::fromArray(json_decode($data, true));
    }

    /**
     * @param array $data
     */
    public function mergeFromArray(array $data)
    {
        foreach ($this as $key => $val) {
            if (isset($data[$key])) {
                $this->$key = $data[$key];
            }
        }
    }

    /**
     * @param $string
     */
    public function mergeFromJson($string)
    {
        $this->mergeFromArray(json_decode($string, true));
    }

    public function __get(string $name)
    {
        if (method_exists($this, "get$name")) return $this->{"get$name"}();
        if (property_exists($this, $name)) return $this->$name;
        if (isset($this->relationship[$name])) return $this->relationship[$name];
        if (isset(static::RELATIONSHIPS[$name])) return $this->resolveRelationship($name);
        return $this->$name;
    }

    protected function resolveRelationship(string $name) {

    }

    /**
     * @param string $name
     * @param $value
     */
    public function __set(string $name, $value)
    {
        if (method_exists($this, "set$name")) {
            $this->{"set$name"}($value);
        } else {
            $this->$name = $value;
        }
    }
}
