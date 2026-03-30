<?php
/**
 * update_profile.php
 * Creates or updates a user's profile in MongoDB.
 *
 * RULES FOLLOWED:
 *  - Session verified via Redis (NOT PHP $_SESSION)
 *  - Profile stored/updated in MongoDB
 *  - Uses upsert:true — inserts if not exists, updates if exists
 *  - Returns JSON (consumed by profile.js jQuery AJAX)
 *  - No HTML, CSS, or JS in this file
 *
 * FLOW:
 *  1. Receive session_token + profile fields from AJAX POST
 *  2. Verify token in Redis → get username
 *  3. Validate incoming data
 *  4. Upsert (insert or update) the document in MongoDB
 *  5. Return success/error JSON
 */

// ─── 1. Response headers ──────────────────────────────────────────────────────
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// ─── 2. Include connection files ──────────────────────────────────────────────
require_once "redis.php";   // $redis + verifySession()
require_once "mongo.php";   // $profilesCollection

// ─── 3. Only accept POST ──────────────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
    exit;
}

// ─── 4. Verify session token via Redis ───────────────────────────────────────
$username = verifySession($redis);

if (!$username) {
    echo json_encode([
        "status"  => "unauthorized",
        "message" => "Session expired. Please login again."
    ]);
    exit;
}

// ─── 5. Read and sanitize POST data ──────────────────────────────────────────
$fullName = isset($_POST["full_name"]) ? trim($_POST["full_name"]) : "";
$age      = isset($_POST["age"])       ? trim($_POST["age"])       : "";
$dob      = isset($_POST["dob"])       ? trim($_POST["dob"])       : "";
$contact  = isset($_POST["contact"])   ? trim($_POST["contact"])   : "";
$gender   = isset($_POST["gender"])    ? trim($_POST["gender"])    : "";
$address  = isset($_POST["address"])   ? trim($_POST["address"])   : "";
$bio      = isset($_POST["bio"])       ? trim($_POST["bio"])       : "";

// ─── 6. Server-side validation ────────────────────────────────────────────────

// Age must be a number if provided
if ($age !== "" && (!is_numeric($age) || (int)$age < 1 || (int)$age > 120)) {
    echo json_encode([
        "status"  => "error",
        "message" => "Please enter a valid age between 1 and 120."
    ]);
    exit;
}

// Contact must be 10 digits if provided
if ($contact !== "" && !preg_match('/^\d{10}$/', $contact)) {
    echo json_encode([
        "status"  => "error",
        "message" => "Contact number must be exactly 10 digits."
    ]);
    exit;
}

// DOB format check (YYYY-MM-DD) if provided
if ($dob !== "" && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
    echo json_encode([
        "status"  => "error",
        "message" => "Invalid date of birth format."
    ]);
    exit;
}

// ─── 7. Build the profile document ───────────────────────────────────────────
$profileDocument = [
    "username"   => $username,                // From Redis session
    "full_name"  => $fullName,
    "age"        => $age !== "" ? (int)$age : "",
    "dob"        => $dob,
    "contact"    => $contact,
    "gender"     => $gender,
    "address"    => $address,
    "bio"        => $bio,
    "updated_at" => new MongoDB\BSON\UTCDateTime()  // Current timestamp
];

// ─── 8. Upsert into MongoDB ───────────────────────────────────────────────────
// upsert: true → INSERT if no document with this username exists
//               UPDATE if document already exists
// This means the same PHP code handles both "first save" and "edit" scenarios
try {
    $result = $profilesCollection->updateOne(
        ["username" => $username],              // FILTER: find by username
        ['$set'     => $profileDocument],       // UPDATE: set all fields
        ["upsert"   => true]                    // OPTIONS: insert if not found
    );

    // Check if the operation was acknowledged by MongoDB
    if ($result->isAcknowledged()) {
        echo json_encode([
            "status"  => "success",
            "message" => "Profile saved successfully."
        ]);
    } else {
        echo json_encode([
            "status"  => "error",
            "message" => "MongoDB did not acknowledge the write."
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "status"  => "error",
        "message" => "Failed to save profile: " . $e->getMessage()
    ]);
}
// ─── END ──────────────────────────────────────────────────────────────────────
?>
