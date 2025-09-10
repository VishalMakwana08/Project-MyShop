<?php
// Initialize the session.
session_start();

// Unset all of the session variables.
$_SESSION = array();
session_unset();
// Destroy the session.
session_destroy();

// Clear the session storage flag via client-side script before redirecting
// This ensures the splash screen plays on the next dashboard visit
echo '<!DOCTYPE html><html><head><title>Logging Out...</title></head><body>';
echo '<script>';
echo 'sessionStorage.removeItem("hasPlayedDashboardSplash");'; // Clear the flag
echo 'window.location.href = "login.php";'; // Redirect to login page
echo '</script>';
echo '</body></html>';
exit; // Important: Exit to prevent further PHP execution and ensure script runs
?>