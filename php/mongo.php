<?php

require_once __DIR__ . "/../vendor/autoload.php";

try {
    $mongoUri    = "mongodb+srv://internuser:InternPass123@cluster0.xriff6m.mongodb.net/intern_profiles?authSource=admin";
    $mongoClient = new MongoDB\Client($mongoUri);
    $mongoDB     = $mongoClient->intern_profiles;

    // profiles collection — auto created on first insert
    $profilesCollection = $mongoDB->profiles;

} catch (Exception $e) {
    header("Content-Type: application/json");
    echo json_encode([
        "status"  => "error",
        "message" => "MongoDB connection failed."
    ]);
    exit;
}
?>