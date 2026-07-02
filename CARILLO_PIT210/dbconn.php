<?php
$host = "localhost";
$user = "postgres"; 
$password = ""; // Put your password here
$dbname = "nella_db";  

$conn = pg_connect("host=$host dbname=$dbname user=$user password=$password");

if (!$conn) {
    // This will print the exact error from PostgreSQL
    die("Connection failed: " . pg_last_error()); 
}
?>