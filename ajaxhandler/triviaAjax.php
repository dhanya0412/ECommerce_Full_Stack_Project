<?php
session_start();
require_once("../database/database.php");

$dbo = new Database();
$conn = $dbo->conn;

$cust_id = $_SESSION['current_user'] ?? null;
if (!$cust_id) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'fetch_question') {
    $theme = $_POST['theme'] ?? 'Tech';
    $difficulty = $_POST['difficulty'] ?? 'Easy';

    $check = $conn->prepare("SELECT * FROM trivia_attempts WHERE customer_id = ? AND attempt_date = CURDATE()");
    $check->execute([$cust_id]);
    if ($check->rowCount() > 0) {
        echo json_encode(['status' => 'played']);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM trivia_questions WHERE theme = ? AND difficulty = ? ORDER BY RAND() LIMIT 1");
    $stmt->execute([$theme, $difficulty]);
    $question = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'ok', 'question' => $question]);
    exit;
}

if ($action === 'submit_answer') {
    $question_id = $_POST['question_id'];
    $answer = $_POST['answer'];

    $stmt = $conn->prepare("SELECT * FROM trivia_questions WHERE question_id = ?");
    $stmt->execute([$question_id]);
    $q = $stmt->fetch(PDO::FETCH_ASSOC);

    $correct = strtoupper($q['correct_option']) === strtoupper($answer);
    $discount = 0;
    $code = null;

    if ($correct) {
        $difficulty = strtolower($q['difficulty']);
        $discount = $difficulty === 'easy' ? 5 : ($difficulty === 'medium' ? 10 : 15);
        $code = strtoupper(substr($difficulty, 0, 1)) . rand(1000, 9999); // e.g., E1234
    }

    $insert = $conn->prepare("INSERT INTO trivia_attempts (customer_id, question_id, attempt_date, score, discount_code, difficulty) VALUES (?, ?, CURDATE(), ?, ?, ?)");
    $insert->execute([$cust_id, $question_id, $correct ? 1 : 0, $code, $q['difficulty']]);

    echo json_encode([
        'correct' => $correct,
        'discount' => $discount,
        'code' => $code
    ]);
    exit;
}

if ($action === 'check_code') {
    $code = $_POST['code'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM trivia_attempts WHERE customer_id = ? AND discount_code = ? AND attempt_date = CURDATE() AND score = 1");
    $stmt->execute([$cust_id, $code]);
    $valid = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($valid) {
        echo json_encode(['valid' => true, 'discount' => ($valid['difficulty'] === 'Easy' ? 5 : ($valid['difficulty'] === 'Medium' ? 10 : 15))]);
    } else {
        echo json_encode(['valid' => false]);
    }
    exit;
}

if ($action === 'leaderboard') {
    $stmt = $conn->query("SELECT c.cust_name, SUM(score) AS total_score FROM trivia_attempts t JOIN customer c ON t.customer_id = c.customer_id GROUP BY t.customer_id ORDER BY total_score DESC LIMIT 10");
    $leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['leaders' => $leaders]);
    exit;
}
