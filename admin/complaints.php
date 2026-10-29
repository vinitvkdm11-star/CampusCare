
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

$allowedStatuses = [
    'Submitted',
    'Under Review',
    'In Progress',
    'Resolved',
    'Rejected'
];

$message = '';
$error = '';

// Update complaint status securely.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaintId = filter_input(INPUT_POST, 'complaint_id', FILTER_VALIDATE_INT);
    $newStatus = $_POST['status'] ?? '';
    $resolutionNote = trim($_POST['resolution_note'] ?? '');

    if (!$complaintId || !in_array($newStatus, $allowedStatuses, true)) {
        $error = 'Invalid complaint or status.';
    } elseif (strlen($resolutionNote) > 5000) {
        $error = 'The resolution note must not exceed 5000 characters.';
    } else {
        try {
            $conn->begin_transaction();

            $stmt = $conn->prepare(
                'SELECT status FROM complaints WHERE id = ? FOR UPDATE'
            );
            $stmt->bind_param('i', $complaintId);
            $stmt->execute();
            $currentComplaint = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$currentComplaint) {
                throw new RuntimeException('Complaint not found.');
            }

            $oldStatus = $currentComplaint['status'];
            $adminId = (int) $_SESSION['user_id'];

            $stmt = $conn->prepare(
                'UPDATE complaints
                 SET status = ?, resolution_note = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?'
            );
            $stmt->bind_param('ssi', $newStatus, $resolutionNote, $complaintId);
            $stmt->execute();
            $stmt->close();

            // Record the status change in the complaint history.
            if ($oldStatus !== $newStatus) {
                $stmt = $conn->prepare(
                    'INSERT INTO complaint_updates
                     (complaint_id, updated_by, old_status, new_status, comment)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->bind_param(
                    'iisss',
                    $complaintId,
                    $adminId,
                    $oldStatus,
                    $newStatus,
                    $resolutionNote
                );
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();
            header('Location: complaints.php?updated=1');
            exit;
        } catch (Throwable $exception) {
            $conn->rollback();
            error_log('CampusCare complaint update failed: ' . $exception->getMessage());
            $error = 'Could not update the complaint. Please try again.';
        }
    }
}

if (isset($_GET['updated'])) {
    $message = 'Complaint updated successfully.';
}

// Optional status filter.
$filterStatus = $_GET['status'] ?? '';

if ($filterStatus !== '' && !in_array($filterStatus, $allowedStatuses, true)) {
    $filterStatus = '';
}

if ($filterStatus !== '') {
    $stmt = $conn->prepare(
        'SELECT c.id, c.complaint_number, c.subject, c.description,
                c.category, c.priority, c.status, c.resolution_note,
                c.created_at, u.full_name, u.email
         FROM complaints c
         INNER JOIN users u ON u.id = c.user_id
         WHERE c.status = ?
         ORDER BY c.created_at DESC'
    );
    $stmt->bind_param('s', $filterStatus);
    $stmt->execute();
    $complaints = $stmt->get_result();
} else {
    $complaints = $conn->query(
        'SELECT c.id, c.complaint_number, c.subject, c.description,
                c.category, c.priority, c.status, c.resolution_note,
                c.created_at, u.full_name, u.email
         FROM complaints c
         INNER JOIN users u ON u.id = c.user_id
         ORDER BY c.created_at DESC'
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaints | CampusCare</title>
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
            gap: 14px;
        }

        header h1 { margin: 0; font-size: 24px; }

        nav { display: flex; gap: 16px; flex-wrap: wrap; }

        nav a { color: white; text-decoration: none; }

        main { width: min(1200px, 94%); margin: 30px auto; }

        .panel {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 16px #1725540d;
            margin-bottom: 24px;
        }

        .muted { color: #64748b; }

        .notice {
            padding: 13px;
            border-radius: 7px;
            margin-bottom: 18px;
        }

        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }

        .filter {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 20px;
        }

        select, textarea {
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font: inherit;
        }

        select { max-width: 100%; }

        button, .button {
            display: inline-block;
            padding: 10px 14px;
            border: 0;
            border-radius: 6px;
            background: #3156c8;
            color: white;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
        }

        .table-wrap { overflow-x: auto; }

        table {
            width: 100%;
            min-width: 1000px;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            vertical-align: top;
            padding: 13px 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        th { background: #f8fafc; }

        .complaint { min-width: 230px; max-width: 300px; }

        .complaint p {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .actions { min-width: 235px; }

        .actions select, .actions textarea {
            width: 100%;
            margin-bottom: 9px;
        }

        .actions textarea { resize: vertical; }

        .status {
            display: inline-block;
            padding: 5px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
        }

        .submitted { background: #dbeafe; color: #1d4ed8; }
        .review { background: #fef3c7; color: #92400e; }
        .progress { background: #ede9fe; color: #6d28d9; }
        .resolved { background: #dcfce7; color: #166534; }
        .rejected { background: #fee2e2; color: #991b1b; }

        .empty { padding: 30px; text-align: center; color: #64748b; }

        @media (max-width: 500px) {
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
        <h2>Manage Complaints</h2>
        <p class="muted">Review student grievances and update their status.</p>

        <?php if ($message !== ''): ?>
            <div class="notice success"><?= e($message) ?></div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="notice error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="GET" class="filter">
            <label for="status">Filter by status:</label>
            <select name="status" id="status">
                <option value="">All complaints</option>
                <?php foreach ($allowedStatuses as $status): ?>
                    <option value="<?= e($status) ?>"
                        <?= $filterStatus === $status ? 'selected' : '' ?>>
                        <?= e($status) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Apply Filter</button>
            <a class="button" href="complaints.php">Clear</a>
        </form>

        <?php if ($complaints->num_rows === 0): ?>
            <div class="empty">No complaints found for this filter.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Complaint</th>
                        <th>Student</th>
                        <th>Category / Priority</th>
                        <th>Current Status</th>
                        <th>Update Complaint</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php while ($c = $complaints->fetch_assoc()): ?>
                        <?php
                        $statusClass = match ($c['status']) {
                            'Submitted' => 'submitted',
                            'Under Review' => 'review',
                            'In Progress' => 'progress',
                            'Resolved' => 'resolved',
                            'Rejected' => 'rejected',
                            default => ''
                        };
                        ?>
                        <tr>
                            <td class="complaint">
                                <strong><?= e($c['complaint_number']) ?></strong>
                                <p><strong><?= e($c['subject']) ?></strong></p>
                                <p><?= e($c['description']) ?></p>
                                <small class="muted">
                                    Submitted: <?= e($c['created_at']) ?>
                                </small>
                            </td>

                            <td>
                                <?= e($c['full_name']) ?><br>
                                <small><?= e($c['email']) ?></small>
                            </td>

                            <td>
                                <?= e($c['category']) ?><br><br>
                                <strong><?= e($c['priority']) ?></strong>
                            </td>

                            <td>
                                <span class="status <?= e($statusClass) ?>">
                                    <?= e($c['status']) ?>
                                </span>
                            </td>

                            <td class="actions">
                                <form method="POST">
                                    <input type="hidden" name="complaint_id"
                                           value="<?= (int) $c['id'] ?>">

                                    <label>Status</label>
                                    <select name="status" required>
                                        <?php foreach ($allowedStatuses as $status): ?>
                                            <option value="<?= e($status) ?>"
                                                <?= $c['status'] === $status ? 'selected' : '' ?>>
                                                <?= e($status) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <label>Resolution / admin note</label>
                                    <textarea name="resolution_note" rows="3"
                                              maxlength="5000"
                                              placeholder="Enter an update or resolution note"><?= e($c['resolution_note'] ?? '') ?></textarea>

                                    <button type="submit">Save Update</button>
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
