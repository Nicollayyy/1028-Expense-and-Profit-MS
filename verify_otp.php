<?php
session_start();
include 'db.php';

if (!isset($_SESSION['otp']) || !isset($_SESSION['phone'])) {
    echo "<script>alert('No OTP request found. Please request a new one.'); window.location='./html/forgot_password.html';</script>";
    exit();
}

if (isset($_POST['verify_otp'])) {
    $entered_otp = $_POST['otp'];

    if ($entered_otp == $_SESSION['otp']) {
        // OTP matched
        $_SESSION['otp_verified'] = true;
        echo "<script>alert('OTP verified successfully!'); window.location='./html/reset_password.html';</script>";
        exit();
    } else {
        echo "<script>alert('Invalid OTP. Please try again.');</script>";
    }
}
?>

