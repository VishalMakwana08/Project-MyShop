<?php
// Function to check for internet connectivity
function checkInternetConnection() {
    $connected = @fsockopen("www.google.com", 80);
    if ($connected) {
        fclose($connected);
        return true;
    }
    return false;
}

// Check if the internet is not connected.
if (!checkInternetConnection()) {
    // If there's no internet, display an error page.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>No Internet Connection</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f2f5;
            text-align: center;
        }
        .container {
            padding: 40px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #d9534f;
        }
        p {
            color: #555;
            font-size: 1.1em;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Offline 😔</h1>
        <p>It seems you're not connected to the internet.</p>
        <p>Please check your network connection and try again.</p>
    </div>
</body>
</html>
<?php
    exit;
}
?>