<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "hrms";

$connection = mysqli_connect($host, $user, $pass, $db);

if (!$connection) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>