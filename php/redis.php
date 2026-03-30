<?php
/**
 * redis.php
 * Redis connection file.
 *
 * RULES FOLLOWED:
 *  - Redis used for backend session storage (replaces PHP $_SESSION)
 *  - No HTML, CSS, or JS in this file
 *
 * HOW SESSIONS WORK WITH REDIS:
 *  - On login   → store  key "session:<token>" = username  (expires in 1hr)
 *  - On request → get    key "session:<token>" to verify who the user is
 *  - On logout  → delete key "session:<token>"
 */

$redis = new Redis();

try {
    // Connect to Redis server
    // Default Redis host: 127.0.0.1
    // Default Redis port: 6379
    $redis->connect("127.0.0.1", 6379);

    // Optional: if you set a Redis password, uncomment this:
    // $redis->auth("your_redis_password");

    // Optional: select database (0 is default)
    $redis->select(0);

} catch (Exception $e) {
    header("Content-Type: application/json");
    echo json_encode([
        "status"  => "error",
        "message" => "Redis connection failed. Please ensure Redis is running."
    ]);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// HELPER FUNCTION: Verify a session token from localStorage
//
// Usage in any PHP file:
//   $username = verifySession($redis);
//   if (!$username) { /* return unauthorized error */ }
// ─────────────────────────────────────────────────────────────────────────────
function verifySession($redis) {
    // The browser sends the token in the POST body as "session_token"
    $token = isset($_POST["session_token"]) ? trim($_POST["session_token"]) : "";

    if (empty($token)) {
        return false; // No token provided
    }

    // Look up this token in Redis
    $redisKey = "session:" . $token;
    $username = $redis->get($redisKey);

    if (!$username) {
        return false; // Token not found or expired
    }

    // Extend session by another hour on each active use
    // (keeps active users logged in)
    $redis->expire($redisKey, 3600);

    return $username; // Return the username associated with this token
}
?>
