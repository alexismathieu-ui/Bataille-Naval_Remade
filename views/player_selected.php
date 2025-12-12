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
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/grid.css">
    <link rel="stylesheet" href="/css/buttons.css">
    <link rel="stylesheet" href="/css/victory.css">
    <link rel="stylesheet" href="/css/placement.css">
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
