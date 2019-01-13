<?php
/**
 * Created by PhpStorm.
 * User: tdlem
 * Date: 13/01/2019
 * Time: 12:21
 */

namespace LowAbstractionORM\entities;


class EntityConfig {

    protected $table;

    public function table() {
        return $this->table;
    }
}