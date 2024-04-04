<?php
session_start();
require_once 'config.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username']) && isset($_POST['password'])) {
    // Get username and password from the form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Connect to the database
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    // Prepare SQL statement to retrieve user from database
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username=? UNION SELECT id, username, NULL FROM oauth_user WHERE username=?");
    $stmt->bind_param("ss", $username, $username); // 's' specifies the variable type => 'string'
    // Execute the statement
    $stmt->execute();
    // Store the result
    $stmt->store_result();
    // Check if username exists
    if ($stmt->num_rows > 0) {
        // Bind the result variables
        $stmt->bind_result($db_id, $db_username, $db_password);
        // Fetch the result
        $stmt->fetch();
        // Verify the password
        if ($db_password === NULL || $password == $db_password) {
            // Password is correct or user is an OAuth user, store username in session
            $_SESSION["username"] = $username;
            $_SESSION["userid"] = $db_id;
            // Redirect to index.php
            header("Location: index.php");
            exit();
        } else {
            // Password is incorrect
            echo "Incorrect credentials";
        }
    } else {
        // Username does not exist
        echo "Incorrect credentials";
    }

    // Close statement
    $stmt->close();
}
require_once 'fw/header.php';
?>

    <h2>Login</h2>


    <form id="form" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" class="form-control size-medium" name="username" id="username">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control size-medium" name="password" id="password">
        </div>
        <div class="form-group">
            <label for="submit" ></label>
            <input id="submit" type="submit" class="btn size-auto" value="Login" />
        </div>
    </form>
    <button class="btn size-auto" onclick="window.location.href='oauth.php'">Login with GitHub</button>

<?php
require_once 'fw/footer.php';
?>