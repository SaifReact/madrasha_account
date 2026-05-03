<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_URL', '/madrasha_account/');

$host = 'localhost';
$db   = 'madrasha_ac';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Fetch first row from setup table (since it's general info)
    $stmt = $pdo->query("SELECT * FROM setup LIMIT 1");
    $setupRow = $stmt->fetch();

    // Store directly in session
    if ($setupRow) {
        $_SESSION['setup'] = $setupRow;
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
