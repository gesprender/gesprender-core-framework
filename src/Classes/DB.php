<?php
namespace Core\Classes;

use Core\Contracts\CoreAbstract;
use Exception;
use mysqli;

class DB extends CoreAbstract
{
    private static function Connection()
    {
        $connection = new mysqli($_ENV['DDBB_HOST'], $_ENV['DDBB_USER'], $_ENV['DDBB_PASSWORD'], $_ENV['DDBB_DBNAME']);
        if($connection->connect_error) throw new Exception('Could not connect to database.');
        return $connection;
    }
    /**
     * @param string $query Query in format string
     * @param bool $fetch Use fetch mode to result query
     * @param string $typeFetch Type of fetch mode. Possible values are: fetch_array or fetch_all or fetch_assoc
     */
    public static function query(string $query, bool $fetch = false, string $typeFetch = 'fetch_all')
    {
        if(empty($query)) return;
        $response = mysqli_query(self::Connection(), $query);
        if ($fetch) {
            switch ($typeFetch) {
                case 'fetch_array':
                    return $response ? $response->fetch_array(MYSQLI_ASSOC) : false;
                case 'fetch_assoc':
                    return $response ? $response->fetch_assoc() : false;
                default:
                case 'fetch_all':
                    return $response ? $response->fetch_all(MYSQLI_ASSOC) : false;
            }
        }
        return $response;
    }

    /**
     * @param array $colum Name of the columns
     * @param string $trable Name of the table
     * @param array $where Conditions for the query
     * @param string $OrderBy other conditions
     * @param bool $fetch Use fetch mode to result query
     * @param string $typeFetch Type of fetch mode. Possible values are: fetch_array or fetch_all or fetch_assoc
     */
    protected static function get(array $colums, string $table,  array $where = [], string $OrderBy = '', bool $fetch = true, $typeFetch = 'fetch_all')
    {
        try {
            $cols = '';
            if (!empty($colums)) {
                foreach ($colums as $colum) {
                    $cols .= $colum . ", ";
                }
                $cols = substr($cols, 0, -2);
            } else {
                $cols = '*';
            }
            $wher = '';
            if (!empty($where)) {
                $wher = 'WHERE ';
                foreach ($where as $key => $value) {
                    $wher .= "$key = '$value' ";
                }
                $wher = substr($wher, 0, -1);
            }
            $query = "SELECT $cols FROM $table $wher $OrderBy";
            $response = self::query($query, $fetch, $typeFetch);

            return $response ? $response : false;
        } catch (Exception $e) {
            Logger::error('DB', 'Error in get -> ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param string $table Name of the table
     * @param array $find Enter the conditions for the search 
     */
    protected static function findBy(string $table, array $find)
    {
        $wher = ' WHERE ';
        $i = 0;
        foreach ($find as $key => $value) {
            if ($i > 0) {
                $wher .= "AND $key = '$value' ";
            }else{
                $wher .= "$key = '$value' ";
            }
            $i++;
        }
        $query = "SELECT * FROM $table" . $wher;
        // ddd($query);
        $data = self::query($query, true, 'fetch_all');
        if ($data) {
            return $data;
        }
        return false;
    }

    /**
     * @param string $table Name of the table
     * @param string|int $find Enter the id for the search 
     */
    protected static function findById(string $table, $findId)
    {
        $query = "SELECT * FROM $table WHERE id = '$findId'";
        $data = self::query($query, true, 'fetch_assoc');
        if ($data) {
            return $data;
        }
        return false;
    }

    /**
     * @param string $table Name of the table
     * @param string $search Enter the value to search 
     */
    protected static function search(string $table, string $search )
    {
        $businessId = $_SESSION['Business']->id;
        $query = "SELECT * FROM $table WHERE product LIKE '%$search%' AND business_id =  $businessId ORDER BY product ASC";
        $data = self::query($query, true, 'fetch_all');
        if ($data) {
            return $data;
        }
        return false;
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
            Logger::error('DB', 'Error in insert -> ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param string $table Name of the table
     * @param array $set values 
     * @param array $where Conditions
     */

    protected static function update(string $table, array $set, array $where = []): ?bool
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
            return self::query($query) ? true : false;
        } catch (Exception $e) {
            Logger::error('DB', 'Error in update -> ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param string $table Name of the table
     * @param string|int $idToDelete Enter the id for delete value
     */
    protected static function deleteById(string $table, int $idToDelete)
    {
        $query = "DELETE FROM $table WHERE id = $idToDelete";
        $data = self::query($query);
        if ($data) {
            return $data;
        }
        return false;
    }


}
