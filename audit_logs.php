<?php
// FILE: audit_logs.php - View all audit logs (Admin only)
session_start();
require_once 'dp.php';
require_once 'includes/audit.php';

if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$auditLogs = getAuditLogs($pdo, $limit);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Audit Logs - Property Management</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f4f8; margin: 0; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; }
        h1 { color: #1F4E79; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
        .back { margin-bottom: 20px; }
        .back a { color: #2E75B6; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="back">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>
        <h1>Audit Logs</h1>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP Address</th>
                    <th>User Agent</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($auditLogs as $log): ?>
                    <tr>
                        <td><?= $log['id'] ?></td>
                        <td><?= htmlspecialchars($log['created_at']) ?></td>
                        <td><?= htmlspecialchars($log['username'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($log['action']) ?></td>
                        <td><?= htmlspecialchars($log['details'] ?? '') ?></td>
                        <td><?= htmlspecialchars($log['ip_address']) ?></td>
                        <td><?= htmlspecialchars(substr($log['user_agent'] ?? '', 0, 50)) ?>...</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>