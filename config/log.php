<?php
// Helper untuk mencatat aktivitas ke tabel system_log
if (!function_exists('logActivity')) {
    function logActivity(
        string $module,
        string $action,
        string $description = '',
        $data_before = null,
        $data_after = null
    ) {
        global $conn;

        if (!isset($conn) || !$conn) {
            return; // tidak ada koneksi DB
        }

        // Ambil info user dari session (kalau ada)
        $user_id   = $_SESSION['user_id']   ?? null;
        $username  = $_SESSION['username']  ?? null;
        $ip        = $_SERVER['REMOTE_ADDR']        ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT']    ?? '';

        $beforeJson = $data_before ? json_encode($data_before, JSON_UNESCAPED_UNICODE) : null;
        $afterJson  = $data_after  ? json_encode($data_after,  JSON_UNESCAPED_UNICODE) : null;

        $sql = "INSERT INTO system_log
                (user_id, username, module, action, description, ip_address, user_agent, data_before, data_after)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return;
        }

        // i s s s s s s s s
        $stmt->bind_param(
            "issssssss",
            $user_id,
            $username,
            $module,
            $action,
            $description,
            $ip,
            $userAgent,
            $beforeJson,
            $afterJson
        );

        $stmt->execute();
        $stmt->close();
    }
}
