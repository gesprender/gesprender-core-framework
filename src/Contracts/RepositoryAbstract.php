<?php
declare(strict_types=1);

namespace Core\Contracts;

use Core\Contracts\DatabaseConnectionInterface;
use Core\Services\ServiceContainer;
use Core\Contracts\Traits\TraitResponseVariants;
use Core\Contracts\Traits\TraitValidateForm;

/**
 * RepositoryAbstract - Clase base para repositorios con dependency injection
 * 
 * Ya no hereda de MySQL, sino que usa DatabaseConnectionInterface inyectado.
 * Mantiene compatibilidad con métodos legacy mientras migra a DI.
 */
abstract class RepositoryAbstract
{
    use TraitResponseVariants;
    use TraitValidateForm;

    protected DatabaseConnectionInterface $database;

    public function __construct(DatabaseConnectionInterface $database = null)
    {
        // Si no se inyecta explícitamente, usar ServiceContainer
        $this->database = $database ?? ServiceContainer::resolve(DatabaseConnectionInterface::class);
    }

    // ===========================================
    // MÉTODOS MODERNOS (NON-STATIC) - USAR ESTOS
    // ===========================================

    /**
     * Obtiene registros con columnas específicas
     */
    protected function get(array $columns, string $table, array $where = [], string $orderBy = ''): array
    {
        return $this->database->get($columns, $table, $where, $orderBy);
    }

    /**
     * Obtiene todos los registros de una tabla
     */
    protected function getAll(string $table, array $where = [], string $orderBy = ''): array
    {
        return $this->database->get(['*'], $table, $where, $orderBy);
    }

    /**
     * Busca registros por múltiples criterios
     */
    protected function findBy(string $table, array $criteria): array
    {
        return $this->database->findBy($table, $criteria);
    }

    /**
     * Busca un registro por ID
     */
    protected function findById(string $table, int $id): array
    {
        return $this->database->findById($table, $id);
    }

    /**
     * Busca un solo registro por criterios
     */
    protected function findOneBy(string $table, array $criteria): array
    {
        return $this->database->findOneBy($table, $criteria);
    }

    /**
     * Busca registros con término de búsqueda
     */
    protected function search(string $table, array $columns, string $searchTerm): array
    {
        return $this->database->search($table, $columns, $searchTerm);
    }

    /**
     * Inserta un nuevo registro
     */
    protected function insert(string $table, array $data): bool
    {
        return $this->database->insert($table, $data);
    }

    /**
     * Actualiza registros existentes
     */
    protected function update(string $table, array $data, array $where = []): bool
    {
        return $this->database->update($table, $data, $where);
    }

    /**
     * Elimina un registro por ID
     */
    protected function deleteById(string $table, int $id): bool
    {
        return $this->database->deleteById($table, $id);
    }

    /**
     * Ejecuta una query SQL raw
     */
    protected function query(string $sql, bool $fetch = false, string $fetchType = 'fetch_all', array $params = []): mixed
    {
        return $this->database->query($sql, $fetch, $fetchType, $params);
    }

    // ===========================================
    // MÉTODOS LEGACY (STATIC) - BACKWARD COMPATIBILITY
    // ===========================================

    /**
     * @deprecated Use $this->get() instead
     */
    protected static function _get(array $columns, string $table, array $where = [], string $orderBy = ''): array
    {
        $instance = self::createRepositoryInstance();
        return $instance->get($columns, $table, $where, $orderBy);
    }

    /**
     * @deprecated Use $this->getAll() instead
     */
    protected static function _getAll(string $table, array $where = [], string $orderBy = ''): array
    {
        $instance = self::createRepositoryInstance();
        return $instance->getAll($table, $where, $orderBy);
    }

    /**
     * @deprecated Use $this->findBy() instead
     */
    protected static function _findBy(string $table, array $criteria): array
    {
        $instance = self::createRepositoryInstance();
        return $instance->findBy($table, $criteria);
    }

    /**
     * @deprecated Use $this->findById() instead
     */
    protected static function _findById(string $table, int $id): array
    {
        $instance = self::createRepositoryInstance();
        return $instance->findById($table, $id);
    }

    /**
     * @deprecated Use $this->findOneBy() instead
     */
    protected static function _findOneBy(string $table, array $criteria): array
    {
        $instance = self::createRepositoryInstance();
        return $instance->findOneBy($table, $criteria);
    }

    /**
     * @deprecated Use $this->insert() instead
     */
    protected static function _insert(string $table, array $data): bool
    {
        $instance = self::createRepositoryInstance();
        return $instance->insert($table, $data);
    }

    /**
     * @deprecated Use $this->update() instead
     */
    protected static function _update(array $data, string $table, array $where = []): bool
    {
        $instance = self::createRepositoryInstance();
        return $instance->update($table, $data, $where);
    }

    /**
     * @deprecated Use $this->deleteById() instead
     */
    protected static function _deleteById(string $table, int $id): bool
    {
        $instance = self::createRepositoryInstance();
        return $instance->deleteById($table, $id);
    }

    /**
     * @deprecated Use $this->query() instead
     */
    protected static function _query(string $sql, bool $fetch = false, string $fetchType = 'fetch_all', array $params = []): mixed
    {
        $instance = self::createRepositoryInstance();
        return $instance->query($sql, $fetch, $fetchType, $params);
    }

    /**
     * Creates a safe repository instance for legacy static methods
     * Avoids creating Domain objects that require constructor parameters
     */
    private static function createRepositoryInstance(): self
    {
        $calledClass = static::class;
        
        // If the called class is a Domain object, find its parent repository
        if (self::isDomainObject($calledClass)) {
            $parentClass = get_parent_class($calledClass);
            if ($parentClass && $parentClass !== self::class) {
                // Create instance of the parent repository instead
                return new $parentClass();
            }
        }
        
        // For regular repositories, try to create instance normally
        try {
            return new static();
        } catch (\Throwable $e) {
            // If constructor requires parameters, create a base repository
            // This is a fallback that should be improved in future refactoring
            $container = \Core\Services\ServiceContainer::getInstance();
            $database = $container->get(\Core\Contracts\DatabaseConnectionInterface::class);
            return new class($database) extends RepositoryAbstract {};
        }
    }

    /**
     * Determines if a class is likely a Domain object (anti-pattern detection)
     */
    private static function isDomainObject(string $className): bool
    {
        // Domain objects typically have "Domain" in their namespace
        return strpos($className, '\\Domain\\') !== false;
    }

    // ===========================================
    // MÉTODOS DE UTILIDAD
    // ===========================================

    /**
     * Obtiene información de la conexión de base de datos
     */
    protected function getDatabaseInfo(): array
    {
        return $this->database->getConnectionInfo();
    }

    /**
     * Verifica si la base de datos está conectada
     */
    protected function isDatabaseConnected(): bool
    {
        return $this->database->isConnected();
    }
}
