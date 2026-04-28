<?php
session_start();

if (!isset($_SESSION['id_personnel'])) {
    header("Location: login2.php");
    exit;
}

// Accès autorisé : Gestionnaire et Dirigeant
if (!in_array($_SESSION['fonction'], ['Gestionnaire', 'Dirigeant'])) {
    header("Location: accueil.php");
    exit;
}

require_once("connexion.php");

// Filtres
$filtreNom = trim($_GET['nom'] ?? '');
$filtreZone = trim($_GET['zone'] ?? '');


// Récupérer les zones
$zones = [];
$stmtZones = oci_parse($conn, "SELECT id_zone, nom_zone FROM zone ORDER BY nom_zone");
oci_execute($stmtZones);
while ($row = oci_fetch_assoc($stmtZones)) {
    $zones[] = $row;
}
oci_free_statement($stmtZones);


$sql = "SELECT 
            p.id_personnel,
            p.nom,
            p.prenom,
            z.nom_zone
        FROM personnel p
        LEFT JOIN zone z ON p.id_zone = z.id_zone
        WHERE p.fonction = 'Personnel Entretien'
          AND (:nom IS NULL OR LOWER(p.nom) LIKE LOWER('%' || :nom || '%'))
          AND (:zone IS NULL OR p.id_zone = :zone)
        ORDER BY p.nom";

$stmt = oci_parse($conn, $sql);

$nomBind = ($filtreNom === '') ? null : $filtreNom;
$zoneBind = ($filtreZone === '') ? null : $filtreZone;

oci_bind_by_name($stmt, ":nom", $nomBind);
oci_bind_by_name($stmt, ":zone", $zoneBind);

$r = oci_execute($stmt);

if (!$r) {
    $e = oci_error($stmt);
    die("Erreur requête : " . htmlentities($e['message'], ENT_QUOTES));
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Personnel d'entretien</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7f6;
            padding: 30px;
        }

        .container {
            max-width: 1000px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
        }

        h1 {
            color: #1f4d3b;
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
            min-width: 200px;
        }

        .btn {
            padding: 10px 14px;
            background: #1f4d3b;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 12px;
        }

        th {
            background: #eef3f1;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Personnel d'entretien</h1>

    <a href="accueil.php" class="btn">← Retour accueil</a>

    <form method="GET">
        <div class="group">
            <label>Nom</label>
            <input type="text" name="nom" value="<?php echo htmlspecialchars($filtreNom); ?>">
        </div>

        <div class="group">
            <label>Zone</label>
            <select name="zone">
                <option value="">-- Toutes --</option>
                <?php foreach ($zones as $z): ?>
                    <option value="<?php echo $z['ID_ZONE']; ?>"
                        <?php if ($filtreZone == $z['ID_ZONE']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($z['NOM_ZONE']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn">Filtrer</button>
        <a href="personnel_entretien.php" class="btn">Réinitialiser</a>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Zone</th>
        </tr>

        <?php while ($row = oci_fetch_assoc($stmt)): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['ID_PERSONNEL']); ?></td>
                <td><?php echo htmlspecialchars($row['NOM']); ?></td>
                <td><?php echo htmlspecialchars($row['PRENOM']); ?></td>
                <td><?php echo htmlspecialchars($row['NOM_ZONE']); ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>

<?php
oci_free_statement($stmt);
oci_close($conn);
?>
