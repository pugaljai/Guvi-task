<?php
/**
 * register.php
 * Backend API for user registration.
 *
 * RULES FOLLOWED:
 *  - Only Prepared Statements used (NO raw SQL strings)
 *  - Returns JSON response (consumed by jQuery AJAX)
 *  - Passwords are hashed using password_hash()
 *  - No HTML, CSS, or JS exists in this file
 */

// ─── 1. Tell the browser this response is JSON ────────────────────────────────
header("Content-Type: application/json");

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { exit; }
// ─── 3. Include MySQL connection ──────────────────────────────────────────────
require_once "db.php";
// $pdo is now available from db.php

// ─── 4. Only accept POST requests ─────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "status"  => "error",
        "message" => "Invalid request method."
    ]);
    exit;
}

// ─── 5. Read and sanitize input ───────────────────────────────────────────────
$username = isset($_POST["username"]) ? trim($_POST["username"]) : "";
$email    = isset($_POST["email"])    ? trim($_POST["email"])    : "";
$password = isset($_POST["password"]) ? $_POST["password"]      : "";

// ─── 6. Server-side validation (never trust only front-end validation) ────────

if (empty($username) || empty($email) || empty($password)) {
    echo json_encode([
        "status"  => "error",
        "message" => "All fields are required."
    ]);
    exit;
}

if (strlen($username) < 3) {
    echo json_encode([
        "status"  => "error",
        "message" => "Username must be at least 3 characters."
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

if (strlen($password) < 6) {
    echo json_encode([
        "status"  => "error",
        "message" => "Password must be at least 6 characters."
    ]);
    exit;
}

// ─── 7. Check if username already exists (Prepared Statement) ─────────────────
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    // ^ PREPARED STATEMENT — no raw SQL with variables
    $stmt->execute([$username]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status"  => "error",
            "message" => "This username is already taken. Please choose another."
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

// ─── 8. Check if email already exists (Prepared Statement) ───────────────────
try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status"  => "error",
            "message" => "This email is already registered. Please login instead."
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

// ─── 9. Hash the password (NEVER store plain text passwords) ──────────────────
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
// PASSWORD_BCRYPT is the recommended algorithm

// ─── 10. Insert new user into MySQL (Prepared Statement) ─────────────────────
try {
    $stmt = $pdo->prepare(
        "INSERT INTO users (username, email, password) VALUES (?, ?, ?)"
    );
    // ^ PREPARED STATEMENT with placeholders — never concatenate user input into SQL
    $stmt->execute([$username, $email, $hashedPassword]);

    // Check that at least 1 row was inserted
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "status"  => "success",
            "message" => "Registration successful! Please login."
        ]);
    } else {
        echo json_encode([
            "status"  => "error",
            "message" => "Registration failed. Please try again."
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status"  => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
// ─── END ──────────────────────────────────────────────────────────────────────
?>
