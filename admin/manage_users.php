
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header('Location: ../login.php');
    exit;
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

// Create a CSRF token for account-management actions.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = '';
$error = '';

// Activate or deactivate a student account.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    $action = $_POST['action'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        $error = 'Security validation failed. Refresh the page and try again.';
    } elseif (!$userId || !in_array($action, ['activate', 'deactivate'], true)) {
        $error = 'Invalid account action.';
    } elseif ($userId === (int) $_SESSION['user_id']) {
        $error = 'You cannot deactivate your own administrator account here.';
    } else {
        $newStatus = $action === 'activate' ? 1 : 0;

        $stmt = $conn->prepare(
            "UPDATE users
             SET is_active = ?
             WHERE id = ? AND role = 'student'"
        );
        $stmt->bind_param('ii', $newStatus, $userId);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = $newStatus
                ? 'Student account activated successfully.'
                : 'Student account deactivated successfully.';
        } else {
            $check = $conn->prepare(
                "SELECT id, is_active FROM users
                 WHERE id = ? AND role = 'student'"
            );
            $check->bind_param('i', $userId);
            $check->execute();
            $student = $check->get_result()->fetch_assoc();
            $check->close();

            if ($student) {
                $message = $newStatus
                    ? 'This student account is already active.'
                    : 'This student account is already inactive.';
            } else {
                $error = 'Student account not found.';
            }
        }

        $stmt->close();
    }
}

// Search students by name or email.
$search = trim($_GET['search'] ?? '');

if (strlen($search) > 100) {
    $search = substr($search, 0, 100);
}

$searchPattern = '%' . $search . '%';

$stmt = $conn->prepare(
    "SELECT id, full_name, email, phone, is_active, created_at
     FROM users
     WHERE role = 'student'
       AND (full_name LIKE ? OR email LIKE ?)
     ORDER BY created_at DESC"
);

$stmt->bind_param('ss', $searchPattern, $searchPattern);
$stmt->execute();
$students = $stmt->get_result();

$countResult = $conn->query(
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(is_active = 1), 0) AS active,
        COALESCE(SUM(is_active = 0), 0) AS inactive
     FROM users
     WHERE role = 'student'"
);
$counts = $countResult->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | CampusCare</title>

    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f6fc;
            color: #1f2937;
        }

        header {
            background: #2446a6;
            color: white;
            padding: 18px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
        }

        header h1 { margin: 0; font-size: 24px; }
        nav { display: flex; flex-wrap: wrap; gap: 16px; }
        nav a { color: white; text-decoration: none; }

        main { width: min(1150px, 94%); margin: 30px auto; }

        .panel, .stat {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px #1725540d;
        }

        .panel { padding: 24px; margin-bottom: 24px; }
        .muted { color: #64748b; }

        .stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat { padding: 20px; border-top: 4px solid #4169e1; }
        .stat p { margin: 0 0 10px; color: #64748b; }
        .stat strong { font-size: 28px; }

        .notice { padding: 13px; border-radius: 7px; margin-bottom: 18px; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }

        .search-form {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        input[type="search"] {
            flex: 1;
            min-width: 200px;
            padding: 11px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font: inherit;
        }

        button, .button {
            display: inline-block;
            border: 0;
            border-radius: 6px;
            padding: 10px 14px;
            background: #3156c8;
            color: white;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
        }

        .deactivate { background: #b91c1c; }
        .activate { background: #15803d; }

        .table-wrap { overflow-x: auto; }

        table { width: 100%; min-width: 750px; border-collapse: collapse; }

        th, td {
            text-align: left;
            padding: 13px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        th { background: #f8fafc; }

        .badge {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .active { background: #dcfce7; color: #166534; }
        .inactive { background: #fee2e2; color: #991b1b; }

        .empty { padding: 30px; text-align: center; color: #64748b; }

        @media (max-width: 600px) {
            .stats { grid-template-columns: 1fr; }
            .panel { padding: 16px; }
        }
    </style>
</head>
<body>

<header>
    <h1>CampusCare Admin</h1>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="complaints.php">Manage Complaints</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="reports.php">Reports</a>
        <a href="../logout.php">Log Out</a>
    </nav>
</header>

<main>
    <section class="panel">
        <h2>Manage Student Accounts</h2>
        <p class="muted">Search registered students and manage account access.</p>

        <?php if ($message !== ''): ?>
            <div class="notice success"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="notice error"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat">
                <p>Total Students</p>
                <strong><?= (int) $counts['total'] ?></strong>
            </div>
            <div class="stat">
                <p>Active Accounts</p>
                <strong><?= (int) $counts['active'] ?></strong>
            </div>
            <div class="stat">
                <p>Inactive Accounts</p>
                <strong><?= (int) $counts['inactive'] ?></strong>
            </div>
        </div>

        <form method="GET" class="search-form">
            <input type="search" name="search"
                   placeholder="Search by student name or email"
                   value="<?= e($search) ?>">
            <button type="submit">Search</button>
            <a class="button" href="manage_users.php">Clear</a>
        </form>

        <?php if ($students->num_rows === 0): ?>
            <div class="empty">No students found.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Student</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Account Status</th>
                        <th>Registered</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($student = $students->fetch_assoc()): ?>
                        <tr>
                            <td><?= (int) $student['id'] ?></td>
                            <td><?= e($student['full_name']) ?></td>
                            <td><?= e($student['email']) ?></td>
                            <td><?= e($student['phone'] ?? '') ?: '—' ?></td>
                            <td>
                                <span class="badge <?= $student['is_active'] ? 'active' : 'inactive' ?>">
                                    <?= $student['is_active'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><?= e($student['created_at']) ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= e($_SESSION['csrf_token']) ?>">
                                    <input type="hidden" name="user_id"
                                           value="<?= (int) $student['id'] ?>">

                                    <?php if ($student['is_active']): ?>
                                        <button class="deactivate" type="submit"
                                                name="action" value="deactivate">
                                            Deactivate
                                        </button>
                                    <?php else: ?>
                                        <button class="activate" type="submit"
                                                name="action" value="activate">
                                            Activate
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

</body>
</html>
