<?php

namespace LowAbstractionORM;

/**
 * Interface IRepo
 * @package LowAbstractionORM
 */
interface IRepo
{
    public function findOne(array $id): IEntity;
    public function findOneBy(array $condition): IEntity;
    public function findAll($limit = "", $orderBy = ""): array;
    public function findBy(array $condition, $limit = "", $orderBy = ""): array;
    public function delete(IEntity $entity): bool;
    public function update(IEntity $entity): ?int;
    public function insert(IEntity $entity): ?int;
    public function getEmptyEntity(): IEntity;

    public function em();
}