<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_personnel'])) {
    header("Location: login2.php");
    exit();
}

require_once("connexion.php");

$id_enclos = trim($_GET['id'] ?? '');

if ($id_enclos === '') {
    die("ID enclos manquant.");
}

//Détails de l'enclos

$sqlEnclos = "SELECT 
                e.id_enclos,
                e.latitude,
                e.longitude,
                e.surface,
                z.nom_zone
              FROM enclos e
              LEFT JOIN zone z ON e.id_zone = z.id_zone
              WHERE e.id_enclos = :id_enclos";

$stmtEnclos = oci_parse($conn, $sqlEnclos);
oci_bind_by_name($stmtEnclos, ":id_enclos", $id_enclos);
$rEnclos = oci_execute($stmtEnclos);

if (!$rEnclos) {
    $e = oci_error($stmtEnclos);
    die("Erreur requête enclos : " . htmlentities($e['message'], ENT_QUOTES));
}

$enclos = oci_fetch_assoc($stmtEnclos);
oci_free_statement($stmtEnclos);

if (!$enclos) {
    oci_close($conn);
    die("Enclos introuvable.");
}


// Particularités de l'enclos
$particularites = [];

$sqlPart = "SELECT p.nom_particularite
            FROM possede po
            JOIN particularite p ON po.id_particularite = p.id_particularite
            WHERE po.id_enclos = :id_enclos
            ORDER BY p.nom_particularite";

$stmtPart = oci_parse($conn, $sqlPart);
oci_bind_by_name($stmtPart, ":id_enclos", $id_enclos);
$rPart = oci_execute($stmtPart);

if (!$rPart) {
    $e = oci_error($stmtPart);
    die("Erreur requête particularités : " . htmlentities($e['message'], ENT_QUOTES));
}

while ($row = oci_fetch_assoc($stmtPart)) {
    $particularites[] = $row;
}
oci_free_statement($stmtPart);

//Animaux présents dans l'enclos
$animaux = [];

$sqlAnimaux = "SELECT 
                 a.id_animal,
                 a.nom,
                 a.poids,
                 a.regime_alimentaire,
                 es.nom_usuel AS espece
               FROM animal a
               LEFT JOIN espece es ON a.id_espece = es.id_espece
               WHERE a.id_enclos = :id_enclos
               ORDER BY a.nom";

$stmtAnimaux = oci_parse($conn, $sqlAnimaux);
oci_bind_by_name($stmtAnimaux, ":id_enclos", $id_enclos);
$rAnimaux = oci_execute($stmtAnimaux);

if (!$rAnimaux) {
    $e = oci_error($stmtAnimaux);
    die("Erreur requête animaux : " . htmlentities($e['message'], ENT_QUOTES));
}

while ($row = oci_fetch_assoc($stmtAnimaux)) {
    $animaux[] = $row;
}
oci_free_statement($stmtAnimaux);

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail de l'enclos</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .detail-grid {
            display: grid;
            gap: 20px;
        }

        .card {
            background: white;
            padding: 22px;
            border-radius: 16px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
            margin-top: 20px;
        }

        .card h2 {
            margin-top: 0;
            color: #1f4d3b;
        }

        .info-line {
            margin: 10px 0;
            font-size: 1rem;
        }

        ul {
            padding-left: 20px;
        }

        li {
            margin-bottom: 8px;
        }

        .top-links {
            margin-bottom: 20px;
        }

        .top-links a {
            margin-right: 12px;
        }

        table {
            margin-top: 12px;
        }
    </style>
</head>
<body>
<div class="page-container">
    <h1>Détail de l'enclos <?php echo htmlspecialchars($enclos['ID_ENCLOS']); ?></h1>

    <div class="top-links">
        <a href="accueil.php" class="btn btn-light">← Retour accueil</a>
        <a href="enclos.php" class="btn btn-outline">Retour aux enclos</a>
    </div>

    <div class="detail-grid">
        <div class="card">
            <h2>Informations générales</h2>
            <p class="info-line"><strong>ID enclos :</strong> <?php echo htmlspecialchars($enclos['ID_ENCLOS']); ?></p>
            <p class="info-line"><strong>Zone :</strong> <?php echo htmlspecialchars($enclos['NOM_ZONE'] ?? 'Non renseignée'); ?></p>
            <p class="info-line"><strong>Latitude :</strong> <?php echo htmlspecialchars($enclos['LATITUDE']); ?></p>
            <p class="info-line"><strong>Longitude :</strong> <?php echo htmlspecialchars($enclos['LONGITUDE']); ?></p>
            <p class="info-line"><strong>Surface :</strong> <?php echo htmlspecialchars($enclos['SURFACE']); ?> m²</p>
        </div>

        <div class="card">
            <h2>Particularités</h2>

            <?php if (empty($particularites)): ?>
                <p>Aucune particularité enregistrée pour cet enclos.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($particularites as $p): ?>
                        <li><?php echo htmlspecialchars($p['NOM_PARTICULARITE']); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Animaux présents</h2>

            <?php if (empty($animaux)): ?>
                <p>Aucun animal dans cet enclos.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>ID animal</th>
                        <th>Nom</th>
                        <th>Espèce</th>
                        <th>Poids</th>
                        <th>Régime alimentaire</th>
                        <th>Détail</th>
                    </tr>

                    <?php foreach ($animaux as $a): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($a['ID_ANIMAL']); ?></td>
                            <td><?php echo htmlspecialchars($a['NOM']); ?></td>
                            <td><?php echo htmlspecialchars($a['ESPECE'] ?? 'Non renseignée'); ?></td>
                            <td><?php echo htmlspecialchars($a['POIDS']); ?> kg</td>
                            <td><?php echo htmlspecialchars($a['REGIME_ALIMENTAIRE']); ?></td>
                            <td>
                                <a href="animal_detail.php?id=<?php echo urlencode($a['ID_ANIMAL']); ?>">Voir</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
