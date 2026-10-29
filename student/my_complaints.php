<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'student'
) {
    header('Location: ../login.php');
    exit;
}

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$userId = (int) $_SESSION['user_id'];

$stmt = $conn->prepare(
    'SELECT id, complaint_number, category, subject,
            description, priority, status, resolution_note,
            created_at, updated_at
     FROM complaints
     WHERE user_id = ?
     ORDER BY created_at DESC'
);

$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

$complaints = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$submitted = isset($_GET['submitted']) && $_GET['submitted'] === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Complaints | CampusCare</title>

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
            margin-right: 16px;
        }

        header a:hover { text-decoration: underline; }

        main {
            width: min(1100px, 94%);
            margin: 32px auto;
        }

        .panel {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 16px #1725540d;
            margin-bottom: 22px;
        }

        .muted { color: #64748b; }

        .success {
            background: #e8f8ed;
            color: #166534;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 760px;
        }

        th, td {
            padding: 13px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        th {
            background: #f8fafc;
            color: #475569;
        }

        .status {
            display: inline-block;
            padding: 5px 9px;
            border-radius: 20px;
            background: #e2e8f0;
            font-size: 13px;
            font-weight: bold;
        }

        .submitted-status { background: #dbeafe; color: #1d4ed8; }
        .review-status { background: #fef3c7; color: #92400e; }
        .progress-status { background: #ede9fe; color: #6d28d9; }
        .resolved-status { background: #dcfce7; color: #166534; }
        .rejected-status { background: #fee2e2; color: #991b1b; }

        .button {
            display: inline-block;
            padding: 12px 17px;
            border-radius: 7px;
            background: #3156c8;
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        .button:hover { background: #2446a6; }

        /* NEW: Make complaint numbers clickable */
        .complaint-link {
            color: #3156c8;
            font-weight: 700;
            text-decoration: none;
        }

        .complaint-link:hover {
            text-decoration: underline;
        }

        .complaint-link:focus-visible {
            outline: 2px solid #3156c8;
            outline-offset: 3px;
            border-radius: 2px;
        }

        .empty { text-align: center; padding: 35px 10px; }

        @media (max-width: 500px) {
            .panel { padding: 16px; }
        }
    </style>
</head>
<body>
<header>
    <h1>CampusCare</h1>
    <nav>
        <a href="dashboard.php">Dashboard</a>
        <a href="submit_complaint.php">Submit Complaint</a>
        <a href="../logout.php">Log Out</a>
    </nav>
</header>

<main>
    <section class="panel">
        <h2>My Complaints</h2>
        <p class="muted">View your submitted complaints and track their status.</p>

        <?php if ($submitted): ?>
            <div class="success">
                Your complaint was submitted successfully!
            </div>
        <?php endif; ?>

        <?php if (count($complaints) === 0): ?>
            <div class="empty">
                <h3>No complaints yet</h3>
                <p class="muted">Your submitted complaints will appear here.</p>
                <a class="button" href="submit_complaint.php">
                    Submit Your First Complaint
                </a>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Complaint No.</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date Submitted</th>
                        <th>Resolution Note</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($complaints as $complaint): ?>
                        <?php
                        $statusClass = match ($complaint['status']) {
                            'Submitted' => 'submitted-status',
                            'Under Review' => 'review-status',
                            'In Progress' => 'progress-status',
                            'Resolved' => 'resolved-status',
                            'Rejected' => 'rejected-status',
                            default => ''
                        };
                        ?>
                        <tr>
                            <td>
                                <!-- NEW: Open the selected complaint details -->
                                <a
                                    class="complaint-link"
                                    href="complaint_details.php?id=<?= (int) $complaint['id'] ?>"
                                    title="View complaint details and status history"
                                >
                                    <?= e($complaint['complaint_number']) ?>
                                </a>
                            </td>

                            <td>
                                <strong><?= e($complaint['subject']) ?></strong>
                                <br>
                                <small><?= e($complaint['description']) ?></small>
                            </td>

                            <td><?= e($complaint['category']) ?></td>
                            <td><?= e($complaint['priority']) ?></td>

                            <td>
                                <span class="status <?= e($statusClass) ?>">
                                    <?= e($complaint['status']) ?>
                                </span>
                            </td>

                            <td><?= e($complaint['created_at']) ?></td>

                            <td>
                                <?= e($complaint['resolution_note'] ?? '') ?: '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <p style="margin-top: 24px;">
            <a class="button" href="submit_complaint.php">+ Submit Complaint</a>
        </p>
    </section>
</main>
</body>
</html>
```