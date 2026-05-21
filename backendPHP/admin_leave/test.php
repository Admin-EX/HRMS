<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo json_encode([
    'test' => 'PHP is working',
    'time' => date('Y-m-d H:i:s')
]);
?>