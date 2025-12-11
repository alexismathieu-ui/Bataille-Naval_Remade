<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Refresh: 2");
session_start();

$fichier = __DIR__ . "/etat_joueurs.json";

// Création du fichier si absent
if (!file_exists($fichier)) {
    file_put_contents($fichier, json_encode([
        "j1" => null,
        "j2" => null,
        "j1_ready" => false,
        "j2_ready" => false,
        "tour" => "joueur1",
        "winner" => null
    ]));
}

$etat = json_decode(file_get_contents($fichier), true);

/* Si les deux joueurs ne sont pas encore connectés → rester sur player_selected.php */
if ($etat["j1"] === null || $etat["j2"] === null) {
    include __DIR__ . '/views/player_selected.php';
    exit;
}

/* Si les deux joueurs sont connectés mais PAS prêts → envoyer vers place-boats.php*/
if ($etat["j1_ready"] === false || $etat["j2_ready"] === false) {
    header("Location: views/place-boats.php");
    exit;
}

/* Si les deux joueurs sont prêts → lancer la partie*/
include __DIR__ . '/views/game.php';
exit;
