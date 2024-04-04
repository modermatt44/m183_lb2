<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: /");
    exit();
}
$taskid = "";
require_once 'fw/db.php'; // Ensure that $conn is defined in the global scope in this file
// see if the id exists in the database

if (isset($_POST['id']) && strlen($_POST['id']) != 0){
    $taskid = $_POST["id"];
    $conn = getConnection();
    $stmt = $conn->prepare("select ID, title, state from tasks where ID = ?");
    $stmt->bind_param("i", $taskid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        $taskid = "";
    }
}

require_once 'fw/header.php';
if (isset($_POST['title']) && isset($_POST['state'])){
    $state = $_POST['state'];
    $title = htmlspecialchars($_POST['title']); // Escape the title to prevent XSS
    $userid = $_SESSION['userid'];
    $conn = getConnection();
    if ($taskid == ""){
        $stmt = $conn->prepare("insert into tasks (title, state, userID) values (?, ?, ?)");
        $stmt->bind_param("ssi", $title, $state, $userid);
    }
    else {
        $stmt = $conn->prepare("update tasks set title = ?, state = ? where ID = ?");
        $stmt->bind_param("ssi", $title, $state, $taskid);
    }
    $stmt->execute();

    echo "<span class='info info-success'>Update successful</span>";
}
else {
    echo "<span class='info info-error'>No update was made</span>";
}

require_once 'fw/footer.php';
?>