<?php
session_start();
include 'dbconn.php';

if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin' && isset($_GET['id'])) {
    pg_query_params($conn, "DELETE FROM comments WHERE id = $1", array($_GET['id']));
    header("Location: home.php");
    exit();
}