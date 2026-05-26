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
        $message = 'Logout success';
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
                $message = 'Erfolgreich wiedergeboren!';
            }
            $updateStmt->close();
        } else {
            $message = 'Nicht genug Geld für einen Rebirth!';
        }
    } else {
        $user = trim($_POST['username'] ?? '');
        $pass = trim($_POST['password'] ?? '');

        if ($user === '' || $pass === '') {
            $message = 'Bitte Benutzername und Passwort eingeben.';
        } else {
            if ($task === 'register') {
                $stmt = $conn->prepare('SELECT player_id FROM PlayerState WHERE name = ?');
                $stmt->bind_param('s', $user);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) {
                    $message = 'Benutzername bereits vergeben.';
                } else {
                    $stmt->close();
                    $stmt = $conn->prepare('INSERT INTO PlayerState (name, password, rebirths, health, isAlive, score) VALUES (?, ?, 0, 100, 1, 0)');
                    $stmt->bind_param('ss', $user, $pass);

                    if ($stmt->execute()) {
                        $_SESSION['player'] = ['name' => $user];
                        $currentUser = $user;
                        $isLoggedIn = true;
                        $message = 'Registrierung erfolgreich. Du bist jetzt angemeldet.';
                        $hideLogin = true;
                        header("Location: " . $_SERVER['PHP_SELF']);
                        exit;
                    } else {
                        $message = 'Registrierung fehlgeschlagen.';
                    }
                }
                $stmt->close();
            } else {
                $stmt = $conn->prepare('SELECT * FROM PlayerState WHERE name = ? AND password = ?');
                $stmt->bind_param('ss', $user, $pass);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    $_SESSION['player'] = $result->fetch_assoc();
                    $currentUser = $user;
                    $isLoggedIn = true;
                    $message = 'Login erfolgreich. Willkommen ' . htmlspecialchars($user) . '!';
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

$featuredProducts = [];
$standardProducts = [];
$prodQuery = "SELECT product_id, name, value, price FROM Product";
$prodResult = $conn->query($prodQuery);
if ($prodResult) {
    while ($row = $prodResult->fetch_assoc()) {
        if (count($featuredProducts) < 4) {
            $featuredProducts[] = $row;
        } else {
            $standardProducts[] = $row;
        }
    }
}

$progressPercent = min(100, max(0, ($playerData['score'] / $rebirthCost) * 100));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work till retirement</title>
    <script src="https://cdn.babylonjs.com/babylon.js"></script>
    <script src="https://cdn.babylonjs.com/loaders/babylonjs.loaders.min.js"></script>
    <script src="./js/babylon.js" defer></script>
</head>
<body>

    <?php if ($isLoggedIn): ?>
    <form id="logout-form" action="" method="POST" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <input type="hidden" name="task" value="logout">
        <button id="logout-button" type="submit">Abmelden</button>
    </form>
    <?php endif; ?>

    <div id="login" class="page-frame" style="<?php echo ($hideLogin || $isLoggedIn) ? 'display:none;' : ''; ?>">
    <form id="auth-form" action="" method="POST">
        <div style="display: grid; grid-template-columns: auto;">
            <input class="input-field" type="text" name="username" placeholder="Name" required>
            <input class="input-field" type="password" name="password" placeholder="Passwort" required>
        </div>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button class="action-button" type="submit" name="auth_action" value="login">Anmelden</button>
            <button class="action-button" type="submit" name="auth_action" value="register">Registrieren</button>
        </div>
        <div id="message" style="color: white; text-align: center; font-size: 14px; margin-top: 10px;">
            <?php echo htmlspecialchars($message); ?>
        </div>
    </form>
    </div>

    <div id="frames-left">
        <div onclick="toggleFrame('index-frame')">
         <img class="icon" src="./Assets/indexIcon.png" alt="indexIcon">
         <h1 class="title">Index</h1>
        </div>
        <div onclick="toggleFrame('shop-frame')">
        <img class="icon" src="./Assets/shopIcon.png" alt="shopIcon">
         <h1 class="title">Shop</h1>
        </div>
        <div onclick="toggleFrame('rebirth-frame')">
        <img class="icon" src="./Assets/rebirthIcon.png" alt="rebirthIcon">
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
                <span class="index-title-text">Index</span>
                <button class="close-btn" onclick="toggleFrame('index-frame')">×</button>
            </div>
            <div class="index-grid">
                <?php foreach ($mutations as $mutation): ?>
                    <div class="grid-item">
                        <div class="silhouette-placeholder"></div>
                        <span class="item-rarity"><?php echo htmlspecialchars($mutation['variant']); ?></span>
                        <span class="item-name"><?php echo htmlspecialchars($mutation['name']); ?></span>
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
                    <img class="shop-basket-icon" src="./Assets/shopIcon.png" alt="basket">
                    <span class="shop-title-text">Shop</span>
                </div>
                <button class="close-btn" onclick="toggleFrame('shop-frame')">×</button>
            </div>

            <div class="shop-shelf shelf-top">
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="shop-item purple-gradient">
                        <div class="shop-item-content">
                            <div class="shop-placeholder-art"></div>
                            <span class="shop-item-value"><?php echo htmlspecialchars($product['value']); ?></span>
                        </div>
                        <div class="shop-item-price-tag">
                            <span class="robux-icon">⏣</span> <?php echo htmlspecialchars($product['price']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="shop-shelf shelf-bottom">
                <?php foreach ($standardProducts as $product): ?>
                    <div class="shop-item green-gradient">
                        <div class="shop-item-content">
                            <div class="shop-placeholder-art item-cash-stack"></div>
                            <span class="shop-item-value">$<?php echo htmlspecialchars($product['value']); ?></span>
                        </div>
                        <div class="shop-item-price-tag">
                            <span class="robux-icon">⏣</span> <?php echo htmlspecialchars($product['price']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div id="rebirth-frame" class="rebirth-modal" style="<?php echo ($isLoggedIn && !isset($_GET['variant'])) ? 'display: flex;' : 'display: none;'; ?>">
        <div class="rebirth-main">
            <div class="rebirth-header">
                <div class="rebirth-title-container">
                    <img class="rebirth-logo-icon" src="./Assets/rebirthIcon.png" alt="rebirth logo">
                    <span class="rebirth-title-text">Rebirth</span>
                </div>
                <button class="close-btn" onclick="toggleFrame('rebirth-frame')">×</button>
            </div>

            <div class="rebirth-perks">
                <div class="perk-card">
                    <div class="perk-art money-bag-art"></div>
                    <span class="perk-value">$5K</span>
                </div>
                <div class="perk-card">
                    <div class="perk-art multiplier-art"></div>
                    <span class="perk-value green-text">x<?php echo ($playerData['rebirths'] + 2); ?></span>
                </div>
                <div class="perk-card">
                    <div class="perk-art floor-art"></div>
                    <span class="perk-value green-text">+1 Floor</span>
                </div>
            </div>

            <div class="rebirth-warning">Rebirth resets everything!</div>

            <div class="rebirth-progress-container">
                <div class="rebirth-progress-bar" style="width: <?php echo $progressPercent; ?>%;"></div>
                <span class="rebirth-progress-text">$<?php echo number_format($playerData['score'] / 1000, 0); ?>K / $<?php echo number_format($rebirthCost / 1000, 0); ?>K</span>
            </div>

            <form action="" method="POST" style="width: 100%; display: flex; justify-content: center;">
                <input type="hidden" name="task" value="rebirth">
                <button type="submit" class="rebirth-action-btn" <?php echo ($playerData['score'] < $rebirthCost) ? 'disabled' : ''; ?>>Rebirth</button>
            </form>
        </div>
    </div>

    <canvas id="renderCanvas" touch-action="none"></canvas>
    <style>
        .title{
            font-family: system-ui;
            margin-top: -28px;
            margin-left: 13px;
            font-size: 30px;
            font-weight:900;
            color: white;
  -webkit-text-stroke: 2px black;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-rendering: optimizeLegibility;
        }

        #frames-left{
            display: flex;
            background: rgba(0, 0, 0, 0);
            position: fixed;
            flex-direction: column;
            top: 50%;
            transform: translate(0,-50%);
            width: 100px;
            z-index: 999;
        }

        #frames-left div {
            cursor: pointer;
            margin-bottom: 15px;
        }

        #frames-left img{
            width: 100px;
        }

        .page-frame {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 50%;
            max-width: 400px;;
            background: rgba(0, 0, 0, 0.75);
            border: 5px solid #000;
            border-radius: 12px;
            padding: 30px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            z-index: 1001;
        }

        .icon {
            transition: transform 0.05s ease-in-out;
        }

        .icon:hover {
            transform: rotate(10deg) translateX(10px);
            transition: transform 0.05s ease-in-out;
        }

        .input-field {
            background: rgba(255, 255, 255, 0.1);
            border: 3px solid #000;
            border-radius: 8px;
            padding: 12px;
            color: white;
            font-weight: bold;
        }

        .action-button {
            display: flex;
            justify-self: center;
            align-self: center;;
            background: #55ff55;
            border: 3px solid #000;
            border-radius: 8px;
            padding: 15px;
            color: white;
            font-weight: 900;
            text-transform: uppercase;
            cursor: pointer;
        }

        #logout-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 120px;
            background: #d62828;
            border: 3px solid #000;
            border-radius: 12px;
            padding: 12px 18px;
            color: white;
            font-weight: 900;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.3);
        }

        #logout-button:hover {
            background: #b32020;
        }

        html, body{
            width: 100%;
            height: 100%;
            overflow: hidden;
            margin: 0;
            padding: 0;
            font-family: 'Arial Black', Gadget, sans-serif;
        }
        #renderCanvas{
            width: 100%;
            height: 100%;
            touch-action: none;
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

        .index-sidebar {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 60px;
            margin-right: -4px;
        }

        .tab-btn {
            font-family: inherit;
            font-weight: 900;
            font-size: 18px;
            text-decoration: none;
            color: #000;
            padding: 12px 20px;
            border: 4px solid #000;
            border-radius: 10px 0 0 10px;
            box-shadow: inset 0 -5px 0 rgba(0,0,0,0.2);
            text-align: center;
            min-width: 90px;
        }

        .normal-tab { background: #e1e1e1; }
        .golden-tab { background: #f9d342; }
        .diamond-tab { background: #5cc2f2; }

        .tab-btn.active {
            transform: scaleX(1.05);
            z-index: 2;
            border-right: none;
        }

        .index-main, .shop-main, .rebirth-main {
            background: #1c1c1c;
            border: 6px solid #000;
            border-radius: 16px;
            width: 520px;
            padding: 16px;
            box-shadow: 0 12px 24px rgba(0,0,0,0.6);
        }

        .index-header, .shop-header, .rebirth-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .shop-title-container, .rebirth-title-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .shop-basket-icon, .rebirth-logo-icon {
            width: 35px;
            height: 35px;
        }

        .index-title-text, .shop-title-text, .rebirth-title-text {
            font-size: 28px;
            color: white;
            font-weight: 900;
            -webkit-text-stroke: 1px black;
        }

        .close-btn {
            background: #ff4a4a;
            color: white;
            border: 4px solid #000;
            border-radius: 8px;
            font-size: 20px;
            font-weight: 900;
            width: 36px;
            height: 36px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: inset 0 -4px 0 rgba(0,0,0,0.3);
        }

        .index-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            background: #0b0b0b;
            padding: 12px;
            border-radius: 12px;
        }

        .grid-item {
            background: #ffffff;
            border: 4px solid #000;
            border-radius: 14px;
            aspect-ratio: 1 / 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 8px;
            position: relative;
            box-shadow: inset 0 -6px 0 rgba(0,0,0,0.15);
        }

        .silhouette-placeholder {
            width: 60px;
            height: 60px;
            background: #8e8e8e;
            clip-path: polygon(50% 0%, 80% 30%, 80% 70%, 50% 100%, 20% 70%, 20% 30%);
            margin-top: 10px;
        }

        .item-rarity {
            background: #8e8e8e;
            color: white;
            font-size: 10px;
            font-weight: 900;
            padding: 2px 8px;
            border-radius: 10px;
            text-transform: uppercase;
            border: 2px solid #000;
            position: absolute;
            top: 65px;
        }

        .item-name {
            color: #000;
            font-size: 13px;
            font-weight: 900;
            text-align: center;
            word-wrap: break-word;
            width: 100%;
            margin-bottom: 4px;
        }

        .shop-shelf {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            padding: 10px;
            border-radius: 10px;
            border: 4px solid #2d2d2d;
            margin-bottom: 10px;
        }

        .shelf-top { background: #9b5de5; }
        .shelf-bottom { background: #ffea00; grid-template-columns: repeat(3, 1fr); }

        .shop-item {
            border: 4px solid #000;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: inset 0 -5px 0 rgba(0,0,0,0.2);
        }

        .purple-gradient { background: linear-gradient(135deg, #b5179e, #7209b7); }
        .green-gradient { background: #55ff55; }

        .shop-item-content {
            padding: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-grow: 1;
            gap: 8px;
        }

        .shop-placeholder-art {
            width: 55px;
            height: 55px;
            background: #fff;
            border-radius: 8px;
            border: 2px solid #000;
        }

        .shop-item-value {
            color: #fff;
            font-size: 16px;
            font-weight: 900;
            -webkit-text-stroke: 1.5px #000;
        }

        .shelf-bottom .shop-item-value { color: #ffea00; }

        .shop-item-price-tag {
            background: #e1e1e1;
            border-top: 4px solid #000;
            text-align: center;
            font-size: 14px;
            font-weight: 900;
            padding: 4px 0;
            color: #000;
        }

        .rebirth-perks {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 12px;
        }

        .perk-card {
            background: linear-gradient(180deg, #fff275, #ffea00);
            border: 4px solid #000;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 12px;
            box-shadow: inset 0 -5px 0 rgba(0,0,0,0.2);
        }

        .perk-art {
            width: 55px;
            height: 55px;
            background-color: #fff;
            border: 3px solid #000;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .perk-value {
            font-size: 16px;
            font-weight: 900;
            color: #222;
            text-shadow: 1px 1px 0 #fff;
        }

        .green-text { color: #169b16; }

        .rebirth-warning {
            color: #df00ff;
            text-align: center;
            font-size: 16px;
            font-weight: 900;
            margin: 8px 0;
        }

        .rebirth-progress-container {
            background: #4a4a4a;
            border: 4px solid #000;
            border-radius: 6px;
            height: 32px;
            position: relative;
            overflow: hidden;
            margin-bottom: 16px;
        }

        .rebirth-progress-bar {
            background: linear-gradient(90deg, #55ff55, #a3ff00);
            height: 100%;
            transition: width 0.3s ease;
        }

        .rebirth-progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-weight: 900;
            font-size: 16px;
            -webkit-text-stroke: 1.5px #000;
        }

        .rebirth-action-btn {
            background: #b5179e;
            color: white;
            border: 4px solid #000;
            border-radius: 12px;
            padding: 12px 40px;
            font-size: 24px;
            font-weight: 900;
            cursor: pointer;
            box-shadow: inset 0 -5px 0 rgba(0,0,0,0.3);
            text-transform: uppercase;
        }

        .rebirth-action-btn:disabled {
            background: #555;
            cursor: not-allowed;
            opacity: 0.6;
        }
    </style>
    <script>
        function toggleFrame(frameId) {
            const indexFrame = document.getElementById('index-frame');
            const shopFrame = document.getElementById('shop-frame');
            const rebirthFrame = document.getElementById('rebirth-frame');
            
            const targetFrame = document.getElementById(frameId);
            const isAlreadyOpen = targetFrame && targetFrame.style.display === 'flex';

            if (indexFrame) indexFrame.style.display = 'none';
            if (shopFrame) shopFrame.style.display = 'none';
            if (rebirthFrame) rebirthFrame.style.display = 'none';

            if (targetFrame && !isAlreadyOpen) {
                targetFrame.style.display = 'flex';
            }
        }
    </script>
</body>
</html>