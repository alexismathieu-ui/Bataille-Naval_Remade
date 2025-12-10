<?php
require __DIR__ . '/../scripts/sql-connect.php';

$sql = new SqlConnect();

if (!isset($_SESSION["role"])) {
    header("Location: ../index.php");
    exit;
}

$currentRole = $_SESSION["role"];           // 'joueur1' ou 'joueur2'
$adversaireTable = $currentRole === 'joueur1' ? 'joueur2' : 'joueur1';

// On récupère l'état des joueurs pour l'affichage
$fichierEtat = __DIR__ . '/../etat_joueurs.json';
$etat = json_decode(file_get_contents($fichierEtat), true);

// Récupération de la grille de l'adversaire
$query = "SELECT * FROM $adversaireTable ORDER BY row_idx, col_idx";
$req = $sql->db->prepare($query);
$req->execute();
$rows = $req->fetchAll(PDO::FETCH_ASSOC);

// On transforme en matrice [row][col]
$grid = [];
foreach ($rows as $cell) {
    $r = (int)$cell["row_idx"];
    $c = (int)$cell["col_idx"];
    $grid[$r][$c] = $cell;
}

// Paramètres de la grille
$totalRows = 12;                // A à L
$totalCols = 10;                // 0 à 9
$letters   = range('A', 'L');

// Noms des bateaux
$shipNames = [
    2 => 'Torpilleur (2 cases)',
    3 => 'Sous-marin (3 cases)',
    4 => 'Croiseur (4 cases)',
    5 => 'Porte-avions (5 cases)',
];

// Détection des bateaux coulés chez l'adversaire
$coules = [];

foreach ($shipNames as $id => $name) {
    // Nombre total de cases pour ce type de bateau
    $totalStmt = $sql->db->prepare("SELECT COUNT(*) FROM $adversaireTable WHERE bateau_id = :id");
    $totalStmt->execute([':id' => $id]);
    $total = (int)$totalStmt->fetchColumn();

    if ($total === 0) {
        continue;
    }

    // Nombre de cases touchées
    $hitStmt = $sql->db->prepare("SELECT COUNT(*) FROM $adversaireTable WHERE bateau_id = :id AND checked = 1");
    $hitStmt->execute([':id' => $id]);
    $hits = (int)$hitStmt->fetchColumn();

    if ($hits === $total) {
        $coules[] = $name;
    }
}

// Vérification de la victoire : plus aucune case bateau non touchée
$remainStmt = $sql->db->query("SELECT COUNT(*) FROM $adversaireTable WHERE bateau_id > 0 AND checked = 0");
$remaining = (int)$remainStmt->fetchColumn();
$victory = ($remaining === 0);

// Mise à jour du tableau des scores (une seule fois grâce à la session)
if ($victory && empty($_SESSION['victory_recorded'])) {
    $winner = $currentRole; // 'joueur1' ou 'joueur2'

    $scoreStmt = $sql->db->prepare("
        INSERT INTO scores (joueur, victoires)
        VALUES (:joueur, 1)
        ON DUPLICATE KEY UPDATE victoires = victoires + 1
    ");
    $scoreStmt->execute([':joueur' => $winner]);

    $_SESSION['victory_recorded'] = true;
}

// Récupération du tableau des scores
$scoreRows = $sql->db->query("SELECT joueur, victoires FROM scores ORDER BY joueur")->fetchAll(PDO::FETCH_ASSOC);

$labelCourant   = $currentRole === 'joueur1' ? 'Joueur 1' : 'Joueur 2';
$labelAdversaire = $adversaireTable === 'joueur1' ? 'Joueur 1' : 'Joueur 2';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Battle Ships Crews - Partie</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background:#0b1b30;
            color:#ffffff;
            text-align:center;
        }
        h1 { margin-top:20px; }

        .info-bar {
            margin-top:10px;
        }

        .grid {
            display:inline-block;
            margin-top:20px;
            border:2px solid #ffffff;
            background:#12233f;
        }

        .row { display:flex; }

        .header-cell, .cell {
            width:32px;
            height:32px;
            display:flex;
            align-items:center;
            justify-content:center;
            border:1px solid #555;
            font-size:14px;
        }

        .header-cell {
            background:#223355;
            font-weight:bold;
        }

        .cell button {
            width:100%;
            height:100%;
            border:none;
            background:transparent;
            cursor:pointer;
        }

        .cell.unknown { background:#1e3a5f; }
        .cell.hit     { background:#c0392b; }
        .cell.miss    { background:#2980b9; }

        .cell button:disabled { cursor:default; }

        .victory {
            margin-top:20px;
            font-size:24px;
            color:#2ecc71;
        }

        .bateaux-liste {
            margin-top:15px;
        }

        .scores {
            margin-top:30px;
        }

        .scores table {
            margin:0 auto;
            border-collapse:collapse;
        }

        .scores th, .scores td {
            border:1px solid #ffffff;
            padding:6px 12px;
        }

        .status-players {
            margin-top:10px;
        }

        .reset-form {
            margin-top:20px;
        }

        button.reset-btn {
            padding:8px 16px;
            border:none;
            border-radius:6px;
            cursor:pointer;
            background:#e74c3c;
            color:#fff;
            font-size:14px;
        }
    </style>
</head>
<body>
    <h1>🚢 Battle Ships Crews</h1>

    <div class="info-bar">
        <p>Tu es : <strong><?= htmlspecialchars($labelCourant) ?></strong></p>
        <p>Tu tires sur : <strong><?= htmlspecialchars($labelAdversaire) ?></strong></p>
    </div>

    <div class="status-players">
        <p>Joueur 1 : <?= $etat["j1"] ? "🟢 Occupé" : "🔴 Libre" ?> |
           Joueur 2 : <?= $etat["j2"] ? "🟢 Occupé" : "🔴 Libre" ?></p>
    </div>

    <?php if ($victory): ?>
        <div class="victory">
            🎉 Vous avez gagné ! Tous les bateaux de l'adversaire sont coulés.
        </div>
    <?php else: ?>
        <p>Clique sur une case pour tirer.</p>
    <?php endif; ?>

    <div class="grid">
        <!-- Ligne d’en-tête des colonnes -->
        <div class="row">
            <div class="header-cell"></div>
            <?php for ($c = 0; $c < $totalCols; $c++): ?>
                <div class="header-cell"><?= $c ?></div>
            <?php endfor; ?>
        </div>

        <!-- Lignes de la grille -->
        <?php for ($r = 0; $r < $totalRows; $r++): ?>
            <div class="row">
                <div class="header-cell"><?= $letters[$r] ?></div>
                <?php for ($c = 0; $c < $totalCols; $c++):
                    $cell = $grid[$r][$c] ?? [
                        "idgrid"    => null,
                        "bateau_id" => 0,
                        "checked"   => 0,
                    ];

                    $classes = 'cell ';
                    $symbol  = '';
                    $disabled = false;

                    if ($cell["checked"]) {
                        if ($cell["bateau_id"] > 0) {
                            $classes .= 'hit';
                            $symbol = '💥';
                        } else {
                            $classes .= 'miss';
                            $symbol = '🌊';
                        }
                        $disabled = true;
                    } else {
                        $classes .= 'unknown';
                    }

                    if ($victory) {
                        $disabled = true;
                    }
                ?>
                    <div class="<?= $classes ?>">
                        <?php if ($cell["idgrid"] !== null): ?>
                            <form method="post" action="../scripts/click_case.php">
                                <input type="hidden" name="cell" value="<?= htmlspecialchars($cell["idgrid"]) ?>">
                                <button type="submit" <?= $disabled ? 'disabled' : '' ?>>
                                    <?= $symbol ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
        <?php endfor; ?>
    </div>

    <div class="bateaux-liste">
        <h2>Bateaux coulés chez l'adversaire</h2>
        <?php if (empty($coules)): ?>
            <p>Aucun bateau entièrement coulé pour l'instant.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($coules as $b): ?>
                    <li><?= htmlspecialchars($b) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="scores">
        <h2>Tableau des scores</h2>
        <table>
            <tr>
                <th>Joueur</th>
                <th>Victoires</th>
            </tr>
            <?php foreach ($scoreRows as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s["joueur"]) ?></td>
                    <td><?= (int)$s["victoires"] ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <div class="reset-form">
        <form method="post" action="../scripts/reset_total.php">
            <button class="reset-btn" type="submit" name="reset_total">
                ❌ Fin de partie (RESET)
            </button>
        </form>
    </div>
</body>
</html>
