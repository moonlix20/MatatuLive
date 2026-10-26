<?php
session_start();
require_once 'db.php';

// If already logged in, redirect straight to admin dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        // Fetch admin user from DB
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        // Verify hash against submitted password
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Set secure session parameters
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];

            header("Location: admin.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Matatu Live</title>
    <style>
        :root {
            --bg-body: #0d1117;
            --bg-card: #161b22;
            --border-color: #30363d;
            --text-primary: #c9d1d9;
            --text-heading: #f0f6fc;
            --accent-blue: #58a6ff;
            --danger-red: #f85149;
            --radius: 12px;
            --font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background-color: var(--bg-body);
            color: var(--text-primary);
            font-family: var(--font-family);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 2.5rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-heading);
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .logo span { color: var(--accent-blue); }

        .subtitle {
            text-align: center;
            color: #8b949e;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.2rem;
        }

        label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: var(--text-heading);
        }

        .form-control {
            width: 100%;
            background-color: var(--bg-body);
            border: 1px solid var(--border-color);
            color: var(--text-heading);
            padding: 0.75rem 1rem;
            border-radius: 6px;
            font-size: 0.95rem;
            outline: none;
        }

        .form-control:focus { border-color: var(--accent-blue); }

        .btn-login {
            background-color: var(--accent-blue);
            color: #fff;
            border: none;
            width: 100%;
            padding: 0.75rem;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            font-size: 1rem;
            margin-top: 0.5rem;
        }

        .alert-error {
            background-color: rgba(248, 81, 73, 0.15);
            border: 1px solid rgba(248, 81, 73, 0.4);
            color: var(--danger-red);
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-size: 0.85rem;
            text-align: center;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--accent-blue);
            text-decoration: none;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="logo">🇰🇪 Matatu<span>Live</span></div>
        <p class="subtitle">Admin Authentication Required</p>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" class="form-control" required placeholder="Enter admin username">
            </div>

            <div class="form-group">
                <label for="password">Encrypted Password</label>
                <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••">
            </div>

            <button type="submit" class="btn-login">Sign In to Dashboard</button>
        </form>

        <a href="index.php" class="back-link">&larr; Return to Public Dashboard</a>
    </div>

</body>
</html>