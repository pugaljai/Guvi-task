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

// Read input
$fullName = isset($_POST["full_name"]) ? trim($_POST["full_name"]) : "";
$age      = isset($_POST["age"])       ? trim($_POST["age"])       : "";
$dob      = isset($_POST["dob"])       ? trim($_POST["dob"])       : "";
$contact  = isset($_POST["contact"])   ? trim($_POST["contact"])   : "";
$gender   = isset($_POST["gender"])    ? trim($_POST["gender"])    : "";
$address  = isset($_POST["address"])   ? trim($_POST["address"])   : "";
$bio      = isset($_POST["bio"])       ? trim($_POST["bio"])       : "";

// Validate
if ($age !== "" && (!is_numeric($age) || (int)$age < 1 || (int)$age > 120)) {
    echo json_encode(["status" => "error", "message" => "Please enter a valid age."]);
    exit;
}

if ($contact !== "" && !preg_match('/^\d{10}$/', $contact)) {
    echo json_encode(["status" => "error", "message" => "Contact must be 10 digits."]);
    exit;
}

// Build document
$profileDocument = [
    "username"   => $username,
    "full_name"  => $fullName,
    "age"        => $age !== "" ? (int)$age : "",
    "dob"        => $dob,
    "contact"    => $contact,
    "gender"     => $gender,
    "address"    => $address,
    "bio"        => $bio,
    "updated_at" => new MongoDB\BSON\UTCDateTime()
];

// Upsert into MongoDB
try {
    $result = $profilesCollection->updateOne(
        ["username" => $username],
        ['$set'     => $profileDocument],
        ["upsert"   => true]
    );

    if ($result->isAcknowledged()) {
        echo json_encode(["status" => "success", "message" => "Profile saved successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to save profile."]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Failed to save profile."]);
}
?>