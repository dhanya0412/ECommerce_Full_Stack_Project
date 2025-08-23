<?php
require_once('database/database.php');
$db = new Database();
$conn = $db->conn;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['return_id'], $_POST['decision'])) {
    $return_id = (int) $_POST['return_id'];
    $decision = $_POST['decision'];  // Should be 'Approved' or 'Rejected'

    if (in_array($decision, ['Approved', 'Rejected'])) {
        $stmt = $conn->prepare("UPDATE return_request SET return_status = ? WHERE return_id = ?");
        $stmt->execute([$decision, $return_id]);
    }
}

header("Location: sellerDashboard.php");
exit();
