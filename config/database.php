<?php
// File: /config/database.php

$servername = "localhost";
$username = "user_db";
$password = "Puputchen12$";
$dbname = "user_db";

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Periksa koneksi
if ($conn->connect_error) {
  // Disarankan untuk log error ini dan menampilkan pesan yang lebih ramah pengguna
  error_log("Connection failed: " . $conn->connect_error);
  die("Situs sedang mengalami masalah teknis. Mohon coba lagi nanti.");
}

// Mengatur charset koneksi (sangat disarankan untuk mencegah masalah karakter)
$conn->set_charset("utf8mb4");

/**
 * Fetches results from a prepared statement.
 *
 * @param mysqli $conn The mysqli database connection object.
 * @param string $sql The SQL query string with placeholders (e.g., ?, ?).
 * @param array $params An array of parameters to bind to the query.
 * @param string $types A string representing the types of the parameters (e.g., "ssi" for string, string, integer).
 * @return array An array of associative arrays representing the query results, or an empty array if no results.
 */
// Tambahkan definisi fungsi get_query_results() di sini
if (!function_exists('get_query_results')) {
    function get_query_results(mysqli $conn, string $sql, array $params = [], string $types = ""): array
    {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            error_log("Failed to prepare statement: " . $conn->error);
            return [];
        }

        if (!empty($params)) {
            // Menggunakan operator spread (...) untuk unpack array $params ke argumen bind_param
            // Ini membutuhkan PHP 5.6+
            if (!$stmt->bind_param($types, ...$params)) {
                error_log("Failed to bind parameters: " . $stmt->error);
                $stmt->close();
                return [];
            }
        }

        if (!$stmt->execute()) {
            error_log("Failed to execute statement: " . $stmt->error);
            $stmt->close();
            return [];
        }

        $result = $stmt->get_result();
        if ($result === false) {
            error_log("Failed to get result set: " . $stmt->error);
            $stmt->close();
            return [];
        }

        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }
}

?>
