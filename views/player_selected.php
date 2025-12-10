<?php
require __DIR__ . '/../scripts/save_state.php';

$fichierEtat = __DIR__ . '/../etat_joueurs.json';
$etat = json_decode(file_get_contents($fichierEtat), true);

// Devenir Joueur 1
if (isset($_POST["joueur1"]) && $etat["j1"] === null) {
    $etat["j1"] = session_id();
    $_SESSION["role"] = "joueur1";
    save_state($fichierEtat, $etat);
}

// Devenir Joueur 2
if (isset($_POST["joueur2"]) && $etat["j2"] === null) {
    $etat["j2"] = session_id();
    $_SESSION["role"] = "joueur2";
    save_state($fichierEtat, $etat);
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Battle Ships Crews - Choix des joueurs</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background:#0b1b30;
            color:#ffffff;
            text-align:center;
        }
        h1 { margin-top:40px; }
        .status {
            margin:20px auto;
            padding:10px 20px;
            background:#12233f;
            border-radius:8px;
            width:300px;
        }
        button {
            padding:10px 20px;
            margin:10px;
            border:none;
            border-radius:6px;
            cursor:pointer;
            font-size:16px;
        }
        button[disabled] {
            opacity:0.5;
            cursor:not-allowed;
        }
        .btn-j1 { background:#3498db; color:#fff; }
        .btn-j2 { background:#e67e22; color:#fff; }
    </style>
</head>
<body>
    <h1>🚢 Battle Ships Crews</h1>
    <p>Ouvre cette page dans deux navigateurs différents.<br>
       Chaque personne clique pour devenir Joueur 1 ou Joueur 2.</p>

    <div class="status">
        <p>Joueur 1 : <?= $etat["j1"] ? "🟢 Occupé" : "🔴 Libre" ?></p>
        <p>Joueur 2 : <?= $etat["j2"] ? "🟢 Occupé" : "🔴 Libre" ?></p>
    </div>

    <form method="post">
        <button class="btn-j1" type="submit" name="joueur1"
            <?= $etat["j1"] !== null ? "disabled" : "" ?>>
            🎮 Devenir Joueur 1
        </button>
        <button class="btn-j2" type="submit" name="joueur2"
            <?= $etat["j2"] !== null ? "disabled" : "" ?>>
            🎮 Devenir Joueur 2
        </button>
    </form>
</body>
</html>
