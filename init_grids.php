<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require __DIR__ . '/scripts/sql-connect.php';

$sql = new SqlConnect();

// matrice 12 x 10 (lignes A à L, colonnes 0 à 9)
$grid = [
    [3, 0, 0, 0, 0, 0, 0, 2, 2, 0],
    [3, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    [3, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    [0, 0, 0, 0, 0, 2, 2, 0, 0, 0],
    [0, 0, 0, 0, 0, 5, 0, 0, 0, 4],
    [0, 0, 0, 0, 0, 5, 0, 0, 0, 4],
    [0, 0, 0, 0, 0, 5, 0, 0, 0, 4],
    [3, 3, 3, 0, 0, 5, 0, 0, 0, 4],
    [0, 0, 0, 0, 0, 5, 0, 0, 0, 0],
    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
    [0, 0, 0, 0, 5, 5, 5, 5, 5, 0],
    [0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
];

// On vide les anciennes grilles
$sql->db->exec("TRUNCATE TABLE joueur1");
$sql->db->exec("TRUNCATE TABLE joueur2");

// Prépare l'INSERT pour joueur1
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

echo "Grilles initialisées pour Joueur 1 et Joueur 2.";
