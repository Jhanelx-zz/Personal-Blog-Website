<?php
session_start();
include 'dbconn.php'; 

if (isset($_SESSION['user_id']) && isset($_POST['comment_text'])) {
    $media_id = $_POST['media_id'];
    $text = $_POST['comment_text'];
    $user = $_SESSION['user_id'];
    
    // Capture parent_id if it exists, otherwise set to NULL
    $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;

    // --- ADMIN LOGIC ---
    // If the logged-in user is an Admin, prepend the "ADMIN: " prefix
    // This allows the Moderate Comments page to style it correctly
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'Admin') {
        // Only prepend if it's not already there (prevents double prefixing if editing later)
        if (strpos($text, 'ADMIN: ') !== 0) {
            $text = "ADMIN: " . $text;
        }
    }

    $query = "INSERT INTO public.comments (media_id, username, comment_text, parent_id) VALUES ($1, $2, $3, $4)";
    $result = pg_query_params($conn, $query, array($media_id, $user, $text, $parent_id));

    if ($result) {
        // Redirect back to the post details page
        header("Location: post_details.php?id=" . $media_id);
        exit();
    } else {
        die("Error: " . pg_last_error($conn));
    }
} else {
    header("Location: user_home.php");
    exit();
}
?>