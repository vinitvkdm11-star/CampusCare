
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

// Ensure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// This page is for students only.
if (($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

// Validate complaint ID from the URL.
$complaintId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$complaintId || $complaintId < 1) {
    http_response_code(400);
    exit('Invalid complaint ID.');
}

// Fetch the complaint only if it belongs to the logged-in student.
$sql = "
    SELECT
        c.id,
        c.complaint_number,
        c.category,
        c.subject,
        c.description,
        c.priority,
        c.status,
        c.resolution_note,
        c.created_at,
        c.updated_at
    FROM complaints c
    WHERE c.id = ? AND c.user_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $complaintId, $userId);
$stmt->execute();

$result = $stmt->get_result();
$complaint = $result->fetch_assoc();
$stmt->close();

if (!$complaint) {
    http_response_code(404);
    exit('Complaint not found or you do not have permission to view it.');
}

// Fetch status-update history for this complaint.
$historySql = "
    SELECT
        cu.old_status,
        cu.new_status,
        cu.comment,
        cu.created_at,
        u.full_name AS updated_by_name
    FROM complaint_updates cu
    LEFT JOIN users u ON u.id = cu.updated_by
    WHERE cu.complaint_id = ?
    ORDER BY cu.created_at DESC, cu.id DESC
";

$historyStmt = $conn->prepare($historySql);
$historyStmt->bind_param('i', $complaintId);
$historyStmt->execute();

$historyResult = $historyStmt->get_result();

function escapeHtml($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function statusClass(string $status): string
{
    return match ($status) {
        'Submitted' => 'submitted',
        'Under Review' => 'review',
        'In Progress' => 'progress',
        'Resolved' => 'resolved',
        'Rejected' => 'rejected',
        default => 'default'
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Complaint Details - CampusCare</title>

    <style>
        * {
            box-sizing: border-box;
        }

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
            gap: 12px;
        }

        .navbar h2 {
            margin: 0;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 18px;
        }

        .container {
            max-width: 1000px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .panel {
            background: white;
            padding: 28px;
            border-radius: 14px;
            margin-bottom: 24px;
            box-shadow: 0 5px 22px rgba(0, 0, 0, 0.04);
        }

        h1, h2 {
            margin-top: 0;
        }

        .muted {
            color: #61748e;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
        }

        .detail {
            padding-bottom: 14px;
            border-bottom: 1px solid #e2e8f0;
            overflow-wrap: anywhere;
        }

        .detail strong {
            display: block;
            margin-bottom: 8px;
            color: #61748e;
            font-size: 14px;
        }

        .description {
            line-height: 1.7;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
        }

        .submitted {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .review {
            background: #fef3c7;
            color: #92400e;
        }

        .progress {
            background: #ede9fe;
            color: #6d28d9;
        }

        .resolved {
            background: #dcfce7;
            color: #166534;
        }

        .rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .default {
            background: #e5e7eb;
            color: #374151;
        }

        .history-item {
            border-left: 3px solid #4169e1;
            padding: 5px 0 22px 18px;
            margin-left: 5px;
        }

        .history-item:last-child {
            padding-bottom: 0;
        }

        .history-item h3 {
            margin: 0 0 8px;
            font-size: 16px;
        }

        .history-item p {
            margin: 7px 0;
            line-height: 1.5;
        }

        .btn {
            display: inline-block;
            background: #3156c8;
            color: white;
            padding: 12px 18px;
            border-radius: 7px;
            text-decoration: none;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            padding: 25px;
            color: #61748e;
        }

        @media (max-width: 650px) {
            .details-grid {
                grid-template-columns: 1fr;
            }

            .navbar a {
                display: inline-block;
                margin: 8px 12px 0 0;
            }
        }
    </style>
</head>

<body>

<nav class="navbar">
    <h2>CampusCare</h2>

    <div>
        <a href="dashboard.php">Dashboard</a>
        <a href="submit_complaint.php">Submit Complaint</a>
        <a href="my_complaints.php">My Complaints</a>
        <a href="../logout.php">Log Out</a>
    </div>
</nav>

<main class="container">

    <section class="panel">
        <h1>Complaint Details</h1>

        <p class="muted">
            Complaint Number:
            <strong><?= escapeHtml($complaint['complaint_number']) ?></strong>
        </p>

        <div class="details-grid">

            <div class="detail">
                <strong>Subject</strong>
                <?= escapeHtml($complaint['subject']) ?>
            </div>

            <div class="detail">
                <strong>Category</strong>
                <?= escapeHtml($complaint['category']) ?>
            </div>

            <div class="detail">
                <strong>Priority</strong>
                <?= escapeHtml($complaint['priority']) ?>
            </div>

            <div class="detail">
                <strong>Current Status</strong>

                <span class="badge <?= statusClass($complaint['status']) ?>">
                    <?= escapeHtml($complaint['status']) ?>
                </span>
            </div>

            <div class="detail">
                <strong>Date Submitted</strong>
                <?= escapeHtml($complaint['created_at']) ?>
            </div>

            <div class="detail">
                <strong>Last Updated</strong>
                <?= escapeHtml($complaint['updated_at']) ?>
            </div>

        </div>

        <h2 style="margin-top: 28px;">Description</h2>

        <p class="description">
            <?= escapeHtml($complaint['description']) ?>
        </p>

        <h2 style="margin-top: 28px;">Resolution / Admin Note</h2>

        <?php if (!empty($complaint['resolution_note'])): ?>
            <p class="description">
                <?= escapeHtml($complaint['resolution_note']) ?>
            </p>
        <?php else: ?>
            <p class="muted">
                No resolution note has been added yet.
            </p>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Status Update History</h2>

        <p class="muted">
            Review the recorded updates made to your complaint.
        </p>

        <?php if ($historyResult->num_rows > 0): ?>

            <?php while ($update = $historyResult->fetch_assoc()): ?>

                <div class="history-item">

                    <h3>
                        <?= escapeHtml($update['new_status']) ?>
                    </h3>

                    <?php if (!empty($update['old_status'])): ?>
                        <p>
                            <strong>Previous status:</strong>
                            <?= escapeHtml($update['old_status']) ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($update['comment'])): ?>
                        <p>
                            <?= escapeHtml($update['comment']) ?>
                        </p>
                    <?php endif; ?>

                    <p class="muted">
                        Updated by:
                        <?= escapeHtml($update['updated_by_name'] ?? 'Administrator') ?>
                    </p>

                    <p class="muted">
                        <?= escapeHtml($update['created_at']) ?>
                    </p>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p class="muted">
                No status-update history is available yet.
            </p>

        <?php endif; ?>

        <?php $historyStmt->close(); ?>

        <a class="btn" href="my_complaints.php" style="margin-top: 24px;">
            Back to My Complaints
        </a>
    </section>

</main>

<footer class="footer">
    CampusCare — Student Complaint and Grievance Management System
</footer>

</body>
</html>
