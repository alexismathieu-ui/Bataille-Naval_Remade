<?php
session_start();
require __DIR__ . '/sql-connect.php';

if (isset($_POST["cell"]) && isset($_SESSION["role"])) {
    $sql = new SqlConnect();

    // On tire sur la grille de l'adversaire
    $playerTable = $_SESSION["role"] === 'joueur1' ? 'joueur2' : 'joueur1';

    $query = "
        UPDATE $playerTable
        SET checked = CASE WHEN checked = 0 THEN 1 ELSE 0 END
        WHERE idgrid = :cell
    ";

    $req = $sql->db->prepare($query);
    $req->execute([':cell' => (int)$_POST["cell"]]);
}

header("Location: ../index.php");
exit;
