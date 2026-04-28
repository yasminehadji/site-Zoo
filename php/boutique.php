<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_personnel'])) {
    header("Location: login2.php");
    exit();
}

require_once("connexion.php");

$fonction = $_SESSION['fonction'];
$erreur = "";
$success = $_GET['success'] ?? "";

$sql = "SELECT b.id_boutique, b.nom_boutique, b.id_zone, z.nom_zone
        FROM boutique b
        LEFT JOIN zone z ON b.id_zone = z.id_zone
        ORDER BY b.id_boutique";

$stmt = oci_parse($conn, $sql);
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
    <title>Gestion des boutiques</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #eef6f0, #dceee3);
            margin: 0;
            padding: 30px;
            color: #243126;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        h1 {
            color: #1f4d3b;
            margin-bottom: 20px;
        }

        .top-actions {
            margin-bottom: 20px;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            background: #1f4d3b;
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            margin-right: 10px;
            transition: 0.3s;
        }

        .btn:hover {
            background: #2d6a4f;
        }

        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .success {
            background: #e8f7ee;
            color: #1d7a46;
            border: 1px solid #b7e4c7;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            padding: 25px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #1f4d3b;
            color: white;
            padding: 14px;
            text-align: center;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
        }

        tr:nth-child(even) {
            background: #f8fbf9;
        }

        tr:hover {
            background: #eef6f0;
        }

        .btn-modifier {
            background: #d4a017;
            color: white;
            padding: 6px 10px;
            border-radius: 6px;
            text-decoration: none;
        }

        .btn-supprimer {
            background: #c0392b;
            color: white;
            padding: 6px 10px;
            border-radius: 6px;
            text-decoration: none;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Gestion des boutiques</h1>

    <div class="top-actions">
        <a href="accueil.php" class="btn">Retour accueil</a>

        <?php if ($fonction === "Gestionnaire"): ?>
            <a href="ajouter_boutique.php" class="btn">Ajouter une boutique</a>
        <?php endif; ?>
    </div>

    <?php if ($success === "ajout"): ?>
        <p class="message success">Boutique ajoutée avec succès.</p>
    <?php elseif ($success === "modif"): ?>
        <p class="message success">Boutique modifiée avec succès.</p>
    <?php elseif ($success === "supp"): ?>
        <p class="message success">Boutique supprimée avec succès.</p>
    <?php endif; ?>

    <div class="card">
        <table>
            <tr>
                <th>ID Boutique</th>
                <th>Nom Boutique</th>
                <th>ID Zone</th>
                <th>Nom Zone</th>
                <?php if ($fonction === "Gestionnaire"): ?>
                    <th>Actions</th>
                <?php endif; ?>
            </tr>

            <?php while ($row = oci_fetch_assoc($stmt)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['ID_BOUTIQUE']); ?></td>
                    <td><?php echo htmlspecialchars($row['NOM_BOUTIQUE']); ?></td>
                    <td><?php echo htmlspecialchars($row['ID_ZONE']); ?></td>
                    <td><?php echo htmlspecialchars($row['NOM_ZONE'] ?? ''); ?></td>

                    <?php if ($fonction === "Gestionnaire"): ?>
                        <td>
                            <a class="btn-modifier" href="modifier_boutique.php?id=<?php echo urlencode($row['ID_BOUTIQUE']); ?>">Modifier</a>
                            <a class="btn-supprimer" href="supprimer_boutique.php?id=<?php echo urlencode($row['ID_BOUTIQUE']); ?>" onclick="return confirm('Supprimer cette boutique ?');">Supprimer</a>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>
</body>
</html>

<?php
oci_free_statement($stmt);
oci_close($conn);
?>
