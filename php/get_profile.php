<?php
/**
 * get_profile.php
 * Fetches a user's profile from MongoDB.
 *
 * RULES FOLLOWED:
 *  - Session verified via Redis (NOT PHP $_SESSION)
 *  - Profile data fetched from MongoDB
 *  - Returns JSON (consumed by profile.js jQuery AJAX)
 *  - No HTML, CSS, or JS in this file
 *
 * FLOW:
 *  1. Receive session_token from AJAX POST
 *  2. Verify token in Redis → get username
 *  3. Query MongoDB profiles collection for that username
 *  4. Return profile data as JSON
 */

// ─── 1. Response headers ──────────────────────────────────────────────────────
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: https://main.d1yook3vyqpxqk.amplifyapp.com");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { exit; }

// ─── 2. Include connection files ──────────────────────────────────────────────
require_once "redis.php";   // $redis + verifySession()
require_once "mongo.php";   // $profilesCollection

// ─── 3. Only accept POST ──────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

// ─── 4. Verify session token via Redis ───────────────────────────────────────
// verifySession() is defined in redis.php
// It reads "session_token" from $_POST and checks it against Redis
$username = verifySession($redis);

if (!$username) {
    // Token missing, invalid, or expired in Redis
    echo json_encode([
        "status"  => "unauthorized",
        "message" => "Session expired. Please login again."
    ]);
    exit;
}

// ─── 5. Fetch profile from MongoDB ───────────────────────────────────────────
try {
    // Find one document where username matches
    // MongoDB findOne returns null if not found
    $profile = $profilesCollection->findOne(
        ["username" => $username]   // filter
    );

    if ($profile === null) {
        // User has no profile saved yet — this is normal for new users
        echo json_encode([
            "status"  => "no_profile",
            "message" => "No profile found. Please fill in your details."
        ]);
        exit;
    }

    // ─── 6. Convert MongoDB document to plain PHP array ──────────────────
    // MongoDB returns a BSON document — we convert to array for JSON encoding
    $profileArray = [
        "full_name" => (string) ($profile["full_name"] ?? ""),
        "age"       => (string) ($profile["age"]       ?? ""),
        "dob"       => (string) ($profile["dob"]       ?? ""),
        "contact"   => (string) ($profile["contact"]   ?? ""),
        "gender"    => (string) ($profile["gender"]    ?? ""),
        "address"   => (string) ($profile["address"]   ?? ""),
        "bio"       => (string) ($profile["bio"]       ?? ""),
    ];

    echo json_encode([
        "status"  => "success",
        "message" => "Profile loaded.",
        "data"    => $profileArray
    ]);

} catch (Exception $e) {
    echo json_encode([
        "status"  => "error",
        "message" => "Failed to load profile: " . $e->getMessage()
    ]);
}
// ─── END ──────────────────────────────────────────────────────────────────────
?>
