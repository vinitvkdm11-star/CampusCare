
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

// Fetch complaint statistics.
$stats = [
    'total' => 0,
    'submitted' => 0,
    'in_progress' => 0,
    'resolved' => 0
];

$sql = "
    SELECT
        COUNT(*) AS total,
        SUM(status = 'Submitted') AS submitted,
        SUM(status = 'In Progress') AS in_progress,
        SUM(status = 'Resolved') AS resolved
    FROM complaints
";

$result = $conn->query($sql);
$row = $result->fetch_assoc();

foreach ($stats as $key => $value) {
    $stats[$key] = (int) ($row[$key] ?? 0);
}

// Fetch the latest complaints.
$sql = "
    SELECT
        c.complaint_number,
        c.subject,
        c.category,
        c.priority,
        c.status,
        c.created_at,
        u.full_name
    FROM complaints c
    INNER JOIN users u ON u.id = c.user_id
    ORDER BY c.created_at DESC
    LIMIT 10
";

$recentComplaints = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | CampusCare</title>

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
            gap: 15px;
        }

        header h1 { margin: 0; font-size: 24px; }

        nav {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
        }

        nav a {
            color: white;
            text-decoration: none;
        }

        nav a:hover { text-decoration: underline; }

        main {
            width: min(1200px, 94%);
            margin: 32px auto;
        }

        .welcome, .panel, .stat-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px #1725540d;
        }

        .welcome {
            padding: 25px;
            margin-bottom: 24px;
        }

        .welcome h2 { margin-top: 0; }

        .muted { color: #64748b; }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 28px;
        }

        .stat-card {
            padding: 23px;
            border-top: 5px solid #4169e1;
        }

        .stat-card p {
            margin: 0 0 12px;
            color: #64748b;
        }

        .stat-card strong {
            font-size: 32px;
        }

        .panel { padding: 24px; }

        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            min-width: 850px;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            padding: 13px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        th {
            background: #f8fafc;
            color: #475569;
        }

        .status {
            display: inline-block;
            border-radius: 20px;
            padding: 5px 9px;
            background: #e2e8f0;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }

        .submitted { background: #dbeafe; color: #1d4ed8; }
        .under-review { background: #fef3c7; color: #92400e; }
        .in-progress { background: #ede9fe; color: #6d28d9; }
        .resolved { background: #dcfce7; color: #166534; }
        .rejected { background: #fee2e2; color: #991b1b; }

        .empty {
            text-align: center;
            padding: 30px;
            color: #64748b;
        }

        footer {
            text-align: center;
            color: #64748b;
            padding: 28px 10px;
        }

        @media (max-width: 800px) {
            .stats { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 450px) {
            .stats { grid-template-columns: 1fr; }
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
    <section class="welcome">
        <h2>Administrator Dashboard</h2>
        <p class="muted">
            Monitor student grievances and track complaint resolution.
        </p>
    </section>

    <section class="stats">
        <div class="stat-card">
            <p>Total Complaints</p>
            <strong><?= $stats['total'] ?></strong>
        </div>

        <div class="stat-card">
            <p>Submitted</p>
            <strong><?= $stats['submitted'] ?></strong>
        </div>

        <div class="stat-card">
            <p>In Progress</p>
            <strong><?= $stats['in_progress'] ?></strong>
        </div>

        <div class="stat-card">
            <p>Resolved</p>
            <strong><?= $stats['resolved'] ?></strong>
        </div>
    </section>

    <section class="panel">
        <h2>Recent Complaints</h2>
        <p class="muted">The 10 most recently submitted complaints.</p>

        <?php if ($recentComplaints->num_rows === 0): ?>
            <div class="empty">No complaints have been submitted yet.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Complaint No.</th>
                        <th>Student</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php while ($complaint = $recentComplaints->fetch_assoc()): ?>
                        <?php
                        $statusClass = match ($complaint['status']) {
                            'Submitted' => 'submitted',
                            'Under Review' => 'under-review',
                            'In Progress' => 'in-progress',
                            'Resolved' => 'resolved',
                            'Rejected' => 'rejected',
                            default => ''
                        };
                        ?>

                        <tr>
                            <td><?= e($complaint['complaint_number']) ?></td>
                            <td><?= e($complaint['full_name']) ?></td>
                            <td><?= e($complaint['subject']) ?></td>
                            <td><?= e($complaint['category']) ?></td>
                            <td><?= e($complaint['priority']) ?></td>
                            <td>
                                <span class="status <?= e($statusClass) ?>">
                                    <?= e($complaint['status']) ?>
                                </span>
                            </td>
                            <td><?= e($complaint['created_at']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer>
    CampusCare — Student Complaint and Grievance Management System
</footer>

</body>
</html>
