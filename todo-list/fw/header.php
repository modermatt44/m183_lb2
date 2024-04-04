<?php
$id = 0;
$roleid = 0;
$root = realpath($_SERVER["DOCUMENT_ROOT"]);
require_once "$root/fw/db.php";
if (isset($_SESSION['userid'])) {
    $id = $_SESSION['userid'];
    if (isset($_SESSION['isOauth'])) {
        $id = $_SESSION['userid'];
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT role_id FROM oauth_user WHERE id = ?");
        if ($stmt === false) {
            die('prepare() failed: ' . htmlspecialchars($conn->error));
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($roleid);
        $stmt->fetch();
        if ($roleid == 2) { // Assuming 2 is the roleid for admin
            // User is an admin
        } else {
            // User is not an admin
        }
    } else {
        $stmt = executeStatement("select users.id userid, roles.id roleid, roles.title rolename from users inner join permissions on users.id = permissions.userid inner join roles on permissions.roleID = roles.id where userid = $id");
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($db_userid, $db_roleid, $db_rolename);
            $stmt->fetch();
            $roleid = $db_roleid;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TBZ 'Secure' App</title>
    <link rel="stylesheet" href="/fw/style.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.1/jquery.validate.min.js"></script>
</head>
<body>
<header>
    <div>This is the insecure m183 test app</div>
    <?php  if (isset($_SESSION['userid'])) { ?>
        <nav>
            <ul>
                <li><a href="/">Tasks</a></li>
                <?php if ($roleid == 1) { ?>
                    <li><a href="/admin/users.php">User List</a></li>
                <?php } ?>
                <li><a href="/logout.php">Logout</a></li>
            </ul>
        </nav>
    <?php  } ?>
</header>
<main>