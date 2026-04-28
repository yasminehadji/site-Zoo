<?php
session_start();

if (!isset($_SESSION['id_personnel'])) {
    header("Location: login2.php");
    exit;
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Animal introuvable.");
}


require_once("connexion.php");

$idRecherche = $_GET['id'];

/* Récupérer l'animal  */

$sqlAnimal = "SELECT 
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
              WHERE a.id_animal = :id_animal";

$stidAnimal = oci_parse($conn, $sqlAnimal);

if (!$stidAnimal) {
    $e = oci_error($conn);
    die("Erreur préparation requête animal : " . htmlentities($e['message'], ENT_QUOTES));
}

oci_bind_by_name($stidAnimal, ":id_animal", $idRecherche);

$rAnimal = oci_execute($stidAnimal);

if (!$rAnimal) {
    $e = oci_error($stidAnimal);
    die("Erreur exécution requête animal : " . htmlentities($e['message'], ENT_QUOTES));
}

$animalTrouve = oci_fetch_array($stidAnimal, OCI_ASSOC + OCI_RETURN_NULLS);

if ($animalTrouve === false) {
    oci_free_statement($stidAnimal);
    oci_close($conn);
    die("Aucun animal correspondant.");
}

/* Récupérer les soins de cet animal*/
$sqlSoins = "SELECT 
                sg.date_intervention,
                s.type_soin,
                s.libelle,
                p.prenom || ' ' || p.nom AS personnel,
                sg.est_attitre
             FROM soigner sg
             JOIN soin s ON sg.id_soin = s.id_soin
             JOIN personnel p ON sg.id_personnel = p.id_personnel
             WHERE sg.id_animal = :id_animal
             ORDER BY sg.date_intervention DESC";

$stidSoins = oci_parse($conn, $sqlSoins);

if (!$stidSoins) {
    $e = oci_error($conn);
    die("Erreur préparation requête soins : " . htmlentities($e['message'], ENT_QUOTES));
}

oci_bind_by_name($stidSoins, ":id_animal", $idRecherche);

$rSoins = oci_execute($stidSoins);

if (!$rSoins) {
    $e = oci_error($stidSoins);
    die("Erreur exécution requête soins : " . htmlentities($e['message'], ENT_QUOTES));
}

$soinsAnimal = [];

while ($row = oci_fetch_array($stidSoins, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $soinsAnimal[] = $row;
}

oci_free_statement($stidAnimal);
oci_free_statement($stidSoins);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail animal</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .page-container {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 900px;
        }

        .top-links {
            margin-bottom: 20px;
        }

        .top-links a {
            margin-right: 15px;
        }

        .btn {
            display: inline-block;
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #f7f7f7;
            text-decoration: none;
            color: black;
            margin: 10px 0 15px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f4f7f6;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <h1>Fiche de l'animal</h1>

        <div class="top-links">
            <a href="animaux.php">Retour à la liste des animaux</a>
            <a href="soins.php">Voir tous les soins</a>
            <a href="accueil.php">Retour à l'accueil</a>
            <a href="logout.php">Se déconnecter</a>
        </div>

        <p><strong>ID :</strong> <?php echo htmlspecialchars($animalTrouve['ID_ANIMAL']); ?></p>
        <p><strong>Nom :</strong> <?php echo htmlspecialchars($animalTrouve['NOM']); ?></p>
        <p><strong>Poids :</strong> <?php echo htmlspecialchars($animalTrouve['POIDS']); ?> kg</p>
        <p><strong>Date de naissance :</strong> <?php echo htmlspecialchars($animalTrouve['DATE_NAISSANCE']); ?></p>
        <p><strong>Régime alimentaire :</strong> <?php echo htmlspecialchars($animalTrouve['REGIME_ALIMENTAIRE']); ?></p>
        <p><strong>Espèce :</strong> <?php echo htmlspecialchars($animalTrouve['ESPECE']); ?></p>
        <p><strong>Enclos :</strong> <?php echo htmlspecialchars($animalTrouve['ID_ENCLOS']); ?></p>
        <p><strong>Zone :</strong> <?php echo htmlspecialchars($animalTrouve['NOM_ZONE']); ?></p>

        <?php
        $rolesAutorisesSoins = ["Chef Soigneur", "Soigneur", "Vétérinaire"];

        if (in_array($_SESSION['fonction'], $rolesAutorisesSoins)) {
            echo '<a href="ajout_soin.php?id=' . urlencode($animalTrouve['ID_ANIMAL']) . '" class="btn">Ajouter un soin pour cet animal</a>';
        }
        ?>

        <h2>Soins de cet animal</h2>

        <?php if (empty($soinsAnimal)): ?>
            <p>Aucun soin enregistré.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Date</th>
                    <th>Type de soin</th>
                    <th>Libellé</th>
                    <th>Personnel</th>
                    <th>Attitré</th>
                </tr>

                <?php foreach ($soinsAnimal as $s): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['DATE_INTERVENTION']); ?></td>
                        <td><?php echo htmlspecialchars($s['TYPE_SOIN']); ?></td>
                        <td><?php echo htmlspecialchars($s['LIBELLE']); ?></td>
                        <td><?php echo htmlspecialchars($s['PERSONNEL']); ?></td>
                        <td><?php echo $s['EST_ATTITRE'] == 1 ? 'Oui' : 'Non'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
