<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_personnel'])) {
    header("Location: login2.php");
    exit();
}

require_once("connexion.php");

$filtreEspece = isset($_GET['espece']) ? trim($_GET['espece']) : "";


/* Récupérer toutes les espèces */
$especes = [];
$sqlEsp = "SELECT id_espece, nom_usuel FROM espece ORDER BY nom_usuel";
$stmtEsp = oci_parse($conn, $sqlEsp);
oci_execute($stmtEsp);

while ($row = oci_fetch_assoc($stmtEsp)) {
    $especes[] = $row;
}
oci_free_statement($stmtEsp);

/* Requête cohabitation */
if ($filtreEspece === "") {
    $sql = "SELECT 
                e1.nom_usuel AS espece_source,
                e2.nom_usuel AS espece_compatible
            FROM cohabiter c
            JOIN espece e1 ON c.id_espece = e1.id_espece
            JOIN espece e2 ON c.id_espece_1 = e2.id_espece
            WHERE c.id_espece < c.id_espece_1
            ORDER BY e1.nom_usuel, e2.nom_usuel";

    $stmt = oci_parse($conn, $sql);
} else {
    $sql = "SELECT 
                e1.nom_usuel AS espece_source,
                e2.nom_usuel AS espece_compatible
            FROM cohabiter c
            JOIN espece e1 ON c.id_espece = e1.id_espece
            JOIN espece e2 ON c.id_espece_1 = e2.id_espece
            WHERE c.id_espece = :espece
            ORDER BY e1.nom_usuel, e2.nom_usuel";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ":espece", $filtreEspece);
}

$r = oci_execute($stmt);

if (!$r) {
    $e = oci_error($stmt);
    die("Erreur exécution requête : " . htmlentities($e['message'], ENT_QUOTES));
}

$cohabitations = [];

while ($row = oci_fetch_assoc($stmt)) {
    $cohabitations[] = $row;
}

oci_free_statement($stmt);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Cohabitation des espèces</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .page-container {
            background: white;
            padding: 25px;
            border-radius: 12px;
            max-width: 1000px;
            margin: auto;
        }

        h1 {
            color: #1f4d3b;
        }

        .filter-form {
            margin: 20px 0;
        }

        select {
            padding: 10px;
            min-width: 250px;
        }

        .btn {
            padding: 10px 14px;
            border-radius: 6px;
            background: #1f4d3b;
            color: white;
            border: none;
            cursor: pointer;
        }

        .btn-light {
            background: #777;
            color: white;
            text-decoration: none;
            padding: 10px 14px;
            border-radius: 6px;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        th, td {
            padding: 10px;
            border: 1px solid #ccc;
        }

        th {
            background: #f4f7f6;
        }
    </style>
</head>

<body>
<div class="page-container">

    <h1>🐾 Cohabitation des espèces</h1>

    <div style="margin-bottom:15px;">
        <a href="accueil.php" class="btn-light">← Retour accueil</a>
    </div>

    <form method="GET" class="filter-form">
        <label for="espece">Choisir une espèce :</label><br>
        <select name="espece" id="espece">
            <option value="">-- Toutes les espèces --</option>
            <?php foreach ($especes as $e): ?>
                <option value="<?php echo $e['ID_ESPECE']; ?>"
                    <?php if ($filtreEspece == $e['ID_ESPECE']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($e['NOM_USUEL']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn">Rechercher</button>
        <a href="cohabitation.php" class="btn-light">Réinitialiser</a>
    </form>

    <?php if (empty($cohabitations)): ?>
        <p>Aucune cohabitation trouvée.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Espèce</th>
                <th>Peut cohabiter avec</th>
            </tr>

            <?php foreach ($cohabitations as $c): ?>
                <tr>
                    <td><?php echo htmlspecialchars($c['ESPECE_SOURCE']); ?></td>
                    <td><?php echo htmlspecialchars($c['ESPECE_COMPATIBLE']); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

</div>
</body>
</html>
