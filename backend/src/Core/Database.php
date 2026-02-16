<?php
/**
 * Database Connection and Query Helper
 */

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

    /**
     * Get PDO connection (singleton)
     *
     * @return PDO
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::connect();
        }

        return self::$connection;
    }

    /**
     * Establish database connection
     *
     * @return void
     */
    private static function connect(): void
    {
        try {
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                env('DB_HOST', 'localhost'),
                env('DB_PORT', '3306'),
                env('DB_NAME')
            );

            self::$connection = new PDO(
                $dsn,
                env('DB_USER'),
                env('DB_PASSWORD'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new \Exception("Database connection failed");
        }
    }

    /**
     * Execute a query with parameters
     *
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return \PDOStatement
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = self::getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage() . " | SQL: " . $sql);
            throw new \Exception("Query failed: " . $e->getMessage());
        }
    }

    /**
     * Fetch all rows from query
     *
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return array
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Fetch one row from query
     *
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return array|false
     */
    public static function fetchOne(string $sql, array $params = [])
    {
        return self::query($sql, $params)->fetch();
    }

    /**
     * Fetch single value
     *
     * @param string $sql SQL query
     * @param array $params Parameters
     * @return mixed
     */
    public static function fetchColumn(string $sql, array $params = [])
    {
        return self::query($sql, $params)->fetchColumn();
    }

    /**
     * Insert a record and return last insert ID
     *
     * @param string $table Table name
     * @param array $data Associative array of column => value
     * @return int Last insert ID
     */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $values = array_values($data);

        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $columns),
            implode(', ', array_fill(0, count($columns), '?'))
        );

        self::query($sql, $values);

        return (int) self::getConnection()->lastInsertId();
    }

    /**
     * Update records
     *
     * @param string $table Table name
     * @param array $data Data to update
     * @param string $where WHERE clause (e.g., "id = ?")
     * @param array $whereParams Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $setParts = [];
        $values = [];

        foreach ($data as $column => $value) {
            $setParts[] = "$column = ?";
            $values[] = $value;
        }

        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $setParts),
            $where
        );

        $values = array_merge($values, $whereParams);

        return self::query($sql, $values)->rowCount();
    }

    /**
     * Delete records
     *
     * @param string $table Table name
     * @param string $where WHERE clause
     * @param array $params Parameters for WHERE clause
     * @return int Number of affected rows
     */
    public static function delete(string $table, string $where, array $params = []): int
    {
        $sql = sprintf("DELETE FROM %s WHERE %s", $table, $where);
        return self::query($sql, $params)->rowCount();
    }

    /**
     * Begin transaction
     *
     * @return bool
     */
    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     *
     * @return bool
     */
    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    /**
     * Rollback transaction
     *
     * @return bool
     */
    public static function rollback(): bool
    {
        return self::getConnection()->rollBack();
    }
}
