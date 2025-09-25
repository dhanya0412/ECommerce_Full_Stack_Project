 <?php
class CustomerAuth {
    private $conn;

    public function __construct($pdo) {
        $this->conn = $pdo;
    }

    //Login method
    public function validateCustomerLogin($email, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM customer WHERE cust_email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $password === $user['cust_password']) {
            return $user;
        }
        return false;
    }

    //Check if email exists
    public function userExists($email) {
        $stmt = $this->conn->prepare("SELECT customer_id FROM customer WHERE cust_email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() !== false;
    }

    //Register new customer
    public function registerCustomer($name, $email, $password) {
        if ($this->userExists($email)) {
            return false;
        }

        $hashedPassword = $password;
        $stmt = $this->conn->prepare("INSERT INTO customer (cust_name, cust_email, cust_password, registration_date) VALUES (?, ?, ?, NOW())");
        return $stmt->execute([$name, $email, $hashedPassword]);
    }
}
