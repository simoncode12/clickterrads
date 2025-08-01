<?php
// Konfigurasi langsung di sini
$config = [
  'host' => 'localhost',
  'user' => 'user_db',
  'pass' => 'Puputchen12$',
  'name' => 'user_db'
];

// Koneksi database
$conn = new mysqli(
    $config['host'],
    $config['user'],
    $config['pass'],
    $config['name']
);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// File user agent list (1 per baris)
$file = __DIR__ . '/user_agents.txt';
$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$imported = 0;
foreach ($lines as $ua) {
    $ua = trim($ua);
    if ($ua === '' || $ua[0] === '#') continue;
    $type = 'user_agent';
    $reason = 'User-Agent bot import';
    $stmt = $conn->prepare("INSERT IGNORE INTO fraud_blacklist (type, value, reason) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $type, $ua, $reason);
    if ($stmt->execute()) $imported++;
    $stmt->close();
}
echo "Import selesai! Total user-agent diimpor: $imported\n";
$conn->close();
?>

