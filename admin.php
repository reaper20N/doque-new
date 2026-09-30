<?php
session_start();
require 'db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied. You must be an administrator.");
}

// CSRF token for the add-user form
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = "";
$success = "";

// Handle "Add User" form submission
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_user'])) {
    $new_username = trim($_POST['new_username'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $new_role     = $_POST['new_role'] ?? 'user';

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please try again.";
    } elseif ($new_username === '' || $new_password === '') {
        $error = "Username and password are required!";
    } elseif (!in_array($new_role, ['user', 'admin'], true)) {
        $error = "Invalid role selected!";
    } else {
        $check = $pdo->prepare("SELECT id FROM Users WHERE username = :username");
        $check->execute(['username' => $new_username]);

        if ($check->fetch()) {
            $error = "That username is already taken!";
        } else {
            // NOTE: plain text to stay compatible with login.php.
            // For production, use password_hash() here and password_verify() in login.php.
            $insert = $pdo->prepare("INSERT INTO Users (username, password, role) VALUES (:username, :password, :role)");
            $insert->execute([
                'username' => $new_username,
                'password' => $new_password,
                'role'     => $new_role
            ]);
            $success = "User '" . $new_username . "' was added as " . $new_role . "!";
        }
    }
}

// Handle "Delete User"
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_user'])) {
    $del_id = (int)($_POST['user_id'] ?? 0);

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please try again.";
    } else {
        $row = $pdo->prepare("SELECT username FROM Users WHERE id = :id");
        $row->execute(['id' => $del_id]);
        $target = $row->fetch();

        if (!$target) {
            $error = "User not found!";
        } elseif ($target['username'] === $_SESSION['username']) {
            $error = "You can't delete your own account!";
        } else {
            $pdo->prepare("DELETE FROM Users WHERE id = :id")->execute(['id' => $del_id]);
            $success = "User '" . $target['username'] . "' was deleted.";
        }
    }
}

// Handle "Configure User" (edit username / password / role)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['configure_user'])) {
    $edit_id       = (int)($_POST['user_id'] ?? 0);
    $edit_username = trim($_POST['edit_username'] ?? '');
    $edit_password = $_POST['edit_password'] ?? '';
    $edit_role     = $_POST['edit_role'] ?? 'user';

    $row = $pdo->prepare("SELECT username FROM Users WHERE id = :id");
    $row->execute(['id' => $edit_id]);
    $target = $row->fetch();

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please try again.";
    } elseif (!$target) {
        $error = "User not found!";
    } elseif ($edit_username === '' || $edit_password === '') {
        $error = "Username and password are required!";
    } elseif (!in_array($edit_role, ['user', 'admin'], true)) {
        $error = "Invalid role selected!";
    } elseif ($target['username'] === $_SESSION['username'] && $edit_role !== 'admin') {
        $error = "You can't remove your own admin role!";
    } else {
        $dup = $pdo->prepare("SELECT id FROM Users WHERE username = :username AND id <> :id");
        $dup->execute(['username' => $edit_username, 'id' => $edit_id]);

        if ($dup->fetch()) {
            $error = "That username is already taken!";
        } else {
            // NOTE: plain text to stay compatible with login.php (see note above).
            $pdo->prepare("UPDATE Users SET username = :username, password = :password, role = :role WHERE id = :id")
                ->execute(['username' => $edit_username, 'password' => $edit_password, 'role' => $edit_role, 'id' => $edit_id]);

            // Keep the session in sync if the admin edited their own account
            if ($target['username'] === $_SESSION['username']) {
                $_SESSION['username'] = $edit_username;
            }
            $success = "User '" . $edit_username . "' was updated.";
        }
    }
}

// Fetch all users to display in the table
$stmt = $pdo->query("SELECT id, username, password, role FROM Users");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Area</title>
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
            position: relative;
            overflow-x: hidden;
            padding: 48px 20px;
            background: linear-gradient(160deg, #FFE3EE 0%, #F4E3FF 45%, #DCEBFF 100%);
        }

        /* soft floating blobs for a dreamy backdrop */
        .blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(2px);
            opacity: 0.5;
            animation: float 9s ease-in-out infinite;
            pointer-events: none;
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
            left: 4%;
            animation-delay: 4s;
        }

        .blob-4 {
            width: 90px;
            height: 90px;
            background: #FFE3A3;
            top: 10%;
            right: 8%;
            animation-delay: 1s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-18px) scale(1.04); }
        }

        .sparkle {
            position: fixed;
            color: #ffffff;
            opacity: 0.85;
            font-size: 18px;
            animation: twinkle 2.4s ease-in-out infinite;
            pointer-events: none;
        }

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

        .admin-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 6px;
        }

        .mascot {
            width: 52px;
            height: 52px;
            flex-shrink: 0;
            border-radius: 50%;
            background: linear-gradient(145deg, #FFB3D1, #C9B6F0);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 6px 16px rgba(255, 179, 209, 0.5);
        }

        h1 {
            font-family: 'Baloo 2', 'Nunito', sans-serif;
            color: #5A3D6B;
            font-size: 22px;
            font-weight: 700;
        }

        .logout-link {
            display: inline-block;
            margin: 14px 0 24px;
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

        .logout-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 20px rgba(230, 150, 200, 0.45);
        }

        .table-wrap {
            overflow-x: auto;
            border-radius: 20px;
            border: 2px solid #F0DFF2;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 14px 16px;
            border-bottom: 1px solid #F5E8F7;
            font-size: 14px;
            color: #5A3D6B;
        }

        th {
            background: #FDF3FA;
            color: #7A5D8C;
            font-weight: 700;
            font-family: 'Baloo 2', sans-serif;
            font-size: 13px;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        tbody tr:hover {
            background: #FFFAFD;
        }

        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
        }

        .role-admin {
            background: #FFE3EC;
            color: #D9598C;
        }

        .role-user {
            background: #E3EEFF;
            color: #4C7CD1;
        }

        .add-user {
            background: #FDF3FA;
            border: 2px solid #F0DFF2;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .add-user h2 {
            font-family: 'Baloo 2', 'Nunito', sans-serif;
            color: #5A3D6B;
            font-size: 18px;
            margin-bottom: 14px;
        }

        .add-user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            align-items: end;
        }

        .add-user label {
            display: block;
            margin-bottom: 6px;
            color: #7A5D8C;
            font-size: 13px;
            font-weight: 700;
        }

        .add-user input,
        .add-user select {
            width: 100%;
            padding: 11px 14px;
            border: 2px solid #F0DFF2;
            border-radius: 14px;
            font-size: 14px;
            font-family: 'Nunito', sans-serif;
            background: #ffffff;
            color: #5A3D6B;
        }

        .add-user input:focus,
        .add-user select:focus {
            outline: none;
            border-color: #F5A9C6;
            box-shadow: 0 0 0 4px rgba(245, 169, 198, 0.2);
        }

        .add-user button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #FFA6C9, #C9A3EE);
            border: none;
            border-radius: 14px;
            color: #ffffff;
            font-size: 14px;
            font-family: 'Baloo 2', sans-serif;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 8px 16px rgba(230, 150, 200, 0.35);
        }

        .add-user button:hover {
            transform: translateY(-2px);
        }

        .msg {
            padding: 10px 14px;
            border-radius: 14px;
            margin-bottom: 14px;
            font-size: 13px;
            font-weight: 600;
        }

        .msg-error { background: #FFE3EC; color: #D9598C; }
        .msg-success { background: #E3FBEE; color: #34A06A; }

        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 6px 14px;
            border: none;
            border-radius: 12px;
            font-family: 'Baloo 2', sans-serif;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .btn-action:hover { transform: translateY(-2px); }
        .btn-configure { background: #E3EEFF; color: #4C7CD1; }
        .btn-delete { background: #FFE3EC; color: #D9598C; }
        .btn-action:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 10;
            background: rgba(90, 61, 107, 0.35);
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.open { display: flex; }

        .modal {
            background: #ffffff;
            border-radius: 28px;
            padding: 30px;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 20px 45px rgba(199, 148, 214, 0.45);
        }

        .modal h2 {
            font-family: 'Baloo 2', sans-serif;
            color: #5A3D6B;
            font-size: 20px;
            margin-bottom: 14px;
        }

        .modal label {
            display: block;
            margin: 10px 0 6px;
            color: #7A5D8C;
            font-size: 13px;
            font-weight: 700;
        }

        .modal input, .modal select {
            width: 100%;
            padding: 11px 14px;
            border: 2px solid #F0DFF2;
            border-radius: 14px;
            font-size: 14px;
            font-family: 'Nunito', sans-serif;
            color: #5A3D6B;
        }

        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-buttons button {
            flex: 1;
            padding: 11px;
            border: none;
            border-radius: 14px;
            font-family: 'Baloo 2', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-save { background: linear-gradient(135deg, #FFA6C9, #C9A3EE); color: #ffffff; }
        .btn-cancel { background: #F5E8F7; color: #7A5D8C; }

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
    <div class="sparkle" style="top:14%; left:18%;">✦</div>
    <div class="sparkle" style="top:60%; right:12%; animation-delay:1.2s;">✧</div>
    <div class="sparkle" style="top:30%; right:20%; animation-delay:0.6s;">✦</div>

    <div class="admin-card">
        <div class="admin-header">
            <div class="mascot">🌸</div>
            <h1>Welcome to the Admin Area, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
        </div>
        <a class="logout-link" href="task.php">Manage Tasks</a>
        <a class="logout-link" href="logout.php">Log Out</a>

        <div class="add-user">
            <h2>Create New User</h2>

            <?php if ($error !== ""): ?>
                <div class="msg msg-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success !== ""): ?>
                <div class="msg msg-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="add-user-grid">
                    <div>
                        <label for="new_username">Username</label>
                        <input type="text" id="new_username" name="new_username" required placeholder="Username">
                    </div>
                    <div>
                        <label for="new_password">Password</label>
                        <input type="password" id="new_password" name="new_password" required placeholder="Password">
                    </div>
                    <div>
                        <label for="new_role">Role</label>
                        <select id="new_role" name="new_role">
                            <option value="user" selected>user</option>
                            <option value="admin">admin</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" name="add_user">Add User</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Password</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['password']); ?></td>
                            <td>
                                <span class="role-badge <?php echo $user['role'] === 'admin' ? 'role-admin' : 'role-user'; ?>">
                                    <?php echo htmlspecialchars($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <button type="button" class="btn-action btn-configure"
                                        data-id="<?php echo (int)$user['id']; ?>"
                                        data-username="<?php echo htmlspecialchars($user['username'], ENT_QUOTES); ?>"
                                        data-password="<?php echo htmlspecialchars($user['password'], ENT_QUOTES); ?>"
                                        data-role="<?php echo htmlspecialchars($user['role'], ENT_QUOTES); ?>"
                                        onclick="openConfigure(this)">Configure</button>

                                    <form method="POST" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn-action btn-delete"
                                            <?php echo $user['username'] === $_SESSION['username'] ? 'disabled title="You cannot delete yourself"' : ''; ?>>Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-overlay" id="configureModal">
        <div class="modal">
            <h2>Configure User</h2>
            <form method="POST" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="user_id" id="edit_id">

                <label for="edit_username">Username</label>
                <input type="text" name="edit_username" id="edit_username" required>

                <label for="edit_password">Password</label>
                <input type="text" name="edit_password" id="edit_password" required>

                <label for="edit_role">Role</label>
                <select name="edit_role" id="edit_role">
                    <option value="user">user</option>
                    <option value="admin">admin</option>
                </select>

                <div class="modal-buttons">
                    <button type="button" class="btn-cancel" onclick="closeConfigure()">Cancel</button>
                    <button type="submit" name="configure_user" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('configureModal');

        function openConfigure(btn) {
            document.getElementById('edit_id').value = btn.dataset.id;
            document.getElementById('edit_username').value = btn.dataset.username;
            document.getElementById('edit_password').value = btn.dataset.password;
            document.getElementById('edit_role').value = btn.dataset.role;
            modal.classList.add('open');
        }

        function closeConfigure() {
            modal.classList.remove('open');
        }

        modal.addEventListener('click', function (e) {
            if (e.target === modal) closeConfigure();
        });
    </script>

</body>
</html>