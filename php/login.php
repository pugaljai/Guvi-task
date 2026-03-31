<?php
/**
 * login.php
 * Backend API for user login.
 *
 * RULES FOLLOWED:
 *  - Only Prepared Statements used for MySQL (NO raw SQL)
 *  - Session stored in Redis (NOT PHP $_SESSION)
 *  - Token sent back as JSON — stored in browser localStorage by JS
 *  - No HTML, CSS, or JS in this file
 *
 * FLOW:
 *  1. Receive email + password from AJAX POST
 *  2. Find user in MySQL using prepared statement
 *  3. Verify password with password_verify()
 *  4. Generate a unique session token
 *  5. Store token → username in Redis (with 1 hour expiry)
 *  6. Return token + username as JSON to the browser
 */

// ─── 1. Set response headers ──────────────────────────────────────────────────
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: https://main.d1yook3vyqpxqk.amplifyapp.com");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { exit; }

// ─── 2. Include connection files ──────────────────────────────────────────────
require_once "db.php";      // $pdo  — MySQL PDO connection
require_once "redis.php";   // $redis — Redis connection

// ─── 3. Only accept POST requests ─────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "status"  => "error",
        "message" => "Invalid request method."
    ]);
    exit;
}

// ─── 4. Read and sanitize input ───────────────────────────────────────────────
$email    = isset($_POST["email"])    ? trim($_POST["email"])  : "";
$password = isset($_POST["password"]) ? $_POST["password"]    : "";

// ─── 5. Server-side validation ────────────────────────────────────────────────
if (empty($email) || empty($password)) {
    echo json_encode([
        "status"  => "error",
        "message" => "Email and password are required."
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "status"  => "error",
        "message" => "Invalid email address."
    ]);
    exit;
}

// ─── 6. Fetch user from MySQL using Prepared Statement ────────────────────────
// We look up the user by email to get their stored hashed password
try {
    $stmt = $pdo->prepare(
        "SELECT id, username, email, password FROM users WHERE email = ? LIMIT 1"
    );
    // ^ PREPARED STATEMENT — never concatenate $email directly into SQL
    $stmt->execute([$email]);

    $user = $stmt->fetch(); // Returns associative array or false

    // If no user found with this email
    if (!$user) {
        echo json_encode([
            "status"  => "error",
            "message" => "No account found with this email address."
        ]);
        exit;
    }

} catch (PDOException $e) {
    echo json_encode([
        "status"  => "error",
        "message" => "Database error. Please try again."
    ]);
    exit;
}

// ─── 7. Verify the password ───────────────────────────────────────────────────
// password_verify() compares the plain text input with the bcrypt hash in DB
if (!password_verify($password, $user["password"])) {
    echo json_encode([
        "status"  => "error",
        "message" => "Incorrect password. Please try again."
    ]);
    exit;
}

// ─── 8. Generate a secure session token ──────────────────────────────────────
// bin2hex(random_bytes(32)) gives a 64-character random hex string
$token = bin2hex(random_bytes(32));

// ─── 9. Store session in Redis ────────────────────────────────────────────────
// Key   = "session:<token>"
// Value = username (so we can look up who owns this token later)
// Expiry = 3600 seconds = 1 hour
//
// This is our backend session store — NO PHP $_SESSION used anywhere.
// The browser will store just the token in localStorage.
$redisKey = "session:" . $token;
$redis->setex($redisKey, 3600, $user["username"]);
// setex(key, seconds, value) — stores with expiry

// ─── 10. Return success response with token ───────────────────────────────────
// The JS (login.js) will receive this and save token to localStorage
echo json_encode([
    "status"   => "success",
    "message"  => "Login successful.",
    "token"    => $token,
    "username" => $user["username"]
]);

// ─── END ──────────────────────────────────────────────────────────────────────
?>
