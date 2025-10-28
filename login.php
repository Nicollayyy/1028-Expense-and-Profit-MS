<?php
session_start();
include 'db.php';

// Check if the form is submitted
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Prepare and execute the query
    $query = "SELECT * FROM admin WHERE username = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if the user exists
    if ($row = mysqli_fetch_assoc($result)) {
        // Verify the password (hashed)
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin_id'] = $row['admin_id'];
            $_SESSION['username'] = $row['username'];
            header("Location: dashboard.php");
            exit();
        } else {
            echo "<script>alert('Incorrect username or password'); window.location='login.html';</script>";
        }
    } else {
        echo "<script>alert('Incorrect username or password'); window.location='login.html';</script>";
    }
}
?>
