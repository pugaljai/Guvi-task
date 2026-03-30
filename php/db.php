<?php
$host = "localhost";
$dbname = "intern_project";
$username = "root";
$password = ""; // XAMPP default is empty

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["status" => "error", "message" => $e->getMessage()]));
}
?>