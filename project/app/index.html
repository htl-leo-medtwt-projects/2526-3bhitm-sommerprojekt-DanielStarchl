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

    <div id="login" class="page-frame">
    <form action="auth.php" method="POST">
        <div style="display: grid; grid-template-columns: auto;">
        <input class="input-field" type="text" name="username" placeholder="Name" required>
        <input class="input-field" type="password" name="password" placeholder="Passwort" required>
        </div>
    <button class="action-button" onclick="TheBeginning()" type="submit">Spielen</button>
    </div>
    <div id="frames-left">
        <div>
         <img class="icon" src="./Assets/indexIcon.png" alt="indexIcon">
         <h1 class="title">Index</h1>
        </div>
        <div>
        <img class="icon" src="./Assets/shopIcon.png" alt="indexIcon">
         <h1 class="title">Shop</h1>
        </div>
        <div>
        <img class="icon" src="./Assets/rebirthIcon.png" alt="indexIcon">
         <h1 class="title">Rebirth</h1>
        </div>
        
        </div>
</form>

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
            width: 50%;
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

        html, body{
            width: 100%;
            height: 100%;
            overflow: hidden;
            margin: 0;
            padding: 0;
        }
        #renderCanvas{
            width: 100%;
            height: 100%;
            touch-action: none;
        }

        #pop{
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-family: sans-serif;
            font-size: 24px;
            color: white;
            background: rgba(0,0,0,0.6);
            padding: 12px 20px;
            border-radius: 8px;
            display: none;
            z-index: 999;
        }

        #fridge{
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #end{
            color: white;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            font-size: 20px;
            align-self: center;
            margin-bottom: 10px;
        }

        #fridge2{
            position: relative;
            border-radius: 20px;
            border: 2px rgb(167, 162, 162) solid;
            width: 500px;
            height: 500px;
            background: rgba(45, 43, 43, 0.966);
            justify-self: center;
            align-self: center;
        }

        #snowflake{
            position: absolute;
            right: -50px;
            top: -50px;
            width: 100px;
            height: 100px;
            object-fit: cover;
        }

        .food-item {
            width: 60px;
            height: 60px;
            border: 2px rgb(167, 162, 162) solid;
            background-color: rgb(255, 255, 255);
            border-radius: 10px;
            margin: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        #food{
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #application{
            width: 100%;
            position: fixed;
            display: flex;
            justify-content: center;
        }

        #application img{
            width: 500px;
            height: 700px;
        }

     

        #hair{
            position: fixed;
            top: 50%;
            left: 50%;
            width: 6px;
            height: 6px;
            color: rgb(229, 224, 224);
            background-color: rgb(225, 225, 225);
            border-radius: 50%;
            border: 2px solid rgb(195, 192, 192);
        }

        #money{
            align-content: center;
            display: flex;
            position: fixed;
            top: 10px;
            left: 10px;
            border: 2px rgb(167, 162, 162) solid;
            width: 300px;
            height: 40px;
            background: rgba(45, 43, 43, 0.966);
            border-radius: 20px;
        }
        #retire{
            display: flex;
            justify-content: center;
            align-items: center;
            top: 100px;
            position: fixed;
            left: 10px;
            border: 2px rgb(167, 162, 162) solid;
            width: 200px;
            height: 40px;
            background: rgba(66, 120, 96, 0.966);
            border-radius: 20px;
        }

        #apply{
             display: flex;
            justify-content: center;
            justify-self: center;
            align-items: center;
            bottom: 10px;
            position: fixed;
            left: 50%;
            border: 2px rgb(167, 162, 162) solid;
            width: 200px;
            height: 40px;
            background: rgba(66, 120, 96, 0.966);
            border-radius: 20px;
        }


        #money img{
            margin-left: 20px;
            align-self: center;
            width: 30px;
            height: 30px;
            object-fit: cover;
            border-radius: 10px;
        }

        #money h2{
            color: white;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
            font-size: 20px;
            margin-left: 10px;
            align-self: center;
        }
    </style>
    <script>
        function closeFridge() {
        document.getElementById("fridge").style.display = "none";
        opeen = false;
    }
        function TheBeginning() {
            document.getElementById("login").style.display = "none";
            document.getElementById("apply").style.display = "none";
        }
        
        function TheEnd() {
            if (balance >= 20) {
                balance -= 20;
                document.getElementById("balance").innerText = `Balance: ${balance}`;
                document.getElementById("applyimg").src = "./Assets/retired.webp";
                document.getElementById("application").style.display = "block";
            }
        }
    </script>
</body>
</html>