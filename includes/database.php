<?php
require_once 'config.php';

/**
 * Database connection class
 */
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $conn;
    private $error;
    private $stmt;
    
    /**
     * Constructor - establishes database connection
     */
    public function __construct() {
        // Set DSN (Data Source Name)
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8mb4';
        
        // Set options for PDO
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        );
        
        // Create a new PDO instance
        try {
            $this->conn = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            echo 'Database Connection Error: ' . $this->error;
        }
    }
    
    /**
     * Prepare statement with query
     * 
     * @param string $query - The SQL query to prepare
     */
    public function query($query) {
        $this->stmt = $this->conn->prepare($query);
    }
    
    /**
     * Bind values to prepared statement
     * 
     * @param string $param - Parameter name/placeholder
     * @param mixed $value - The value to bind
     * @param mixed $type - Optional parameter type
     */
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
    
    /**
     * Execute the prepared statement
     * 
     * @return boolean
     */
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch(PDOException $e) {
            echo 'Query Error: ' . $e->getMessage();
            return false;
        }
    }
    
    /**
     * Get result set as array of objects
     * 
     * @return array
     */
    public function resultSet() {
        $this->execute();
        return $this->stmt->fetchAll();
    }
    
    /**
     * Get single record as object
     * 
     * @return object
     */
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }
    
    /**
     * Get record count
     * 
     * @return int
     */
    public function rowCount() {
        return $this->stmt->rowCount();
    }
    
    /**
     * Get last inserted ID
     * 
     * @return int
     */
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    /**
     * Begin a transaction
     */
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    /**
     * Check if a transaction is active
     */
    public function inTransaction() {
        return $this->conn->inTransaction();
    }
    
    /**
     * End a transaction and commit
     */
    public function commit() {
        return $this->conn->commit();
    }
    
    /**
     * Cancel a transaction and roll back
     */
    public function rollBack() {
        return $this->conn->rollBack();
    }
}
