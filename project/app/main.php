<?php
session_start();
require_once 'database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['player'])) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

$name = $_SESSION['player']['name'];
$action = $_POST['action'] ?? '';

if ($action === 'get_balance' || $action === 'get_player') {
    $stmt = $conn->prepare('SELECT score, money_multiplier, speed_multiplier, overall_multiplier, rebirths FROM PlayerState WHERE name = ?');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    $score = isset($row['score']) ? (int)$row['score'] : 0;
    $moneyMult = isset($row['money_multiplier']) ? floatval($row['money_multiplier']) : 1.0;
    $speedMult = isset($row['speed_multiplier']) ? floatval($row['speed_multiplier']) : 1.0;
    $overall = isset($row['overall_multiplier']) ? floatval($row['overall_multiplier']) : 1.0;
    $rebirths = isset($row['rebirths']) ? intval($row['rebirths']) : 0;
    echo json_encode(['success' => true, 'balance' => $score, 'moneyMultiplier' => $moneyMult, 'speedMultiplier' => $speedMult, 'overallMultiplier' => $overall, 'rebirths' => $rebirths]);
    exit;
}

if ($action === 'add_money') {
    $amount = intval($_POST['amount'] ?? 0);
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'invalid_amount']);
        exit;
    }
    $stmt = $conn->prepare('UPDATE PlayerState SET score = score + ? WHERE name = ?');
    $stmt->bind_param('is', $amount, $name);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) {
        $stmt = $conn->prepare('SELECT score, money_multiplier, speed_multiplier, overall_multiplier FROM PlayerState WHERE name = ?');
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        $score = isset($row['score']) ? (int)$row['score'] : 0;
        $moneyMult = isset($row['money_multiplier']) ? floatval($row['money_multiplier']) : 1.0;
        $speedMult = isset($row['speed_multiplier']) ? floatval($row['speed_multiplier']) : 1.0;
        $overall = isset($row['overall_multiplier']) ? floatval($row['overall_multiplier']) : 1.0;
        echo json_encode(['success' => true, 'balance' => $score, 'moneyMultiplier' => $moneyMult, 'speedMultiplier' => $speedMult, 'overallMultiplier' => $overall]);
        exit;
    }
    echo json_encode(['success' => false, 'error' => 'db_error']);
    exit;
}

if ($action === 'buy_upgrade') {
    $type = $_POST['type'] ?? '';
    $cost = intval($_POST['cost'] ?? 0);
    if (!in_array($type, ['money','speed']) || $cost <= 0) {
        echo json_encode(['success' => false, 'error' => 'invalid_params']);
        exit;
    }
    // check balance
    $stmt = $conn->prepare('SELECT score, money_multiplier, speed_multiplier, overall_multiplier, rebirths FROM PlayerState WHERE name = ? FOR UPDATE');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    $score = isset($row['score']) ? intval($row['score']) : 0;
    $moneyMult = isset($row['money_multiplier']) ? floatval($row['money_multiplier']) : 1.0;
    $speedMult = isset($row['speed_multiplier']) ? floatval($row['speed_multiplier']) : 1.0;
    $overall = isset($row['overall_multiplier']) ? floatval($row['overall_multiplier']) : 1.0;
    $rebirths = isset($row['rebirths']) ? intval($row['rebirths']) : 0;

    if ($score < $cost) {
        echo json_encode(['success' => false, 'error' => 'insufficient_funds']);
        exit;
    }

    if ($type === 'money') $moneyMult += 0.25; else $speedMult += 0.25;

    $stmt = $conn->prepare('UPDATE PlayerState SET score = score - ?, money_multiplier = ?, speed_multiplier = ? WHERE name = ?');
    $stmt->bind_param('idds', $cost, $moneyMult, $speedMult, $name);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) {
        // return updated values
        $stmt = $conn->prepare('SELECT score, money_multiplier, speed_multiplier FROM PlayerState WHERE name = ?');
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        // include overall multiplier if present
        $stmt2 = $conn->prepare('SELECT overall_multiplier FROM PlayerState WHERE name = ?');
        $stmt2->bind_param('s', $name);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $row2 = $res2->fetch_assoc();
        $stmt2->close();
        $overallNow = isset($row2['overall_multiplier']) ? floatval($row2['overall_multiplier']) : 1.0;
        echo json_encode(['success' => true, 'balance' => intval($row['score']), 'moneyMultiplier' => floatval($row['money_multiplier']), 'speedMultiplier' => floatval($row['speed_multiplier']), 'overallMultiplier' => $overallNow]);
        exit;
    }
    echo json_encode(['success' => false, 'error' => 'db_error']);
    exit;
}

if ($action === 'rebirth') {
    // rebirth requires a cost and grants an overall multiplier increase
    $conn->begin_transaction();
    // load current values
    $stmt = $conn->prepare('SELECT score, rebirths, overall_multiplier FROM PlayerState WHERE name = ? FOR UPDATE');
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    $score = isset($row['score']) ? intval($row['score']) : 0;
    $rebirths = isset($row['rebirths']) ? intval($row['rebirths']) : 0;
    $overall = isset($row['overall_multiplier']) ? floatval($row['overall_multiplier']) : 1.0;

    $cost = ($rebirths + 1) * 10000;
    if ($score < $cost) {
        echo json_encode(['success' => false, 'error' => 'insufficient_funds', 'required' => $cost, 'balance' => $score]);
        $conn->rollback();
        exit;
    }

    // apply rebirth: reset score, increment rebirths, increase overall multiplier (e.g., *1.1)
    $newOverall = $overall * 1.1;
    $stmt = $conn->prepare('UPDATE PlayerState SET score = 0, rebirths = rebirths + 1, overall_multiplier = ? WHERE name = ?');
    $stmt->bind_param('ds', $newOverall, $name);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok) {
        $conn->commit();
        echo json_encode(['success' => true, 'overallMultiplier' => $newOverall, 'rebirths' => $rebirths + 1]);
        exit;
    }
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => 'db_error']);
    exit;
}

echo json_encode(['success' => false, 'error' => 'unknown_action']);
exit;

?>
