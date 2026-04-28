<?php
session_start();
require_once 'database.php';

$user = $_POST['username'];
$pass = $_POST['password'];

$sql = "SELECT * FROM PlayerState WHERE username = '$user' AND password = '$pass'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $_SESSION['player'] = $result->fetch_assoc();
    header("Location: index.html?login=success");
} else {
    echo "Wrong Credentials.";
}
?>