<?php
require __DIR__ . '/scripts/sql-connect.php';

$sql = new SqlConnect();

// matrice 10 x 10
$grid = [
    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
];

// On vide les anciennes grilles
$sql->db->exec("TRUNCATE TABLE joueur1");
$sql->db->exec("TRUNCATE TABLE joueur2");

// Prépare l'insertition pour joueur1
$insert = $sql->db->prepare(
    "INSERT INTO joueur1 (row_idx, col_idx, bateau_id, checked) 
     VALUES (:row_idx, :col_idx, :bateau_id, 0)"
);

foreach ($grid as $rowIndex => $row) {
    foreach ($row as $colIndex => $value) {
        $insert->execute([
            ':row_idx'   => $rowIndex,
            ':col_idx'   => $colIndex,
            ':bateau_id' => $value,
        ]);
    }
}

// Copie la même grille pour joueur2
$sql->db->exec("
    INSERT INTO joueur2 (row_idx, col_idx, bateau_id, checked)
    SELECT row_idx, col_idx, bateau_id, 0 FROM joueur1
");
