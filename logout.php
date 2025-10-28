<?php
// index.php
session_start();
if (!isset($_SESSION['admin'])) {
  header("Location:./html/login.html");
  exit();
}
?>

