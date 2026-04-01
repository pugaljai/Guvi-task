<?php
// Health check file for Elastic Beanstalk
http_response_code(200);
header("Content-Type: application/json");
echo json_encode(["status" => "ok"]);
exit;
?>