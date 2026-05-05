<?php
session_start();
require_once 'database.php';

$user = trim($_POST['username'] ?? '');
$pass = trim($_POST['password'] ?? '');
$task = $_POST['task'] ?? 'login';

if ($user === '' || $pass === '') {
    echo "Bitte Benutzername und Passwort eingeben.";
    exit;
}

if ($task === 'register') {
    $stmt = $conn->prepare("SELECT player_id FROM PlayerState WHERE name = ?");
    $stmt->bind_param('s', $user);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "Benutzername bereits vergeben.";
        $stmt->close();
        exit;
    }

    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO PlayerState (name, password, rebirths, health, isAlive) VALUES (?, ?, 0, 100, 1)");
    $stmt->bind_param('ss', $user, $pass);

    if ($stmt->execute()) {
        $_SESSION['player'] = ['name' => $user];
        header('Location: index.php?register=success');
        exit;
    }

    echo "Registrierung fehlgeschlagen.";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM PlayerState WHERE name = ? AND password = ?");
$stmt->bind_param('ss', $user, $pass);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $_SESSION['player'] = $result->fetch_assoc();
    header('Location: index.php?login=success');
    exit;
}

echo "Falsche Anmeldedaten.";
exit;
?>