<?php
require_once('database/database.php');

class sellerAuth {
    private $conn;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->conn;
    }

    public function login($email, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM seller WHERE seller_email = :email");
        $stmt->execute(['email' => $email]);
        $seller = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check plain-text match (insecure — only for testing)
        if ($seller && $password === $seller['seller_password']) {
            return ["status" => "SUCCESS", "seller" => $seller];
        } else {
            return ["status" => "ERROR", "message" => "Invalid email or password"];
        }
    }

    public function register($name, $email, $password, $reg_id) {
        $stmt = $this->conn->prepare("INSERT INTO seller (seller_name, seller_email, seller_password, registration_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $reg_id]);

        return ["status" => "SUCCESS", "message" => "Seller registered successfully"];
    }
}
?>
