
<?php
session_start();

require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit;
}

$email = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Enter a valid email address and password.';
    } else {
        $stmt = $conn->prepare(
            'SELECT id, full_name, email, password, role, is_active
             FROM users
             WHERE email = ?
             LIMIT 1'
        );

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if (
            !$user ||
            !password_verify($password, $user['password']) ||
            (int) $user['is_active'] !== 1
        ) {
            $error = 'Invalid email or password, or this account is inactive.';
        } else {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: student/dashboard.php');
            }
            exit;
        }
    }
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CampusCare</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f2f5fb;
            color: #1f2937;
        }

        .container {
            width: min(430px, 92%);
            margin: 65px auto;
            padding: 30px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, .08);
        }

        h1 { margin-top: 0; color: #2446a6; }
        .subtitle { color: #64748b; margin-bottom: 25px; }

        label {
            display: block;
            margin: 17px 0 7px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 15px;
        }

        input:focus {
            outline: 2px solid #b9c9ff;
            border-color: #4169e1;
        }

        button {
            width: 100%;
            margin-top: 23px;
            padding: 13px;
            border: 0;
            border-radius: 7px;
            background: #3156c8;
            color: #fff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover { background: #2446a6; }

        .message {
            padding: 12px;
            border-radius: 7px;
            margin-bottom: 18px;
        }

        .error { background: #fff0f0; color: #a21d2d; }
        .success { background: #e8f8ed; color: #166534; }

        .footer { text-align: center; margin-top: 22px; }
        a { color: #3156c8; }
    </style>
</head>
<body>
    <main class="container">
        <h1>CampusCare</h1>
        <p class="subtitle">Student Complaint and Grievance Management</p>

        <?php if (isset($_GET['registered'])): ?>
            <p class="message success">
                Registration successful! Please log in.
            </p>
        <?php endif; ?>

        <?php if (isset($_GET['logged_out'])): ?>
            <p class="message success">
                You have logged out successfully.
            </p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="message error"><?= e($error) ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <label for="email">Email address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= e($email) ?>"
                autocomplete="username"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >

            <button type="submit">Log In</button>
        </form>

        <p class="footer">
            Don't have an account?
            <a href="register.php">Register here</a>
        </p>
    </main>
</body>
</html>
