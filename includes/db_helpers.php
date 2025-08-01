<?php
// File: /includes/helpers.php (NEW)
// Tempat untuk semua fungsi pembantu global

if (!function_exists('get_query_results')) {
    function get_query_results($conn, $sql, $params = [], $types = '') {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) { 
            error_log("SQL Prepare Error: " . $conn->error . " | Query: " . $sql);
            return []; 
        }
        if (!empty($params) && !empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $data;
    }
}

if (!function_exists('get_single_metric')) {
    function get_single_metric($conn, $sql, $params = [], $types = '') {
        $data = get_query_results($conn, $sql, $params, $types);
        return !empty($data) ? (float)array_values($data[0])[0] : 0;
    }
}
?>