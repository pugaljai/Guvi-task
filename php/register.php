<?php


header("Content-Type: application/json");
header("Access-Control-Allow-Origin: https://main.d1yook3vyqpxqk.amplifyapp.com");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { exit; }

require_once "db.php";

// Only accept POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

// Read input
$username = isset($_POST["username"]) ? trim($_POST["username"]) : "";
$email    = isset($_POST["email"])    ? trim($_POST["email"])    : "";
$password = isset($_POST["password"]) ? $_POST["password"]      : "";

// Validate
if (empty($username) || empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "All fields are required."]);
    exit;
}

if (strlen($username) < 3) {
    echo json_encode(["status" => "error", "message" => "Username must be at least 3 characters."]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Invalid email address."]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(["status" => "error", "message" => "Password must be at least 6 characters."]);
    exit;
}

// Check duplicate username
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => "error", "message" => "Username already taken."]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error."]);
    exit;
}

// Check duplicate email
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => "error", "message" => "Email already registered."]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error."]);
    exit;
}

// Hash password and insert
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->execute([$username, $email, $hashedPassword]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["status" => "success", "message" => "Registration successful!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Registration failed."]);
    }
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error."]);
}
?>