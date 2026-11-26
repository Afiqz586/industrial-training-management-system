<?php
function sendCrossNotification($dbconn, $senderRole, $senderName, $actionMessage){
    $targetRole = ($senderRole == 'Chief Clerk') ? 'Super Admin' : 'Chief Clerk';

    $newNotif = [
        'id' => uniqid(),
        'message' => "$senderName ($senderRole) $actionMessage",
        'read' => false,
        'time' => date('d M, h:i A')
    ];

    $stmt = $dbconn->prepare("SELECT id, notifications FROM users WHERE role = ?");
    $stmt->bind_param("s", $targetRole);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()){
    $existingNotifs = json_decode($row['notifications'], true) ?: [];

    array_unshift($existingNotifs, $newNotif);

    $existingNotifs = array_slice($existingNotifs, 0, 30);

    $updatedJson = json_encode($existingNotifs);

    $updateStmt = $dbconn->prepare("UPDATE users SET notifications = ? WHERE id = ?");
    $updateStmt->bind_param("si", $updatedJson, $row['id']);
    $updateStmt->execute();

    }
}
?>