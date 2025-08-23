<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="css/loader.css">
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
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .signupform {
            background: white;
            width: 380px;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .signupform:hover {
            transform: translateY(-5px);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo span {
            font-size: 32px;
            font-weight: 700;
            color: #ff3399;
        }
        
        h2 {
            color: #333;
            font-weight: 600;
            margin-bottom: 30px;
            text-align: center;
            font-size: 24px;
        }
        
        .inputgroup {
            position: relative;
            margin-bottom: 25px;
        }
        
        .inputgroup input {
            width: 100%;
            padding: 12px 10px;
            border: none;
            border-bottom: 2px solid #ddd;
            outline: none;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .inputgroup input:focus {
            border-bottom: 2px solid #ff3399;
        }
        
        .inputgroup label {
            position: absolute;
            left: 0;
            top: 12px;
            font-size: 16px;
            color: #999;
            pointer-events: none;
            transition: all 0.3s ease;
        }
        
        .inputgroup input:focus ~ label,
        .inputgroup input:valid ~ label {
            top: -20px;
            font-size: 14px;
            color: #ff3399;
            font-weight: 500;
        }
        
        .btnlogin {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 50px;
            background: #ff3399;
            color: white;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(255, 51, 153, 0.2);
        }
        
        .btnlogin:hover {
            background: #e61e8c;
            box-shadow: 0 7px 20px rgba(255, 51, 153, 0.3);
        }
        
        .btnlogin.inactivecolor {
            background: #ccc;
            box-shadow: none;
        }
        
        .divcallforaction {
            text-align: center;
            margin-top: 20px;
        }
        
        .diverror {
            background-color: #fff0f0;
            color: #d63031;
            padding: 10px;
            border-radius: 8px;
            font-size: 14px;
            display: none;
            margin-top: 15px;
            margin-bottom: 15px;
        }
        
        .topmargin {
            margin-top: 15px;
        }
        
        .topmarginlarge {
            margin-top: 25px;
        }
        
        a {
            text-decoration: none;
            color: #ff3399;
            transition: color 0.3s ease;
        }
        
        a:hover {
            text-decoration: underline;
        }
        
        .lockscreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            display: none;
        }
        
        .lblwait {
            color: #ff3399;
            font-weight: 500;
            margin-top: 20px;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="signupform">
        <div class="logo">
            <span>Pookie<strong>Shop</strong></span>
        </div>
        
        <h2>Create Account</h2>
        
        <div class="inputgroup topmarginlarge">
            <input type="text" id="txtName" required>
            <label for="txtName">FULL NAME</label>
        </div>
        
        <div class="inputgroup topmargin">
            <input type="email" id="txtEmail" required>
            <label for="txtEmail">EMAIL</label>
        </div>
        
        <div class="inputgroup topmargin">
            <input type="password" id="txtPassword" required>
            <label for="txtPassword">PASSWORD</label>
        </div>
        
        <div class="inputgroup topmargin">
            <input type="password" id="txtConfirmPassword" required>
            <label for="txtConfirmPassword">CONFIRM PASSWORD</label>
        </div>

        <div class="diverror" id="diverror">
            <span id="errormessage"></span>
        </div>

        <div class="divcallforaction topmargin">
            <button class="btnlogin inactivecolor" id="btnSignup">SIGN UP</button>
        </div>

        <div class="divcallforaction topmargin">
            <a href="login.php" style="color:#ff3399; font-weight:500;">Already have an account? Login</a>
        </div>
    </div>

    <!-- Loading screen -->
    <div class="lockscreen" id="lockscreen">
        <div class="spinner"></div>
        <div class="lblwait">PLEASE WAIT</div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/signup.js"></script>
</body>
</html>