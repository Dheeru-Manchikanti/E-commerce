<?php
class Database {
    private $pdo;

    public function __construct() {
        try {
            $dbUrl = "postgresql://ecommerce_pg_jxpe_user:Rl5f25xZ92UCrkJBGDw5UzkJDl8bGkq7@dpg-d3agna8dl3ps73eq4m8g-a/ecommerce_pg_jxpe";

            $dbParts = parse_url($dbUrl);

            $host = $dbParts["host"];
            $port = $dbParts["port"] ?? 5432;
            $user = $dbParts["user"];
            $pass = $dbParts["pass"];
            $dbname = ltrim($dbParts["path"], "/");


            $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";


            $this->pdo = new PDO($dsn, $user, $pass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (Exception $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
    }

    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
