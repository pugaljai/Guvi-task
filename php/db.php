<?php
// Heroku JawsDB provides the MySQL URL as an environment variable
// We parse it to get host, dbname, user, password
$url = parse_url(getenv("JAWSDB_URL"));

$host   = $url["host"];
$dbname = ltrim($url["path"], "/");
$dbuser = $url["user"];
$dbpass = $url["pass"];

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