<?php
session_start();
require_once 'database.php';

$message = '';
$hideLogin = false;
$isLoggedIn = isset($_SESSION['player']);
$currentUser = $isLoggedIn ? ($_SESSION['player']['name'] ?? '') : '';

$playerData = [
    'rebirths' => 0,
    'score' => 0
];

if ($isLoggedIn) {
    $stmt = $conn->prepare('SELECT rebirths, score FROM PlayerState WHERE name = ?');
    $stmt->bind_param('s', $currentUser);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $playerData = $row;
    }
    $stmt->close();
}

$rebirthCost = ($playerData['rebirths'] + 1) * 10000;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task = $_POST['task'] ?? $_POST['auth_action'] ?? '';

    if ($task === 'logout') {
        session_unset();
        session_destroy();
        session_start();
        $message = 'Abmeldung erfolgreich';
        $isLoggedIn = false;
        $currentUser = '';
        $hideLogin = false;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } elseif ($task === 'rebirth' && $isLoggedIn) {
        if ($playerData['score'] >= $rebirthCost) {
            $newScore = $playerData['score'] - $rebirthCost;
            $newRebirths = $playerData['rebirths'] + 1;
            
            $updateStmt = $conn->prepare('UPDATE PlayerState SET score = ?, rebirths = ? WHERE name = ?');
            $updateStmt->bind_param('iis', $newScore, $newRebirths, $currentUser);
            if ($updateStmt->execute()) {
                $playerData['score'] = $newScore;
                $playerData['rebirths'] = $newRebirths;
                $rebirthCost = ($newRebirths + 1) * 10000;
                $message = 'Successfully reborn!';
            }
            $updateStmt->close();
        } else {
            $message = 'Not enough cash for a Rebirth!';
        }
    } else {
        $user = trim($_POST['username'] ?? '');
        $pass = trim($_POST['password'] ?? '');
        $confirmPass = trim($_POST['confirm_password'] ?? '');

        if ($user === '' || $pass === '' || ($task === 'register' && $confirmPass === '')) {
            $message = 'Bitte Benutzername und Passwort eingeben.';
        } else {
            if ($task === 'register') {
                if ($pass !== $confirmPass) {
                    $message = 'Passwörter stimmen nicht überein.';
                } else {
                    $stmt = $conn->prepare('SELECT player_id FROM PlayerState WHERE name = ?');
                    $stmt->bind_param('s', $user);
                    $stmt->execute();
                    $stmt->store_result();

                    if ($stmt->num_rows > 0) {
                        $message = 'Benutzername bereits vergeben.';
                    } else {
                        $stmt->close();
                        $passwordHash = password_hash($pass, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare('INSERT INTO PlayerState (name, password, rebirths, health, isAlive, score) VALUES (?, ?, 0, 100, 1, 0)');
                        $stmt->bind_param('ss', $user, $passwordHash);

                        if ($stmt->execute()) {
                            $_SESSION['player'] = ['name' => $user];
                            $currentUser = $user;
                            $isLoggedIn = true;
                            $message = 'Registrierung erfolgreich.';
                            $hideLogin = true;
                            header("Location: " . $_SERVER['PHP_SELF']);
                            exit;
                        } else {
                            $message = 'Registrierung fehlgeschlagen.';
                        }
                    }
                    $stmt->close();
                }
            } else {
                $stmt = $conn->prepare('SELECT * FROM PlayerState WHERE name = ?');
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
                    $currentUser = $user;
                    $isLoggedIn = true;
                    $message = 'Login erfolgreich.';
                    $hideLogin = true;
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit;
                } else {
                    $message = 'Falsche Anmeldedaten.';
                }
                $stmt->close();
            }
        }
    }
}

$selectedVariant = isset($_GET['variant']) ? intval($_GET['variant']) : 1;
$mutations = [];
$query = "SELECT m.mutation_id, m.name, m.variant_id, v.variant, (m.multiplier * v.multiplier) AS total_multiplier 
          FROM Mutation m 
          JOIN Variant v ON m.variant_id = v.variant_id 
          WHERE m.variant_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $selectedVariant);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $mutations[] = $row;
}
$stmt->close();

$products = [];
$prodQuery = "SELECT product_id, name, value, price FROM Product";
$prodResult = $conn->query($prodQuery);
if ($prodResult) {
    while ($row = $prodResult->fetch_assoc()) {
        $products[] = $row;
    }
}

$progressPercent = min(100, max(0, ($playerData['score'] / $rebirthCost) * 100));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broken Bones Clicker</title>
    <script src="https://cdn.babylonjs.com/babylon.js"></script>
    <script src="https://cdn.babylonjs.com/loaders/babylonjs.loaders.min.js"></script>
    <script>const INITIAL_BALANCE = <?php echo intval($playerData['score']); ?>;</script>
    <script src="./js/babylon.js" defer></script>
</head>
<body>

    <div id="event-bg-overlay"></div>

    <?php if ($isLoggedIn): ?>
    <form id="logout-form" action="" method="POST" style="position: fixed; top: 20px; left: 20px; z-index: 1000;">
        <input type="hidden" name="task" value="logout">
        <button id="logout-button" type="submit">Abmelden</button>
    </form>
    <?php endif; ?>

    <div id="balance-ui">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div style="font-weight:900; letter-spacing: 1px;">BALANCE</div>
            <div style="font-weight:900; font-size:22px; color: #55ff55;">€ <span id="balance-amount"><?php echo intval($playerData['score']); ?></span></div>
        </div>
        <div id="mults">
            <div class="stat-badge money-color">Money x<span id="money-mult">1</span></div>
            <div class="stat-badge speed-color">Speed x<span id="speed-mult">1</span></div>
        </div>
        <div id="event-badge" class="stat-badge event-color hidden">EVENT FRENZY x<span id="event-mult">1</span></div>
        <div style="display:flex; gap:8px; margin-top:10px;">
            <button id="upgrade-money" class="action-btn-primary" style="flex:1;">+ Money Mult<br><small id="money-cost-lbl">Cost: 100</small></button>
            <button id="upgrade-speed" class="action-btn-primary" style="flex:1;">+ Speed Mult<br><small id="speed-cost-lbl">Cost: 100</small></button>
        </div>
        <div style="margin-top:12px; background: rgba(0,0,0,0.4); padding:10px; border-radius:8px; border: 2px solid #333;">
            <div style="font-weight:900; font-size: 14px; text-align: center; color: #df00ff;">REBIRTH SYSTEM</div>
            <div style="font-size:12px; margin-top:6px; display:flex; justify-content:space-between;"><span>Price:</span><span id="rebirth-cost">0</span></div>
            <div style="font-size:12px; margin-top:2px; display:flex; justify-content:space-between;"><span>Global Mult:</span><span>x<span id="overall-mult">1</span></span></div>
            <button id="rebirth-btn" class="action-btn-primary" style="margin-top:8px; width:100%; background: #b5179e;">Start Rebirth</button>
        </div>
    </div>

    <div id="login" class="page-frame" style="<?php echo ($hideLogin || $isLoggedIn) ? 'display:none;' : ''; ?>">
        <h2 style="color: white; text-align: center; margin: 0 0 10px 0; font-size: 24px; text-transform: uppercase;-webkit-text-stroke: 1px #000;">Broken Bones</h2>
        <form id="auth-form" action="" method="POST">
            <input type="hidden" name="task" id="hidden-task-field" value="login">
            <div style="display: grid; grid-template-columns: auto; gap: 10px; margin-bottom: 15px;">
                <input class="input-field" type="text" name="username" placeholder="Benutzername" required autocomplete="username">
                <input class="input-field" id="password-field" type="password" name="password" placeholder="Passwort" required autocomplete="current-password">
                <input class="input-field hidden" id="confirm-password" type="password" name="confirm_password" placeholder="Passwort bestätigen" autocomplete="new-password">
            </div>
            <div style="display: flex; gap: 10px; justify-content: center;">
                <button id="login-button" class="action-button" type="submit" name="auth_action" value="login">Anmelden</button>
                <button id="register-button" class="action-button" type="submit" name="auth_action" value="register" style="background: #3a86ff;">Registrieren</button>
            </div>
            <div id="message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        </form>
    </div>

    <div id="frames-left">
        <div onclick="toggleFrame('index-frame')">
            <img class="icon" src="./Assets/indexIcon.png" alt="Index" onerror="this.onerror=null;this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'64\' height=\'64\'><rect width=\'100%\' height=\'100%\' fill=\'%23222\' /><text x=\'50%\' y=\'50%\' fill=\'%23fff\' font-size=\'20\' font-family=\'sans-serif\' dominant-baseline=\'middle\' text-anchor=\'middle\'>IDX</text></svg>';">
            <h1 class="title">Index</h1>
        </div>
        <div onclick="toggleFrame('shop-frame')">
            <img class="icon" src="./Assets/shopIcon.png" alt="Shop" onerror="this.onerror=null;this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'64\' height=\'64\'><rect width=\'100%\' height=\'100%\' fill=\'%23222\' /><text x=\'50%\' y=\'50%\' fill=\'%23fff\' font-size=\'20\' font-family=\'sans-serif\' dominant-baseline=\'middle\' text-anchor=\'middle\'>SHOP</text></svg>';">
            <h1 class="title">Shop</h1>
        </div>
        <div onclick="toggleFrame('rebirth-frame')">
            <img class="icon" src="./Assets/rebirthIcon.png" alt="Rebirth" onerror="this.onerror=null;this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' width=\'64\' height=\'64\'><rect width=\'100%\' height=\'100%\' fill=\'%23222\' /><text x=\'50%\' y=\'50%\' fill=\'%23fff\' font-size=\'20\' font-family=\'sans-serif\' dominant-baseline=\'middle\' text-anchor=\'middle\'>RB</text></svg>';">
            <h1 class="title">Rebirth</h1>
        </div>
    </div>

    <div id="index-frame" class="index-modal" style="display: none;">
        <div class="index-sidebar">
            <a href="?variant=1" class="tab-btn normal-tab <?php echo $selectedVariant === 1 ? 'active' : ''; ?>">Normal</a>
            <a href="?variant=2" class="tab-btn golden-tab <?php echo $selectedVariant === 2 ? 'active' : ''; ?>">Golden</a>
            <a href="?variant=3" class="tab-btn diamond-tab <?php echo $selectedVariant === 3 ? 'active' : ''; ?>">Diamond</a>
        </div>
        <div class="index-main">
            <div class="index-header">
                <span class="index-title-text">Mutations</span>
                <button class="close-btn" onclick="toggleFrame('index-frame')">×</button>
            </div>
            <div class="index-grid">
                <?php foreach ($mutations as $mutation): ?>
                    <div class="grid-item">
                        <div class="silhouette-placeholder"></div>
                        <span class="item-rarity"><?php echo htmlspecialchars($mutation['variant']); ?></span>
                        <span class="item-name"><?php echo htmlspecialchars($mutation['name']); ?></span>
                        <span style="font-size: 11px; color: #55ff55; font-weight: bold;">x<?php echo htmlspecialchars($mutation['total_multiplier']); ?></span>
                    </div>
                <?php endforeach; ?>
                <?php 
                $totalItems = count($mutations);
                if ($totalItems < 9): 
                    for ($i = 0; $i < (9 - $totalItems); $i++): ?>
                        <div class="grid-item locked">
                            <div class="silhouette-placeholder"></div>
                            <span class="item-rarity">Locked</span>
                            <span class="item-name">???</span>
                        </div>
                    <?php endfor; 
                endif; ?>
            </div>
        </div>
    </div>

    <div id="shop-frame" class="shop-modal" style="display: none;">
        <div class="shop-main">
            <div class="shop-header">
                <div class="shop-title-container">
                    <span class="shop-title-text">Shop Upgrade Items</span>
                </div>
                <button class="close-btn" onclick="toggleFrame('shop-frame')">×</button>
            </div>

            <div class="shop-shelf">
                <?php if (empty($products)): ?>
                    <div style="color: white; text-align: center; width: 100%; padding: 20px;">No items available in the shop.</div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="shop-item purple-gradient" onclick="openFakePaymentGate(<?php echo intval($product['product_id']); ?>, <?php echo intval($product['price']); ?>, '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>')">
                            <div class="shop-item-content">
                                <div class="shop-placeholder-art"></div>
                                <span class="shop-item-name"><?php echo htmlspecialchars($product['name']); ?></span>
                                <span class="shop-item-value">Value: +<?php echo htmlspecialchars($product['value']); ?></span>
                            </div>
                            <div class="shop-item-price-tag">
                                <span class="robux-icon">⏣</span> <?php echo number_format($product['price'], 0, ',', '.'); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="fake-payment-gate" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.85); z-index: 3000; align-items: center; justify-content: center;">
        <div style="background: #1e1e24; border: 4px solid #ffea00; border-radius: 16px; padding: 25px; width: 320px; box-shadow: 0 10px 40px rgba(0,0,0,0.8); color: white;">
            <div style="font-size: 20px; font-weight: 900; text-align: center; color: #ffea00; margin-bottom: 15px; text-transform: uppercase;">Robux Secure Pay</div>
            <div id="pay-item-info" style="font-size: 14px; text-align: center; margin-bottom: 20px; color: #ccc;"></div>
            
            <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px;">
                <input id="pay-cardholder" class="input-field" type="text" placeholder="Roblox Username" autocomplete="off">
                <input id="pay-pin" class="input-field" type="password" placeholder="4-Digit Secure PIN (Fake)" maxlength="4" autocomplete="off">
            </div>

            <div style="display: flex; gap: 10px; justify-content: center;">
                <button onclick="processFakePurchase()" style="background: #55ff55; border: 3px solid #000; border-radius: 8px; padding: 10px 20px; font-weight: 900; cursor: pointer; text-transform: uppercase;">Confirm Purchase</button>
                <button onclick="closeFakePaymentGate()" style="background: #ff5555; border: 3px solid #000; border-radius: 8px; padding: 10px 20px; font-weight: 900; cursor: pointer; text-transform: uppercase; color: white;">Cancel</button>
            </div>
        </div>
    </div>

    <div id="rebirth-frame" class="rebirth-modal" style="<?php echo ($isLoggedIn && !isset($_GET['variant'])) ? 'display: flex;' : 'display: none;'; ?>">
        <div class="rebirth-main">
            <div class="rebirth-header">
                <div class="rebirth-title-container">
                    <span class="rebirth-title-text">Rebirth</span>
                </div>
                <button class="close-btn" onclick="toggleFrame('rebirth-frame')">×</button>
            </div>

            <div class="rebirth-perks">
                <div class="perk-card">
                    <div class="perk-art money-bag-art"></div>
                    <span class="perk-value">Bonus Cash</span>
                </div>
                <div class="perk-card">
                    <div class="perk-art multiplier-art"></div>
                    <span class="perk-value green-text">x<?php echo ($playerData['rebirths'] + 2); ?> Mult</span>
                </div>
                <div class="perk-card">
                    <div class="perk-art floor-art"></div>
                    <span class="perk-value green-text">+1 Level</span>
                </div>
            </div>

            <div class="rebirth-warning">Rebirth resets cash but permanently increases all multipliers!</div>

            <div class="rebirth-progress-container">
                <div class="rebirth-progress-bar" style="width: <?php echo $progressPercent; ?>%;"></div>
                <span class="rebirth-progress-text">€ <?php echo number_format($playerData['score'], 0, ',', '.'); ?> / € <?php echo number_format($rebirthCost, 0, ',', '.'); ?></span>
            </div>

            <form action="" method="POST" style="width: 100%; display: flex; justify-content: center;">
                <input type="hidden" name="task" value="rebirth">
                <button type="submit" class="rebirth-action-btn" <?php echo ($playerData['score'] < $rebirthCost) ? 'disabled' : ''; ?>>Start Rebirth</button>
            </form>
        </div>
    </div>

    <canvas id="renderCanvas" touch-action="none"></canvas>

    <div id="modal-overlay" style="display: none;">
        <div id="modal-dialog">
            <div id="modal-message"></div>
            <div id="modal-buttons">
                <button id="modal-btn-primary">OK</button>
                <button id="modal-btn-secondary" style="display: none;">Cancel</button>
            </div>
        </div>
    </div>

    <style>
        html, body {
            width: 100%;
            height: 100%;
            overflow: hidden;
            margin: 0;
            padding: 0;
            font-family: 'Arial Black', Gadget, sans-serif;
            background-color: #66b2ff; 
        }

        #renderCanvas {
            width: 100%;
            height: 100%;
            touch-action: none;
            display: block;
            background-color: #66b2ff;
        }

        #event-bg-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .event-active #event-bg-overlay {
            opacity: 0.25;
            animation: eventRainbow 6s linear infinite;
        }

        @keyframes eventRainbow {
            0% { background-color: #ff0055; }
            33% { background-color: #00ff55; }
            66% { background-color: #0055ff; }
            100% { background-color: #ff0055; }
        }

        #balance-ui {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: linear-gradient(135deg, #1e1e24, #111115);
            color: white;
            padding: 16px;
            border-radius: 14px;
            width: 280px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.7);
            border: 3px solid #000;
        }

        #mults {
            display: flex;
            gap: 6px;
            margin-top: 8px;
        }

        .stat-badge {
            flex: 1;
            padding: 6px;
            border-radius: 6px;
            text-align: center;
            font-size: 11px;
            font-weight: 900;
            border: 2px solid #000;
            text-transform: uppercase;
        }
        .money-color { background: #00a86b; }
        .speed-color { background: #0077b6; }
        .event-color { background: #ff0055; margin-top: 6px; animation: pulse 1s infinite alternate; }

        @keyframes pulse {
            0% { transform: scale(1); }
            100% { transform: scale(1.03); }
        }

        .action-btn-primary {
            background: #2a9d8f;
            color: white;
            border: 3px solid #000;
            border-radius: 8px;
            padding: 6px;
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
            box-shadow: inset 0 -3px 0 rgba(0,0,0,0.2);
            text-transform: uppercase;
            font-family: inherit;
            margin-top: 6px;
        }
        .action-btn-primary:hover { background: #264653; }

        .title {
            font-family: inherit;
            margin-top: -24px;
            margin-left: 10px;
            font-size: 24px;
            font-weight: 900;
            color: white;
            -webkit-text-stroke: 1.5px black;
        }

        #frames-left {
            display: flex;
            position: fixed;
            flex-direction: column;
            top: 50%;
            left: 20px;
            transform: translate(0,-50%);
            width: 100px;
            z-index: 999;
            gap: 10px;
        }

        #frames-left div { cursor: pointer; }
        #frames-left img { width: 85px; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.5)); }

        .page-frame {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 380px;
            background: rgba(15, 15, 18, 0.95);
            border: 4px solid #000;
            border-radius: 16px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            z-index: 1001;
            box-shadow: 0 20px 50px rgba(0,0,0,0.8);
        }

        .input-field {
            background: #222;
            border: 3px solid #000;
            border-radius: 8px;
            padding: 10px;
            color: white;
            font-weight: bold;
            font-family: inherit;
        }

        .action-button {
            background: #00f5d4;
            border: 3px solid #000;
            border-radius: 8px;
            padding: 10px 20px;
            color: black;
            font-weight: 900;
            text-transform: uppercase;
            cursor: pointer;
            font-family: inherit;
        }

        #logout-button {
            background: #e63946;
            border: 3px solid #000;
            border-radius: 10px;
            padding: 8px 16px;
            color: white;
            font-weight: 900;
            text-transform: uppercase;
            cursor: pointer;
            font-family: inherit;
        }

        .index-modal, .shop-modal, .rebirth-modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            align-items: flex-start;
            z-index: 1000;
        }

        .index-sidebar { display: flex; flex-direction: column; gap: 6px; margin-top: 50px; margin-right: -4px; }
        
        .tab-btn {
            font-family: inherit;
            font-weight: 900;
            font-size: 14px;
            text-decoration: none;
            color: #000;
            padding: 10px 14px;
            border: 3px solid #000;
            border-radius: 8px 0 0 8px;
            text-align: center;
        }
        .normal-tab { background: #e1e1e1; }
        .golden-tab { background: #f9d342; }
        .diamond-tab { background: #5cc2f2; }
        .tab-btn.active { transform: scaleX(1.05); z-index: 2; border-right: none; }

        .index-main, .shop-main, .rebirth-main {
            background: #151518;
            border: 5px solid #000;
            border-radius: 16px;
            width: 480px;
            padding: 16px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.7);
            color: white;
        }

        .index-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            background: #000;
            padding: 10px;
            border-radius: 10px;
        }

        .grid-item {
            background: #222;
            border: 3px solid #000;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 8px;
            position: relative;
        }
        .grid-item.locked { opacity: 0.4; }

        .silhouette-placeholder {
            width: 45px;
            height: 45px;
            background: #555;
            clip-path: polygon(50% 0%, 80% 30%, 80% 70%, 50% 100%, 20% 70%, 20% 30%);
        }

        .item-rarity { font-size: 9px; color: #aaa; text-transform: uppercase; margin-top: 4px; }
        .item-name { font-size: 11px; font-weight: 900; text-align: center; margin-top: 2px; }

        .shop-shelf {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            background: #000;
            padding: 12px;
            border-radius: 12px;
            max-height: 360px;
            overflow-y: auto;
        }

        .shop-item {
            border: 3px solid #000;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            cursor: pointer;
            transition: transform 0.1s;
        }
        .shop-item:hover { transform: scale(1.02); }

        .purple-gradient { background: linear-gradient(135deg, #4c0082, #1a0033); }

        .shop-item-content { padding: 12px; display: flex; flex-direction: column; align-items: center; gap: 4px; }
        .shop-item-name { font-size: 13px; font-weight: 900; color: #fff; text-align: center; }
        .shop-item-value { font-size: 11px; color: #55ff55; }

        .shop-placeholder-art {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 6px;
            border: 2px solid #000;
        }

        .shop-item-price-tag {
            background: #222;
            border-top: 3px solid #000;
            text-align: center;
            font-size: 12px;
            font-weight: 900;
            padding: 4px 0;
            color: #ffea00;
        }

        .rebirth-perks { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 12px; }
        
        .perk-card {
            background: #222;
            border: 3px solid #000;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px;
        }

        .perk-art { width: 40px; height: 40px; background-color: #333; border: 2px solid #000; border-radius: 6px; margin-bottom: 4px; }
        .perk-value { font-size: 12px; font-weight: 900; text-align: center; }

        .rebirth-progress-container {
            background: #222;
            border: 3px solid #000;
            border-radius: 6px;
            height: 24px;
            position: relative;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .rebirth-progress-bar { background: linear-gradient(90deg, #b5179e, #7209b7); height: 100%; }
        .rebirth-progress-text {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            color: white; font-weight: 900; font-size: 12px; -webkit-text-stroke: 1px #000;
        }

        .rebirth-action-btn {
            background: #b5179e; color: white; border: 3px solid #000; border-radius: 10px;
            padding: 10px; font-size: 16px; font-weight: 900; cursor: pointer; width: 100%;
        }
        .rebirth-action-btn:disabled { background: #444; opacity: 0.5; cursor: not-allowed; }

        #modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.75); z-index: 2000; align-items: center; justify-content: center;
        }

        #modal-dialog {
            background: #151518; border: 4px solid #000; border-radius: 12px;
            padding: 20px; max-width: 320px; text-align: center; color: white;
        }

        #modal-buttons { display: flex; gap: 10px; justify-content: center; margin-top: 15px; }
        #modal-buttons button {
            border: 3px solid #000; border-radius: 6px; padding: 8px 16px;
            font-weight: 900; cursor: pointer; font-family: inherit; text-transform: uppercase;
        }
        #modal-btn-primary { background: #00f5d4; color: black; }
        #modal-btn-secondary { background: #e63946; color: white; }

        #message { color: #ff3333; text-align: center; font-size: 13px; margin-top: 8px; font-weight: bold; }
    </style>

    <script>
        let selectedProduct = null;
        
        let currentMoneyMult = parseFloat(localStorage.getItem('moneyMultiplier')) || 1.0;
        let currentSpeedMult = parseFloat(localStorage.getItem('speedMultiplier')) || 1.0;
        
        // EXPONENTIELLE KOSTEN-SKALIERUNG: 
        // Formel: Basispreis (100) * (Multiplier ^ 1.8) -> Steigt rasant an!
        let nextMoneyCost = Math.floor(100 * Math.pow(currentMoneyMult, 1.8));
        let nextSpeedCost = Math.floor(100 * Math.pow(currentSpeedMult, 1.8));

        function openFakePaymentGate(productId, price, productName) {
            const currentBalance = parseInt(document.getElementById('balance-amount').innerText) || 0;
            if (currentBalance < price) {
                window.showAlert('Insufficient cash for ' + productName);
                return;
            }
            selectedProduct = { id: productId, price: price, name: productName };
            document.getElementById('pay-item-info').innerText = `Item: ${productName}\nPrice: €${price.toLocaleString('en-US')}`;
            document.getElementById('fake-payment-gate').style.display = 'flex';
        }

        function closeFakePaymentGate() {
            document.getElementById('fake-payment-gate').style.display = 'none';
            document.getElementById('pay-cardholder').value = '';
            document.getElementById('pay-pin').value = '';
            selectedProduct = null;
        }

        async function processFakePurchase() {
            const name = document.getElementById('pay-cardholder').value.trim();
            const pin = document.getElementById('pay-pin').value.trim();

            if(name === "" || pin.length < 4) {
                await window.showAlert('Please complete all secure fields!');
                return;
            }

            const fd = new FormData();
            fd.append('action', 'buy_product');
            fd.append('product_id', selectedProduct.id);

            try {
                const res = await fetch('./main.php', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    document.getElementById('balance-amount').innerText = data.balance;
                    closeFakePaymentGate();
                    await window.showAlert(selectedProduct.name + ' successfully unlocked!');
                    location.reload(); 
                } else {
                    await window.showAlert('Purchase failed: ' + (data.error || 'Server Error'));
                }
            } catch(e) {
                await window.showAlert('Connection error.');
            }
        }

        function toggleFrame(frameId) {
            const frames = ['index-frame', 'shop-frame', 'rebirth-frame'];
            frames.forEach(f => {
                const el = document.getElementById(f);
                if(el) {
                    if(f === frameId && el.style.display !== 'flex') {
                        el.style.display = 'flex';
                    } else {
                        el.style.display = 'none';
                    }
                }
            });
        }
    </script>
    
    <script>
        window.showAlert = function(message) {
            return new Promise((resolve) => {
                const overlay = document.getElementById('modal-overlay');
                const msgDiv = document.getElementById('modal-message');
                const primaryBtn = document.getElementById('modal-btn-primary');
                const secondaryBtn = document.getElementById('modal-btn-secondary');
                
                msgDiv.textContent = message;
                primaryBtn.textContent = 'OK';
                secondaryBtn.style.display = 'none';
                primaryBtn.onclick = () => { overlay.style.display = 'none'; resolve(true); };
                overlay.style.display = 'flex';
            });
        };

        window.showConfirm = function(message) {
            return new Promise((resolve) => {
                const overlay = document.getElementById('modal-overlay');
                const msgDiv = document.getElementById('modal-message');
                const primaryBtn = document.getElementById('modal-btn-primary');
                const secondaryBtn = document.getElementById('modal-btn-secondary');
                
                msgDiv.textContent = message;
                primaryBtn.textContent = 'Yes';
                secondaryBtn.textContent = 'No';
                secondaryBtn.style.display = 'block';
                
                primaryBtn.onclick = () => { overlay.style.display = 'none'; resolve(true); };
                secondaryBtn.onclick = () => { overlay.style.display = 'none'; resolve(false); };
                overlay.style.display = 'flex';
            });
        };
    </script>

    <script>
        (function(){
            const balanceEl = document.getElementById('balance-amount');
            const upgradeMoneyBtn = document.getElementById('upgrade-money');
            const upgradeSpeedBtn = document.getElementById('upgrade-speed');
            const rebirthBtn = document.getElementById('rebirth-btn');
            const moneyMultEl = document.getElementById('money-mult');
            const speedMultEl = document.getElementById('speed-mult');
            const overallMultEl = document.getElementById('overall-mult');
            const rebirthCostEl = document.getElementById('rebirth-cost');
            const moneyCostLbl = document.getElementById('money-cost-lbl');
            const speedCostLbl = document.getElementById('speed-cost-lbl');

            if (moneyMultEl) moneyMultEl.innerText = currentMoneyMult;
            if (speedMultEl) speedMultEl.innerText = currentSpeedMult;
            if (moneyCostLbl) moneyCostLbl.innerText = "Cost: €" + nextMoneyCost.toLocaleString('de-DE');
            if (speedCostLbl) speedCostLbl.innerText = "Cost: €" + nextSpeedCost.toLocaleString('de-DE');

            window.addEventListener('gameEvent', (e) => {
                const data = e.detail;
                const eventBadge = document.getElementById('event-badge');
                const eventMultEl = document.getElementById('event-mult');

                if (data.active) {
                    document.body.classList.add('event-active');
                    if (eventBadge) eventBadge.classList.remove('hidden');
                    if (eventMultEl) eventMultEl.innerText = data.multiplier;
                } else {
                    document.body.classList.remove('event-active');
                    if (eventBadge) eventBadge.classList.add('hidden');
                }
            });

            async function refreshBalance(){
                try{
                    const fd = new FormData(); fd.append('action','get_player');
                    const res = await fetch('./main.php',{method:'POST', body:fd});
                    const data = await res.json();
                    if (data && data.success) {
                        if(balanceEl) balanceEl.textContent = data.balance;
                        
                        if (data.moneyMultiplier && parseFloat(data.moneyMultiplier) >= currentMoneyMult) {
                            currentMoneyMult = parseFloat(data.moneyMultiplier);
                            localStorage.setItem('moneyMultiplier', String(currentMoneyMult));
                        }
                        if (data.speedMultiplier && parseFloat(data.speedMultiplier) >= currentSpeedMult) {
                            currentSpeedMult = parseFloat(data.speedMultiplier);
                            localStorage.setItem('speedMultiplier', String(currentSpeedMult));
                        }

                        if (moneyMultEl) moneyMultEl.innerText = currentMoneyMult;
                        if (speedMultEl) speedMultEl.innerText = currentSpeedMult;
                        if (overallMultEl && data.overallMultiplier) overallMultEl.innerText = data.overallMultiplier;
                        
                        // Exponentieller Preisanstieg wird auch beim Live-Refresh synchronisiert
                        nextMoneyCost = Math.floor(100 * Math.pow(currentMoneyMult, 1.8));
                        nextSpeedCost = Math.floor(100 * Math.pow(currentSpeedMult, 1.8));
                        
                        if(moneyCostLbl) moneyCostLbl.innerText = "Cost: €" + nextMoneyCost.toLocaleString('de-DE');
                        if(speedCostLbl) speedCostLbl.innerText = "Cost: €" + nextSpeedCost.toLocaleString('de-DE');

                        if (typeof data.rebirths !== 'undefined') {
                            const req = (data.rebirths + 1) * 10000;
                            if (rebirthCostEl) rebirthCostEl.innerText = "€ " + req.toLocaleString('en-US');
                            if (rebirthBtn) rebirthBtn.disabled = data.balance < req;
                        }
                    }
                }catch(e){}
            }

            upgradeMoneyBtn?.addEventListener('click', async ()=>{
                const currentBalance = parseInt(balanceEl.innerText) || 0;
                if (currentBalance < nextMoneyCost) {
                    await window.showAlert('Not enough cash for this upgrade!');
                    return;
                }

                currentMoneyMult += 0.5;
                localStorage.setItem('moneyMultiplier', String(currentMoneyMult));
                if (moneyMultEl) moneyMultEl.innerText = currentMoneyMult;

                const fd = new FormData(); 
                fd.append('action','buy_upgrade'); 
                fd.append('type','money');
                fd.append('cost', String(nextMoneyCost)); 
                
                const res = await fetch('./main.php', {method:'POST', body:fd});
                refreshBalance();
            });

            upgradeSpeedBtn?.addEventListener('click', async ()=>{
                const currentBalance = parseInt(balanceEl.innerText) || 0;
                if (currentBalance < nextSpeedCost) {
                    await window.showAlert('Not enough cash for this upgrade!');
                    return;
                }

                currentSpeedMult += 0.5;
                localStorage.setItem('speedMultiplier', String(currentSpeedMult));
                if (speedMultEl) speedMultEl.innerText = currentSpeedMult;

                const fd = new FormData(); 
                fd.append('action','buy_upgrade'); 
                fd.append('type','speed');
                fd.append('cost', String(nextSpeedCost)); 
                
                const res = await fetch('./main.php', {method:'POST', body:fd});
                refreshBalance();
            });

            setInterval(refreshBalance, 4000);
            refreshBalance();
        })();
    </script>
</body>
</html>