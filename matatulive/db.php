<?php
// db.php - XAMPP MySQL Connection for Matatu Live

$host = 'localhost';
$db   = 'matatulive';
$user = 'root';
$pass = ''; // Default XAMPP password is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("<div style='background: #161b22; color: #f85149; padding: 20px; font-family: monospace; border: 1px solid #30363d; margin: 20px; border-radius: 8px;'>
            <strong>Database Connection Error:</strong> " . htmlspecialchars($e->getMessage()) . "
         </div>");
}
?>
