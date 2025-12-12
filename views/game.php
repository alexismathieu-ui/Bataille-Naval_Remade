<?php
require __DIR__ . '/../scripts/sql-connect.php';

if (!isset($_SESSION["role"])) {
    header("Location: ../index.php");
    exit;
}

$sql = new SqlConnect();

// Déterminer l'ordre des tables selon le rôle
$currentRole = $_SESSION["role"];
$myTable     = $currentRole === 'joueur1' ? 'joueur1' : 'joueur2';
$enemyTable  = $currentRole === 'joueur1' ? 'joueur2' : 'joueur1';

// Lettres et dimensions de la grille

$letters = range('A', 'J');
$totalRows = 10;
$totalCols = 10;

$etatFile = __DIR__ . '/../etat_joueurs.json';
$etat = json_decode(file_get_contents($etatFile), true);

/* GESTION DE LA VICTOIRE  */

// Si un gagnant a déjà été enregistré
$winnerDeclared = $etat["winner"] ?? null;
$isWinner       = ($winnerDeclared === $currentRole);

// Si pas encore déclaré → on vérifie maintenant
if ($winnerDeclared === null) {

    // Voir s'il reste des bateaux non coulés
    $remaining = $sql->db->query("
        SELECT COUNT(*) 
        FROM $enemyTable 
        WHERE bateau_id > 0 AND checked = 0
    ")->fetchColumn();

    if ($remaining == 0) {
        // Partie gagnée !
        $etat["winner"] = $currentRole;
        file_put_contents($etatFile, json_encode($etat));

        // Score
        if (empty($_SESSION["victory_recorded"])) {
            $stmt = $sql->db->prepare("
                INSERT INTO scores (joueur, victoires)
                VALUES (:j, 1)
                ON DUPLICATE KEY UPDATE victoires = victoires + 1
            ");
            $stmt->execute([':j' => $currentRole]);
            $_SESSION["victory_recorded"] = true;
        }

        $winnerDeclared = $currentRole;
        $isWinner = true;
    }
}

/* CHARGEMENT DES GRILLES */
function loadGrid(PDO $db, string $table): array {
    $req = $db->prepare("SELECT * FROM $table ORDER BY row_idx, col_idx");
    $req->execute();
    $cells = $req->fetchAll(PDO::FETCH_ASSOC);

    $grid = [];
    foreach ($cells as $cell) {
        $r = $cell["row_idx"];
        $c = $cell["col_idx"];
        $grid[$r][$c] = $cell;
    }
    return $grid;
}

$myGrid = loadGrid($sql->db, $myTable);
$enemyGrid = loadGrid($sql->db, $enemyTable);

/* BATEAUX COULÉS */
$shipNames = [
    2 => 'Torpilleur (2 cases)',
    3 => 'Sous-marin (3 cases)',
    8 => 'Sous-marin (3 cases)',
    4 => 'Croiseur (4 cases)',
    5 => 'Porte-avions (5 cases)',
];

$coules = [];
foreach ($shipNames as $id => $name) {
    $total = $sql->db->query("SELECT COUNT(*) FROM $enemyTable WHERE bateau_id = $id")->fetchColumn();
    if ($total == 0) continue;

    $hits = $sql->db->query("SELECT COUNT(*) FROM $enemyTable WHERE bateau_id = $id AND checked = 1")->fetchColumn();

    if ($hits == $total) $coules[] = $name;
}

/* SCORES */
$scoreRows = $sql->db->query("SELECT joueur, victoires FROM scores ORDER BY joueur")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Battle Ships - Partie</title>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<link rel="stylesheet" href="/css/base.css">
<link rel="stylesheet" href="/css/grid.css">
<link rel="stylesheet" href="/css/buttons.css">
<link rel="stylesheet" href="/css/victory.css">
<link rel="stylesheet" href="/css/placement.css">
</head>

<body>

<h1>🚢 Battle Ships</h1>

<?php
/* AFFICHAGE DE LA FIN DE PARTIE*/
if ($winnerDeclared !== null):
?>

<div class="victory-overlay">

    <?php if ($isWinner): ?>
        <div class="victory-title">🎉 VICTOIRE ! 🎉</div>
        <div class="victory-sub">Tu as remporté la partie !</div>
        <script>
        confetti({ particleCount: 400, spread: 100, origin:{y:0.6} });
        </script>
    <?php else: ?>
        <div class="victory-title defeat">💀 DÉFAITE 💀</div>
        <div class="victory-sub">L’adversaire a gagné…</div>
    <?php endif; ?>

    <form action="../scripts/reset_total.php" method="POST">
        <button class="victory-btn">🔄 Rejouer</button>
    </form>

    <form action="../scripts/reset_total.php" method="GET">
        <button class="victory-btn">🏠 Retour au menu</button>
    </form>

</div>

<?php
exit;
endif;
?>

<!-- TOUR ACTUEL -->
<div class="turn">
<?php if ($etat["tour"] === $currentRole): ?>
    👉 C'est TON tour !
<?php else: ?>
    ⏳ En attente de l’adversaire…
<?php endif; ?>
</div>


<!--   GRILLES DE JEU    -->

<div class="grid-container">

    <!-- TA GRILLE -->
    <div>
        <h2>Ta grille</h2>
        <div class="grid">

            <div class="row">
                <div class="header-cell"></div>
                <?php for ($c=0;$c<$totalCols;$c++): ?>
                    <div class="header-cell"><?= $c ?></div>
                <?php endfor; ?>
            </div>

            <?php for ($r=0;$r<$totalRows;$r++): ?>
                <div class="row">
                    <div class="header-cell"><?= $letters[$r] ?></div>

                    <?php for ($c=0;$c<$totalCols;$c++):
                        $cell = $myGrid[$r][$c];
                        $class = "cell";

                        if ($cell["checked"] == 1) {
                            $class .= $cell["bateau_id"] > 0 ? " hit" : " miss";
                        } else {
                            $class .= $cell["bateau_id"] > 0 ? " boat" : " unknown";
                        }
                    ?>
                        <div class="<?= $class ?>"></div>
                    <?php endfor; ?>
                </div>
            <?php endfor; ?>

        </div>
    </div>


    <!-- GRILLE ADVERSE -->
    <div>
        <h2>Grille adverse</h2>
        <div class="grid">

            <div class="row">
                <div class="header-cell"></div>
                <?php for ($c=0;$c<$totalCols;$c++): ?>
                    <div class="header-cell"><?= $c ?></div>
                <?php endfor; ?>
            </div>

            <?php for ($r=0;$r<$totalRows;$r++): ?>
                <div class="row">
                    <div class="header-cell"><?= $letters[$r] ?></div>

                    <?php for ($c=0;$c<$totalCols;$c++):
                        $cell = $enemyGrid[$r][$c];
                        $class = "cell unknown";
                        $symbol = "";
                        $disabled = false;

                        if ($cell["checked"] == 1) {
                            if ($cell["bateau_id"] > 0) {
                                $class = "cell hit";
                                $symbol = "💥";
                            } else {
                                $class = "cell miss";
                                $symbol = "🌊";
                            }
                            $disabled = true;
                        }

                        if ($etat["tour"] !== $currentRole) {
                            $disabled = true;
                        }
                    ?>
                        <div class="<?= $class ?>">
                            <form method="POST" action="../scripts/click_case.php">
                                <input type="hidden" name="cell" value="<?= $cell['idgrid'] ?>">
                                <button class="shoot" type="submit" <?= $disabled ? "disabled" : "" ?>>
                                    <?= $symbol ?>
                                </button>
                            </form>
                        </div>
                    <?php endfor; ?>

                </div>
            <?php endfor; ?>

        </div>
    </div>

</div>


<h2>Bateaux coulés</h2>
<?php if (empty($coules)): ?>
    <p>Aucun pour le moment</p>
<?php else: ?>
    <ul>
        <?php foreach ($coules as $b): ?>
            <li><?= $b ?></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>


<h2>Scores</h2>
<table border="1" style="margin:auto; padding:8px; border-collapse:collapse;">
    <tr><th>Joueur</th><th>Victoires</th></tr>
    <?php foreach ($scoreRows as $s): ?>
        <tr><td><?= $s["joueur"] ?></td><td><?= $s["victoires"] ?></td></tr>
    <?php endforeach; ?>
</table>

<form method="POST" action="../scripts/reset_total.php">
    <button style="margin-top:20px; padding:10px;">🔄 RESET</button>
</form>

</body>
</html>
