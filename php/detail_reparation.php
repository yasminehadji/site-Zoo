<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_personnel'])) {
    header("Location: login2.php");
    exit();
}

if (!isset($_SESSION['fonction']) || !in_array($_SESSION['fonction'], ['Gestionnaire', 'Dirigeant', 'Technicien'])) {
    header("Location: accueil.php");
    exit();
}

require_once("connexion.php");

$id = trim($_GET['id'] ?? '');

if ($id === '') {
    die("ID réparation manquant.");
}

$sql = "
    SELECT
        r.id_reparation,
        r.nature,
        r.libelle,
        e.id_enclos,
        z.nom_zone,
        CASE
            WHEN r.nature = 'Gros' THEN (
                SELECT MIN(pr.contact)
                FROM realise re
                JOIN prestataires pr ON pr.id_prestataire = re.id_prestataire
                WHERE re.id_reparation = r.id_reparation
            )
            WHEN r.nature = 'Petit' THEN (
                SELECT MIN(p.prenom || ' ' || p.nom)
                FROM personnel_technique pt
                JOIN personnel p ON p.id_personnel = pt.id_personnel
                WHERE pt.id_reparation = r.id_reparation
            )
            ELSE 'Non renseigné'
        END AS intervenant
    FROM reparation r
    JOIN faite f ON f.id_reparation = r.id_reparation
    JOIN enclos e ON e.id_enclos = f.id_enclos
    LEFT JOIN zone z ON z.id_zone = e.id_zone
    WHERE r.id_reparation = :id
";
// j’utilise une jointure externe pour conserver la réparation même si la zone est manquante.”
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ":id", $id);
$r = oci_execute($stmt);

if (!$r) {
    $e = oci_error($stmt);
    die("Erreur exécution requête : " . htmlentities($e['message'], ENT_QUOTES));
}

$rep = oci_fetch_assoc($stmt);

if (!$rep) {
    die("Réparation introuvable.");
}

oci_free_statement($stmt);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail réparation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7f6;
            padding: 30px;
        }
        .container {
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
        }
        h1 {
            color: #1f4d3b;
        }
        p {
            font-size: 16px;
            margin: 12px 0;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            text-decoration: none;
            background: #1f4d3b;
            color: white;
            border-radius: 8px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Détail de la réparation</h1>

    <p><strong>ID réparation :</strong> <?php echo htmlspecialchars($rep['ID_REPARATION']); ?></p>
    <p><strong>Nature :</strong> <?php echo htmlspecialchars($rep['NATURE']); ?></p>
    <p><strong>Libellé :</strong> <?php echo htmlspecialchars($rep['LIBELLE']); ?></p>
    <p><strong>Enclos :</strong> <?php echo htmlspecialchars($rep['ID_ENCLOS']); ?></p>
    <p><strong>Zone :</strong> <?php echo htmlspecialchars($rep['NOM_ZONE'] ?? 'Non renseignée'); ?></p>
    <p><strong>Intervenant :</strong> <?php echo htmlspecialchars($rep['INTERVENANT'] ?? 'Non renseigné'); ?></p>

    <a href="reparations.php">Retour à la liste</a>
</div>
</body>
</html>