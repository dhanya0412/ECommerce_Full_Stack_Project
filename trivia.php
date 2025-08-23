<?php 
session_start();
if (!isset($_SESSION['current_user'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Trivia Game</title>
  <link rel="stylesheet" href="css/bootstrap.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
      text-align: center;
      font-size: 28px;
    }
    
    .form-label {
      font-weight: 500;
      color: #555;
    }
    
    .form-select {
      padding: 12px 10px;
      border: none;
      border-bottom: 2px solid #ddd;
      border-radius: 0;
      outline: none;
      font-size: 16px;
      transition: all 0.3s ease;
      background-color: transparent;
    }
    
    .form-select:focus {
      border-bottom: 2px solid #ff3399;
      box-shadow: none;
    }
    
    .btn {
      padding: 12px 25px;
      border: none;
      border-radius: 50px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .btn-primary {
      background: #ff3399;
      color: white;
      box-shadow: 0 5px 15px rgba(108, 92, 231, 0.2);
    }
    
    .btn-primary:hover {
      background: #ff3399;
      box-shadow: 0 7px 20px rgba(108, 92, 231, 0.3);
    }
    
    .btn-success {
      background: #00b894;
      color: white;
      box-shadow: 0 5px 15px rgba(0, 184, 148, 0.2);
    }
    
    .btn-success:hover {
      background: #00a382;
      box-shadow: 0 7px 20px rgba(0, 184, 148, 0.3);
    }
    
    #questionBox {
      background: #f8f9fa;
      padding: 30px;
      border-radius: 15px;
      margin-top: 30px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .question {
      font-size: 20px;
      color: #333;
      font-weight: 500;
    }
    
    .form-check {
      background: white;
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 10px;
      border: 2px solid transparent;
      transition: all 0.3s ease;
    }
    
    .form-check:hover {
      border-color: #ff3399;
      transform: translateX(5px);
    }
    
    .form-check-input {
      margin-top: 3px;
    }
    
    .form-check-label {
      margin-left: 10px;
      font-size: 16px;
      color: #444;
    }
    
    .alert {
      padding: 15px 20px;
      border-radius: 15px;
      margin-top: 20px;
      font-weight: 500;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    
    .alert-success {
      background: #d4edda;
      color: #155724;
      border-left: 5px solid #28a745;
    }
    
    .alert-danger {
      background: #f8d7da;
      color: #721c24;
      border-left: 5px solid #dc3545;
    }
    
    .alert-warning {
      background: #fff3cd;
      color: #856404;
      border-left: 5px solid #ffc107;
    }
    
    /* Logo styling */
    .logo {
      text-align: center;
      margin-bottom: 20px;
    }
    
    .logo span {
      font-size: 32px;
      font-weight: 700;
      color: #ff3399;
    }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="logo">
      <span>Trivia<strong>Game</strong></span>
    </div>
    
    <h2 class="mb-4 text-center">🎮 Play Trivia & Win Discount</h2>

    <div class="row mb-3 justify-content-center">
      <div class="col-md-4">
        <label for="theme" class="form-label">Theme:</label>
        <select id="theme" class="form-select">
          <option value="Tech">Tech</option>
          <option value="Movies">Movies</option>
          <option value="General Knowledge">General Knowledge</option>
          <option value="Science">Science</option>
          <option value="Sports">Sports</option>
        </select>
      </div>
      <div class="col-md-4">
        <label for="difficulty" class="form-label">Difficulty:</label>
        <select id="difficulty" class="form-select">
          <option value="Easy">Easy</option>
          <option value="Medium">Medium</option>
          <option value="Hard">Hard</option>
        </select>
      </div>
      <div class="col-md-4 d-flex justify-content-center mt-4">
        <button id="startTrivia" class="btn btn-primary btn-lg">Start Trivia</button>
      </div>
    </div>

    <div id="questionBox" style="display: none;" class="mb-4 text-center">
      <h4 id="questionText" class="question mb-4"></h4>
      <div id="options" class="mt-3"></div>
      <button id="submitAnswer" class="btn btn-success btn-lg mt-4">Submit Answer</button>
    </div>

    <div id="result" class="mt-3 text-center"></div>
  </div>

  <script>
    let currentQuestion = {};
    let selectedAnswer = null;

    $('#startTrivia').click(() => {
      $.post('ajaxhandler/triviaAjax.php', {
        action: 'fetch_question',
        theme: $('#theme').val(),
        difficulty: $('#difficulty').val()
      }, function (data) {
        if (data.status === 'played') {
          $('#result').html('<div class="alert alert-warning">You already played today!</div>');
        } else if (data.status === 'ok') {
          currentQuestion = data.question;
          $('#questionText').text(currentQuestion.question_text);
          $('#options').empty();
          ['A','B','C','D'].forEach(opt => {
            const text = currentQuestion['option_' + opt.toLowerCase()];
            $('#options').append(
              `<div class='form-check'>
                <input class='form-check-input' type='radio' name='option' value='${opt}' id='opt${opt}'>
                <label class='form-check-label' for='opt${opt}'>${text}</label>
              </div>`
            );
          });
          $('#questionBox').show();
        }
      }, 'json');
    });

    $('#submitAnswer').click(() => {
      selectedAnswer = $('input[name="option"]:checked').val();
      if (!selectedAnswer) {
        alert("Please select an option");
        return;
      }
      $.post('ajaxhandler/triviaAjax.php', {
        action: 'submit_answer',
        question_id: currentQuestion.question_id,
        answer: selectedAnswer
      }, function (data) {
        if (data.correct) {
          $('#result').html(`<div class='alert alert-success'>🎉 Correct! Use code <strong>${data.code}</strong> to get ${data.discount}% off.</div>`);
        } else {
          $('#result').html('<div class="alert alert-danger">❌ Incorrect! Better luck tomorrow!</div>');
        }
        $('#questionBox').hide();
      }, 'json');
    });
  </script>
</body>
</html>