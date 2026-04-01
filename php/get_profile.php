<?php


header("Content-Type: application/json");
header("Access-Control-Allow-Origin: https://main.d1yook3vyqpxqk.amplifyapp.com");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { exit; }

require_once "redis.php";
require_once "mongo.php";

// Only accept POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

// Verify session token via Redis
$username = verifySession($redis);
if (!$username) {
    echo json_encode(["status" => "unauthorized", "message" => "Session expired. Please login again."]);
    exit;
}

// Fetch profile from MongoDB
try {
    $profile = $profilesCollection->findOne(["username" => $username]);

    if ($profile === null) {
        echo json_encode([
            "status"  => "no_profile",
            "message" => "No profile found."
        ]);
        exit;
    }

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
        "status" => "success",
        "data"   => $profileArray
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Failed to load profile."]);
}
?>