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

$filtreNature = trim($_GET['nature'] ?? '');
$filtreEnclos = trim($_GET['id_enclos'] ?? '');

$sql = "
    SELECT
        r.id_reparation,
        r.nature,
        r.libelle,
        e.id_enclos,
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
    WHERE (:nature IS NULL OR r.nature = :nature)
      AND (:id_enclos IS NULL OR e.id_enclos = :id_enclos)
    ORDER BY r.id_reparation
";

$stmt = oci_parse($conn, $sql);

$natureBind = ($filtreNature === '') ? null : $filtreNature;
$enclosBind = ($filtreEnclos === '') ? null : $filtreEnclos;

oci_bind_by_name($stmt, ":nature", $natureBind);
oci_bind_by_name($stmt, ":id_enclos", $enclosBind);

$r = oci_execute($stmt);

if (!$r) {
    $e = oci_error($stmt);
    die("Erreur exécution requête : " . htmlentities($e['message'], ENT_QUOTES));
}

$reparations = [];
while ($row = oci_fetch_assoc($stmt)) {
    $reparations[] = $row;
}

oci_free_statement($stmt);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Réparations</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7f6;
            padding: 30px;
            margin: 0;
        }
        .container {
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
        }
        h1 {
            color: #1f4d3b;
        }
        .top-links {
            margin-bottom: 20px;
        }
        .top-links a, .btn {
            display: inline-block;
            margin-right: 10px;
            padding: 10px 14px;
            background: #1f4d3b;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            border: none;
            cursor: pointer;
        }
        form {
            margin: 20px 0;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }
        .group {
            display: flex;
            flex-direction: column;
        }
        input, select {
            padding: 10px;
            min-width: 180px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 12px;
            text-align: left;
        }
        th {
            background: #eef3f1;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Liste des réparations</h1>

    <div class="top-links">
        <a href="accueil.php">Retour accueil</a>
        <a href="ajout_reparation.php">Ajouter une réparation</a>
    </div>

    <form method="GET">
        <div class="group">
            <label for="nature">Nature</label>
            <select name="nature" id="nature">
                <option value="">-- Toutes --</option>
                <option value="Petit" <?php if ($filtreNature === 'Petit') echo 'selected'; ?>>Petit</option>
                <option value="Gros" <?php if ($filtreNature === 'Gros') echo 'selected'; ?>>Gros</option>
            </select>
        </div>

        <div class="group">
            <label for="id_enclos">ID enclos</label>
            <input type="number" name="id_enclos" id="id_enclos" value="<?php echo htmlspecialchars($filtreEnclos); ?>">
        </div>

        <button type="submit" class="btn">Filtrer</button>
    </form>

    <?php if (empty($reparations)): ?>
        <p>Aucune réparation trouvée.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Nature</th>
                <th>Libellé</th>
                <th>Enclos</th>
                <th>Intervenant</th>
                <th>Détail</th>
            </tr>
            <?php foreach ($reparations as $rep): ?>
                <tr>
                    <td><?php echo htmlspecialchars($rep['ID_REPARATION']); ?></td>
                    <td><?php echo htmlspecialchars($rep['NATURE']); ?></td>
                    <td><?php echo htmlspecialchars($rep['LIBELLE']); ?></td>
                    <td><?php echo htmlspecialchars($rep['ID_ENCLOS']); ?></td>
                    <td><?php echo htmlspecialchars($rep['INTERVENANT'] ?? 'Non renseigné'); ?></td>
                    <td>
                        <a href="detail_reparation.php?id=<?php echo urlencode($rep['ID_REPARATION']); ?>">Voir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>
</body>
</html>