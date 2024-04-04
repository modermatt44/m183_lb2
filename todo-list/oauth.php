<?php
session_start();
require_once 'config.php';

$config = [
    'CLIENT_ID' => CLIENT_ID,
    'CLIENT_SECRET' => CLIENT_SECRET,
    'redirect_uri' => 'http://localhost:80/oauth.php'
];

$oauth = new GitHubOAuth($config);

class GitHubOAuth {
    public $clientId;
    public $clientSecret;
    public $authorizationUrl = 'https://github.com/login/oauth/authorize';
    public $apiURLBase = 'https://api.github.com/';
    public $tokenUrl = 'https://github.com/login/oauth/access_token';
    public $redirectUri;

    public function __construct(array $config = []) {
        $this->clientId = $config['CLIENT_ID'] ?? '';
        $this->clientSecret = $config['CLIENT_SECRET'] ?? '';
        $this->redirectUri = $config['redirect_uri'] ?? '';
    }


    public function getAuthorizationUrl(): string
    {
        $authorizationUrl = $this->authorizationUrl;
        $authorizationUrl .= '?client_id=' . $this->clientId;
        $authorizationUrl .= '&redirect_uri=' . urlencode($this->redirectUri);
        $authorizationUrl .= '&scope=user,user:email'; // Request user and email scope

        return $authorizationUrl;
    }

    public function getAccessToken($code) {
        $tokenUrl = $this->tokenUrl;
        $data = array(
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $this->redirectUri
        );

        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            )
        );

        $context  = stream_context_create($options);
        $response = file_get_contents($tokenUrl, false, $context);
        parse_str($response, $output);

        if (!isset($output['access_token'])) {
            die('Failed to get access token');
        }

        return $output['access_token'];
    }

    public function getUser($accessToken)
    {
        // Get user profile information
        $apiUrl = $this->apiURLBase . 'user';
        $apiOptions = array(
            'http' => array(
                'header'  => "Authorization: token " . $accessToken . "\r\nUser-Agent: MyApp\r\n",
                'method'  => 'GET'
            )
        );

        $apiContext = stream_context_create($apiOptions);
        $apiResponse = file_get_contents($apiUrl, false, $apiContext);
        $user = json_decode($apiResponse);

        // Get user email addresses
        $apiUrl = $this->apiURLBase . 'user/emails';
        $apiResponse = file_get_contents($apiUrl, false, $apiContext);
        $emails = json_decode($apiResponse);

        // Find primary email address
        foreach ($emails as $email) {
            if ($email->primary) {
                $user->email = $email->email;
                break;
            }
        }

        return $user;
    }
}

if (!isset($_GET['code'])) {
    // Step 1: Get authorization code
    $authorizeUrl = $oauth->getAuthorizationUrl();
    header('Location: ' . $authorizeUrl);
    exit();
} else {
    // Step 2: Get access token
    $code = $_GET['code'];
    $accessToken = $oauth->getAccessToken($code);

    // Step 3: Use the access token to call GitHub API
    $user = $oauth->getUser($accessToken);

    // Now $user contains the user's GitHub information
    // You can use this information to create or update a user in your application
}

// Step 1: Establish a connection to your database
$db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($db->connect_error) {
    die('Failed to connect to database: ' . $db->connect_error);
}

// Define the username
$username = $user->login;

// Step 2: Prepare an SQL SELECT statement to check if the username already exists
$stmt = $db->prepare('SELECT id, username FROM oauth_user WHERE username = ?');
$stmt->bind_param('s', $username);

// Execute the statement
$stmt->execute();
$stmt->store_result();

// Check if username exists
if ($stmt->num_rows > 0) {
    // Username exists, start a session and store user information
    $stmt->bind_result($db_id, $db_username);
    $stmt->fetch();
    $_SESSION["username"] = $db_username;
    $_SESSION["userid"] = $db_id;
    $_SESSION["isOauth"] = true;
    // Redirect to index.php
    header("Location: index.php");
    exit();
} else {
    // Username does not exist, prepare an SQL INSERT statement
    $stmt = $db->prepare('INSERT INTO oauth_user (oauth_provider, oauth_uid, name, username, email, user_id) VALUES (?, ?, ?, ?, ?, 2)');

    if (!$stmt) {
        die('Failed to prepare statement: ' . $db->error);
    }

    // Step 3: Execute the SQL INSERT statement
    $oauth_provider = 'github';
    $oauth_uid = $user->id;
    $name = $user->name;
    $email = $user->email;

    if (!$stmt->bind_param('sssss', $oauth_provider, $oauth_uid, $name, $username, $email)) {
        die('Failed to bind parameters: ' . $stmt->error);
    }

    if (!$stmt->execute()) {
        die('Failed to execute statement: ' . $stmt->error);
    }

    // Start a session and store user information
    $_SESSION["username"] = $username;
    $_SESSION["userid"] = $db->insert_id;
    $_SESSION["isOauth"] = true;
    // Redirect to index.php
    header("Location: index.php");
    exit();
}
$stmt->close();
$db->close();