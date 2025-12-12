<?php
session_start();
require __DIR__ . '/../scripts/sql-connect.php';

$sql = new SqlConnect();

$currentRole = $_SESSION["role"] ?? null;
if (!$currentRole) {
    header("Location: ../index.php");
    exit;
}

$table = $currentRole;

$boats = [
    ["id" => 5, "name" => "Porte-avions", "size" => 5],
    ["id" => 4, "name" => "Croiseur",     "size" => 4],
    ["id" => 3, "name" => "Destroyer #1", "size" => 3],
    ["id" => 8, "name" => "Destroyer #2", "size" => 3], // id différent
    ["id" => 2, "name" => "Sous-marin",   "size" => 2],
];

$count = $sql->db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
$ready = $count >= 17;

// Si le joueur a fini :
if (isset($_POST["ready"])) {

    $etat = json_decode(file_get_contents(__DIR__ . '/../etat_joueurs.json'), true);

    if ($_SESSION["role"] === "joueur1") {
        $etat["j1_ready"] = true;
    } else {
        $etat["j2_ready"] = true;
    }

    file_put_contents(__DIR__ . '/../etat_joueurs.json', json_encode($etat));

    header("Location: wait.php");
    exit;
}

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Placement des bateaux</title>
<link rel="stylesheet" href="/css/base.css">
<link rel="stylesheet" href="/css/grid.css">
<link rel="stylesheet" href="/css/buttons.css">
<link rel="stylesheet" href="/css/victory.css">
<link rel="stylesheet" href="/css/placement.css">
<script>
// Gestion du placement des bateaux
let selectedBoat = null;
let selectedSize = 0;
let orientation = "horizontal";

/* -------- Affichage toast -------- */
function showToast(message) {
    let toast = document.getElementById("toast");
    if (!toast) return; // <-- empêche tout crash

    toast.textContent = message;
    toast.classList.add("show");

    setTimeout(() => {
        toast.classList.remove("show");
    }, 1500);
}

/* ------- SONS ------- */
const soundSelect = new Audio("/assets/sounds/select.mp3");
const soundPlace  = new Audio("/assets/sounds/place.mp3");
const soundError  = new Audio("/assets/sounds/error.mp3");

// Volume doux (important)
soundSelect.volume = 0.4;
soundPlace.volume  = 0.5;
soundError.volume  = 0.4;

/* -------- Sélection bateau -------- */
function selectBoat(id, size) {
    selectedBoat = id;
    selectedSize = size;
    soundSelect.play();
    showToast("Bateau sélectionné");
}

/* -------- Orientation -------- */
function setOrientation(o) {
    orientation = o;
    document.getElementById("btn-h").classList.toggle("orientation-selected", o === "horizontal");
    document.getElementById("btn-v").classList.toggle("orientation-selected", o === "vertical");
}

/* -------- Placement bateau -------- */
function placeBoat(r, c) {
    if (!selectedBoat) {
        soundError.play();
        showToast("Choisis un bateau d’abord");
        return;
    }

    fetch("../scripts/save_boat.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
            boat: selectedBoat,
            size: selectedSize,
            row: r,
            col: c,
            orientation: orientation
        })
    })
    .then(res => res.text())
    .then(txt => {
        if (txt.toLowerCase().includes("erreur")) {
            soundError.play();
        } else {
            soundPlace.play();
        }
        showToast(txt);
        setTimeout(() => location.reload(), 400);
    });
}

/* -------- Gestion de la grille -------- */
let gridCells = [];

window.onload = () => {
    gridCells = Array.from(document.querySelectorAll(".cell"));
};

/* -------- Effacer la preview -------- */
function clearPreview() {
    gridCells.forEach(c =>
        c.classList.remove("preview-ok", "preview-bad")
    );
}

/* -------- Preview bateau -------- */
function previewBoat(r, c) {
    clearPreview();
    if (!selectedBoat) return;

    let ok = true;
    let targets = [];

    for (let i = 0; i < selectedSize; i++) {
        let rr = orientation === "vertical" ? r + i : r;
        let cc = orientation === "horizontal" ? c + i : c;

        if (rr >= 10 || cc >= 10) {
            ok = false;
            continue;
        }

        let index = rr * 10 + cc;
        let cell = gridCells[index];

        if (!cell || cell.classList.contains("boat")) ok = false;

        targets.push(cell);
    }

    targets.forEach(cell =>
        cell.classList.add(ok ? "preview-ok" : "preview-bad")
    );
}
</script>

</head>

<body>
<h1>Placement des bateaux - <?= htmlspecialchars($currentRole) ?></h1>

<?php
// Récupérer les bateaux déjà placés
$placed = [];
$q = $sql->db->query("SELECT DISTINCT bateau_id FROM $table WHERE bateau_id > 0");
while ($row = $q->fetchColumn()) {
    $placed[] = intval($row);
}
?>

<h2>Bateaux à placer</h2>

<div class="boats-list">
<?php foreach ($boats as $b): 
    $isPlaced = in_array($b["id"], $placed);
?>
    <button 
        onclick="selectBoat(<?= $b['id'] ?>, <?= $b['size'] ?>)"
        <?= $isPlaced ? "disabled" : "" ?>
        style="margin:5px; padding:10px; background:<?= $isPlaced ? '#555' : '#27ae60' ?>; color:white;">
        <?= $b['name'] ?> (<?= $b['size'] ?> cases)
        <?= $isPlaced ? "✔" : "" ?>
    </button>
<?php endforeach; ?>
</div>

<h2>Orientation</h2>
<button id="btn-h" class="orientation-btn orientation-selected"
        onclick="setOrientation('horizontal')">
    ➖ Horizontal
</button>

<button id="btn-v" class="orientation-btn"
        onclick="setOrientation('vertical')">
    ↕ Vertical
</button>

<button id="soundToggle" class="sound-btn" onclick="toggleSound()">
    🔊 Son ON
</button>



<h2>Cliquez sur la grille pour placer</h2>

<div class="grid-container">
    <div class="grid">

        <!-- Ligne des numéros 0 à 9 -->
        <div class="row">
            <div class="header-cell"></div>
            <?php for ($c=0; $c<10; $c++): ?>
                <div class="header-cell"><?= $c ?></div>
            <?php endfor; ?>
        </div>

        <!-- Grille + lettres A–J -->
        <?php 
        $letters = range('A','J');
        for ($r=0; $r<10; $r++): ?>
            <div class="row">
                <div class="header-cell"><?= $letters[$r] ?></div>

                <?php for ($c=0; $c<10; $c++): 
                    $cell = $sql->db->query("SELECT bateau_id FROM $table WHERE row_idx=$r AND col_idx=$c")->fetchColumn();
                    $class = $cell > 0 ? "boat" : "";
                ?>
                    <div class="cell <?= $class ?>"
                        onmouseover="previewBoat(<?= $r ?>, <?= $c ?>)"
                        onmouseout="clearPreview()"
                        onclick="placeBoat(<?= $r ?>, <?= $c ?>)">
                    </div>
                <?php endfor; ?>
            </div>
        <?php endfor; ?>
    </div>
</div>

<?php if ($ready): ?>
    <form method="POST">
        <button type="submit" class="btn-ready" name="ready">✔ J’ai fini de placer mes bateaux</button>
    </form>
<?php endif; ?>
<div id="toast"></div>

</body>
</html>
