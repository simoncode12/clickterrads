<?php
// File: /includes/fraud_detector.php (NEW)

function is_fraudulent_request($conn) {
    // Ambil data request saat ini
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $domain = parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_HOST);
    
    // Siapkan query untuk mengecek semua tipe blacklist sekaligus
    $sql = "
        SELECT 1 FROM fraud_blacklist 
        WHERE 
            (type = 'ip' AND value = ?) OR 
            (type = 'user_agent' AND ? LIKE CONCAT('%', value, '%')) OR
            (type = 'domain' AND ? LIKE CONCAT('%', value, '%'))
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) { return false; } // Jika query gagal, anggap tidak fraud
    
    $stmt->bind_param("sss", $ip_address, $user_agent, $domain);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    // Jika ditemukan satu baris saja, berarti ini adalah traffic fraud
    return $result->num_rows > 0;
}
?>