<?php
$cfg = require __DIR__ . '/../config/database.php';
try {
    $p = new PDO("mysql:host={$cfg['host']};port={$cfg['port']}", $cfg['username'], $cfg['password']);
    echo "OK MySQL connection successful\n";
} catch (Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
    exit(1);
}
