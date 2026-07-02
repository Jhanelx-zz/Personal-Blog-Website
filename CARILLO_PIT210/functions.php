<?php
// --- FLASH MESSAGE SYSTEM ---
// Call this function before a redirect to set a message
function set_flash_message($message, $type = 'success') {
    $_SESSION['flash_msg'] = [
        'text' => $message,
        'type' => $type
    ];
}

// Call this in your main layout files to show the indicator
function display_flash_message() {
    if (isset($_SESSION['flash_msg'])) {
        $msg = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg']); // Clear so it only shows once
        return "<div id='toast' class='toast-{$msg['type']}'>{$msg['text']}</div>";
    }
    return '';
}

// --- NOTIFICATION LOGIC ---
if (isset($_SESSION['user_id'])) {
    $notif_query = "
        SELECT c.id, c.comment_text, c.username, m.title as post_title, m.id as post_id, r.student_fname, r.student_lname
        FROM public.comments c
        JOIN public.media m ON c.media_id = m.id
        JOIN public.registration r ON c.username = r.student_email
        WHERE c.is_read = FALSE AND c.username != $1
        ORDER BY c.id DESC LIMIT 5";

    $notif_res = pg_query_params($conn, $notif_query, array($_SESSION['user_id']));
    $unread_count = pg_num_rows($notif_res);
}
?>