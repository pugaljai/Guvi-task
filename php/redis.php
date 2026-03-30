<?php
$redisUrl = parse_url(getenv("REDIS_URL"));

$redis = new Redis();
try {
    $redis->connect($redisUrl["host"], $redisUrl["port"]);
    if (isset($redisUrl["pass"])) {
        $redis->auth($redisUrl["pass"]);
    }
} catch (Exception $e) {
    header("Content-Type: application/json");
    echo json_encode(["status" => "error", "message" => "Redis connection failed."]);
    exit;
}

function verifySession($redis) {
    $token = isset($_POST["session_token"]) ? trim($_POST["session_token"]) : "";
    if (empty($token)) return false;

    $redisKey = "session:" . $token;
    $username = $redis->get($redisKey);
    if (!$username) return false;

    $redis->expire($redisKey, 3600);
    return $username;
}
?>