<?php
// Detect whether this is local development or the hosted environment.
$serverHost = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? php_uname('n');
$isLocal = preg_match('/localhost|127\.0\.0\.1|::1/i', $serverHost);

if ($isLocal) {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db   = 'hrms';
} else {
    $host = 'sql305.byethost13.com';
    $user = 'b13_41990515';
    $pass = '@BTechHRMS0247';
    $db   = 'b13_41990515_hrms';
}

$connection = mysqli_connect($host, $user, $pass, $db);

if (!$connection) {
    die('Database connection failed: ' . mysqli_connect_error());
}
?>