<?php
// Initialize the session
session_start();

// Unset all of the session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session on the server
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="1.8;url=login.php">
    <title>Logging Out</title>
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

        .logout-card {
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
            text-align: center;
        }

        .mascot {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            border-radius: 50%;
            background: linear-gradient(145deg, #FFB3D1, #C9B6F0);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            box-shadow: 0 6px 16px rgba(255, 179, 209, 0.5);
            animation: wave 1.6s ease-in-out infinite;
        }

        @keyframes wave {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-8deg); }
            75% { transform: rotate(8deg); }
        }

        .logout-card h2 {
            font-family: 'Baloo 2', 'Nunito', sans-serif;
            margin-bottom: 4px;
            color: #5A3D6B;
            font-size: 26px;
            font-weight: 700;
        }

        .logout-card .subtitle {
            color: #9C82AC;
            font-size: 13px;
            margin-bottom: 22px;
        }

        .spinner-dots {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 22px;
        }

        .spinner-dots span {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FFA6C9, #C9A3EE);
            animation: bounce 1.2s ease-in-out infinite;
        }

        .spinner-dots span:nth-child(2) { animation-delay: 0.15s; }
        .spinner-dots span:nth-child(3) { animation-delay: 0.3s; }

        @keyframes bounce {
            0%, 80%, 100% { transform: translateY(0); opacity: 0.6; }
            40% { transform: translateY(-8px); opacity: 1; }
        }

        .manual-link {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #FFA6C9, #C9A3EE);
            border-radius: 18px;
            color: #ffffff;
            font-size: 14px;
            font-family: 'Baloo 2', sans-serif;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 10px 20px rgba(230, 150, 200, 0.4);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .manual-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 24px rgba(230, 150, 200, 0.5);
        }

        @media (prefers-reduced-motion: reduce) {
            .blob, .sparkle, .mascot, .spinner-dots span {
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

    <div class="logout-card">
        <div class="mascot">👋</div>
        <h2>See You Soon~</h2>
        <p class="subtitle">You've been logged out safely.</p>

        <div class="spinner-dots">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <a class="manual-link" href="login.php">Back to Login</a>
    </div>

</body>
</html>