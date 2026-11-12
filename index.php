
<?php

session_start();
require "db.php";

$message = "";

if (isset($_GET["registered"])) {
    $message = "Registration successful! You can now sign in.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $sql = "SELECT * FROM users WHERE email = ?";

    $stmt = $conn->prepare($sql);
    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
if ($user && password_verify($password, $user["password"])) {

    if ((int)$user["status"] === 0) {
    $message = "Your account is waiting for Admin approval!";
} elseif ((int)$user["status"] !== 1) {
    $message = "Your account is not active!";

    } else {
        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["fullname"] = $user["fullname"];
        $_SESSION["role"] = $user["role"];

        header("Location: home.php");
        exit;
    }

} else {
    $message = "Invalid email or password!";
}   
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WebConnect - Sign In</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <h1>TaskMaster</h1>
    <h2>Welcome!</h2>
    <p>Sign in to continue</p>

    <?php if ($message != ""): ?>
        <p class="message">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <input type="email"
               name="email"
               placeholder="Email Address"
               required>

        <input type="password"
               name="password"
               placeholder="Password"
               required>

        <button type="submit">Sign In</button>

    </form>

    <p class="switch">
        Don't have an account?
        <a href="register.php">Register</a>
    </p>

</div>

</body>
</html>
