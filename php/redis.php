<?php


require_once __DIR__ . "/../vendor/autoload.php";

try {
    $redis = new Predis\Client([
        "scheme"   => "tls",  // SSL/TLS required for Upstash
        "host"     => "firm-aardvark-89648.upstash.io",
        "port"     => 6379,
        "password" => "gQAAAAAAAV4wAAIncDE5NjViOGNjMWUzYzI0NjE0YWFlNDY4ZjkyMDFiZTE4N3AxODk2NDg",
    ]);

    // Test connection
    $redis->ping();

} catch (Exception $e) {
    header("Content-Type: application/json");
    echo json_encode([
        "status"  => "error",
        "message" => "Redis connection failed: " . $e->getMessage()
    ]);
    exit;
}


function verifySession($redis) {
    $token = isset($_POST["session_token"]) ? trim($_POST["session_token"]) : "";

    if (empty($token)) {
        return false;
    }

    $redisKey = "session:" . $token;
    $username = $redis->get($redisKey);

    if (!$username) {
        return false;
    }

    // Extend session by 1 hour on each active use
    $redis->expire($redisKey, 3600);

    return $username;
}
?>