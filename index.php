<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Refresh: 2");
session_start();

$fichier = __DIR__ . "/etat_joueurs.json";

if (!file_exists($fichier)) {
    file_put_contents($fichier, json_encode(["j1" => null, "j2" => null]));
}

$etat = json_decode(file_get_contents($fichier), true);

// Si les deux joueurs sont connectés -> afficher le jeu
if ($etat["j1"] !== null && $etat["j2"] !== null) {
    include __DIR__ . '/views/game.php';
} else {
    include __DIR__ . '/views/player_selected.php';
}
