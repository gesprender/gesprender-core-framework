<?php
declare(strict_types=1);

namespace Core\Contracts;

use Core\Storage\MySQL;
use Core\Storage\SQLite;

abstract class RepositoryAbstract extends MySQL
{
    
    public static function SQLiteAbstract(string $db_name):? SQLite
    {
        try {
            return new SQLite($db_name);
        } catch (\Throwable $th) {
            self::ExceptionCapture($th, 'RepositoryAbstract:SQLite');
        }
    }

    protected static function _get(array $Columns, string $table, array $where = [], string $orderBy = ''): array
    {
        return parent::get($Columns, $table, $where, $orderBy);
    }

    protected static function _getAll(string $table, array $where = [], string $orderBy = ''): array
    {
        return parent::get(['*'], $table, $where, $orderBy);
    }

    protected static function _findBy(array $arrayData, string $table): array
    {
        return parent::findBy($table, $arrayData);
    }

    protected static function _findById(string $table, int $id): array
    {
        $data = parent::findById($table, $id);
        if ($data) return $data;
        return [];
    }

    protected static function _findOneBy(string $table, array $where): array
    {
        $data = parent::findOneBy($table, $where);
        return $data;
    }

    protected static function _insert(string $table, array $arsInsert): bool
    {
        return parent::insert($table, $arsInsert);
    }

    protected static function _update(array $set, string $table, array $where = []): bool
    {
        return parent::update($table, $set, $where);
    }

    protected static function _deleteById(string $table, int $id): bool
    {
        return parent::deleteById($table, $id);
    }
}
