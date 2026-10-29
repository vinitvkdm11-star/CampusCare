
<?php
session_start();

require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: ../login.php');
    exit;
}

$errors = [];
$success = '';
$category = '';
$subject = '';
$description = '';
$priority = 'Medium';

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = trim($_POST['category'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority = trim($_POST['priority'] ?? 'Medium');

    $allowedCategories = [
        'Infrastructure',
        'Laboratory',
        'Internet',
        'Cleanliness',
        'Faculty',
        'Administration',
        'Other'
    ];

    $allowedPriorities = ['Low', 'Medium', 'High', 'Critical'];

    if (!in_array($category, $allowedCategories, true)) {
        $errors[] = 'Please select a valid complaint category.';
    }

    if ($subject === '' || strlen($subject) > 200) {
        $errors[] = 'Subject is required and must not exceed 200 characters.';
    }

    if ($description === '' || strlen($description) < 10) {
        $errors[] = 'Description must contain at least 10 characters.';
    }

    if (!in_array($priority, $allowedPriorities, true)) {
        $errors[] = 'Please select a valid priority.';
    }

    if (empty($errors)) {
        // Generate a unique complaint number and insert the complaint.
        $complaintNumber = 'CC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));

        try {
            $stmt = $conn->prepare(
                'INSERT INTO complaints
                    (complaint_number, user_id, category, subject, description, priority)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );

            $userId = (int) $_SESSION['user_id'];

            $stmt->bind_param(
                'sissss',
                $complaintNumber,
                $userId,
                $category,
                $subject,
                $description,
                $priority
            );

            $stmt->execute();
            $stmt->close();

            header('Location: my_complaints.php?submitted=1');
            exit;
        } catch (mysqli_sql_exception $exception) {
            error_log('CampusCare complaint submission failed: ' . $exception->getMessage());
            $errors[] = 'Unable to submit your complaint right now. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Complaint | CampusCare</title>
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

        main {
            width: min(760px, 92%);
            margin: 35px auto;
        }

        .panel {
            background: white;
            padding: 28px;
            border-radius: 12px;
            box-shadow: 0 4px 16px #1725540d;
        }

        h2 { margin-top: 0; }
        .muted { color: #64748b; }

        label {
            display: block;
            margin: 18px 0 7px;
            font-weight: bold;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font: inherit;
        }

        textarea {
            min-height: 150px;
            resize: vertical;
        }

        input:focus, select:focus, textarea:focus {
            outline: 2px solid #b9c9ff;
            border-color: #4169e1;
        }

        button {
            margin-top: 22px;
            padding: 13px 20px;
            background: #3156c8;
            color: white;
            border: 0;
            border-radius: 7px;
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

        .back {
            display: inline-block;
            margin-top: 20px;
            color: #3156c8;
            text-decoration: none;
        }

        @media (max-width: 500px) {
            .panel { padding: 20px; }
        }
    </style>
</head>
<body>
    <header>
        <h1>CampusCare</h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="my_complaints.php">My Complaints</a>
            <a href="../logout.php">Log Out</a>
        </nav>
    </header>

    <main>
        <section class="panel">
            <h2>Submit a Complaint</h2>
            <p class="muted">
                Describe the issue clearly so the college administration can review it.
            </p>

            <?php if (!empty($errors)): ?>
                <ul class="errors">
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="POST" action="submit_complaint.php">
                <label for="category">Complaint category</label>
                <select id="category" name="category" required>
                    <option value="">Select a category</option>
                    <?php foreach ([
                        'Infrastructure', 'Laboratory', 'Internet',
                        'Cleanliness', 'Faculty', 'Administration', 'Other'
                    ] as $option): ?>
                        <option value="<?= e($option) ?>"
                            <?= $category === $option ? 'selected' : '' ?>>
                            <?= e($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="subject">Subject</label>
                <input
                    type="text"
                    id="subject"
                    name="subject"
                    maxlength="200"
                    value="<?= e($subject) ?>"
                    required
                >

                <label for="description">Complaint description</label>
                <textarea
                    id="description"
                    name="description"
                    minlength="10"
                    required
                ><?= e($description) ?></textarea>

                <label for="priority">Priority</label>
                <select id="priority" name="priority" required>
                    <?php foreach (['Low', 'Medium', 'High', 'Critical'] as $option): ?>
                        <option value="<?= e($option) ?>"
                            <?= $priority === $option ? 'selected' : '' ?>>
                            <?= e($option) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit">Submit Complaint</button>
            </form>

            <a class="back" href="dashboard.php">← Back to Dashboard</a>
        </section>
    </main>
</body>
</html>
