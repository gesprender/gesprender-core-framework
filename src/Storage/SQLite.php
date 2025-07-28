<?php

namespace Core\Storage;

use Core\Contracts\CoreAbstract;
use Exception;
use SQLite3;

class SQLite extends CoreAbstract
{
    public $db;

    public function __construct($name_db) {
        $this->db = new SQLite3($name_db);
    }

    public function get(array $colums, string $table, array $where = [], string $OrderBy = ''): array
    {
        try {
            $cols = empty($colums) ? '*' : implode(", ", $colums);

            $query = "SELECT $cols FROM $table";

            if (!empty($where)) {
                $whereClauses = [];
                foreach ($where as $key => $value) {
                    $whereClauses[] = "$key = '$value'";
                }
                $query .= " WHERE " . implode(' ', $whereClauses);
            }

            if ($OrderBy) {
                $query .= " ORDER BY $OrderBy";
            }
            
            $response = $this->db->query($query);
            $dataResponse = [];
            while ($response && ($row = $response->fetchArray(SQLITE3_ASSOC))){
                $dataResponse[] = $row;
            }
            return $dataResponse;
        } catch (Exception $e) {
            self::ExceptionCapture($e, 'SQLite::get');
            return [];
        }
    }

    public function getOneBy(array $colums, string $table, array $where = [], string $OrderBy = ''): array
    {
        try {
            $cols = empty($colums) ? '*' : implode(", ", $colums);

            $query = "SELECT $cols FROM $table";

            if (!empty($where)) {
                $whereClauses = [];
                foreach ($where as $key => $value) {
                    $whereClauses[] = "$key = '$value'";
                }
                $query .= " WHERE " . implode(' ', $whereClauses);
            }

            if ($OrderBy) {
                $query .= " ORDER BY $OrderBy";
            }
            
            $response = $this->db->query("$query LIMIT 1");
            $dataResponse = [];
            while ($response && ($row = $response->fetchArray(SQLITE3_ASSOC))){
                $dataResponse[] = $row;
            }
            return $dataResponse? reset($dataResponse) : [];
        } catch (Exception $e) {
            self::ExceptionCapture($e, 'SQLite::get');
            return [];
        }
    }

    public function insert(string $table, array $insert): bool
    {
        try {
            $data_key = '';
            $data_value = '';
            foreach ($insert as $key => $value) {
                $data_key .= "$key, ";
                $data_value .= "'$value', ";
            }

            $data_key = substr($data_key, 0, -2);
            $data_value = substr($data_value, 0, -2);

            $query = "INSERT INTO $table ($data_key) VALUES ($data_value)";
            $data = (bool) $this->db->exec($query);

            return $data;
        } catch (Exception $e) {
            self::ExceptionCapture($e, 'SQLite::insert');
            return false;
        }
    }

    public function deleteById(string $table, int $idToDelete): bool
    {
        try {
            $query = "DELETE FROM $table WHERE id = $idToDelete";
            $data = $this->db->exec($query);

            return (bool) $data;
        } catch (\Throwable $th) {
            self::ExceptionCapture($th, 'SQLite::deleteById');
            return false;
        }
    }

    public function update(string $table, array $set, array $where = []): bool
    {
        try {
            $query = "UPDATE $table SET ";
            foreach ($set as $key => $value) {
                $query .= "$key = '$value', ";
            }
            $query = substr($query, 0, -2);
            $wher = '';
            if (!empty($where)) {
                $wher = ' WHERE ';
                foreach ($where as $key => $value) {
                    $wher .= "$key = '$value' ";
                }
                $wher = substr($wher, 0, -1);
            }
            $query .= $wher;

            $data = $this->db->exec($query);
            return (bool) $data;
        } catch (Exception $e) {
            self::ExceptionCapture($e, 'DB::update');
            return false;
        }
    }

    /**
     * Ejecuta una consulta SELECT (optimizado para memoria)
     */
    public function executeSelect(string $query, array $params = [], int $limit = 1000, int $offset = 0): array
    {
        $connection = $this->db;
        
        // Agregar LIMIT automáticamente si no está presente para proteger memoria
        if (stripos($query, 'LIMIT') === false) {
            $query .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        $startMemory = memory_get_usage(true);
        $rowCount = 0;
        $maxMemoryIncrease = 100 * 1024 * 1024; // 100MB máximo
        
        if (!empty($params)) {
            $stmt = $connection->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $response = $stmt->execute();
        } else {
            $response = $connection->query($query);
        }

        $results = [];
        while ($response && ($row = $response->fetchArray(SQLITE3_ASSOC))) {
            $results[] = $row;
            $rowCount++;
            
            // PROTECCIÓN: Monitorear memoria cada 100 filas
            if ($rowCount % 100 === 0) {
                $currentMemory = memory_get_usage(true);
                $memoryIncrease = $currentMemory - $startMemory;
                
                if ($memoryIncrease > $maxMemoryIncrease) {
                    error_log("SQLite query truncated due to high memory usage. Rows: {$rowCount}, Memory: " . 
                              number_format($memoryIncrease / 1024 / 1024, 2) . "MB");
                    break;
                }
            }
        }

        return $results;
    }

    /**
     * Ejecuta una consulta con resultados iterables (Generator para memoria eficiente)
     */
    public function executeSelectIterator(string $query, array $params = []): \Generator
    {
        $connection = $this->db;
        
        if (!empty($params)) {
            $stmt = $connection->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $response = $stmt->execute();
        } else {
            $response = $connection->query($query);
        }

        $rowCount = 0;
        while ($response && ($row = $response->fetchArray(SQLITE3_ASSOC))) {
            yield $row;
            $rowCount++;
            
            // Permitir liberación de memoria periódicamente
            if ($rowCount % 1000 === 0) {
                gc_collect_cycles();
            }
        }
    }

    /**
     * Obtiene el total de filas con COUNT (sin cargar datos)
     */
    public function getRowCount(string $table, string $where = ''): int
    {
        $connection = $this->db;
        $query = "SELECT COUNT(*) as count FROM {$table}";
        
        if (!empty($where)) {
            $query .= " WHERE {$where}";
        }
        
        $result = $connection->querySingle($query);
        return (int) $result;
    }

    public function selectAll(string $query, int $limit = 1000, int $offset = 0): array
    {
        // Usar el método optimizado
        return $this->executeSelect($query, [], $limit, $offset);
    }

    public function selectWithParams(string $query, array $params, int $limit = 1000, int $offset = 0): array
    {
        // Usar el método optimizado  
        return $this->executeSelect($query, $params, $limit, $offset);
    }
}