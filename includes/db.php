<?php
/**
 * Sevilla Secreta - Clase de Base de Datos
 * 
 * Maneja la conexión PDO y operaciones comunes de base de datos
 */

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    // Método para ejecutar consultas SELECT
    public function query($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Método para obtener múltiples resultados
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    // Método para obtener un solo resultado
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    // Método para INSERT/UPDATE/DELETE
    public function execute($sql, $params = []) {
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute($params);
    }

    // Obtener el último ID insertado
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }

    // Iniciar transacción
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }

    // Confirmar transacción
    public function commit() {
        return $this->conn->commit();
    }

    // Revertir transacción
    public function rollback() {
        return $this->conn->rollback();
    }
}