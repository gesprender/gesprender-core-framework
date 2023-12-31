<?php
declare(strict_types=1);

namespace Core\Classes;

use Core\Contracts\CoreAbstract;
use Exception;
use InvalidArgumentException;
use mysqli;
use RuntimeException;

class DB extends CoreAbstract
{
    private static function Connection(): ?mysqli
    {
        try {
            $connection = new mysqli($_ENV['DDBB_HOST'], $_ENV['DDBB_USER'], $_ENV['DDBB_PASSWORD'], $_ENV['DDBB_DBNAME']);
            if ($connection->connect_error) throw new Exception('Could not connect to database.');
            return $connection;
        } catch (\Throwable $th) {
            self::ExceptionResponse($th, 'DB::Connection');
        }
    }

    /**
     * Ejecuta una consulta SQL y recupera los resultados.
     * 
     * @param string $query Query in format string
     * @param bool $customFetch Use custom fetch mode to result query
     * @param string $typeFetch Type of fetch mode. Possible values are: fetch_array or fetch_all or fetch_assoc
     */
    public static function query(string $query, bool $customFetch = false, string $typeFetch = 'fetch_all'): ?array
    {
        try {

            if (empty($query)) {
                throw new InvalidArgumentException("Query no puede estar vacío.");
            }

            if (!in_array($typeFetch, ['fetch_array', 'fetch_assoc', 'fetch_all'])) {
                throw new InvalidArgumentException("typeFetch no válido.");
            }

            $response = mysqli_query(self::Connection(), $query);

            if (!$response) {
                throw new RuntimeException("Error ejecutando la consulta: " . mysqli_error(self::Connection()));
            }

            if ($customFetch) {
                switch ($typeFetch) {
                    case 'fetch_array':
                        return $response ? $response->fetch_array(MYSQLI_ASSOC) : [];
                    case 'fetch_assoc':
                        return $response ? $response->fetch_assoc() : [];
                    case 'fetch_all':
                    default:
                        return $response ? $response->fetch_all(MYSQLI_ASSOC) : [];
                }
            }
            return (array)$response;
        } catch (\Throwable $th) {
            self::ExceptionResponse($th, 'DB::query');
        }
    }

    /**
     * @param array $colum Name of the columns. Use example "col1, col2, col3..."
     * @param string $trable Name of the table
     * @param array $where Conditions for the query
     * @param string $OrderBy other conditions
     * @param bool $fetch Use fetch mode to result query
     * @param string $typeFetch Type of fetch mode. Possible values are: fetch_array or fetch_all or fetch_assoc
     */
    protected static function get(array $colums, string $table, array $where = [], string $OrderBy = '', bool $fetch = true, $typeFetch = 'fetch_all'): array
    {
        try {
            // Build the SELECT clause
            $cols = empty($colums) ? '*' : implode(", ", $colums);

            // Initialize the query
            $query = "SELECT $cols FROM $table";

            // Prepare WHERE clause if applicable
            if (!empty($where)) {
                $whereClauses = [];
                foreach ($where as $key => $value) {
                    $whereClauses[] = "$key = '$value'";
                }
                $query .= " WHERE " . implode(' ', $whereClauses);
            }

            // Append ORDER BY clause if provided
            if ($OrderBy) {
                $query .= " ORDER BY $OrderBy";
            }

            $response = self::query($query, $fetch, $typeFetch);

            return (array)$response;
        } catch (Exception $e) {
            self::ExceptionCapture($e, 'DB::get');
            return [];
        }
    }

    /**
     * @param string $table Name of the table
     * @param array $find Enter the conditions for the search 
     */
    protected static function findBy(string $table, array $find): array
    {
        try {
            $wher = ' WHERE ';
            $i = 0;
            foreach ($find as $key => $value) {
                if ($i > 0) {
                    $wher .= "AND $key = '$value' ";
                } else {
                    $wher .= "$key = '$value' ";
                }
                $i++;
            }
            $query = "SELECT * FROM $table" . $wher;
            $data = self::query($query, true, 'fetch_all');
            if ($data) {
                return $data;
            }
            return [];
        } catch (\Throwable $th) {
            self::ExceptionCapture($th, 'DB::findBy');
            return [];
        }
    }

    /**
     * @param string $table Name of the table
     * @param array $find Enter the conditions for the search 
     */
    protected static function findOneBy(string $table, array $find): array
    {
        try {
            $wher = ' WHERE ';
            $i = 0;
            foreach ($find as $key => $value) {
                if ($i > 0) {
                    $wher .= "AND $key = '$value' ";
                } else {
                    $wher .= "$key = '$value' ";
                }
                $i++;
            }
            $query = "SELECT * FROM $table" . "$wher LIMIT 1"; 
            $data = self::query($query, true, 'fetch_all');
            if ($data) {
                return reset($data);
            }
            return [];
        } catch (\Throwable $th) {
            self::ExceptionCapture($th, 'DB::findOneBy');
            return [];
        }
    }

    /**
     * @param string $table Name of the table
     * @param string|int $find Enter the id for the search 
     */
    protected static function findById(string $table, int $findId): array
    {
        try {
            $query = "SELECT * FROM $table WHERE id = $findId";
            $data = self::query($query, true, 'fetch_assoc');

            return $data ? $data : [];
        } catch (\Throwable $th) {
            self::ExceptionCapture($th, 'DB::findById');
            return [];
        }
    }

    /**
     * @param string $table Name of the table
     * @param string $search Enter the value to search 
     */
    protected static function search(string $table, string $search): array
    {
        try {
            $query = "SELECT * FROM $table WHERE product LIKE '%$search%' ORDER BY product ASC";
            $data = self::query($query, true, 'fetch_all');
            
            return $data ? $data : [];
        } catch (\Throwable $th) {
            self::ExceptionCapture($th, 'DB::search');
            return [];
        }
    }

    /**
     * @param string $table Name of the table
     * @param array $inser values to be inserted into the table
     */
    protected static function insert(string $table, array $insert): bool
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
            $data = (bool) self::query($query);

            return $data;
        } catch (Exception $e) {
            self::ExceptionCapture($e, 'DB::insert');
            return false;
        }
    }

    /**
     * @param string $table Name of the table
     * @param array $set values 
     * @param array $where Conditions
     */

    protected static function update(string $table, array $set, array $where = []): bool
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

            return (bool) self::query($query);

        } catch (Exception $e) {
            self::ExceptionCapture($e, 'DB::update');
            return false;
        }
    }

    /**
     * @param string $table Name of the table
     * @param string|int $idToDelete Enter the id for delete value
     */
    protected static function deleteById(string $table, int $idToDelete): bool
    {
        try {
            $query = "DELETE FROM $table WHERE id = $idToDelete";
            $data = self::query($query);

            return (bool) $data;
        } catch (\Throwable $th) {
            self::ExceptionCapture($th, 'DB::deleteById');
            return false;
        }
    }
}
