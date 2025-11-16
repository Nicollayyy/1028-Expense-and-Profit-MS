<?php
session_start();
include 'db.php';

if (isset($_POST['send_otp'])) {
    $phone = $_POST['phone'];

    // Check if phone exists in the database
    $check = mysqli_query($conn, "SELECT * FROM admin WHERE phone_number='$phone'");
    if (mysqli_num_rows($check) > 0) {
        // Generate a 6-digit OTP
        $otp = rand(100000, 999999);

        // Store OTP and phone in session for verification
        $_SESSION['otp'] = $otp;
        $_SESSION['phone'] = $phone;

        // iProgSMS API credentials
        $api_token = "72779804ac459f18e0dc14840ed187cb2b1760e5"; // 🔹 Replace with your actual API token

        // Prepare data for sending
        $data = [
            'api_token'    => $api_token,
            'phone_number' => $phone,
            'message'      => "Your OTP code is: $otp. It will expire in 5 minutes."
        ];

        // Initialize cURL
        $ch = curl_init("https://sms.iprogtech.com/api/v1/otp/send_otp");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        // Check if request was successful
        if ($error) {
            echo "<script>alert('Failed to send OTP. Error: " . addslashes($error) . "');</script>";
        } else {
            $result = json_decode($response, true);
            if (isset($result['status']) && $result['status'] === 'success') {
                echo "<script>alert('OTP sent successfully!'); window.location='./html/verify_otp.html';</script>";
            } else {
                echo "<script>alert('Failed to send OTP. Please try again.');</script>";
            }
        }
    } else {
        echo "<script>alert('Phone number not found in system.');</script>";
    }
}
?>
