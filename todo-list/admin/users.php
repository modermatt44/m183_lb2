<?php
    session_start();
    if (!isset($_SESSION['username'])) {
        header("Location: ../login.php");
        exit();
    }

    require_once '../config.php';
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    // Prepare SQL statement to retrieve user from database
    $stmtU = $conn->prepare("SELECT users.ID, users.username, users.password, roles.title FROM users inner join permissions on users.ID = permissions.userID inner join roles on permissions.roleID = roles.ID order by username");
    // Execute the statement
    $stmtU->execute();
    // Store the result
    $stmtU->store_result();
    // Bind the result variables
    $stmtU->bind_result($db_id, $db_username, $db_password, $db_title);

// Prepare SQL statement to retrieve OAuth users from database
$stmtO = $conn->prepare("SELECT oauth_user.id, oauth_user.username, roles.title FROM oauth_user INNER JOIN roles ON oauth_user.role_id = roles.id ORDER BY username");

// Execute the statement
$stmtO->execute();

// Store the result
$stmtO->store_result();

// Bind the result variables
$stmtO->bind_result($db_oauth_id, $db_oauth_username, $db_oauth_role);

    require_once '../fw/header.php';
?>
<h2>User List</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Username</th>
        <th>Role</th>
    </tr>
    <?php
        // Fetch the result
        while ($stmtU->fetch()) {
            echo "<tr><td>$db_id</td><td>$db_username</td><td>$db_title</td><input type='hidden' name='password' value='$db_password' /></tr>";
        }
    ?>

    <table>
        <h2>OAuth User List</h2>
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
        </tr>
        <?php
        // Fetch the result
        while ($stmtO->fetch()) {
            echo "<tr><td>$db_oauth_id</td><td>$db_oauth_username</td><td>$db_oauth_role</td></tr>";
        }
        ?>
    </table>

<?php
    require_once '../fw/footer.php';
?>