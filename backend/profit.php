<?php
session_start();
// Only allow access if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: html/login.html');
    exit();
}
header('Location: html/profit.html');
exit();
?>
