<?php
include 'db.php';

$username = 'admin';
$password = password_hash('admin123', PASSWORD_DEFAULT); // ✅ Hashes password securely
$phone_number = '09474427459';

$query = "INSERT INTO admin (username, password, phone_number) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "sss", $username, $password, $phone_number);

if (mysqli_stmt_execute($stmt)) {
    echo "✅ Admin account created successfully!";
} else {
    echo "❌ Error: " . mysqli_error($conn);
}
?>
