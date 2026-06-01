<?php
// FILE: google_callback.php - Handle Google's response
session_start();
require_once 'vendor/autoload.php';
require_once 'dp.php';

// Get credentials from environment variables
$clientID = getenv('GOOGLE_CLIENT_ID');
$clientSecret = getenv('GOOGLE_CLIENT_SECRET');
$redirectUri = getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/property_mgt/google_callback.php';

if (!$clientID || !$clientSecret) {
    die('Google OAuth credentials not configured.');
}

$client = new Google\Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);

// Get access token
$code = $_GET['code'] ?? '';
if (!$code) {
    die('No authorization code received.');
}

$token = $client->fetchAccessTokenWithAuthCode($code);
$client->setAccessToken($token);

// Get user info from Google
$oauth = new Google\Service\Oauth2($client);
$googleUser = $oauth->userinfo->get();

$email = $googleUser->email;
$username = $googleUser->name;
$google_id = $googleUser->id;

// Check if user exists in database
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? OR google_id = ?');
$stmt->execute([$email, $google_id]);
$user = $stmt->fetch();

if ($user) {
    // Existing user - log them in
    $_SESSION['authenticated'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['last_active'] = time();
    session_regenerate_id(true);
    header('Location: dashboard.php');
    exit;
} else {
    // New user - redirect to complete registration
    $_SESSION['google_email'] = $email;
    $_SESSION['google_username'] = $username;
    $_SESSION['google_id'] = $google_id;
    header('Location: google_register.php');
    exit;
}
?>