<?php
session_start();

if (!isset($_SESSION['id_personnel'])) {
    header("Location: login2.php");
    exit;
}

require_once("connexion.php");

$fonctionBrute = trim($_SESSION['fonction'] ?? '');
$fonction = mb_strtolower($fonctionBrute, 'UTF-8');

$rolesAutorises = ["chef soigneur", "soigneur", "vétérinaire", "veterinaire"];

if (!in_array($fonction, $rolesAutorises)) {
    die("Accès refusé.");
}

// Filtres
$filtreAnimal = isset($_GET['animal']) ? trim($_GET['animal']) : "";
$filtreType = isset($_GET['type']) ? trim($_GET['type']) : "";

// Types de soins
$soinsSimples = ["Vaccination", "Brossage", "Nettoyage"];
$soinsComplexes = ["Examen", "Chirurgie", "Radiographie"];

// Déterminer les types autorisés selon le rôle
if ($fonction === "chef soigneur" || $fonction === "soigneur") {
    $typesAutorises = "'" . implode("','", $soinsSimples) . "'";
} elseif ($fonction === "vétérinaire" || $fonction === "veterinaire") {
    $typesAutorises = "'" . implode("','", $soinsComplexes) . "'";
} else {
    die("Accès refusé.");
}

$sql = "SELECT 
            sg.id_animal,
            a.nom AS nom_animal,
            sg.id_personnel,
            p.prenom || ' ' || p.nom AS nom_personnel,
            p.fonction,
            s.type_soin,
            s.libelle,
            sg.date_intervention,
            sg.est_attitre
        FROM soigner sg
        JOIN animal a ON sg.id_animal = a.id_animal
        JOIN personnel p ON sg.id_personnel = p.id_personnel
        JOIN soin s ON sg.id_soin = s.id_soin
        WHERE (:animal IS NULL OR LOWER(a.nom) LIKE LOWER('%' || :animal || '%'))
          AND (:type IS NULL OR LOWER(s.type_soin) LIKE LOWER('%' || :type || '%'))
          AND s.type_soin IN ($typesAutorises)
        ORDER BY sg.date_intervention DESC";

$stid = oci_parse($conn, $sql);

if (!$stid) {
    $e = oci_error($conn);
    die("Erreur préparation requête : " . htmlentities($e['message'], ENT_QUOTES));
}

$animalBind = ($filtreAnimal === "") ? null : $filtreAnimal;
$typeBind = ($filtreType === "") ? null : $filtreType;

oci_bind_by_name($stid, ":animal", $animalBind);
oci_bind_by_name($stid, ":type", $typeBind);

$r = oci_execute($stid);

if (!$r) {
    $e = oci_error($stid);
    die("Erreur exécution requête : " . htmlentities($e['message'], ENT_QUOTES));
}

$listeSoins = [];

while ($row = oci_fetch_array($stid, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $listeSoins[] = $row;
}

oci_free_statement($stid);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Soins des animaux</title>
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
        <h1>Historique des soins</h1>

        <p>
            Connecté en tant que
            <strong><?php echo htmlspecialchars($_SESSION['prenom'] . " " . $_SESSION['nom']); ?></strong>
            (<?php echo htmlspecialchars($_SESSION['fonction']); ?>)
        </p>

        <div class="top-links">
            <a href="accueil.php">Retour à l'accueil</a>
            <a href="animaux.php">Voir les animaux</a>
            <a href="ajout_soin.php">Ajouter un soin</a>
            <a href="logout.php">Se déconnecter</a>
        </div>

        <form method="get" action="soins.php" class="filter-form">
            <div class="filter-group">
                <label for="animal">Rechercher par animal</label>
                <input type="text" name="animal" id="animal" value="<?php echo htmlspecialchars($filtreAnimal); ?>">
            </div>

            <div class="filter-group">
                <label for="type">Rechercher par type de soin</label>
                <input type="text" name="type" id="type" value="<?php echo htmlspecialchars($filtreType); ?>">
            </div>

            <div>
                <button type="submit" class="btn">Rechercher</button>
                <a href="soins.php" class="btn">Réinitialiser</a>
            </div>
        </form>

        <?php if (empty($listeSoins)): ?>
            <p>Aucun soin trouvé.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>Date</th>
                    <th>Animal</th>
                    <th>Soin</th>
                    <th>Libellé</th>
                    <th>Personnel</th>
                    <th>Fonction</th>
                    <th>Attitré</th>
                    <th>Détail animal</th>
                </tr>

                <?php foreach ($listeSoins as $ligne): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ligne['DATE_INTERVENTION']); ?></td>
                        <td><?php echo htmlspecialchars($ligne['NOM_ANIMAL']); ?></td>
                        <td><?php echo htmlspecialchars($ligne['TYPE_SOIN']); ?></td>
                        <td><?php echo htmlspecialchars($ligne['LIBELLE']); ?></td>
                        <td><?php echo htmlspecialchars($ligne['NOM_PERSONNEL']); ?></td>
                        <td><?php echo htmlspecialchars($ligne['FONCTION']); ?></td>
                        <td><?php echo $ligne['EST_ATTITRE'] == 1 ? 'Oui' : 'Non'; ?></td>
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
