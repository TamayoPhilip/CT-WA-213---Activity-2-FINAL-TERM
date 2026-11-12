
<?php

require "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = trim($_POST["fullname"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm = $_POST["confirm"];

    if ($password !== $confirm) {

        $message = "Passwords do not match!";

    } elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters!";

    } else {

        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $check->execute([$email]);

        if ($check->fetch()) {

            $message = "Email is already registered!";

        } else {

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

    $role = "user";
$status = 0;

$sql = "INSERT INTO users
        (fullname, email, password, role, status)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

$stmt->execute([
    $fullname,
    $email,
    $hashedPassword,
    $role,
    $status
]);

            header("Location: index.php?registered=1");
            exit;

        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WebConnect - Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

    <h1>TaskMaster</h1>
    <h2>Create Account</h2>
    <p>Register to get started</p>

    <?php if ($message != ""): ?>
        <p class="message">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <input type="text"
               name="fullname"
               placeholder="Full Name"
               maxlength="100"
               required>

        <input type="email"
               name="email"
               placeholder="Email Address"
               maxlength="100"
               required>

        <input type="password"
               name="password"
               placeholder="Password"
               minlength="6"
               required>

        <input type="password"
               name="confirm"
               placeholder="Confirm Password"
               required>

        <button type="submit">Register</button>

    </form>

    <p class="switch">
        Already have an account?
        <a href="index.php">Sign In</a>
    </p>

</div>

</body>
</html>
