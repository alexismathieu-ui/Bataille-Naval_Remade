<?php
// Sauvegarde l'état des joueurs dans un fichier JSON
function save_state(string $file, array $data): void {
    file_put_contents($file, json_encode($data));
}
