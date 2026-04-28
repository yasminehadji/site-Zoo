<?php
session_start();

if (!isset($_SESSION['id_personnel'])) {
    header("Location: login2.php");
    exit;
}

$fonction = $_SESSION['fonction'];
$rolesAutorises = ["Chef Soigneur", "Soigneur"];

if (!in_array($fonction, $rolesAutorises)) {
    die("Accès refusé.");
}


require_once("connexion.php");

// Filtres
$filtreAnimal = isset($_GET['animal']) ? trim($_GET['animal']) : "";
$filtreNourriture = isset($_GET['nourriture']) ? trim($_GET['nourriture']) : "";


$sql = "SELECT 
            nr.id_animal,
            a.nom AS nom_animal,
            p.prenom || ' ' || p.nom AS nom_personnel,
            p.fonction,
            n.type_nourriture,
            n.dose_journaliere,
            nr.date_nourrissage
        FROM nourrir nr
        JOIN animal a ON nr.id_animal = a.id_animal
        JOIN personnel p ON nr.id_personnel = p.id_personnel
        JOIN nourriture n ON nr.id_nourriture = n.id_nourriture
        WHERE (:animal IS NULL OR LOWER(a.nom) LIKE LOWER('%' || :animal || '%'))
          AND (:nourriture IS NULL OR LOWER(n.type_nourriture) LIKE LOWER('%' || :nourriture || '%'))
        ORDER BY nr.date_nourrissage DESC";

$stid = oci_parse($conn, $sql);

if (!$stid) {
    $e = oci_error($conn);
    die("Erreur préparation requête : " . htmlentities($e['message'], ENT_QUOTES));
}

$animalBind = ($filtreAnimal === "") ? null : $filtreAnimal;
$nourritureBind = ($filtreNourriture === "") ? null : $filtreNourriture;

oci_bind_by_name($stid, ":animal", $animalBind);
oci_bind_by_name($stid, ":nourriture", $nourritureBind);

$r = oci_execute($stid);

if (!$r) {
    $e = oci_error($stid);
    die("Erreur exécution requête : " . htmlentities($e['message'], ENT_QUOTES));
}

$listeNourrissage = [];

while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $listeNourrissage[] = $row;
}

oci_free_statement($stid);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Nourrissage des animaux</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .page-container {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1100px;
        }

        .top-links {
            margin-bottom: 20px;
        }

        .top-links a {
            margin-right: 15px;
        }

        .filter-form {
            margin: 20px 0;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group input {
            padding: 8px;
            min-width: 200px;
        }

        .btn {
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background: #f7f7f7;
            cursor: pointer;
            text-decoration: none;
            color: black;
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
        <h1>Historique des nourrissages</h1>

        <p>
            Connecté en tant que
            <strong><?php echo htmlspecialchars($_SESSION['prenom'] . " " . $_SESSION['nom']); ?></strong>
            (<?php echo htmlspecialchars($_SESSION['fonction']); ?>)
        </p>

        <div class="top-links">
            <a href="accueil.php">Retour à l'accueil</a>
            <a href="animaux.php">Voir les animaux</a>
            <a href="ajout_nourrissage.php">Ajouter un nourrissage</a>
            <a href="logout.php">Se déconnecter</a>
        </div>

        <form method="get" action="nourrir.php" class="filter-form">
            <div class="filter-group">
                <label for="animal">Rechercher par animal</label>
                <input type="text" name="animal" id="animal" value="<?php echo htmlspecialchars($filtreAnimal); ?>">
            </div>

            <div class="filter-group">
                <label for="nourriture">Rechercher par nourriture</label>
                <input type="text" name="nourriture" id="nourriture" value="<?php echo htmlspecialchars($filtreNourriture); ?>">
            </div>

            <div>
                <button type="submit" class="btn">Rechercher</button>
                <a href="nourrir.php" class="btn">Réinitialiser</a>
            </div>
        </form>

        <?php if (empty($listeNourrissage)): ?>
            <p>Aucun nourrissage trouvé.</p>
        <?php else: ?>
         <table>
    <tr>
        <th>Date</th>
        <th>Animal</th>
        <th>Nourriture</th>
        <th>Dose journalière</th>
        <th>Personnel</th>
        <th>Fonction</th>
        <th>Détail animal</th>
    </tr>

    <?php foreach ($listeNourrissage as $ligne): ?>
        <tr>
            <td><?php echo htmlspecialchars($ligne['DATE_NOURRISSAGE']); ?></td>
            <td><?php echo htmlspecialchars($ligne['NOM_ANIMAL']); ?></td>
            <td><?php echo htmlspecialchars($ligne['TYPE_NOURRITURE']); ?></td>
            <td><?php echo htmlspecialchars($ligne['DOSE_JOURNALIERE']); ?></td>
            <td><?php echo htmlspecialchars($ligne['NOM_PERSONNEL']); ?></td>
            <td><?php echo htmlspecialchars($ligne['FONCTION']); ?></td>
            <td>
                <a href="animal_detail.php?id=<?php echo urlencode($ligne['ID_ANIMAL']); ?>">Voir</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
        <?php endif; ?>
    </div>
</body>
</html>
