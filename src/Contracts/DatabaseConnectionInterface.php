<?php

declare(strict_types=1);

namespace Core\Contracts;

/**
 * DatabaseConnectionInterface - Interfaz para conexiones de base de datos
 * 
 * Define los métodos estándar que deben implementar todos los adaptadores
 * de base de datos para uso en repositories.
 */
interface DatabaseConnectionInterface
{
    /**
     * Ejecuta una consulta SQL raw
     */
    public function query(string $sql, bool $fetch = false, string $fetchType = 'fetch_all', array $params = []): mixed;

    /**
     * Obtiene registros con columnas específicas
     */
    public function get(array $columns, string $table, array $where = [], string $orderBy = ''): array;

    /**
     * Busca registros por múltiples criterios
     */
    public function findBy(string $table, array $criteria): array;

    /**
     * Busca un registro por ID
     */
    public function findById(string $table, int $id): array;

    /**
     * Busca un solo registro por criterios
     */
    public function findOneBy(string $table, array $criteria): array;

    /**
     * Busca registros con término de búsqueda
     */
    public function search(string $table, array $columns, string $searchTerm): array;

    /**
     * Inserta un nuevo registro
     */
    public function insert(string $table, array $data): bool;

    /**
     * Actualiza registros existentes
     */
    public function update(string $table, array $data, array $where = []): bool;

    /**
     * Elimina un registro por ID
     */
    public function deleteById(string $table, int $id): bool;

    /**
     * Verifica conectividad
     */
    public function isConnected(): bool;

    /**
     * Obtiene información de la conexión
     */
    public function getConnectionInfo(): array;
} 