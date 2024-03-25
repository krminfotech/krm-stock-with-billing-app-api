<?php
$hostname = "localhost";
$username = "root";
$password = "";
$database = "stock_app_mtf";

$connection = new mysqli($hostname, $username, $password, $database);

if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}
?>
