
<?php
session_start();

require_once __DIR__ . '/config/database.php';

$errors = [];
$full_name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($full_name === '' || strlen($full_name) > 100) {
        $errors[] = 'Enter your full name (maximum 100 characters).';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors[] = 'Enter a valid email address.';
    }

    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,15}$/', $phone)) {
        $errors[] = 'Enter a valid phone number.';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must contain at least 8 characters.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = 'An account with this email already exists.';
        }

        $stmt->close();
    }

    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare(
            "INSERT INTO users (full_name, email, password, role, phone)
             VALUES (?, ?, ?, 'student', ?)"
        );
        $stmt->bind_param('ssss', $full_name, $email, $password_hash, $phone);
        $stmt->execute();
        $stmt->close();

        header('Location: login.php?registered=1');
        exit;
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
    <title>Student Registration | CampusCare</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f2f5fb;
            color: #1f2937;
        }

        .container {
            width: min(480px, 92%);
            margin: 45px auto;
            padding: 30px;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.08);
        }

        h1 { margin-top: 0; color: #2446a6; }
        .subtitle { color: #64748b; margin-bottom: 24px; }

        label {
            display: block;
            margin: 16px 0 7px;
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
            margin-top: 22px;
            padding: 13px;
            border: 0;
            border-radius: 7px;
            background: #3156c8;
            color: white;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover { background: #2446a6; }

        .errors {
            padding: 12px 12px 12px 32px;
            background: #fff0f0;
            color: #a21d2d;
            border-radius: 7px;
        }

        .footer { margin-top: 20px; text-align: center; }
        a { color: #3156c8; }
    </style>
</head>
<body>
    <main class="container">
        <h1>CampusCare</h1>
        <p class="subtitle">Create your student account</p>

        <?php if (!empty($errors)): ?>
            <ul class="errors">
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <label for="full_name">Full name</label>
            <input
                type="text"
                id="full_name"
                name="full_name"
                maxlength="100"
                value="<?= e($full_name) ?>"
                autocomplete="name"
                required
            >

            <label for="email">College email</label>
            <input
                type="email"
                id="email"
                name="email"
                maxlength="150"
                value="<?= e($email) ?>"
                autocomplete="email"
                required
            >

            <label for="phone">Phone number (optional)</label>
            <input
                type="tel"
                id="phone"
                name="phone"
                value="<?= e($phone) ?>"
                autocomplete="tel"
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <label for="confirm_password">Confirm password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                autocomplete="new-password"
                required
            >

            <button type="submit">Create Account</button>
        </form>

        <p class="footer">
            Already registered? <a href="login.php">Log in</a>
        </p>
    </main>
</body>
</html>
