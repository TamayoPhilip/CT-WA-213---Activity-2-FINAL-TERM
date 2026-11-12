
<?php
require_once "auth.php";
requireLogin();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>WebConnect - Home</title>
    <link rel="stylesheet" href="style.css">

    <style>
      h1, h2, p {
    color: black !important;
    font-weight: bold !important;
}

button.logout-button {
    background-color: #dc3545 !important;
    color: white !important;
    font-weight: bold !important;
    border: none;
}
.success-text {
    color: green !important;
    font-weight: bold !important;
}
    </style>
</head>
<body>

<div class="container">

    <h1>TaskMaster</h1>

    <h2>Welcome!</h2>

    <h2 class="welcome-text">
        Hello, <?= htmlspecialchars($_SESSION["fullname"]) ?>!
    </h2>

    <p class="success-text">
        You have successfully logged in.
    </p>

 
<?php if (($_SESSION["role"] ?? "") === "admin"): ?>
    <a href="users.php">
        <button type="button">User Management</button>
    </a>
<?php endif; ?>

<a href="tasks.php">
    <button type="button">My Task Board</button>
</a>

    <img src="images/meme.jpg"
         alt="My Meme Picture"
         width="300">

            <p class="apology-text">
        MAAM si boss dogs 
    </p>
    <a href="logout.php">
        <button type="button" class="logout-button">Logout</button>
    </a>

</div>

</body>
</html>
