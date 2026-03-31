<?php
$redis = new Redis();
try {
    $redis->connect(
        "intern-redis-8hg9bz.serverless.apse2.cache.amazonaws.com",
        6379
    );
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