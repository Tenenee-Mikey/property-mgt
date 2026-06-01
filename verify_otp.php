<?php
// FILE: verify_otp.php - Complete with CSRF and Audit Logs
session_start();
require_once 'dp.php';
require_once 'includes/csrf.php';
require_once 'includes/audit.php';

define('MAX_OTP_ATTEMPTS', 5);

// Guard: user must have passed Step 1
if (!isset($_SESSION['2fa_user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$userId = $_SESSION['2fa_user_id'];
$maskedEmail = preg_replace('/(?<=.).(?=.*@)/', '*', $_SESSION['2fa_email']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $submitted = trim($_POST['otp'] ?? '');
        
        if (!preg_match('/^\d{6}$/', $submitted)) {
            $error = 'Please enter the 6-digit code.';
        } else {
            // Fetch valid (non-expired) OTP token
            $stmt = $pdo->prepare('
                SELECT id, otp_hash, attempts FROM otp_tokens 
                WHERE user_id = ? AND expires_at > NOW() 
                ORDER BY created_at DESC LIMIT 1
            ');
            $stmt->execute([$userId]);
            $token = $stmt->fetch();
            
            if (!$token) {
                logAction($pdo, $userId, $_SESSION['2fa_username'], 'LOGIN_2FA_EXPIRED', 'OTP code expired');
                $error = 'Your code has expired. <a href="login.php">Request a new one</a>.';
            } elseif ($token['attempts'] >= MAX_OTP_ATTEMPTS) {
                $error = 'Too many failed attempts. <a href="login.php">Start over</a>.';
            } else {
                $submittedHash = hash('sha256', $submitted);
                
                if (hash_equals($token['otp_hash'], $submittedHash)) {
                    // SUCCESS
                    logAction($pdo, $userId, $_SESSION['2fa_username'], 'LOGIN_2FA_SUCCESS', 'OTP verification successful');
                    
                    $stmt = $pdo->prepare('DELETE FROM otp_tokens WHERE id = ?');
                    $stmt->execute([$token['id']]);
                    
                    session_regenerate_id(true);
                    
                    $stmt = $pdo->prepare('SELECT role FROM users WHERE id = ?');
                    $stmt->execute([$userId]);
                    $user = $stmt->fetch();
                    
                    $_SESSION['authenticated'] = true;
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['username'] = $_SESSION['2fa_username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['last_active'] = time();
                    
                    unset($_SESSION['2fa_user_id'], $_SESSION['2fa_username'], $_SESSION['2fa_email']);
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    // Wrong OTP
                    $remaining = MAX_OTP_ATTEMPTS - ($token['attempts'] + 1);
                    logAction($pdo, $userId, $_SESSION['2fa_username'], 'LOGIN_2FA_FAILED', "Invalid OTP attempt. Remaining attempts: $remaining");
                    
                    $stmt = $pdo->prepare('UPDATE otp_tokens SET attempts = attempts + 1 WHERE id = ?');
                    $stmt->execute([$token['id']]);
                    $error = 'Incorrect code. ' . max(0, $remaining) . ' attempt(s) remaining.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP - Property Management</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f4f8; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #fff; border-radius: 8px; padding: 30px; width: 340px; }
        h2 { color: #1F4E79; text-align: center; }
        .sub { text-align: center; color: #555; font-size: 14px; margin-bottom: 20px; }
        input { width: 100%; padding: 12px; font-size: 24px; letter-spacing: 8px; text-align: center; border: 2px solid #2E75B6; border-radius: 4px; margin-bottom: 15px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #2E75B6; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        .error { background: #fdecea; color: #c0392b; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; }
        .back { text-align: center; margin-top: 15px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Check Your Email</h2>
        <p class="sub">We sent a 6-digit code to<br><strong><?= htmlspecialchars($maskedEmail) ?></strong></p>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <input type="text" name="otp" maxlength="6" pattern="\d{6}" inputmode="numeric" autocomplete="one-time-code" placeholder="000000" required>
            <button type="submit">Verify Code</button>
        </form>
        
        <p class="back"><a href="login.php">Back to login</a></p>
    </div>
</body>
</html>