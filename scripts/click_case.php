<?php
session_start();
require __DIR__ . '/sql-connect.php';

// Charger état joueurs
$etatFile = __DIR__ . '/../etat_joueurs.json';
$etat = json_decode(file_get_contents($etatFile), true);

// Le joueur courant
$current = $_SESSION["role"]; // joueur1 ou joueur2

// Vérifier que c'est son tour
if ($etat["tour"] !== $current) {
    header("Location: ../index.php");
    exit;
}

if (isset($_POST["cell"])) {
    $sql = new SqlConnect();

    // Grille adverse
    $enemy = $current === "joueur1" ? "joueur2" : "joueur1";

    $stmt = $sql->db->prepare("
        UPDATE $enemy
        SET checked = 1
        WHERE idgrid = :id
    ");
    $stmt->execute([":id" => $_POST["cell"]]);

    // changer de tour
    $etat["tour"] = ($current === "joueur1") ? "joueur2" : "joueur1";
    file_put_contents($etatFile, json_encode($etat));
}

header("Location: ../index.php");
exit;
