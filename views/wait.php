<?php
session_start();

$fichier = __DIR__ . '/../etat_joueurs.json';
$etat = json_decode(file_get_contents($fichier), true);

$role = $_SESSION["role"];

// Vérifie toutes les 2 secondes
header("Refresh: 2");

// SI les deux ont fini → envoyer vers game.php
if ($etat["j1_ready"] && $etat["j2_ready"]) {
    header("Location: game.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>En attente…</title>
<link rel="stylesheet" href="/css/base.css">
<link rel="stylesheet" href="/css/grid.css">
<link rel="stylesheet" href="/css/buttons.css">
<link rel="stylesheet" href="/css/victory.css">
<link rel="stylesheet" href="/css/placement.css">
</head>
<body>

<h1>⏳ En attente de l’autre joueur…</h1>

<?php if ($role === "joueur1"): ?>
    <p>Tu as fini ! En attente de Joueur 2…</p>
<?php else: ?>
    <p>Tu as fini ! En attente de Joueur 1…</p>
<?php endif; ?>

</body>
</html>
