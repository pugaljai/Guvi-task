<?php
/**
 * logout.php
 * Destroys the user's session by deleting the token from Redis.
 *
 * RULES FOLLOWED:
 *  - Session stored in Redis — so logout = delete from Redis
 *  - NO PHP $_SESSION used anywhere
 *  - After this, the localStorage token in the browser becomes useless
 *  - profile.js also clears localStorage after calling this
 *
 * FLOW:
 *  1. Receive session_token from AJAX POST
 *  2. Delete "session:<token>" key from Redis
 *  3. Return success JSON
 *  4. profile.js clears localStorage and redirects to login.html
 */

// ─── 1. Response headers ──────────────────────────────────────────────────────
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// ─── 2. Include Redis connection ──────────────────────────────────────────────
require_once "redis.php";   // $redis

// ─── 3. Read the token from POST ─────────────────────────────────────────────
$token = isset($_POST["session_token"]) ? trim($_POST["session_token"]) : "";

if (empty($token)) {
    // No token sent — just return success anyway
    // (the browser will clear localStorage regardless)
    echo json_encode(["status" => "success", "message" => "Logged out."]);
    exit;
}

// ─── 4. Delete the token from Redis ──────────────────────────────────────────
// Once deleted, any future request with this token will fail Redis verification
$redisKey = "session:" . $token;
$redis->del($redisKey);
// del() removes the key — even if it doesn't exist, it won't throw an error

// ─── 5. Return success ────────────────────────────────────────────────────────
echo json_encode([
    "status"  => "success",
    "message" => "Logged out successfully."
]);
// ─── END ──────────────────────────────────────────────────────────────────────
?>
