<?php
session_start();

if (!isset($_SESSION['id_personnel'])) {
    header("Location: login2.php");
    exit;
}


require_once("connexion.php");

// Requête Oracle avec jointures
$sql = "SELECT 
            a.id_animal,
            a.nom,
            a.poids,
            a.date_naissance,
            a.regime_alimentaire,
            e.nom_usuel AS espece,
            en.id_enclos,
            z.nom_zone
        FROM animal a
        JOIN espece e ON a.id_espece = e.id_espece
        JOIN enclos en ON a.id_enclos = en.id_enclos
        JOIN zone z ON en.id_zone = z.id_zone
        ORDER BY a.id_animal";

$stid = oci_parse($conn, $sql);

if (!$stid) {
    $e = oci_error($conn);
    die("Erreur préparation requête : " . htmlentities($e['message'], ENT_QUOTES));
}

$r = oci_execute($stid);

if (!$r) {
    $e = oci_error($stid);
    die("Erreur exécution requête : " . htmlentities($e['message'], ENT_QUOTES));
}

$animaux = [];

while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $animaux[] = $row;
}

oci_free_statement($stid);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des animaux</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <div class="login-container" style="max-width: 1000px;">
        <h1>Liste des animaux</h1>

        <p>
            Connecté en tant que
            <strong><?php echo htmlspecialchars($_SESSION['prenom'] . " " . $_SESSION['nom']); ?></strong>
            (<?php echo htmlspecialchars($_SESSION['fonction']); ?>)
        </p>

        <p>
            <a href="accueil.php">Retour à l'accueil</a> |
            <a href="logout.php">Se déconnecter</a>
          <?php
if ($_SESSION['fonction'] === "Gestionnaire") {
    echo '<p><a href="ajout_animal.php" class="btn">Ajouter un animal</a></p>';
}
?>
        <table border="1" cellpadding="8" cellspacing="0" width="100%">
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Espèce</th>
                <th>Poids</th>
                <th>Date de naissance</th>
                <th>Régime</th>
                <th>Enclos</th>
                <th>Zone</th>
                <th>Détail</th>
            </tr>

            <?php foreach ($animaux as $animal): ?>
                <tr>
                    <td><?php echo htmlspecialchars($animal['ID_ANIMAL']); ?></td>
                    <td><?php echo htmlspecialchars($animal['NOM']); ?></td>
                    <td><?php echo htmlspecialchars($animal['ESPECE']); ?></td>
                    <td><?php echo htmlspecialchars($animal['POIDS']); ?> kg</td>
                    <td><?php echo htmlspecialchars($animal['DATE_NAISSANCE']); ?></td>
                    <td><?php echo htmlspecialchars($animal['REGIME_ALIMENTAIRE']); ?></td>
                    <td><?php echo htmlspecialchars($animal['ID_ENCLOS']); ?></td>
                    <td><?php echo htmlspecialchars($animal['NOM_ZONE']); ?></td>
                    <td>
                        <a href="animal_detail.php?id=<?php echo urlencode($animal['ID_ANIMAL']); ?>">
                            Voir
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
