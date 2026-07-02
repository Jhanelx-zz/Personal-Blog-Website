<?php
session_start();
include 'dbconn.php';

if (isset($_SESSION['user_id']) && isset($_POST['media_id'])) {
    $email = $_SESSION['user_id'];
    $media_id = $_POST['media_id'];

    // Check if already favorited
    $check = pg_query_params($conn, "SELECT id FROM favorites WHERE student_email = $1 AND media_id = $2", [$email, $media_id]);

    if (pg_num_rows($check) > 0) {
        // Already exists, so remove it (Unlike)
        pg_query_params($conn, "DELETE FROM favorites WHERE student_email = $1 AND media_id = $2", [$email, $media_id]);
        echo "removed";
    } else {
        // Doesn't exist, so add it (Like)
        pg_query_params($conn, "INSERT INTO favorites (student_email, media_id) VALUES ($1, $2)", [$email, $media_id]);
        echo "added";
    }
}
?>