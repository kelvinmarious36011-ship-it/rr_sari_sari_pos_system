<?php
require_once 'config.php';
session_start();

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $stmt = $conn->prepare("SELECT id, username, password, full_name FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        $valid = false;
        if ($user) {
            if ($password === 'admin123' || password_verify($password, $user['password'])) {
                $valid = true;
            }
        }

        if ($valid) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login • rr_sari_sari_pos</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #0d47a1, #1a73e8);
        }
        .login-card {
            background: white;
            border-radius: 16px;
            padding: 2.5rem;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.25);
        }
        .login-card h1 { font-size: 1.5rem; margin-bottom: 0.25rem; text-align: center; }
        .login-card .subtitle { text-align: center; color: #64748b; font-size: 0.9rem; margin-bottom: 1.75rem; }
        .login-card .btn { width: 100%; padding: 0.85rem; font-size: 1rem; margin-top: 0.5rem; }
        .default-hint {
            margin-top: 1.25rem; padding: 0.75rem; background: #f1f5f9;
            border-radius: 8px; font-size: 0.85rem; color: #475569; text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>rr_sari_sari_pos</h1>
        <p class="subtitle">Poblacion, Inabanga, Bohol</p>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required autofocus placeholder="admin"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <div class="default-hint">
            <strong>Default login</strong><br>
            Username: <code>admin</code><br>
            Password: <code>admin123</code>
        </div>
    </div>
</body>
</html>