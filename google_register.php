<?php
// FILE: google_register.php - Complete registration for Google users
session_start();
require_once 'dp.php';

// Guard: must come from Google
if (!isset($_SESSION['google_email'])) {
    header('Location: login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'] ?? 'tenant';
    
    // Insert new user
    $stmt = $pdo->prepare('
        INSERT INTO users (username, email, google_id, role) 
        VALUES (?, ?, ?, ?)
    ');
    
    if ($stmt->execute([$_SESSION['google_username'], $_SESSION['google_email'], $_SESSION['google_id'], $role])) {
        $userId = $pdo->lastInsertId();
        
        $_SESSION['authenticated'] = true;
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $_SESSION['google_username'];
        $_SESSION['role'] = $role;
        $_SESSION['last_active'] = time();
        
        unset($_SESSION['google_email'], $_SESSION['google_username'], $_SESSION['google_id']);
        
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Registration failed. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Complete Registration - Property Management</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f4f8; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #fff; border-radius: 8px; padding: 30px; width: 340px; }
        h2 { color: #1F4E79; text-align: center; }
        label { display: block; margin-bottom: 5px; }
        select { width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; }
        button { width: 100%; padding: 10px; background: #2E75B6; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        .error { background: #fdecea; color: #c0392b; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .info { background: #e8f4fd; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Complete Registration</h2>
        <div class="info">
            Welcome <?= htmlspecialchars($_SESSION['google_username']) ?>!<br>
            Email: <?= htmlspecialchars($_SESSION['google_email']) ?>
        </div>
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <label>Select your role:</label>
            <select name="role">
                <option value="tenant">Tenant</option>
                <option value="property_manager">Property Manager</option>
                <option value="admin">Admin</option>
            </select>
            <button type="submit">Complete Registration</button>
        </form>
    </div>
</body>
</html>