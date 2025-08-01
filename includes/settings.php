<?php
// File: /includes/settings.php (CLEANED)
function get_setting($key, $conn = null) {
    static $settings = null;

    if ($settings === null && $conn) {
        $settings = [];
        $result = $conn->query("SELECT setting_key, setting_value FROM settings");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            $result->close();
        }
    }

    return $settings[$key] ?? null;
}