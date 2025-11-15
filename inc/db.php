<?php
// inc/db.php
// edit these if your MySQL credentials are different
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = ''; // default XAMPP is empty
$DB_NAME = 'recipebook';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    die("Database connection failed: " . $mysqli->connect_error);
}
$mysqli->set_charset("utf8mb4");
?>
