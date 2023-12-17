<?php
namespace Core\Contracts;
interface RepositoryInterface {
    
    public static function _getAll(array $Ars, string $table);
    public static function _findBy(array $arrayData, string $table);
    public static function _findById(string $table, int $id);
    public static function _insert(string $table, array $arsInsert);
    
}