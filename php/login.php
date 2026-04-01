<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: https://main.d1yook3vyqpxqk.amplifyapp.com");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { exit; }

require_once "db.php";
require_once "redis.php";

// Only accept POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

// Read input
$email    = isset($_POST["email"])    ? trim($_POST["email"]) : "";
$password = isset($_POST["password"]) ? $_POST["password"]   : "";

// Validate
if (empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Email and password are required."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Invalid email address."]);
    exit;
}

// Find user in MySQL
try {
    $stmt = $pdo->prepare("SELECT id, username, email, password FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(["status" => "error", "message" => "No account found with this email."]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error."]);
    exit;
}

// Verify password
if (!password_verify($password, $user["password"])) {
    echo json_encode(["status" => "error", "message" => "Incorrect password."]);
    exit;
}

// Generate session token
$token = bin2hex(random_bytes(32));

// Store token in Upstash Redis (expires in 1 hour)
try {
    $redisKey = "session:" . $token;
    $redis->setex($redisKey, 3600, $user["username"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Session creation failed."]);
    exit;
}

// Return token to browser — JS saves it in localStorage
echo json_encode([
    "status"   => "success",
    "message"  => "Login successful.",
    "token"    => $token,
    "username" => $user["username"]
]);
?>