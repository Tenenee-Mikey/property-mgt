<?php
// FILE: dashboard.php - Complete with Audit Log View for Admin
session_start();
require_once 'dp.php';
require_once 'includes/audit.php';

// Check if user is fully authenticated
if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
    header('Location: login.php');
    exit;
}

// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);
if (time() - $_SESSION['last_active'] > SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}
$_SESSION['last_active'] = time();

$username = htmlspecialchars($_SESSION['username']);
$role = $_SESSION['role'];
$session_id = session_id();

// Get properties based on role
$properties = [];
if ($role === 'admin' || $role === 'property_manager') {
    $stmt = $pdo->query('SELECT * FROM properties ORDER BY created_at DESC');
    $properties = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare('
        SELECT p.* FROM rental_agreements ra 
        JOIN properties p ON ra.property_id = p.id 
        WHERE ra.tenant_id = ? AND ra.status = "active"
    ');
    $stmt->execute([$_SESSION['user_id']]);
    $properties = $stmt->fetchAll();
}

// Get audit logs for admin
$auditLogs = [];
if ($role === 'admin') {
    $auditLogs = getAuditLogs($pdo, 20);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Property Management</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f4f8; margin: 0; padding: 0; }
        .header { background: #1F4E79; color: #fff; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; }
        .badge { background: #27ae60; padding: 4px 12px; border-radius: 20px; font-size: 12px; }
        .nav { background: #2c3e50; padding: 10px 20px; }
        .nav a { color: #fff; text-decoration: none; margin-right: 20px; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .card { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .property { border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
        .logout { background: #e74c3c; padding: 6px 12px; border-radius: 4px; color: #fff; text-decoration: none; }
        hr { margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Property Management System</h1>
        <div>
            <span class="badge">2FA Verified</span>
            <span>Welcome, <?= $username ?> (<?= $role ?>)</span>
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </div>
    
    <div class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="properties.php">Properties</a>
        <?php if ($role === 'admin'): ?>
            <a href="audit_logs.php">Audit Logs</a>
        <?php endif; ?>
    </div>
    
    <div class="container">
        <div class="card">
            <h2>Dashboard</h2>
            <p>You have successfully authenticated using <strong>Two-Factor Authentication</strong>.</p>
            <ul>
                <li>Factor 1: Username + Password</li>
                <li>Factor 2: One-Time Code via Email</li>
            </ul>
            <p>Session ID: <code><?= $session_id ?></code></p>
            <p>Session will auto-expire after 30 minutes of inactivity.</p>
            
            <hr>
            
            <h3>Properties</h3>
            <?php if (empty($properties)): ?>
                <p>No properties found.</p>
            <?php else: ?>
                <?php foreach ($properties as $property): ?>
                    <div class="property">
                        <strong><?= htmlspecialchars($property['address']) ?></strong><br>
                        <?= htmlspecialchars($property['description']) ?><br>
                        Bedrooms: <?= $property['bedrooms'] ?> | Bathrooms: <?= $property['bathrooms'] ?> | Rent: $<?= number_format($property['rent_amount'], 2) ?><br>
                        Status: <?= $property['status'] ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($role === 'admin' && !empty($auditLogs)): ?>
            <div class="card">
                <h3>Recent Audit Logs</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($auditLogs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['created_at']) ?></td>
                                <td><?= htmlspecialchars($log['username'] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($log['action']) ?></td>
                                <td><?= htmlspecialchars($log['ip_address']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p><a href="audit_logs.php">View all audit logs →</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>