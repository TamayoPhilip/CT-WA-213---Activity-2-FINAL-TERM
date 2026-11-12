
<?php
require_once "auth.php";
requireAdmin();

$message = "";

// CREATE CSRF TOKEN
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// APPROVE USER
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["approve_user"])) {

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        exit("Invalid request.");
    }

    $user_id = (int)($_POST["user_id"] ?? 0);

    $stmt = $conn->prepare(
        "UPDATE users
         SET status = 1
         WHERE id = ? AND role = 'user' AND status = 0"
    );

    $stmt->execute([$user_id]);

    if ($stmt->rowCount() > 0) {
        $message = "User approved successfully!";
    } else {
        $message = "User could not be approved.";
    }
}

// UPDATE USER ROLE AND ACCOUNT STATUS
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_user"])) {

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        exit("Invalid request.");
    }

    $user_id = (int)($_POST["user_id"] ?? 0);
    $role = $_POST["role"] ?? "";
    $status = (int)($_POST["status"] ?? -1);

    if ($user_id === (int)$_SESSION["user_id"]) {
        $message = "You cannot change your own account settings here.";
    } elseif (!in_array($role, ["admin", "user"], true) ||
              !in_array($status, [0, 1], true)) {
        $message = "Invalid role or account status.";
    } else {
        $stmt = $conn->prepare(
            "UPDATE users
             SET role = ?, status = ?
             WHERE id = ? AND role != 'admin'"
        );

        $stmt->execute([$role, $status, $user_id]);

        $message = "User information updated!";
    }
}

// DELETE A REGULAR USER
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_user"])) {

    if (!hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"] ?? "")) {
        exit("Invalid request.");
    }

    $user_id = (int)($_POST["user_id"] ?? 0);

    if ($user_id === (int)$_SESSION["user_id"]) {
        $message = "You cannot delete your own account.";
    } else {

        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE id = ? AND role = 'user'"
        );

        $stmt->execute([$user_id]);

        if ($stmt->fetch()) {

            try {
                $conn->beginTransaction();

                // DELETE USER TASKS FIRST
                $stmt = $conn->prepare(
                    "DELETE FROM tasks WHERE user_id = ?"
                );

                $stmt->execute([$user_id]);

                // DELETE USER
                $stmt = $conn->prepare(
                    "DELETE FROM users
                     WHERE id = ? AND role = 'user'"
                );

                $stmt->execute([$user_id]);

                $conn->commit();

                $message = "User and their tasks deleted successfully!";

            } catch (PDOException $e) {

                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }

                $message = "Unable to delete this user.";
            }

        } else {
            $message = "Admin accounts cannot be deleted here.";
        }
    }
}

// VIEW ALL USERS
$stmt = $conn->query(
    "SELECT id, fullname, email, role, status, created_at
     FROM users
     ORDER BY id DESC"
);

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>TaskMaster - User Management</title>

    <link rel="stylesheet" href="style.css">

    <style>

        .user-management {
            width: 1000px;
            max-width: 95%;
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            overflow-x: auto;
        }

        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .user-table th,
        .user-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        .user-table th {
            background: #764ba2;
            color: white;
        }

        .user-table select {
            padding: 7px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .user-table button {
            width: auto;
            padding: 8px 12px;
            margin: 4px 0;
            font-size: 13px;
        }

        .delete-button {
            background: #dc3545;
        }

        .user-message {
            margin-top: 15px;
            color: #764ba2;
            font-weight: bold;
        }

        .active-status {
            color: green;
            font-weight: bold;
        }

        .inactive-status {
            color: #dc3545;
            font-weight: bold;
        }

        button[name="approve_user"] {
            background-color: green;
            color: white;
            font-weight: bold;
        }

    </style>

</head>

<body>

<div class="user-management">

    <h1>TaskMaster</h1>

    <h2>User Management</h2>

    <p>
        Welcome, <?= htmlspecialchars($_SESSION["fullname"]) ?>!
    </p>

    <?php if ($message !== ""): ?>

        <p class="user-message">
            <?= htmlspecialchars($message) ?>
        </p>

    <?php endif; ?>

    <table class="user-table">

        <thead>
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Account Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>

        <?php foreach ($users as $user): ?>

            <tr>

                <td>
                    <?= (int)$user["id"] ?>
                </td>

                <td>
                    <?= htmlspecialchars($user["fullname"]) ?>
                </td>

                <td>
                    <?= htmlspecialchars($user["email"]) ?>
                </td>

                <td>
                    <?= htmlspecialchars($user["role"]) ?>
                </td>

                <td>

                    <?php if ((int)$user["status"] === 1): ?>

                        <span class="active-status">
                            Approved
                        </span>

                    <?php else: ?>

                        <span class="inactive-status">
                            Pending
                        </span>

                    <?php endif; ?>

                </td>

                <td>
                    <?= htmlspecialchars($user["created_at"]) ?>
                </td>

                <td>

                    <?php if (
                        $user["role"] === "user" &&
                        (int)$user["id"] !== (int)$_SESSION["user_id"]
                    ): ?>

                        <!-- APPROVE USER BUTTON -->

                        <?php if ((int)$user["status"] === 0): ?>

                            <form method="POST">

                                <input type="hidden"
                                       name="csrf_token"
                                       value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                                <input type="hidden"
                                       name="user_id"
                                       value="<?= (int)$user["id"] ?>">

                                <button type="submit"
                                        name="approve_user">
                                    Approve User
                                </button>

                            </form>

                        <?php endif; ?>


                        <!-- UPDATE USER -->

                        <form method="POST">

                            <input type="hidden"
                                   name="csrf_token"
                                   value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                            <input type="hidden"
                                   name="user_id"
                                   value="<?= (int)$user["id"] ?>">

                            <select name="role">

                                <option value="user"
                                    <?= $user["role"] === "user" ? "selected" : "" ?>>
                                    User
                                </option>

                                <option value="admin"
                                    <?= $user["role"] === "admin" ? "selected" : "" ?>>
                                    Admin
                                </option>

                            </select>

                            <select name="status">

                                <option value="1"
                                    <?= (int)$user["status"] === 1 ? "selected" : "" ?>>
                                    Active
                                </option>

                                <option value="0"
                                    <?= (int)$user["status"] === 0 ? "selected" : "" ?>>
                                    Inactive
                                </option>

                            </select>

                            <button type="submit" name="update_user">
                                Update
                            </button>

                        </form>


                        <!-- DELETE USER -->

                        <form method="POST"
                              onsubmit="return confirm('Delete this user and their tasks?');">

                            <input type="hidden"
                                   name="csrf_token"
                                   value="<?= htmlspecialchars($_SESSION["csrf_token"]) ?>">

                            <input type="hidden"
                                   name="user_id"
                                   value="<?= (int)$user["id"] ?>">

                            <button type="submit"
                                    name="delete_user"
                                    class="delete-button">
                                Delete
                            </button>

                        </form>

                    <?php else: ?>

                        <small>Protected account</small>

                    <?php endif; ?>

                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

    <a href="home.php">
        <button type="button">Back to Home</button>
    </a>

</div>

</body>
</html>
