<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_personnel'])) {
    header('Location: login2.php');
    exit();
}

if (!isset($_SESSION['fonction']) ||!in_array($_SESSION['fonction'], ['Gestionnaire', 'Dirigeant'])) {
    header('Location: accueil.php');
    exit();
}

require_once("connexion.php");

$filtreNom = trim($_GET['nom'] ?? '');
$filtreFonction = trim($_GET['fonction'] ?? '');

$sql = "SELECT id_personnel, nom, prenom, fonction
        FROM personnel
        WHERE (:nom IS NULL OR LOWER(nom) LIKE LOWER('%' || :nom || '%'))
          AND (:fonction IS NULL OR fonction = :fonction)
        ORDER BY id_personnel";

$stmt = oci_parse($conn, $sql);

if (!$stmt) {
    $e = oci_error($conn);
    die("Erreur préparation requête : " . htmlentities($e['message'], ENT_QUOTES));
}

$nomBind = ($filtreNom === '') ? null : $filtreNom;
$fonctionBind = ($filtreFonction === '') ? null : $filtreFonction;

oci_bind_by_name($stmt, ':nom', $nomBind);
oci_bind_by_name($stmt, ':fonction', $fonctionBind);

$r = oci_execute($stmt);

if (!$r) {
    $e = oci_error($stmt);
    die("Erreur exécution requête : " . htmlentities($e['message'], ENT_QUOTES));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion du personnel</title>
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

        .top-links a,
        .btn {
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
            min-width: 200px;
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

        .actions a {
            margin-right: 8px;
            text-decoration: none;
            color: #1f4d3b;
            font-weight: bold;
        }

        .actions a.supprimer {
            color: #b42318;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Gestion du personnel</h1>

    <div class="top-links">
        <a href="accueil.php">Retour accueil</a>
        <a href="ajout_personnel.php">Ajouter un personnel</a>
    </div>

    <form method="GET">
        <div class="group">
            <label for="nom">Nom</label>
            <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars($filtreNom); ?>" placeholder="Rechercher par nom">
        </div>

        <div class="group">
            <label for="fonction">Fonction</label>
            <select name="fonction" id="fonction">
                <option value="">-- Toutes --</option>
                <option value="Gestionnaire" <?php if ($filtreFonction === 'Gestionnaire') echo 'selected'; ?>>Gestionnaire</option>
                <option value="Dirigeant" <?php if ($filtreFonction === 'Dirigeant') echo 'selected'; ?>>Dirigeant</option>
                <option value="Comptable" <?php if ($filtreFonction === 'Comptable') echo 'selected'; ?>>Comptable</option>
                <option value="Chef Soigneur" <?php if ($filtreFonction === 'Chef Soigneur') echo 'selected'; ?>>Chef Soigneur</option>
                <option value="Soigneur" <?php if ($filtreFonction === 'Soigneur') echo 'selected'; ?>>Soigneur</option>
                <option value="Vétérinaire" <?php if ($filtreFonction === 'Vétérinaire') echo 'selected'; ?>>Vétérinaire</option>
                <option value="Technicien" <?php if ($filtreFonction === 'Technicien') echo 'selected'; ?>>Technicien</option>
                <option value="Personnel Entretien" <?php if ($filtreFonction === 'Personnel Entretien') echo 'selected'; ?>>Personnel Entretien</option>
                <option value="Responsable Boutique" <?php if ($filtreFonction === 'Responsable Boutique') echo 'selected'; ?>>Responsable Boutique</option>
                <option value="Employé Boutique" <?php if ($filtreFonction === 'Employé Boutique') echo 'selected'; ?>>Employé Boutique</option>
               
            </select>
        </div>

        <button type="submit" class="btn">Filtrer</button>
        <a href="gestion_personnel.php" class="btn">Réinitialiser</a>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Fonction</th>
            <th>Actions</th>
        </tr>

        <?php while ($row = oci_fetch_assoc($stmt)): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['ID_PERSONNEL']); ?></td>
                <td><?php echo htmlspecialchars($row['NOM']); ?></td>
                <td><?php echo htmlspecialchars($row['PRENOM']); ?></td>
                <td><?php echo htmlspecialchars($row['FONCTION']); ?></td>
                <td class="actions">
                    <a href="modifier_personnel.php?id=<?php echo urlencode($row['ID_PERSONNEL']); ?>">Modifier</a>
                    <a class="supprimer" href="supprimer_personnel.php?id=<?php echo urlencode($row['ID_PERSONNEL']); ?>" onclick="return confirm('Supprimer ce personnel ?');">Supprimer</a>
                </td>
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
