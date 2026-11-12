
<?php
require_once "auth.php";
requireLogin();

$user_id = $_SESSION["user_id"];
$message = "";

$allowedStatuses = ["Pending", "In Progress", "Completed"];

// ADD TASK
if (isset($_POST["add_task"])) {

    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $deadline = trim($_POST["deadline"] ?? "");

    if ($title === "" || $deadline === "") {

        $message = "Please enter a task title and deadline!";

    } else {

        $date = DateTime::createFromFormat("!Y-m-d", $deadline);

        if (!$date || $date->format("Y-m-d") !== $deadline) {

            $message = "Invalid deadline!";

        } else {

            $sql = "INSERT INTO tasks
                    (user_id, title, description, deadline, status)
                    VALUES (:user_id, :title, :description, :deadline, 'Pending')";

            $stmt = $conn->prepare($sql);

            $stmt->execute([
                ":user_id" => $user_id,
                ":title" => $title,
                ":description" => $description,
                ":deadline" => $deadline
            ]);

            header("Location: tasks.php");
            exit;
        }
    }
}


// UPDATE TASK STATUS
if (isset($_POST["update_status"])) {

    $task_id = (int) $_POST["task_id"];
    $status = $_POST["status"];

    if (in_array($status, $allowedStatuses, true)) {

        $sql = "UPDATE tasks
                SET status = ?
                WHERE id = ? AND user_id = ?";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            $status,
            $task_id,
            $user_id
        ]);

        $message = "Task status updated!";
    }
}


// DELETE COMPLETED TASK
if (isset($_POST["delete_task"])) {

    $task_id = (int) $_POST["task_id"];

    $sql = "DELETE FROM tasks
            WHERE id = ?
            AND user_id = ?
            AND status = 'Completed'";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        $task_id,
        $user_id
    ]);

    if ($stmt->rowCount() > 0) {

        $message = "Completed task deleted!";

    } else {

        $message = "Only completed tasks can be deleted!";
    }
}


// READ TASKS
$sql = "SELECT * FROM tasks
        WHERE user_id = ?
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([$user_id]);

$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>WebConnect - Task Board</title>

    <link rel="stylesheet" href="style.css">

    <style>

.task-board {
    display: flex;
    flex-direction: row;
    gap: 15px;
    width: 100%;
}

.task-column {
    flex: 1;
    min-width: 0;
}

.pending {
    background-color: #fff3cd;
    color: #856404;
}

.in-progress {
    background-color: #cce5ff;
    color: #004085;
}

.completed {
    background-color: #d4edda;
    color: #155724;
}

/* Status colors */
.status-pending {
    background-color: #fff3cd;
    color: #856404;
}

.status-progress {
    background-color: #cce5ff;
    color: #004085;
}

.status-completed {
    background-color: #d4edda;
    color: #155724;
}

/* Deadline colors */
.deadline-normal {
    color: black;
    font-weight: bold;
}

.deadline-warning {
    color: red;
    font-weight: bold;
}

    </style>

</head>

<body>

<div class="container" style="width: 950px; max-width: 95%;">

    <h1>TaskMaster</h1>

    <h2>My Task Board</h2>

    <p>
        Welcome,
        <?= htmlspecialchars($_SESSION["fullname"]) ?>!
    </p>

    <?php if ($message !== ""): ?>

        <p class="message">
            <?= htmlspecialchars($message) ?>
        </p>

    <?php endif; ?>


    <!-- CREATE TASK -->

    <h2>Create New Task</h2>

    <form method="POST">

        <input
            type="text"
            name="title"
            placeholder="Enter task title"
            maxlength="150"
            required
        >

        <textarea
            name="description"
            placeholder="Enter task description"
            rows="3"
            style="width: 100%; padding: 12px; margin: 10px 0;"
        ></textarea>

        <label for="deadline">Deadline:</label>

        <input
            type="date"
            name="deadline"
            id="deadline"
            required
        >

        <button type="submit" name="add_task">
            Add Task
        </button>

    </form>


    <!-- VIEW TASK BOARD -->

    <h2>My Tasks</h2>

    <div class="task-board">

        <?php foreach ($allowedStatuses as $columnStatus): ?>

            <div class="task-column">

                <h3 class="<?= strtolower(str_replace(' ', '-', $columnStatus)) ?>">
                    <?= htmlspecialchars($columnStatus) ?>
                </h3>

                <?php

                $columnTasks = array_filter(
                    $tasks,
                    function ($task) use ($columnStatus) {
                        return $task["status"] === $columnStatus;
                    }
                );

                ?>

                <?php if (empty($columnTasks)): ?>

                    <p>No tasks here.</p>

                <?php endif; ?>


                <?php foreach ($columnTasks as $task): ?>

                    <div class="task-card">

                        <h3>
                            <?= htmlspecialchars($task["title"]) ?>
                        </h3>

                        <p>
                            <?= nl2br(htmlspecialchars(
                                $task["description"] ?? ""
                            )) ?>
                        </p>


                        <!-- DEADLINE DISPLAY -->

                        <?php

                        $today = date("Y-m-d");

                        $deadlineClass = (
                            !empty($task["deadline"]) &&
                            $task["deadline"] <= $today
                        ) ? "deadline-warning" : "deadline-normal";

                        ?>

                        <p class="<?= $deadlineClass ?>">
                            Deadline:
                            <?= !empty($task["deadline"])
                                ? htmlspecialchars($task["deadline"])
                                : "No deadline" ?>
                        </p>


                        <!-- CREATED DATE -->

                        <small>
                            Created:
                            <?= htmlspecialchars($task["created_at"]) ?>
                        </small>


                        <!-- UPDATE STATUS -->

                        <form method="POST">

                            <input
                                type="hidden"
                                name="task_id"
                                value="<?= (int) $task["id"] ?>"
                            >

                            <select name="status"
                                class="<?=
                                    $task["status"] === "Pending" ? "status-pending" :
                                    ($task["status"] === "In Progress" ? "status-progress" :
                                    "status-completed")
                                ?>">

                                <?php foreach ($allowedStatuses as $status): ?>

                                    <option
                                        value="<?= htmlspecialchars($status) ?>"
                                        <?= $task["status"] === $status ? "selected" : "" ?>
                                    >
                                        <?= htmlspecialchars($status) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                            <button
                                type="submit"
                                name="update_status"
                            >
                                Update Status
                            </button>

                        </form>


                        <!-- DELETE COMPLETED TASKS ONLY -->

                        <?php if ($task["status"] === "Completed"): ?>

                            <form
                                method="POST"
                                onsubmit="return confirm('Delete this completed task?');"
                            >

                                <input
                                    type="hidden"
                                    name="task_id"
                                    value="<?= (int) $task["id"] ?>"
                                >

                                <button
                                    type="submit"
                                    name="delete_task"
                                >
                                    Delete Completed Task
                                </button>

                            </form>

                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endforeach; ?>

    </div>


    <a href="home.php">
        <button type="button">Back to Home</button>
    </a>

</div>

</body>
</html>
