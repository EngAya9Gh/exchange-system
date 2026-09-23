<?php
$data = file_get_contents('php://input');
$log = date('Y-m-d H:i:s') . " - " . $_SERVER['REMOTE_ADDR'] . " - " . $_SERVER['REQUEST_METHOD'] . "\n" . $data . "\n\n";
file_put_contents(__DIR__.'/../storage/logs/telegram_raw.log', $log, FILE_APPEND);
echo json_encode(["status" => "ok"]);
