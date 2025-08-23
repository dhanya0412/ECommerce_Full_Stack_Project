<?php
class Database {
    public $conn;

    public function __construct() {
        $servername = "localhost";
        $username = "root";
        $password = "";
        $port=3307;
        $dbname = "ecommerce_db";

        try {
            $this->conn = new PDO("mysql:host=$servername;port=$port;dbname=$dbname", $username, $password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // echo "Connected successfully"; // Remove this to prevent unwanted output
        } catch (PDOException $e) {
            die(json_encode(["status" => "ERROR", "message" => "Connection failed: " . $e->getMessage()]));
        }
    }
}
?>
