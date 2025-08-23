<?php
// sellerLogin.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/loader.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .container {
            background: white;
            width: 380px;
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
            font-size: 24px;
        }
        
        h3 {
            color: #333;
            font-weight: 500;
            margin-bottom: 20px;
            text-align: center;
            font-size: 20px;
        }
        
        input {
            width: 100%;
            padding: 12px 10px;
            margin-bottom: 20px;
            border: none;
            border-bottom: 2px solid #ddd;
            outline: none;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        input:focus {
            border-bottom: 2px solid #9966cc;
        }
        
        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 50px;
            background: #9966cc;
            color: white;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(153, 102, 204, 0.2);
            margin-bottom: 20px;
        }
        
        button:hover {
            background: #8a5cb8;
            box-shadow: 0 7px 20px rgba(153, 102, 204, 0.3);
        }
        
        p {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }
        
        a {
            text-decoration: none;
            color: #9966cc;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        #response, #register-response {
            background-color: #fff0f0;
            color: #d63031;
            padding: 10px;
            border-radius: 8px;
            font-size: 14px;
            margin-top: 15px;
            display: none;
        }
        
        #register-form {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        /* Logo styling */
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo span {
            font-size: 32px;
            font-weight: 700;
            color: #9966cc;
        }
    </style>
    <script src="js/sellerLogin.js" defer></script>
</head>
<body>
    <div class="container">
        <div class="logo">
            <span>Pookie<strong>Shop</strong></span>
        </div>
        
        <h2>Seller Login</h2>
        
        <input type="email" id="seller_email" placeholder="Enter your email" required>
        <input type="password" id="seller_password" placeholder="Enter your password" required>
        <button onclick="sellerLogin()">LOGIN</button>

        <p>New seller? <a href="#" onclick="document.getElementById('register-form').style.display='block';">Create an account</a></p>
        <p>Not a seller? <a href="login.php">Login as Customer</a></p>

        <div id="response"></div>

        <div id="register-form" style="display:none;">
            <h3>Register as Seller</h3>
            <input type="text" id="new_name" placeholder="Full Name" required>
            <input type="email" id="new_email" placeholder="Email" required>
            <input type="password" id="new_password" placeholder="Password" required>
            <input type="text" id="new_registration" placeholder="Registration ID" required>
            <button onclick="registerSeller()">REGISTER</button>
            <div id="register-response"></div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>