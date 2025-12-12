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
<style>
body { background:#0b1b30; color:white; font-family:Arial; text-align:center; }
.grid { display:inline-block; margin-top:20px; }
.row { display:flex; }
.cell { width:32px; height:32px; border:1px solid #456; background:#123; cursor:pointer; }
.boat { background:#27ae60 !important; }
.preview-ok {
    background: rgba(46, 204, 113, 0.6) !important;
}

.preview-bad {
    background: rgba(231, 76, 60, 0.6) !important;
}

.orientation-btn {
    padding: 10px 20px;
    margin: 5px;
    background: #34495e;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.2s;
}

.orientation-btn:hover {
    background: #3d5a73;
}

.orientation-selected {
    background: #3498db !important;
    box-shadow: 0 0 10px #2980b9;
}


</style>

<script>
// Gestion du placement des bateaux
let selectedBoat = null;
let selectedSize = 0;
let orientation = "horizontal";

function selectBoat(id, size) {
    selectedBoat = id;
    selectedSize = size;
    alert("Bateau choisi : " + id);
}


function setOrientation(o) {
    orientation = o;
}

function placeBoat(r, c) {
    if (!selectedBoat) { alert("Choisis un bateau d’abord !"); return; }

    fetch("../scripts/save_boat.php", {
        method:"POST",
        headers:{"Content-Type":"application/json"},
        body: JSON.stringify({
            boat: selectedBoat,
            size: selectedSize,
            row: r,
            col: c,
            orientation: orientation
        })
    })
    .then(r => r.text())
    .then(txt => { alert(txt); location.reload(); });
}

// Gestion de la grille
let gridCells = [];

window.onload = () => {
    document.querySelectorAll(".cell").forEach((cell, index) => {
        gridCells.push(cell);
    });
};


// Effacer la preview
function clearPreview() {
    gridCells.forEach(c => c.classList.remove("preview-ok", "preview-bad"));
}


// Afficher la preview
function previewBoat(r, c) {
    clearPreview();

    if (!selectedBoat) return;

    let ok = true;
    let targets = [];

    for (let i = 0; i < selectedSize; i++) {
        let rr = orientation === "vertical" ? r + i : r;
        let cc = orientation === "horizontal" ? c + i : c;

        if (rr >= 10 || cc >= 10) { ok = false; continue; }

        let index = rr * 10 + cc;
        let cell = gridCells[index];

        if (cell.classList.contains("boat")) ok = false;

        targets.push(cell);
    }

    targets.forEach(cell =>
        cell.classList.add(ok ? "preview-ok" : "preview-bad")
    );
}
function setOrientation(o) {
    orientation = o;
    document.getElementById("btn-h").classList.toggle("orientation-selected", o === "horizontal");
    document.getElementById("btn-v").classList.toggle("orientation-selected", o === "vertical");
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

<div style="margin-bottom:20px;">
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


<h2>Cliquez sur la grille pour placer</h2>

<div class="grid">

    <!-- Ligne des numéros 0 à 9 -->
    <div class="row">
        <div style="width:32px;"></div>
        <?php for ($c=0; $c<10; $c++): ?>
            <div style="width:32px; text-align:center;"><?= $c ?></div>
        <?php endfor; ?>
    </div>

    <!-- Grille + lettres A–J -->
    <?php 
    $letters = range('A','J');
    for ($r=0; $r<10; $r++): ?>
        <div class="row">
            <div style="width:32px; text-align:center;"><?= $letters[$r] ?></div>

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

<?php if ($ready): ?>
    <form method="POST">
        <button type="submit" style="margin-top:20px; padding:12px; background:#3498db; color:white;" name="ready">✔ J’ai fini de placer mes bateaux</button>
    </form>
<?php endif; ?>

</body>
</html>
