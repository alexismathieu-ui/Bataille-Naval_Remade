<?php
session_start();
require __DIR__ . '/save_state.php';

$fichier = __DIR__ . '/../etat_joueurs.json';
$etat = ["j1" => null, "j2" => null];

save_state($fichier, $etat);

session_unset();
session_destroy();
