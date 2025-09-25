<?php
require_once 'config.php';

class Database {
    private $host = DB_HOST;
    private $port = DB_PORT;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $driver = DB_DRIVER;
    
    private $conn;
    private $error;
    private $stmt;
    
    public function __construct() {
        $dsn = '';
        if ($this->driver == 'pgsql') {
            $dsn = 'pgsql:host=' . $this->host . ';port=' . $this->port . ';dbname=' . $this->dbname;
        } else {
            $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8mb4';
        }
        
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        );
        
        try {
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            echo 'Database Connection Error: ' . $this->error;
        }
    }
    
    public function query($query) {
        $this->stmt = $this->conn->prepare($query);
    }
    
    public function bind($param, $value, $type = null) {
        if(is_null($type)) {
            switch(true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        
        $this->stmt->bindValue($param, $value, $type);
    }
    
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch(PDOException $e) {
            echo 'Query Error: ' . $e->getMessage();
            return false;
        }
    }
    
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }
    
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }
    
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    public function lastInsertId($sequenceName = null) {
        if ($this->driver == 'pgsql') {
            // For PostgreSQL, the sequence name is often needed, e.g., 'products_id_seq'
            return $this->conn->lastInsertId($sequenceName);
        } else {
            return $this->conn->lastInsertId();
        }
    }
    
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    public function inTransaction() {
        return $this->conn->inTransaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollBack() {
        return $this->conn->rollBack();
    }
}
