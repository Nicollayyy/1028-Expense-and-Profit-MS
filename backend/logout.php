<?php
// Proper logout: clear session and redirect to login
session_start();
session_unset();
session_destroy();
// make sure cookies are cleared (optional)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
header('Location: html/login.html');
exit();
?>
