<?php
header("Refresh: 2"); // refresh auto toutes les 2 secondes
session_start();

$fichier = __DIR__ . "/etat_joueurs.json";

if (!file_exists($fichier)) {
    file_put_contents($fichier, json_encode(["j1" => null, "j2" => null]));
}

$etat = json_decode(file_get_contents($fichier), true);

// Si les deux joueurs sont connectés -> jeu
if ($etat["j1"] !== null && $etat["j2"] !== null) {
    include __DIR__ . '/views/game.php';
} else { // sinon choix des joueurs
    include __DIR__ . '/views/players-selected.php';
}
