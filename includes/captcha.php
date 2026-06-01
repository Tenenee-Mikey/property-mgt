<?php
// FILE: includes/captcha.php - Fixed session start

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function generateCaptcha() {
    $num1 = rand(1, 10);
    $num2 = rand(1, 10);
    $_SESSION['captcha_answer'] = $num1 + $num2;
    $_SESSION['captcha_num1'] = $num1;
    $_SESSION['captcha_num2'] = $num2;
}

function displayCaptcha() {
    if (!isset($_SESSION['captcha_num1'])) {
        generateCaptcha();
    }
    return $_SESSION['captcha_num1'] . ' + ' . $_SESSION['captcha_num2'] . ' = ?';
}

function verifyCaptcha($answer) {
    if (!isset($_SESSION['captcha_answer'])) {
        return false;
    }
    return (int)$answer === (int)$_SESSION['captcha_answer'];
}

function clearCaptcha() {
    unset($_SESSION['captcha_answer']);
    unset($_SESSION['captcha_num1']);
    unset($_SESSION['captcha_num2']);
}
?>