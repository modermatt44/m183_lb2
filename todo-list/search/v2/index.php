<?php

    if (!isset($_GET["userid"]) || !isset($_GET["terms"])) {
        die("Not enough information to search");
    }

    $userid = $_GET["userid"];
    $terms = $_GET["terms"];

    require_once '../../fw/db.php';

    // Prepare SQL statement
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT ID, title, state FROM tasks WHERE userID = ? AND title LIKE ?");
    $likeTerms = "%" . $terms . "%";
    $stmt->bind_param("is", $userid, $likeTerms); // 'i' specifies the variable type => 'integer', 's' => 'string'

    // Execute the statement
    $stmt->execute();

    // Bind the result variables
    $stmt->bind_result($db_id, $db_title, $db_state);

    // Fetch the results
    while ($stmt->fetch()) {
        echo $db_title . ' (' . $db_state . ')<br />';
    }
?>