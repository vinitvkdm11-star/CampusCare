
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if (($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$fullName = $_SESSION['full_name'] ?? 'Student';

$stmt = $conn->prepare(
    'SELECT
        COUNT(*) AS total,
        SUM(status = "Submitted") AS submitted,
        SUM(status = "In Progress") AS in_progress,
        SUM(status = "Resolved") AS resolved
     FROM complaints
     WHERE user_id = ?'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total = (int) ($stats['total'] ?? 0);
$submitted = (int) ($stats['submitted'] ?? 0);
$inProgress = (int) ($stats['in_progress'] ?? 0);
$resolved = (int) ($stats['resolved'] ?? 0);

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | CampusCare</title>
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
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        header h1 { margin: 0; font-size: 24px; }

        header a {
            color: white;
            text-decoration: none;
            margin-left: 16px;
        }

        main {
            width: min(1100px, 92%);
            margin: 35px auto;
        }

        .welcome {
            background: white;
            padding: 26px;
            border-radius: 12px;
            box-shadow: 0 4px 16px #1725540d;
        }

        .welcome h2 { margin-top: 0; }
        .muted { color: #64748b; }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 18px;
            margin: 24px 0;
        }

        .card {
            background: white;
            padding: 22px;
            border-radius: 12px;
            border-top: 4px solid #4169e1;
            box-shadow: 0 4px 16px #1725540d;
        }

        .card p { margin: 0 0 10px; color: #64748b; }
        .number { font-size: 32px; font-weight: bold; }

        .actions {
            display: flex;
            gap: 14px;
            flex-wrap: wrap;
            margin-top: 24px;
        }

        .button {
            display: inline-block;
            background: #3156c8;
            color: white;
            padding: 13px 18px;
            border-radius: 7px;
            text-decoration: none;
            font-weight: bold;
        }

        .button.secondary {
            background: white;
            color: #3156c8;
            border: 1px solid #cbd5e1;
        }

        footer {
            text-align: center;
            padding: 25px;
            color: #64748b;
        }

        @media (max-width: 500px) {
            header a { margin-left: 0; margin-right: 12px; }
        }
    </style>
</head>
<body>
    <header>
        <h1>CampusCare</h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="submit_complaint.php">Submit Complaint</a>
            <a href="my_complaints.php">My Complaints</a>
            <a href="../logout.php">Log Out</a>
        </nav>
    </header>

    <main>
        <section class="welcome">
            <h2>Welcome, <?= e($fullName) ?>!</h2>
            <p class="muted">
                Manage your campus complaints and track their progress here.
            </p>

            <div class="actions">
                <a class="button" href="submit_complaint.php">
                    + Submit a Complaint
                </a>
                <a class="button secondary" href="my_complaints.php">
                    View My Complaints
                </a>
            </div>
        </section>

        <section class="cards">
            <div class="card">
                <p>Total Complaints</p>
                <div class="number"><?= $total ?></div>
            </div>

            <div class="card">
                <p>Submitted</p>
                <div class="number"><?= $submitted ?></div>
            </div>

            <div class="card">
                <p>In Progress</p>
                <div class="number"><?= $inProgress ?></div>
            </div>

            <div class="card">
                <p>Resolved</p>
                <div class="number"><?= $resolved ?></div>
            </div>
        </section>
    </main>

    <footer>CampusCare — Student Complaint and Grievance Management System</footer>
</body>
</html>
