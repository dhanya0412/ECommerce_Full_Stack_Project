<?php
require_once "database/database.php";
$dbo = new Database();
$conn = $dbo->conn;

$stmt = $conn->query("SELECT c.cust_name, SUM(t.score) as total_score FROM trivia_attempts t JOIN customer c ON c.customer_id = t.customer_id GROUP BY t.customer_id ORDER BY total_score DESC LIMIT 10");
$leaders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>🏆 Trivia Leaderboard</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link rel="stylesheet" href="css/trivia.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }
    
    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    
    .container {
      background: white;
      width: 100%;
      max-width: 800px;
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
    }
    
    .container:hover {
      transform: translateY(-5px);
    }
    
    h2 {
      color: #333;
      font-weight: 600;
      margin-bottom: 30px;
      font-size: 28px;
    }
    
    .leaderboard {
      margin-top: 20px;
    }
    
    .table {
      border-collapse: separate;
      border-spacing: 0;
      width: 100%;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .table thead {
      background-color: #ff3399;
      color: white;
    }
    
    .table th {
      padding: 15px;
      font-weight: 500;
      text-align: left;
    }
    
    .table td {
      padding: 15px;
      border-bottom: 1px solid #f0f0f0;
    }
    
    .table tbody tr:hover {
      background-color: #f9f9f9;
    }
    
    .table tbody tr:last-child td {
      border-bottom: none;
    }
    
    .btn-outline-primary {
      border: 2px solid #ff3399;
      color: #ff3399;
      padding: 8px 20px;
      border-radius: 50px;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .btn-outline-primary:hover {
      background-color: #ff3399;
      color: white;
      box-shadow: 0 5px 15px rgba(255, 51, 153, 0.2);
    }
    
    .alert-info {
      background-color: #e7f5ff;
      color: #0082ca;
      padding: 15px;
      border-radius: 10px;
      font-weight: 500;
      text-align: center;
    }
    
    /* Rank styles */
    tbody tr:nth-child(1) td:first-child {
      font-weight: 600;
      color: gold;
      font-size: 18px;
    }
    
    tbody tr:nth-child(2) td:first-child {
      font-weight: 600;
      color: silver;
      font-size: 16px;
    }
    
    tbody tr:nth-child(3) td:first-child {
      font-weight: 600;
      color: #cd7f32; /* bronze */
      font-size: 16px;
    }
    
    /* Score styling */
    td:last-child {
      font-weight: 500;
      color: #ff3399;
    }
    
    /* Logo styling */
    .logo {
      text-align: center;
      margin-bottom: 20px;
    }
    
    .logo span {
      font-size: 28px;
      font-weight: 700;
      color: #ff3399;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="logo">
      <span>Trivia<strong>Masters</strong></span>
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>🏆 Top Trivia Players</h2>
      <a href="e_commerce.php" class="btn btn-outline-primary">← Back to Shop</a>
    </div>

    <?php if (count($leaders) > 0): ?>
      <div class="leaderboard">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>#</th>
              <th>Player</th>
              <th>Score</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($leaders as $index => $leader): ?>
              <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($leader['cust_name']) ?></td>
                <td><?= $leader['total_score'] ?> pts</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert alert-info">No trivia plays yet!</div>
    <?php endif; ?>
  </div>
</body>
</html>