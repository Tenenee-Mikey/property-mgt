<?php
// FILE: google_auth.php - Redirect to Google
session_start();
require_once 'vendor/autoload.php';

// Get credentials from environment variables (Railway)
$clientID = getenv('GOOGLE_CLIENT_ID');
$clientSecret = getenv('GOOGLE_CLIENT_SECRET');
$redirectUri = getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/property_mgt/google_callback.php';

// Check if credentials are set
if (!$clientID || !$clientSecret) {
    die('Google OAuth credentials not configured. Please set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET environment variables.');
}

$client = new Google\Client();
$client->setClientId($clientID);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope('email');
$client->addScope('profile');

// Send user to Google login page
$authUrl = $client->createAuthUrl();
header('Location: ' . $authUrl);
exit;
?>