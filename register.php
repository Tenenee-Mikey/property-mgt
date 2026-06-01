<?php
// FILE: register.php - Correct version with RBAC (Tenant only for public)
session_start();
require_once 'dp.php';
require_once 'includes/csrf.php';

$error = '';
$success = '';

// Force default role for public registration (Least Privilege)
$defaultRole = 'tenant';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Role is forced to tenant - no selection from user
        $role = $defaultRole;
        
        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $error = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $error = 'Password must contain at least one number.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            // Check if username or email already exists
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
                $error = 'Username or email already exists.';
            } else {
                // Hash the password
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                
                // Insert new user as TENANT only
                $stmt = $pdo->prepare('
                    INSERT INTO users (username, email, password_hash, role) 
                    VALUES (?, ?, ?, ?)
                ');
                
                if ($stmt->execute([$username, $email, $password_hash, $role])) {
                    $success = 'Registration successful! You can now <a href="login.php">login here</a>.';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Property Management</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f4f8; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #fff; border-radius: 8px; padding: 30px; width: 400px; }
        h2 { color: #1F4E79; text-align: center; }
        label { display: block; margin-top: 10px; }
        input { width: 100%; padding: 8px; margin-top: 5px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #2E75B6; color: #fff; border: none; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        .error { background: #fdecea; color: #c0392b; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .link { text-align: center; margin-top: 15px; }
        .info { text-align: center; font-size: 12px; color: #666; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Property Management System</h2>
        <h3 style="text-align: center;">Register</h3>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            
            <label>Username:</label>
            <input type="text" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            
            <label>Email:</label>
            <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            
            <label>Password (min 8 chars, 1 uppercase, 1 number):</label>
            <input type="password" name="password" required>
            
            <label>Confirm Password:</label>
            <input type="password" name="confirm_password" required>
            
            <button type="submit">Register</button>
        </form>
        
        <div class="link">Already have an account? <a href="login.php">Login here</a></div>
        <div class="info">Note: All new registrations are created as Tenants. Admin and Property Manager accounts are created by administrators only.</div>
    </div>
</body>
</html>