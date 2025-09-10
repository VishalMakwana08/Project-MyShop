<?php
// include 'check_internet.php';
require_once '../config.php';

$owner_email = $password = "";
$owner_email_err = $password_err = $login_err = "";
$registration_success_message = "";

if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: dashboard.php");
    exit;
}

if (isset($_GET['registration_success']) && $_GET['registration_success'] == 'true') {
    $registration_success_message = "🎉 Registration successful! Please log in.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $owner_email = trim($_POST["owner_email"]);
    $password = trim($_POST["password"]);

    if (empty($owner_email)) $owner_email_err = "Please enter your email.";
    if (empty($password)) $password_err = "Please enter your password.";

    if (empty($owner_email_err) && empty($password_err)) {
        $owner_email_safe = mysqli_real_escape_string($conn, $owner_email);
        $sql = "SELECT * FROM shops WHERE owner_email = '$owner_email_safe' LIMIT 1";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);

            if (password_verify($password, $row["password_hash"])) {
                $_SESSION["loggedin"] = true;
                $_SESSION["shop_id"] = $row["id"];
                $_SESSION["owner_email"] = $row["owner_email"];
                $_SESSION["shop_name"] = $row["shop_name"];
                $_SESSION["owner_name"] = $row["owner_name"];
                $_SESSION["shop_type"] = $row["shop_type"];
                $_SESSION["gst_number"] = $row["gst_number"];
                $_SESSION["shop_license"] = $row["shop_license"];
                
                header("location: dashboard.php");
                exit();
            } else {
                $login_err = "❌ Invalid email or password.";
            }
        } else {
            $login_err = "❌ Invalid email or password.";
        }
    }

    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MyShop</title>
    <link rel="stylesheet" href="../asset/css/all.min.css" />
    <link rel="stylesheet" href="../asset/css/tailwind.min.css" />
        <script src="../assets/js/tailwind.js"></script>
            <link rel="stylesheet" href="../assets/css/google_font.css">


    <style>
        .bg-primary {
            background-color: #3B82F6; /* Primary color */
        }
        .bg-success {
            background-color: #34D399; /* Success color */
        }
    </style>
</head>
<body class="bg-gray-50 flex justify-center items-center min-h-screen">
    <div class="bg-white shadow-md rounded-lg p-6 w-full max-w-md">
        <h2 class="text-2xl font-bold text-center mb-6 text-primary">🔐 Shop Login - MyShop</h2>

        <?php if (!empty($registration_success_message)): ?>
            <div class="bg-success text-white px-4 py-2 mb-4 rounded">
                <?= $registration_success_message ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($login_err)): ?>
            <div class="bg-red-100 text-red-800 px-4 py-2 mb-4 rounded">
                <?= $login_err ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-4">
                <label for="owner_email" class="block mb-1 font-semibold text-gray-700">Email</label>
                <input type="email" name="owner_email" id="owner_email" value="<?= htmlspecialchars($owner_email) ?>" required
                       class="w-full p-2.5 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                <span class="text-sm text-red-600"><?= $owner_email_err ?></span>
            </div>

            <div class="mb-6">
                <label for="password" class="block mb-1 font-semibold text-gray-700">Password</label>
                <input type="password" name="password" id="password" required
                       class="w-full p-2.5 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
                <span class="text-sm text-red-600"><?= $password_err ?></span>
            </div>

            <button type="submit"
                    class="w-full bg-primary text-white py-2 rounded hover:bg-blue-700 transition">
                Login
            </button>

            <p class="text-sm text-center mt-4 text-gray-600">
                Don’t have an account?
                <a href="index.php" class="text-primary hover:underline">Register here</a>
            </p>
        </form>
        <p class="text-sm text-center mt-2 text-gray-600">
            Forgot your password?
            <a href="forgot_password.php" class="text-primary hover:underline">Reset here</a>
        </p>
    </div>
</body>
</html>
