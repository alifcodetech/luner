<?php
// Start session for the whole site
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// Database connection settings
$db_host = 'localhost';
$db_user = 'root'; // change as needed
$db_pass = '';     // change as needed
$db_name = 'investment'; // change as needed

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_errno) {
	die('Failed to connect to MySQL: ' . $mysqli->connect_error);
}

// Ensure strict mode for mysqli
$mysqli->set_charset('utf8mb4');
?>