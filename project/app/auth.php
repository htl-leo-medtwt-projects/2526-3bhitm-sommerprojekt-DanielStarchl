<?php
session_start();
require_once 'database.php';

$user = trim($_POST['username'] ?? '');
$pass = trim($_POST['password'] ?? '');
$confirmPass = trim($_POST['confirm_password'] ?? '');
$task = $_POST['task'] ?? 'login';

if ($user === '' || $pass === '' || ($task === 'register' && $confirmPass === '')) {
    echo "Bitte Benutzername und Passwort eingeben.";
    exit;
}

if ($task === 'register') {
    if ($pass !== $confirmPass) {
        echo "Passwörter stimmen nicht überein.";
        exit;
    }

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

    $passwordHash = password_hash($pass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO PlayerState (name, password, rebirths, health, isAlive, score, money_multiplier, speed_multiplier, overall_multiplier) VALUES (?, ?, 0, 100, 1, 0, 1.0, 1.0, 1.0)");
    $stmt->bind_param('ss', $user, $passwordHash);

    if ($stmt->execute()) {
        $_SESSION['player'] = ['name' => $user];
        header('Location: index.php?register=success');
        exit;
    }

    echo "Registrierung fehlgeschlagen.";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM PlayerState WHERE name = ?");
$stmt->bind_param('s', $user);
$stmt->execute();
$result = $stmt->get_result();
$row = $result ? $result->fetch_assoc() : null;

if ($row && (password_verify($pass, $row['password']) || $pass === $row['password'])) {
    if (!password_verify($pass, $row['password'])) {
        $newHash = password_hash($pass, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare('UPDATE PlayerState SET password = ? WHERE name = ?');
        $updateStmt->bind_param('ss', $newHash, $user);
        $updateStmt->execute();
        $updateStmt->close();
    }

    $_SESSION['player'] = ['name' => $user];
    header('Location: index.php?login=success');
    exit;
}

$stmt->close();
echo "Falsche Anmeldedaten.";
exit;
?>