<?php

declare(strict_types=1);

namespace Core\Services;

use Core\Contracts\DatabaseConnectionInterface;
use Core\Classes\LoggerCompatibilityWrapper;
use Exception;
use InvalidArgumentException;
use mysqli;

/**
 * DatabaseService - Servicio de base de datos con dependency injection
 * 
 * Implementa DatabaseConnectionInterface y adapta la lógica existente de MySQL
 * para ser usado como service inyectable.
 */
class DatabaseService implements DatabaseConnectionInterface
{
    private ?mysqli $connection = null;
    private ConfigService $config;
    private LoggerService $logger;

    public function __construct(ConfigService $config, LoggerService $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Establece la conexión a la base de datos
     */
    private function connect(): mysqli
    {
        if ($this->connection !== null && $this->connection->ping()) {
            return $this->connection;
        }

        try {
            $db_host = $this->config->get('database.host');
            $db_user = $this->config->get('database.user');
            $db_name = $this->config->get('database.name');
            $db_password = $this->config->get('database.password');

            // Multi-tenant support
            if ($this->config->get('app.multi_tenant') === 'true') {
                if (isset($_SERVER['HTTP_HOST'])) {
                    $domain = $_SERVER['HTTP_HOST'];
                    $tenant_db = $_ENV[$domain] ?? null;
                    
                    if (!$tenant_db) {
                        throw new Exception("Tenant database not found for domain: $domain");
                    }

                    $db_name = $tenant_db;
                    $db_password = $_ENV["{$tenant_db}_password"] ?? $db_password;
                }
            }

            $this->connection = new mysqli($db_host, $db_user, $db_password, $db_name);
            
            if ($this->connection->connect_error) {
                throw new Exception('Could not connect to database: ' . $this->connection->connect_error);
            }

            // Set charset
            $this->connection->set_charset('utf8mb4');

            return $this->connection;

        } catch (\Throwable $e) {
            $this->logger->error('Database connection failed', [
                'error' => $e->getMessage(),
                'host' => $db_host ?? 'unknown',
                'database' => $db_name ?? 'unknown'
            ]);
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Ejecuta una consulta SQL raw con soporte para prepared statements
     */
    public function query(string $sql, bool $fetch = false, string $fetchType = 'fetch_all', array $params = []): mixed
    {
        try {
            $connection = $this->connect();

            // Si hay parámetros o placeholders, usar prepared statements
            if (!empty($params) || strpos($sql, '?') !== false) {
                return $this->executePreparedStatement($sql, $params, $fetch, $fetchType);
            }

            // Query directa sin parámetros
            $result = $connection->query($sql);

            if ($result === false) {
                throw new Exception('Query failed: ' . $connection->error);
            }

            if (!$fetch) {
                return $result;
            }

            return match ($fetchType) {
                'fetch_array' => $result->fetch_array(),
                'fetch_assoc' => $result->fetch_assoc(),
                'fetch_all' => $result->fetch_all(MYSQLI_ASSOC),
                default => $result->fetch_all(MYSQLI_ASSOC)
            };

        } catch (\Throwable $e) {
            $this->logger->error('Database query failed', [
                'error' => $e->getMessage(),
                'sql' => $sql,
                'params' => $params
            ]);
            throw $e;
        }
    }

    /**
     * Ejecuta prepared statements de forma segura
     */
    private function executePreparedStatement(string $sql, array $params, bool $fetch, string $fetchType): mixed
    {
        $connection = $this->connect();
        $stmt = $connection->prepare($sql);

        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $connection->error);
        }

        // Bind parameters si existen
        if (!empty($params)) {
            $types = str_repeat('s', count($params)); // Por simplicidad, todos como strings
            $stmt->bind_param($types, ...$params);
        }

        $success = $stmt->execute();
        
        if (!$success) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }

        if (!$fetch) {
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            return $affected_rows;
        }

        $result = $stmt->get_result();
        
        if ($result === false) {
            $stmt->close();
            return [];
        }

        $data = match ($fetchType) {
            'fetch_array' => $result->fetch_array(),
            'fetch_assoc' => $result->fetch_assoc(),
            'fetch_all' => $result->fetch_all(MYSQLI_ASSOC),
            default => $result->fetch_all(MYSQLI_ASSOC)
        };

        $stmt->close();
        return $data ?? [];
    }

    /**
     * Obtiene registros con columnas específicas
     */
    public function get(array $columns, string $table, array $where = [], string $orderBy = ''): array
    {
        try {
            $sql = 'SELECT ' . implode(', ', $columns) . ' FROM ' . $table;

            if (!empty($where)) {
                $whereClause = [];
                foreach ($where as $key => $value) {
                    $whereClause[] = "$key = '" . $this->escape($value) . "'";
                }
                $sql .= ' WHERE ' . implode(' ', $whereClause);
            }

            if (!empty($orderBy)) {
                $sql .= ' ORDER BY ' . $orderBy;
            }

            $result = $this->query($sql, true);
            return is_array($result) ? $result : [];

        } catch (\Throwable $e) {
            $this->logger->error('Database get query failed', [
                'error' => $e->getMessage(),
                'table' => $table,
                'columns' => $columns
            ]);
            return [];
        }
    }

    /**
     * Busca registros por múltiples criterios
     */
    public function findBy(string $table, array $criteria): array
    {
        return $this->get(['*'], $table, $criteria);
    }

    /**
     * Busca un registro por ID
     */
    public function findById(string $table, int $id): array
    {
        $result = $this->findOneBy($table, ['id' => $id]);
        return $result ?: [];
    }

    /**
     * Busca un solo registro por criterios
     */
    public function findOneBy(string $table, array $criteria): array
    {
        $results = $this->get(['*'], $table, $criteria);
        return !empty($results) ? $results[0] : [];
    }

    /**
     * Busca registros con término de búsqueda
     */
    public function search(string $table, array $columns, string $searchTerm): array
    {
        try {
            $escapedTerm = $this->escape($searchTerm);
            $searchConditions = [];
            
            foreach ($columns as $column) {
                $searchConditions[] = "$column LIKE '%$escapedTerm%'";
            }

            $sql = "SELECT * FROM $table WHERE " . implode(' OR ', $searchConditions);
            $result = $this->query($sql, true);
            
            return is_array($result) ? $result : [];

        } catch (\Throwable $e) {
            $this->logger->error('Database search failed', [
                'error' => $e->getMessage(),
                'table' => $table,
                'search_term' => $searchTerm
            ]);
            return [];
        }
    }

    /**
     * Inserta un nuevo registro
     */
    public function insert(string $table, array $data): bool
    {
        try {
            if (empty($data)) {
                throw new InvalidArgumentException('Data array cannot be empty');
            }

            $columns = array_keys($data);
            $values = [];
            
            foreach ($data as $value) {
                $values[] = $this->escape($value);
            }
            
            $sql = "INSERT INTO $table (" . implode(', ', $columns) . ") VALUES ('" . implode("', '", $values) . "')";
            
            $this->query($sql);
            return true;

        } catch (\Throwable $e) {
            $this->logger->error('Database insert failed', [
                'error' => $e->getMessage(),
                'table' => $table,
                'data_keys' => array_keys($data)
            ]);
            return false;
        }
    }

    /**
     * Actualiza registros existentes
     */
    public function update(string $table, array $data, array $where = []): bool
    {
        try {
            if (empty($data)) {
                throw new InvalidArgumentException('Data array cannot be empty');
            }

            $setClause = [];
            foreach ($data as $key => $value) {
                $setClause[] = "$key = '" . $this->escape($value) . "'";
            }

            $sql = "UPDATE $table SET " . implode(', ', $setClause);

            if (!empty($where)) {
                $whereClause = [];
                foreach ($where as $key => $value) {
                    $whereClause[] = "$key = '" . $this->escape($value) . "'";
                }
                $sql .= ' WHERE ' . implode(' AND ', $whereClause);
            }

            $this->query($sql);
            return true;

        } catch (\Throwable $e) {
            $this->logger->error('Database update failed', [
                'error' => $e->getMessage(),
                'table' => $table,
                'data_keys' => array_keys($data)
            ]);
            return false;
        }
    }

    /**
     * Elimina un registro por ID
     */
    public function deleteById(string $table, int $id): bool
    {
        try {
            $sql = "DELETE FROM $table WHERE id = " . intval($id);
            $this->query($sql);
            return true;

        } catch (\Throwable $e) {
            $this->logger->error('Database delete failed', [
                'error' => $e->getMessage(),
                'table' => $table,
                'id' => $id
            ]);
            return false;
        }
    }

    /**
     * Verifica conectividad
     */
    public function isConnected(): bool
    {
        try {
            $connection = $this->connect();
            return $connection->ping();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Obtiene información de la conexión
     */
    public function getConnectionInfo(): array
    {
        try {
            $connection = $this->connect();
            return [
                'host' => $this->config->get('database.host'),
                'database' => $this->config->get('database.name'),
                'server_info' => $connection->server_info,
                'connected' => $this->isConnected(),
                'multi_tenant' => $this->config->get('app.multi_tenant') === 'true'
            ];
        } catch (\Throwable $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Escapa valores para prevenir SQL injection
     */
    private function escape(mixed $value): string
    {
        $connection = $this->connect();
        
        if (is_null($value)) {
            return 'NULL';
        }
        
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        
        if (is_numeric($value)) {
            return (string)$value;
        }
        
        return $connection->real_escape_string((string)$value);
    }

    /**
     * Cierra la conexión
     */
    public function __destruct()
    {
        if ($this->connection) {
            $this->connection->close();
        }
    }
} 