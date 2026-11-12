
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/db.php";

if (!function_exists("requireLogin")) {

    function requireLogin() {
        global $conn;

        if (!isset($_SESSION["user_id"])) {
            header("Location: index.php");
            exit;
        }

        $stmt = $conn->prepare(
            "SELECT status FROM users WHERE id = ?"
        );

        $stmt->execute([$_SESSION["user_id"]]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account || (int)$account["status"] !== 1) {
            session_unset();
            session_destroy();

            header("Location: index.php");
            exit;
        }
    }
}

if (!function_exists("requireAdmin")) {

    function requireAdmin() {
        requireLogin();

        if (($_SESSION["role"] ?? "") !== "admin") {
            http_response_code(403);
            exit("Access denied! Admin only.");
        }
    }
}

?>
