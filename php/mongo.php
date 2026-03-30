<?php
require_once __DIR__ . "/../vendor/autoload.php";

try {
    $mongoUri = getenv("MONGODB_URI");
    $mongoClient = new MongoDB\Client($mongoUri);
    $mongoDB = $mongoClient->intern_profiles;
    $profilesCollection = $mongoDB->profiles;
} catch (Exception $e) {
    header("Content-Type: application/json");
    echo json_encode(["status" => "error", "message" => "MongoDB connection failed."]);
    exit;
}
?>