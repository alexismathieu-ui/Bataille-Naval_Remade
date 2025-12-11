<?php
session_start();

// 1) Reset JSON
$etatFile = __DIR__ . '/../etat_joueurs.json';

file_put_contents($etatFile, json_encode([
    "j1" => null,
    "j2" => null,
    "j1_ready" => false,
    "j2_ready" => false,
    "tour" => "joueur1",
    "winner" => null
], JSON_PRETTY_PRINT));

// 2) Reset session
$_SESSION = [];
session_unset();
session_destroy();

// 3) Reset SQL : réinitialiser les grilles de jeu
require __DIR__ . '/../init_grids.php';
// 4) Redirection vers la page d'accueil
header("Location: ../index.php");
exit;