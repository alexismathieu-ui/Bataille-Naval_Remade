<?php
require __DIR__ . '/sql-connect.php';

$sql = new SqlConnect();

// On vide les anciennes données
$sql->db->exec("TRUNCATE TABLE joueur1");
$sql->db->exec("TRUNCATE TABLE joueur2");

// Préparer l'insert
$insert = $sql->db->prepare("
    INSERT INTO %s (row_idx, col_idx, bateau_id, checked)
    VALUES (:r, :c, 0, 0)
");

// Remplit les 2 grilles vides
foreach (["joueur1", "joueur2"] as $table) {
    for ($r = 0; $r < 12; $r++) {
        for ($c = 0; $c < 10; $c++) {
            $sql->db->query(
                "INSERT INTO $table (row_idx, col_idx, bateau_id, checked)
                 VALUES ($r, $c, 0, 0)"
            );
        }
    }
}

