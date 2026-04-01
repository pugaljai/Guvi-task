<?php


header("Content-Type: application/json");
header("Access-Control-Allow-Origin: https://main.d1yook3vyqpxqk.amplifyapp.com");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { exit; }

require_once "redis.php";

$token = isset($_POST["session_token"]) ? trim($_POST["session_token"]) : "";

if (!empty($token)) {
    $redisKey = "session:" . $token;
    $redis->del($redisKey);
}

echo json_encode(["status" => "success", "message" => "Logged out successfully."]);
?>