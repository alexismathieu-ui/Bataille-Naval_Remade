<?php
require __DIR__ . '/../scripts/sql-connect.php';


if (!isset($_SESSION["role"])) {
    header("Location: ../index.php");
    exit;
}

$sql = new SqlConnect();

$currentRole = $_SESSION["role"]; // joueur1 ou joueur2
$myTable = $currentRole === 'joueur1' ? 'joueur1' : 'joueur2';
$enemyTable = $currentRole === 'joueur1' ? 'joueur2' : 'joueur1';
$letters = range('A', 'L');
$totalRows = 12;
$totalCols = 10;

// Charger l'état des joueurs (tour)
$etatFile = __DIR__ . '/../etat_joueurs.json';
$etat = json_decode(file_get_contents($etatFile), true);

/* ------------------------------
   Fonction pour charger une grille
--------------------------------*/
function loadGrid(PDO $db, string $table): array {
    $req = $db->prepare("SELECT * FROM $table ORDER BY row_idx, col_idx");
    $req->execute();
    $cells = $req->fetchAll(PDO::FETCH_ASSOC);

    $grid = [];
    foreach ($cells as $cell) {
        $r = (int)$cell["row_idx"];
        $c = (int)$cell["col_idx"];
        $grid[$r][$c] = $cell;
    }
    return $grid;
}

$myGrid = loadGrid($sql->db, $myTable);
$enemyGrid = loadGrid($sql->db, $enemyTable);

/* ------------------------------
   Détection victoire
--------------------------------*/
$remaining = $sql->db->query("
    SELECT COUNT(*) 
    FROM $enemyTable 
    WHERE bateau_id > 0 AND checked = 0
")->fetchColumn();

$victory = ($remaining == 0);

/* ------------------------------
   Bateaux coulés
--------------------------------*/
$shipNames = [
    2 => 'Torpilleur (2 cases)',
    3 => 'Sous-marin (3 cases)',
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

/* ------------------------------
   Mise à jour des scores
--------------------------------*/
if ($victory && empty($_SESSION['victory_recorded'])) {
    $stmt = $sql->db->prepare("
        INSERT INTO scores (joueur, victoires)
        VALUES (:j, 1)
        ON DUPLICATE KEY UPDATE victoires = victoires + 1
    ");
    $stmt->execute([':j' => $currentRole]);
    $_SESSION['victory_recorded'] = true;
}

/* ------------------------------
   Charger tableau des scores
--------------------------------*/
$scoreRows = $sql->db->query("SELECT joueur, victoires FROM scores ORDER BY joueur")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Battle Ships - Partie</title>
    <style>
        body {
            background:#0b1b30;
            color:white;
            font-family: Arial, sans-serif;
            text-align:center;
        }
        h1 { margin-top:20px; }

        .turn {
            font-size:20px;
            margin-bottom:20px;
            color:#f1c40f;
        }

        .grid-container {
            display:flex;
            justify-content:center;
            gap:60px;
            margin-top:30px;
        }

        .grid {
            background:#12233f;
            border:2px solid white;
            padding:5px;
        }

        .row { display:flex; }

        .header-cell {
            width:32px;
            height:32px;
            background:#223355;
            border:1px solid #555;
            display:flex;
            align-items:center;
            justify-content:center;
            font-weight:bold;
        }

        .cell {
            width:32px;
            height:32px;
            border:1px solid #555;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .unknown { background:#1e3a5f; }
        .boat    { background:#6ab04c; }
        .hit     { background:#c0392b; }
        .miss    { background:#2980b9; }

        button.shoot {
            width:100%;
            height:100%;
            border:none;
            background:transparent;
            cursor:pointer;
            color:white;
        }
        button.shoot:disabled {
            cursor:not-allowed;
        }
    </style>
</head>
<body>

<h1>🚢 Battle Ships</h1>

<?php if ($victory): ?>
    <div class="victory-overlay">
        <div class="victory-title">🎉 VICTOIRE ! 🎉</div>
        <div class="victory-sub">
            <?= strtoupper($currentRole) ?> a remporté la partie !<br>
            Tous les bateaux adverses ont été coulés.
        </div>

        <form action="../scripts/reset_total.php" method="POST">
            <button class="victory-btn">🔄 Rejouer une partie</button>
        </form>

        <form action="../index.php" method="GET">
            <button class="victory-btn">🏠 Retour au menu</button>
        </form>
    </div>
<?php else: ?>
    <div class="turn">
        <?php if ($etat["tour"] === $currentRole): ?>
            👉 C'est TON tour !
        <?php else: ?>
            ⏳ En attente de l’adversaire…
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="grid-container">

    <!-- ======================= -->
    <!--    TA GRILLE            -->
    <!-- ======================= -->
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

    <!-- ======================= -->
    <!--    GRILLE ADVERSE       -->
    <!-- ======================= -->
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

                        if ($etat["tour"] !== $currentRole || $victory) {
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
        <tr>
            <td><?= $s["joueur"] ?></td>
            <td><?= $s["victoires"] ?></td>
        </tr>
    <?php endforeach; ?>
</table>

<form method="POST" action="../scripts/reset_total.php">
    <button style="margin-top:20px; padding:10px;">🔄 RESET</button>
</form>

</body>
</html>
