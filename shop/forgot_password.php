<?php
require_once '../config.php';

$owner_email = $owner_name = "";
$owner_email_err = $owner_name_err = $verify_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $owner_email = trim($_POST["owner_email"]);
    $owner_name = trim($_POST["owner_name"]);

    if (empty($owner_email)) $owner_email_err = "Please enter your email.";
    if (empty($owner_name)) $owner_name_err = "Please enter your registered name.";

    if (empty($owner_email_err) && empty($owner_name_err)) {
        $stmt = $conn->prepare("SELECT id FROM shops WHERE owner_email = ? AND owner_name = ? LIMIT 1");
        $stmt->bind_param("ss", $owner_email, $owner_name);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            // Email + name match, allow reset
            $_SESSION['reset_email'] = $owner_email;
            header("Location: reset_password.php");
            exit;
        } else {
            $verify_err = "❌ No match found. Please check your details.";
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
    <title>Forgot Password</title>
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
       <script src="../assets/js/tailwind.js"></script>
    <link rel="stylesheet" href="../assets/css/all.min.css">
        <link rel="stylesheet" href="../asset/css/tailwind.min.css" />

    <link rel="stylesheet" href="../assets/css/google_font.css">
</head>
<body class="bg-gray-100 flex justify-center items-center min-h-screen">
<div class="bg-white p-6 rounded shadow-md w-full max-w-md">
    <h2 class="text-xl font-bold mb-4 text-center">🔐 Forgot Password</h2>

    <?php if (!empty($verify_err)): ?>
        <div class="bg-red-100 text-red-700 p-2 rounded mb-4"><?= $verify_err ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-4">
            <label class="block font-semibold">Registered Email</label>
            <input type="email" name="owner_email" value="<?= htmlspecialchars($owner_email) ?>"
                   class="w-full border p-2 rounded" required>
            <span class="text-sm text-red-600"><?= $owner_email_err ?></span>
        </div>

        <div class="mb-6">
            <label class="block font-semibold">Owner Name</label>
            <input type="text" name="owner_name" value="<?= htmlspecialchars($owner_name) ?>"
                   class="w-full border p-2 rounded" required>
            <span class="text-sm text-red-600"><?= $owner_name_err ?></span>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded w-full hover:bg-blue-700">
            Verify & Reset Password
        </button>
    </form>
</div>
</body>
</html>
