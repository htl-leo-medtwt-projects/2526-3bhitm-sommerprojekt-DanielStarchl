<?php
require_once 'database.php';

function columnExists($conn, $table, $column) {
    $db = $conn->real_escape_string($conn->query("SELECT DATABASE() AS db")->fetch_assoc()['db']);
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $q = "SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = '$table' AND COLUMN_NAME = '$column'";
    $res = $conn->query($q);
    if (!$res) return false;
    $row = $res->fetch_assoc();
    return intval($row['c']) > 0;
}

$table = 'PlayerState';
$adds = [];
if (!columnExists($conn, $table, 'score')) $adds[] = "ADD COLUMN score INT NOT NULL DEFAULT 0";
if (!columnExists($conn, $table, 'money_multiplier')) $adds[] = "ADD COLUMN money_multiplier DOUBLE NOT NULL DEFAULT 1";
if (!columnExists($conn, $table, 'speed_multiplier')) $adds[] = "ADD COLUMN speed_multiplier DOUBLE NOT NULL DEFAULT 1";
if (!columnExists($conn, $table, 'overall_multiplier')) $adds[] = "ADD COLUMN overall_multiplier DOUBLE NOT NULL DEFAULT 1";

if (count($adds) === 0) {
    echo "No changes required\n";
    exit;
}

$sql = "ALTER TABLE $table " . implode(', ', $adds);
if ($conn->query($sql) === TRUE) {
    echo "Migration applied: $sql\n";
} else {
    echo "Error applying migration: " . $conn->error . "\n";
}

?>
