<?php
session_start();
include 'db.php';

// Make sure the OTP verification was completed
if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['phone'])) {
    echo "<script>alert('Access denied. Please verify OTP first.'); window.location='./html/forgot_password.html';</script>";
    exit();
}

if (isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $phone = $_SESSION['phone'];

        // Update password in the admin table
        $query = "UPDATE admin SET password = ? WHERE phone_number = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $phone);
        mysqli_stmt_execute($stmt);

        // Clear OTP sessions after success
        unset($_SESSION['otp']);
        unset($_SESSION['otp_verified']);
        unset($_SESSION['phone']);

        echo "<script>alert('Password reset successfully! You can now log in.'); window.location='./html/login.html';</script>";
        exit();
    } else {
        echo "<script>alert('Passwords do not match!');</script>";
    }
}
?>
