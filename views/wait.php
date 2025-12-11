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
<style>
body {
    background:#0b1b30;
    color:white;
    font-family:Arial;
    text-align:center;
    padding-top:100px;
}
</style>
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
