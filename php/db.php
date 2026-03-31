<?php
$host   = "intern-db.cj4w2iuwwwgv.ap-southeast-2.rds.amazonaws.com";
$dbname = "intern_project";
$dbuser = "admin";
$dbpass = "InternPass123";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $dbuser,
        $dbpass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header("Content-Type: application/json");
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit;
}
?>