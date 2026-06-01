<?php
// FILE: login.php - Complete with Lockout, CAPTCHA, and Audit Logs
session_start();

// If already fully authenticated
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: dashboard.php');
    exit;
}

require_once 'includes/csrf.php';
require_once 'includes/captcha.php';
require_once 'includes/audit.php';
require_once 'dp.php';
require_once 'mail_config.php';

$error = '';
$showCaptcha = false;
$captchaHtml = '';

// Check if user is locked out
function isUserLocked($pdo, $username) {
    $stmt = $pdo->prepare('SELECT locked_until FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user && $user['locked_until']) {
        $lockedUntil = strtotime($user['locked_until']);
        if (time() < $lockedUntil) {
            $minutesLeft = ceil(($lockedUntil - time()) / 60);
            return "Too many failed attempts. Account locked for {$minutesLeft} minute(s).";
        } else {
            $stmt = $pdo->prepare('UPDATE users SET login_attempts = 0, locked_until = NULL WHERE username = ? OR email = ?');
            $stmt->execute([$username, $username]);
        }
    }
    return false;
}

// Record failed login attempt
function recordFailedAttempt($pdo, $username) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT login_attempts FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    
    if ($user) {
        $attempts = $user['login_attempts'] + 1;
        
        if ($attempts >= 5) {
            $lockedUntil = date('Y-m-d H:i:s', time() + (15 * 60));
            $stmt = $pdo->prepare('UPDATE users SET login_attempts = ?, last_failed_login = NOW(), locked_until = ? WHERE username = ? OR email = ?');
            $stmt->execute([$attempts, $lockedUntil, $username, $username]);
            logAction($pdo, null, $username, 'ACCOUNT_LOCKED', "Account locked for 15 minutes due to 5 failed attempts");
            return "Account locked for 15 minutes due to too many failed attempts.";
        } else {
            $stmt = $pdo->prepare('UPDATE users SET login_attempts = ?, last_failed_login = NOW() WHERE username = ? OR email = ?');
            $stmt->execute([$attempts, $username, $username]);
            return "Invalid credentials. " . (5 - $attempts) . " attempts remaining.";
        }
    }
    return "Invalid username or password.";
}

// Reset login attempts on successful login
function resetLoginAttempts($pdo, $userId) {
    $stmt = $pdo->prepare('UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = ?');
    $stmt->execute([$userId]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $captchaAnswer = $_POST['captcha'] ?? '';
        
        // Check if CAPTCHA is required
        $stmt = $pdo->prepare('SELECT login_attempts FROM users WHERE username = ? OR email = ?');
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && $user['login_attempts'] >= 3) {
            $showCaptcha = true;
            if (!verifyCaptcha($captchaAnswer)) {
                $error = 'Incorrect CAPTCHA answer.';
            }
        }
        
        // Check if user is locked out
        $lockError = isUserLocked($pdo, $username);
        if ($lockError) {
            $error = $lockError;
        } elseif (!isset($error) || $error === '') {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Log successful password verification
                logAction($pdo, $user['id'], $user['username'], 'LOGIN_PASSWORD_SUCCESS', "User entered correct password");
                
                resetLoginAttempts($pdo, $user['id']);
                clearCaptcha();
                
                // Generate OTP
                $otp = sprintf("%06d", random_int(0, 999999));
                $otp_hash = hash('sha256', $otp);
                
                date_default_timezone_set('Pacific/Fiji');
                $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                $stmt = $pdo->prepare('DELETE FROM otp_tokens WHERE user_id = ?');
                $stmt->execute([$user['id']]);
                
                $stmt = $pdo->prepare('INSERT INTO otp_tokens (user_id, otp_hash, expires_at) VALUES (?, ?, ?)');
                $stmt->execute([$user['id'], $otp_hash, $expiresAt]);
                
                if (sendOTPEmail($user['email'], $user['username'], $otp)) {
                    $_SESSION['2fa_user_id'] = $user['id'];
                    $_SESSION['2fa_username'] = $user['username'];
                    $_SESSION['2fa_email'] = $user['email'];
                    header('Location: verify_otp.php');
                    exit;
                } else {
                    $error = 'Failed to send verification code.';
                }
            } else {
                logAction($pdo, null, $username, 'LOGIN_FAILED', "Failed password attempt for user: $username");
                $error = recordFailedAttempt($pdo, $username);
            }
        }
    }
}

// Check if CAPTCHA should be shown
if (isset($_POST['username']) && !isset($error)) {
    $stmt = $pdo->prepare('SELECT login_attempts FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$_POST['username'], $_POST['username']]);
    $user = $stmt->fetch();
    if ($user && $user['login_attempts'] >= 3) {
        $showCaptcha = true;
    }
}

if ($showCaptcha) {
    generateCaptcha();
    $captchaHtml = '
    <div style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
        <label>CAPTCHA: What is ' . displayCaptcha() . '</label>
        <input type="text" name="captcha" required style="margin-top: 5px;">
    </div>';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Property Management</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f4f8; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #fff; border-radius: 8px; padding: 30px; width: 340px; }
        h2 { color: #1F4E79; text-align: center; }
        label { display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #2E75B6; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        .error { background: #fdecea; color: #c0392b; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .hint { text-align: center; font-size: 12px; color: #999; margin-top: 15px; }
        hr { margin: 20px 0; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Property Management Login</h2>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            
            <label>Username or Email</label>
            <input type="text" name="username" required>
            
            <label>Password</label>
            <input type="password" name="password" required>
            
            <?= $captchaHtml ?>
            
            <button type="submit">Continue</button>
        </form>
        
        <p class="hint">Step 1 of 2 - A code will be emailed to you</p>
        <p class="hint"><a href="register.php">Don't have an account? Register</a></p>
        
        <hr>
        
        <div style="text-align: center;">
            <p>Or</p>
            <a href="google_auth.php" style="display: inline-block; background: #4285F4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Sign in with Google</a>
        </div>
    </div>
</body>
</html>