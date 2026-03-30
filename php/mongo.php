<?php
/**
 * mongo.php
 * MongoDB connection file.
 *
 * RULES FOLLOWED:
 *  - MongoDB stores profile details (age, dob, contact, etc.)
 *  - No HTML, CSS, or JS in this file
 *
 * REQUIRES:
 *  - MongoDB PHP Driver (php_mongodb.dll / php_mongodb.so)
 *  - MongoDB PHP Library installed via Composer:
 *      composer require mongodb/mongodb
 *    This creates vendor/autoload.php in your project root.
 *
 * DATABASE  : intern_profiles
 * COLLECTION: profiles
 * DOCUMENT STRUCTURE:
 *  {
 *    username  : "john_doe",   ← links to MySQL user
 *    full_name : "John Doe",
 *    age       : 21,
 *    dob       : "2003-05-15",
 *    contact   : "9876543210",
 *    gender    : "Male",
 *    address   : "Chennai, TN",
 *    bio       : "CS student...",
 *    updated_at: ISODate(...)
 *  }
 */

// Load Composer autoloader — required for the MongoDB PHP library
// Make sure you ran: composer require mongodb/mongodb
require_once __DIR__ . "/../vendor/autoload.php";

try {
    // Create MongoDB client — connects to local MongoDB on default port 27017
    $mongoClient = new MongoDB\Client("mongodb://127.0.0.1:27017");

    // Select our database
    $mongoDB = $mongoClient->intern_profiles;

    // Select the profiles collection
    // (MongoDB auto-creates this collection on first insert)
    $profilesCollection = $mongoDB->profiles;

} catch (Exception $e) {
    header("Content-Type: application/json");
    echo json_encode([
        "status"  => "error",
        "message" => "MongoDB connection failed. Please ensure MongoDB is running."
    ]);
    exit;
}
?>
