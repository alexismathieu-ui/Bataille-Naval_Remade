<?php
session_start();
header("Content-Type: text/plain");
require __DIR__ . "/sql-connect.php";

$sql = new SqlConnect();

// Rôle du joueur
$role = $_SESSION["role"] ?? null;
if (!$role) {
    echo "Erreur : pas de rôle joueur";
    exit;
}
$table = $role;

// Récupération des données JSON
$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    echo "Erreur : données manquantes.";
    exit;
}

$boatId = intval($data["boat"]);
$size = intval($data["size"]);
$row = intval($data["row"]);
$col = intval($data["col"]);
$orientation = $data["orientation"];

// Vérification des limites
if ($orientation === "horizontal" && $col + $size > 10) {
    echo "Erreur : bateau dépasse la grille horizontalement.";
    exit;
}
if ($orientation === "vertical" && $row + $size > 12) {
    echo "Erreur : bateau dépasse la grille verticalement.";
    exit;
}

// Vérification de chevauchement
for ($i = 0; $i < $size; $i++) {
    $rr = ($orientation === "vertical") ? $row + $i : $row;
    $cc = ($orientation === "horizontal") ? $col + $i : $col;

    $existing = $sql->db->query("SELECT bateau_id FROM $table WHERE row_idx=$rr AND col_idx=$cc")->fetchColumn();
    if ($existing > 0) {
        echo "Erreur : un bateau est déjà placé ici.";
        exit;
    }
}

// Mise à jour des cases
$stmt = $sql->db->prepare("
    UPDATE $table
    SET bateau_id = :boat
    WHERE row_idx = :row AND col_idx = :col
");

for ($i = 0; $i < $size; $i++) {
    $rr = ($orientation === "vertical") ? $row + $i : $row;
    $cc = ($orientation === "horizontal") ? $col + $i : $col;

    $stmt->execute([
        ":boat" => $boatId,
        ":row"  => $rr,
        ":col"  => $cc,
    ]);
}

echo "Bateau placé avec succès !";
