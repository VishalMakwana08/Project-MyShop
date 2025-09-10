<?php
require_once '../config.php';

$new_password = $confirm_password = "";
$new_password_err = $confirm_password_err = $update_success = "";
$show_login_link = false;

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = trim($_POST["new_password"]);
    $confirm_password = trim($_POST["confirm_password"]);

    // Strong password check
    if (strlen($new_password) < 8 ||
        !preg_match('/[A-Z]/', $new_password) ||
        !preg_match('/[a-z]/', $new_password) ||
        !preg_match('/[0-9]/', $new_password) ||
        !preg_match('/[\W]/', $new_password)) {
        $new_password_err = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
    }

    if ($new_password !== $confirm_password) {
        $confirm_password_err = "Passwords do not match.";
    }

    if (empty($new_password_err) && empty($confirm_password_err)) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE shops SET password_hash = ? WHERE owner_email = ?");
        $stmt->bind_param("ss", $hashed, $_SESSION['reset_email']);
        if ($stmt->execute()) {
            $update_success = "✅ Password updated successfully. You can now login.";
            $show_login_link = true;
            unset($_SESSION['reset_email']);
        } else {
            $update_success = "❌ Something went wrong. Try again.";
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!-- HTML -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
    <!-- <link rel="stylesheet" href="../tailwind/src/output.css" /> -->
       <script src="../assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="../assets/css/all.min.css">
        <link rel="stylesheet" href="../asset/css/tailwind.min.css" />

    <link rel="stylesheet" href="../assets/css/google_font.css">

</head>
<body class="bg-gray-100 flex justify-center items-center min-h-screen">
<div class="bg-white p-6 rounded shadow-md w-full max-w-md">
    <h2 class="text-xl font-bold mb-4 text-center">🔐 Set New Password</h2>

    <?php if (!empty($update_success)): ?>
        <div class="bg-green-100 text-green-700 p-2 rounded mb-4"><?= $update_success ?></div>
        <?php if ($show_login_link): ?>
            <div class="text-center mt-2">
                <a href="login.php" class="text-blue-600 hover:underline font-semibold">🔁 Back to Login</a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-4">
            <label class="block font-semibold">New Password</label>
            <input type="password" name="new_password" class="w-full border p-2 rounded" required>
            <span class="text-sm text-red-600"><?= $new_password_err ?></span>
        </div>

        <div class="mb-6">
            <label class="block font-semibold">Confirm Password</label>
            <input type="password" name="confirm_password" class="w-full border p-2 rounded" required>
            <span class="text-sm text-red-600"><?= $confirm_password_err ?></span>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded w-full hover:bg-blue-700">
            Update Password
        </button>
    </form>
</div>
</body>
</html>
