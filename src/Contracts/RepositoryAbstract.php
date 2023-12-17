<?php
namespace Core\Contracts;

use Core\Classes\DB;

abstract class RepositoryAbstract extends DB
{
    protected static function _getAll(array $Column, string $table, array $where = [])
    {
        return parent::get($Column, $table, $where );
    }
    
    protected static function _findBy(array $arrayData, string $table)
    {
        return parent::findBy($table, $arrayData);
    }

    protected static function _findById(string $table, int $id)
    {
        $data = parent::findById($table, $id);
        if($data) return $data;
        return [];
    }

    protected static function _findOneBy(array $arrayData, string $table) : array
    {
        $data = parent::findBy($table, $arrayData);
        if($data) return reset($data);
        return [];
    }

    protected static function _insert(string $table, array $arsInsert)
    {
        return parent::insert($table, $arsInsert);
    }

    protected static function _deleteById(string $table, int $id)
    {
        return parent::deleteById($table, $id);
    }
}