
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Only administrators can access this page.
if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header('Location: ../login.php');
    exit;
}

function reportCount(mysqli $conn, string $sql): int
{
    $result = $conn->query($sql);
    return (int) $result->fetch_assoc()['total'];
}

$total = reportCount($conn, "SELECT COUNT(*) AS total FROM complaints");
$submitted = reportCount($conn, "SELECT COUNT(*) AS total FROM complaints WHERE status = 'Submitted'");
$inProgress = reportCount($conn, "SELECT COUNT(*) AS total FROM complaints WHERE status = 'In Progress'");
$resolved = reportCount($conn, "SELECT COUNT(*) AS total FROM complaints WHERE status = 'Resolved'");
$pending = reportCount($conn, "SELECT COUNT(*) AS total FROM complaints WHERE status IN ('Submitted', 'Under Review', 'In Progress')");

$categoryResult = $conn->query(
    "SELECT category, COUNT(*) AS total
     FROM complaints
     GROUP BY category
     ORDER BY total DESC, category ASC"
);

$statusResult = $conn->query(
    "SELECT status, COUNT(*) AS total
     FROM complaints
     GROUP BY status
     ORDER BY total DESC, status ASC"
);

// Export all complaints to CSV.
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportResult = $conn->query(
        "SELECT c.complaint_number, u.full_name,
                u.email, c.category, c.subject,
                c.priority, c.status, c.resolution_note,
                c.created_at
         FROM complaints c
         INNER JOIN users u ON u.id = c.user_id
         ORDER BY c.created_at DESC"
    );

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="campuscare_report.csv"');

    $output = fopen('php://output', 'w');

    fputcsv($output, [
        'Complaint Number',
        'Student',
        'Email',
        'Category',
        'Subject',
        'Priority',
        'Status',
        'Resolution Note',
        'Submitted At'
    ]);

    while ($row = $exportResult->fetch_assoc()) {
        // Prevent spreadsheet formula injection.
        foreach ($row as &$value) {
            if (is_string($value) && preg_match('/^[\s]*[=+\-@]/u', $value)) {
                $value = "'" . $value;
            }
        }
        unset($value);
        fputcsv($output, array_values($row));
    }

    fclose($output);
    exit;
}

function escapeHtml($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - CampusCare Admin</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f3f6fd;
            color: #182638;
        }

        .navbar {
            background: #2846a4;
            color: white;
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .navbar h2 { margin: 0; }

        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 18px;
        }

        .container {
            max-width: 1200px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .panel {
            background: white;
            border-radius: 14px;
            padding: 28px;
            margin-bottom: 25px;
            box-shadow: 0 5px 22px rgba(0,0,0,0.04);
        }

        .subtitle { color: #61748e; }

        .heading-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .btn {
            display: inline-block;
            padding: 13px 19px;
            background: #3156c8;
            color: white;
            border-radius: 7px;
            text-decoration: none;
            font-weight: bold;
        }

        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 18px;
            margin-bottom: 25px;
        }

        .card {
            background: white;
            border-top: 4px solid #4169e1;
            padding: 24px;
            border-radius: 12px;
        }

        .card p {
            color: #61748e;
            margin: 0 0 12px;
        }

        .card h1 { margin: 0; }

        .tables {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 22px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 14px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th { background: #f7f9fc; }

        .footer {
            text-align: center;
            padding: 25px;
            color: #61748e;
        }

        @media (max-width: 650px) {
            .navbar a {
                margin: 0 12px 0 0;
                display: inline-block;
                padding-top: 8px;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <h2>CampusCare Admin</h2>
    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="complaints.php">Manage Complaints</a>
        <a href="manage_users.php">Manage Users</a>
        <a href="reports.php">Reports</a>
        <a href="../logout.php">Log Out</a>
    </div>
</nav>

<main class="container">

    <section class="panel">
        <div class="heading-row">
            <div>
                <h1>Complaint Reports</h1>
                <p class="subtitle">
                    View complaint statistics and download reports.
                </p>
            </div>
            <a class="btn" href="reports.php?export=csv">
                Download CSV Report
            </a>
        </div>
    </section>

    <section class="cards">
        <div class="card">
            <p>Total Complaints</p>
            <h1><?= $total ?></h1>
        </div>

        <div class="card">
            <p>Submitted</p>
            <h1><?= $submitted ?></h1>
        </div>

        <div class="card">
            <p>In Progress</p>
            <h1><?= $inProgress ?></h1>
        </div>

        <div class="card">
            <p>Resolved</p>
            <h1><?= $resolved ?></h1>
        </div>

        <div class="card">
            <p>Pending (All Open)</p>
            <h1><?= $pending ?></h1>
        </div>
    </section>

    <section class="tables">
        <div class="panel">
            <h2>Complaints by Category</h2>
            <table>
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($categoryResult->num_rows === 0): ?>
                        <tr>
                            <td colspan="2">No complaints found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php while ($row = $categoryResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= escapeHtml($row['category']) ?></td>
                            <td><?= (int) $row['total'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <h2>Complaints by Status</h2>
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($statusResult->num_rows === 0): ?>
                        <tr>
                            <td colspan="2">No complaints found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php while ($row = $statusResult->fetch_assoc()): ?>
                        <tr>
                            <td><?= escapeHtml($row['status']) ?></td>
                            <td><?= (int) $row['total'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<footer class="footer">
    CampusCare — Student Complaint and Grievance Management System
</footer>

</body>
</html>
