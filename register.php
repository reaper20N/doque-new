<?php
session_start();
require 'db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($username === '' || $password === '' || $confirm_password === '') {
        $error = "All fields are required!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM Users WHERE username = :username");
        $stmt->execute(['username' => $username]);

        if ($stmt->fetch()) {
            $error = "That username is already taken!";
        } else {
            // NOTE: login.php currently compares passwords in plain text
            // (password === $user['password']), so this stores plain text
            // to stay compatible with it. For production, switch both
            // login.php and this file to password_hash()/password_verify().
            $role = 'user'; // default role for self-registration

            $stmt = $pdo->prepare("INSERT INTO Users (username, password, role) VALUES (:username, :password, :role)");
            $stmt->execute([
                'username' => $username,
                'password' => $password,
                'role' => $role
            ]);

            $success = "Account created successfully! You can now log in.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Nunito', 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
            background: linear-gradient(160deg, #FFE3EE 0%, #F4E3FF 45%, #DCEBFF 100%);
        }

        /* soft floating blobs for a dreamy backdrop */
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(2px);
            opacity: 0.55;
            animation: float 9s ease-in-out infinite;
        }

        .blob-1 {
            width: 220px;
            height: 220px;
            background: #FFC1DC;
            top: -60px;
            left: -60px;
            animation-delay: 0s;
        }

        .blob-2 {
            width: 160px;
            height: 160px;
            background: #C9B6F0;
            bottom: -40px;
            right: -30px;
            animation-delay: 2s;
        }

        .blob-3 {
            width: 110px;
            height: 110px;
            background: #AEE3F5;
            bottom: 60px;
            left: 6%;
            animation-delay: 4s;
        }

        .blob-4 {
            width: 90px;
            height: 90px;
            background: #FFE3A3;
            top: 12%;
            right: 10%;
            animation-delay: 1s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-18px) scale(1.04); }
        }

        .sparkle {
            position: absolute;
            color: #ffffff;
            opacity: 0.85;
            font-size: 18px;
            animation: twinkle 2.4s ease-in-out infinite;
        }

        @keyframes twinkle {
            0%, 100% { opacity: 0.2; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.1); }
        }

        .login-card {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(6px);
            padding: 44px 36px;
            border-radius: 32px;
            box-shadow: 0 20px 45px rgba(199, 148, 214, 0.35);
            width: 100%;
            max-width: 380px;
            border: 2px solid #FFFFFF;
        }

        .mascot {
            width: 64px;
            height: 64px;
            margin: 0 auto 12px;
            border-radius: 50%;
            background: linear-gradient(145deg, #FFB3D1, #C9B6F0);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            box-shadow: 0 6px 16px rgba(255, 179, 209, 0.5);
        }

        .login-card h2 {
            font-family: 'Baloo 2', 'Nunito', sans-serif;
            margin-bottom: 4px;
            color: #5A3D6B;
            text-align: center;
            font-size: 26px;
            font-weight: 700;
        }

        .login-card .subtitle {
            text-align: center;
            color: #9C82AC;
            font-size: 13px;
            margin-bottom: 26px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            color: #7A5D8C;
            font-size: 13px;
            font-weight: 700;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #F0DFF2;
            border-radius: 16px;
            font-size: 14px;
            font-family: 'Nunito', sans-serif;
            background: #FFFAFD;
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }

        .form-group input::placeholder {
            color: #C7B3CF;
        }

        .form-group input:focus {
            outline: none;
            border-color: #F5A9C6;
            box-shadow: 0 0 0 4px rgba(245, 169, 198, 0.2);
        }

        button[type="submit"] {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #FFA6C9, #C9A3EE);
            border: none;
            border-radius: 18px;
            color: #ffffff;
            font-size: 15px;
            font-family: 'Baloo 2', sans-serif;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 10px 20px rgba(230, 150, 200, 0.4);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 24px rgba(230, 150, 200, 0.5);
        }

        button[type="submit"]:active {
            transform: translateY(0);
        }

        .error-message {
            background: #FFE3EC;
            color: #D9598C;
            padding: 10px 14px;
            border-radius: 14px;
            margin-bottom: 18px;
            font-size: 13px;
            text-align: center;
            font-weight: 600;
        }

        .success-message {
            background: #E3FBEE;
            color: #34A06A;
            padding: 10px 14px;
            border-radius: 14px;
            margin-bottom: 18px;
            font-size: 13px;
            text-align: center;
            font-weight: 600;
        }

        .switch-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            font-size: 13px;
            color: #9C82AC;
        }

        .switch-link a {
            color: #D67BB0;
            text-decoration: none;
            font-weight: 700;
        }

        .switch-link a:hover {
            text-decoration: underline;
        }

        @media (prefers-reduced-motion: reduce) {
            .blob, .sparkle {
                animation: none;
            }
        }
    </style>
</head>
<body>

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="blob blob-4"></div>
    <div class="sparkle" style="top:18%; left:20%;">✦</div>
    <div class="sparkle" style="top:70%; right:14%; animation-delay:1.2s;">✧</div>
    <div class="sparkle" style="top:40%; right:22%; animation-delay:0.6s;">✦</div>

    <div class="login-card">
        <div class="mascot">🌸</div>
        <form method="POST">
            <h2>Create Account</h2>
            <p class="subtitle">Join us, it'll only take a moment~</p>

            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="Choose a username">
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Choose a password">
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required placeholder="Re-enter your password">
            </div>

            <button type="submit">Register</button>

            <span class="switch-link">Already have an account? <a href="login.php">Log In</a></span>
        </form>
    </div>

</body>
</html>