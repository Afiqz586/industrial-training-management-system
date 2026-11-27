<?php
function sendCrossNotification($dbconn, $senderRole, $senderName, $actionMessage) {
    $targetRole = ($senderRole === 'Chief Clerk') ? 'Super Admin' : 'Chief Clerk';
    $message = "$senderName ($senderRole) $actionMessage";

    // one notification for each user with the target role
    $stmt = mysqli_prepare(
        $dbconn,
        "INSERT INTO notifications (recipient_user_id, message)
         SELECT user_id, ? FROM users WHERE role = ?"
    );
    mysqli_stmt_bind_param($stmt, "ss", $message, $targetRole);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
?>