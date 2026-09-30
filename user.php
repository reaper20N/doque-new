<?php
session_start();

if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit();
}

require 'db.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Find the logged-in user's id
$me = $pdo->prepare("SELECT id FROM Users WHERE username = :username");
$me->execute(['username' => $_SESSION['username']]);
$my_id = (int)($me->fetchColumn() ?: 0);

$error = "";

// Mark a task done / pending again (only for tasks assigned to me)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['set_status'])) {
    $task_id    = (int)($_POST['task_id'] ?? 0);
    $new_status = ($_POST['status'] ?? '') === 'completed' ? 'completed' : 'pending';

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please try again.";
    } else {
        $pdo->prepare("UPDATE tasks SET status = :s WHERE id = :id AND user_id = :uid")
            ->execute(['s' => $new_status, 'id' => $task_id, 'uid' => $my_id]);
        header("Location: user.php");
        exit();
    }
}

$stmt = $pdo->prepare("SELECT id, title, status, created_at FROM tasks WHERE user_id = :uid ORDER BY status ASC, created_at DESC");
$stmt->execute(['uid' => $my_id]);
$tasks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Nunito', 'Segoe UI', sans-serif;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
            padding: 48px 20px;
            background: linear-gradient(160deg, #FFE3EE 0%, #F4E3FF 45%, #DCEBFF 100%);
        }

        .blob { position: fixed; border-radius: 50%; filter: blur(2px); opacity: 0.5; animation: float 9s ease-in-out infinite; pointer-events: none; }
        .blob-1 { width: 220px; height: 220px; background: #FFC1DC; top: -60px; left: -60px; }
        .blob-2 { width: 160px; height: 160px; background: #C9B6F0; bottom: -40px; right: -30px; animation-delay: 2s; }
        .blob-3 { width: 110px; height: 110px; background: #AEE3F5; bottom: 60px; left: 4%; animation-delay: 4s; }
        .blob-4 { width: 90px; height: 90px; background: #FFE3A3; top: 10%; right: 8%; animation-delay: 1s; }

        @keyframes float {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-18px) scale(1.04); }
        }

        .sparkle { position: fixed; color: #ffffff; opacity: 0.85; font-size: 18px; animation: twinkle 2.4s ease-in-out infinite; pointer-events: none; }

        @keyframes twinkle {
            0%, 100% { opacity: 0.2; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.1); }
        }

        .admin-card {
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(6px);
            padding: 36px;
            border-radius: 32px;
            box-shadow: 0 20px 45px rgba(199, 148, 214, 0.35);
            max-width: 900px;
            margin: 0 auto;
            border: 2px solid #FFFFFF;
        }

        .admin-header { display: flex; align-items: center; gap: 14px; margin-bottom: 6px; }

        .mascot {
            width: 52px; height: 52px; flex-shrink: 0; border-radius: 50%;
            background: linear-gradient(145deg, #FFB3D1, #C9B6F0);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; box-shadow: 0 6px 16px rgba(255, 179, 209, 0.5);
        }

        h1 { font-family: 'Baloo 2', 'Nunito', sans-serif; color: #5A3D6B; font-size: 22px; font-weight: 700; }

        .nav { display: flex; gap: 10px; flex-wrap: wrap; margin: 14px 0 24px; }

        .logout-link {
            display: inline-block;
            padding: 8px 18px;
            background: linear-gradient(135deg, #FFA6C9, #C9A3EE);
            border-radius: 14px;
            color: #ffffff;
            text-decoration: none;
            font-family: 'Baloo 2', sans-serif;
            font-size: 13px;
            font-weight: 600;
            box-shadow: 0 8px 16px rgba(230, 150, 200, 0.35);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .logout-link:hover { transform: translateY(-2px); box-shadow: 0 12px 20px rgba(230, 150, 200, 0.45); }

        .table-wrap { overflow-x: auto; border-radius: 20px; border: 2px solid #F0DFF2; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 14px 16px; border-bottom: 1px solid #F5E8F7; font-size: 14px; color: #5A3D6B; }
        th { background: #FDF3FA; color: #7A5D8C; font-weight: 700; font-family: 'Baloo 2', sans-serif; font-size: 13px; }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: #FFFAFD; }
        tr.done td.task-cell { text-decoration: line-through; color: #B7A3C2; }

        .role-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 700; }
        .badge-pending { background: #FFF1D6; color: #B98018; }
        .badge-done { background: #E3FBEE; color: #34A06A; }

        .add-user { background: #FDF3FA; border: 2px solid #F0DFF2; border-radius: 20px; padding: 20px; margin-bottom: 24px; }
        .add-user h2 { font-family: 'Baloo 2', 'Nunito', sans-serif; color: #5A3D6B; font-size: 18px; margin-bottom: 14px; }
        .add-user-grid { display: grid; grid-template-columns: 2fr 1fr auto; gap: 12px; align-items: end; }
        .add-user label { display: block; margin-bottom: 6px; color: #7A5D8C; font-size: 13px; font-weight: 700; }

        .add-user input, .add-user select {
            width: 100%; padding: 11px 14px; border: 2px solid #F0DFF2; border-radius: 14px;
            font-size: 14px; font-family: 'Nunito', sans-serif; background: #ffffff; color: #5A3D6B;
        }
        .add-user input:focus, .add-user select:focus {
            outline: none; border-color: #F5A9C6; box-shadow: 0 0 0 4px rgba(245, 169, 198, 0.2);
        }

        .add-user button {
            padding: 12px 26px;
            background: linear-gradient(135deg, #FFA6C9, #C9A3EE);
            border: none; border-radius: 14px; color: #ffffff;
            font-size: 14px; font-family: 'Baloo 2', sans-serif; font-weight: 600;
            cursor: pointer; box-shadow: 0 8px 16px rgba(230, 150, 200, 0.35);
            transition: transform 0.2s ease;
        }
        .add-user button:hover { transform: translateY(-2px); }

        .msg { padding: 10px 14px; border-radius: 14px; margin-bottom: 14px; font-size: 13px; font-weight: 600; }
        .msg-error { background: #FFE3EC; color: #D9598C; }
        .msg-success { background: #E3FBEE; color: #34A06A; }

        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .actions form { display: inline; }
        .btn-action {
            padding: 6px 14px; border: none; border-radius: 12px;
            font-family: 'Baloo 2', sans-serif; font-size: 12px; font-weight: 600;
            cursor: pointer; transition: transform 0.2s ease;
        }
        .btn-action:hover { transform: translateY(-2px); }
        .btn-configure { background: #E3EEFF; color: #4C7CD1; }
        .btn-delete { background: #FFE3EC; color: #D9598C; }

        .empty { padding: 24px 16px; color: #9C82AC; text-align: center; font-size: 14px; }

        @media (max-width: 640px) {
            .add-user-grid { grid-template-columns: 1fr; }
            .admin-card { padding: 24px 18px; }
        }

        @media (prefers-reduced-motion: reduce) { .blob, .sparkle { animation: none; } }
    </style>
</head>
<body>

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>
    <div class="blob blob-4"></div>
    <div class="sparkle" style="top:14%; left:18%;">&#10022;</div>
    <div class="sparkle" style="top:60%; right:12%; animation-delay:1.2s;">&#10023;</div>
    <div class="sparkle" style="top:30%; right:20%; animation-delay:0.6s;">&#10022;</div>

    <div class="admin-card">
        <div class="admin-header">
            <div class="mascot">🌸</div>
            <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        </div>

        <div class="nav">
            <a class="logout-link" href="logout.php">Log Out</a>
        </div>

        <div class="add-user" style="padding-bottom:6px;">
            <h2>🌷 My Tasks</h2>
            <?php if ($error !== ""): ?><div class="msg msg-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Task</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (!$tasks): ?>
                        <tr><td colspan="3" class="empty">You have no tasks right now. New tasks will show up here.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($tasks as $t): ?>
                        <?php $is_done = $t['status'] === 'completed'; ?>
                        <tr class="<?php echo $is_done ? 'done' : ''; ?>">
                            <td class="task-cell"><?php echo htmlspecialchars($t['title']); ?></td>
                            <td>
                                <span class="role-badge <?php echo $is_done ? 'badge-done' : 'badge-pending'; ?>">
                                    <?php echo $is_done ? 'Done' : 'Pending'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="task_id" value="<?php echo (int)$t['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $is_done ? 'pending' : 'completed'; ?>">
                                        <button type="submit" name="set_status" class="btn-action btn-configure"><?php echo $is_done ? 'Reopen' : 'Mark done'; ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>