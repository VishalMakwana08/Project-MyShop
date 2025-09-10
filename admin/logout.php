<?php
// logout.php
// Destroys the admin session and redirects to the login page.

session_start(); // Start the session to ensure session variables can be accessed/destroyed

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to the login page. Adjust this path if your login.php is not in the same directory.
header('Location: login.php');
exit(); // Important: Stop script execution after redirection
?>
