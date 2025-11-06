<?php
session_start();

// If not logged in, redirect to the login page inside html/
if (!isset($_SESSION['admin_id'])) {
    header('Location: html/login.html');
    exit();
}

// User is logged in — include the HTML dashboard
// User is logged in — redirect to the HTML dashboard so CSS and asset paths
// resolve the same way they do for `html/login.html`.
header('Location: html/dashboard.html');
exit();

?>
