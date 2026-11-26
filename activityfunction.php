<?php

function logActivity(mysqli $dbconn, int $user_id, string $activity, string $description): void
{
    $sql = "INSERT INTO activity_logs
            (user_id, activity, description)
            VALUES (?, ?, ?)";

    $stmt = $dbconn->prepare($sql);

    $stmt->bind_param(
        "iss",
        $user_id,
        $activity,
        $description
    );

    $stmt->execute();

    $stmt->close();
}

?>