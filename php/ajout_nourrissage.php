<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['id_personnel'])) {
    header("Location: index.php");
    exit;
}

$rolesAutorises = ["Chef Soigneur", "Soigneur"];

if (!in_array($_SESSION['fonction'], $rolesAutorises)) {
    die("Accès refusé.");
}

require_once("connexion.php");

$idAnimalPreselectionne = isset($_GET['id']) ? trim($_GET['id']) : "";
$message = "";
$erreur = "";


/* Traitement du formulaire */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_animal = trim($_POST['animal'] ?? '');
    $id_personnel = trim($_POST['personnel'] ?? '');
    $type_nourriture = trim($_POST['nourriture'] ?? '');
    $date = trim($_POST['date'] ?? '');

    if ($id_animal !== "" && $id_personnel !== "" && $type_nourriture !== "" && $date !== "") {

        $type_nourriture = ucfirst(strtolower($type_nourriture));
 
 
        /* Vérifier si la nourriture existe */
        $sqlCheck = "SELECT id_nourriture
                     FROM nourriture
                     WHERE LOWER(type_nourriture) = LOWER(:type)";

        $stidCheck = oci_parse($conn, $sqlCheck);

        if (!$stidCheck) {
            $e = oci_error($conn);
            die("Erreur préparation vérification nourriture : " . htmlentities($e['message'], ENT_QUOTES));
        }

        oci_bind_by_name($stidCheck, ":type", $type_nourriture);
        $rCheck = oci_execute($stidCheck);

        if (!$rCheck) {
            $e = oci_error($stidCheck);
            die("Erreur exécution vérification nourriture : " . htmlentities($e['message'], ENT_QUOTES));
        }

        $row = oci_fetch_assoc($stidCheck);

        if ($row) {
            $id_nourriture = $row['ID_NOURRITURE'];
        } else { 
        
            /* Créer une nouvelle nourriture */
            
            $sqlMax = "SELECT NVL(MAX(id_nourriture), 0) + 1 AS NEW_ID FROM nourriture";
            $stidMax = oci_parse($conn, $sqlMax);

            if (!$stidMax) {
                $e = oci_error($conn);
                die("Erreur préparation nouvel ID nourriture : " . htmlentities($e['message'], ENT_QUOTES));
            }

            $rMax = oci_execute($stidMax);

            if (!$rMax) {
                $e = oci_error($stidMax);
                die("Erreur exécution nouvel ID nourriture : " . htmlentities($e['message'], ENT_QUOTES));
            }

            $maxRow = oci_fetch_assoc($stidMax);
            $id_nourriture = $maxRow['NEW_ID'];

            oci_free_statement($stidMax);

            $sqlInsertNourriture = "INSERT INTO nourriture (id_nourriture, dose_journaliere, type_nourriture)
                                    VALUES (:id_nourriture, 0, :type_nourriture)";

            $stidInsertNourriture = oci_parse($conn, $sqlInsertNourriture);

            if (!$stidInsertNourriture) {
                $e = oci_error($conn);
                die("Erreur préparation insertion nourriture : " . htmlentities($e['message'], ENT_QUOTES));
            }

            oci_bind_by_name($stidInsertNourriture, ":id_nourriture", $id_nourriture);
            oci_bind_by_name($stidInsertNourriture, ":type_nourriture", $type_nourriture);

            $rInsertNourriture = oci_execute($stidInsertNourriture, OCI_COMMIT_ON_SUCCESS);

            if (!$rInsertNourriture) {
                $e = oci_error($stidInsertNourriture);
                die("Erreur insertion nourriture : " . htmlentities($e['message'], ENT_QUOTES));
            }

            oci_free_statement($stidInsertNourriture);
        }

        oci_free_statement($stidCheck);
 
        /* Insertion dans nourrir */
        $sqlInsert = "INSERT INTO nourrir (id_animal, id_personnel, id_nourriture, date_nourrissage)
                      VALUES (:id_animal, :id_personnel, :id_nourriture, TO_DATE(:date_nourrissage, 'YYYY-MM-DD'))";

        $stidInsert = oci_parse($conn, $sqlInsert);

        if (!$stidInsert) {
            $e = oci_error($conn);
            die("Erreur préparation insertion nourrissage : " . htmlentities($e['message'], ENT_QUOTES));
        }

        oci_bind_by_name($stidInsert, ":id_animal", $id_animal);
        oci_bind_by_name($stidInsert, ":id_personnel", $id_personnel);
        oci_bind_by_name($stidInsert, ":id_nourriture", $id_nourriture);
        oci_bind_by_name($stidInsert, ":date_nourrissage", $date);

        $rInsert = oci_execute($stidInsert, OCI_COMMIT_ON_SUCCESS);

        if ($rInsert) {
            $message = "Nourrissage ajouté avec succès.";
        } else {
            $e = oci_error($stidInsert);
            $erreur = "Erreur lors de l'ajout : " . htmlentities($e['message'], ENT_QUOTES);
        }

        oci_free_statement($stidInsert);
    } else {
        $erreur = "Veuillez remplir tous les champs.";
    }
}


/*  Récupérer les animaux */
$animaux = [];
$sqlAnimaux = "SELECT id_animal, nom
               FROM animal
               ORDER BY nom";

$stidAnimaux = oci_parse($conn, $sqlAnimaux);

if (!$stidAnimaux) {
    $e = oci_error($conn);
    die("Erreur préparation animaux : " . htmlentities($e['message'], ENT_QUOTES));
}

$rAnimaux = oci_execute($stidAnimaux);

if (!$rAnimaux) {
    $e = oci_error($stidAnimaux);
    die("Erreur exécution animaux : " . htmlentities($e['message'], ENT_QUOTES));
}

while ($row = oci_fetch_array($stidAnimaux, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $animaux[] = $row;
}

oci_free_statement($stidAnimaux);


/* Récupérer le personnel autorisé */
$personnelListe = [];
$sqlPersonnel = "SELECT id_personnel, nom, prenom, fonction
                 FROM personnel
                 WHERE fonction IN ('Chef Soigneur', 'Soigneur')
                 ORDER BY prenom, nom";

$stidPersonnel = oci_parse($conn, $sqlPersonnel);

if (!$stidPersonnel) {
    $e = oci_error($conn);
    die("Erreur préparation personnel : " . htmlentities($e['message'], ENT_QUOTES));
}

$rPersonnel = oci_execute($stidPersonnel);

if (!$rPersonnel) {
    $e = oci_error($stidPersonnel);
    die("Erreur exécution personnel : " . htmlentities($e['message'], ENT_QUOTES));
}

while ($row = oci_fetch_array($stidPersonnel, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $personnelListe[] = $row;
}

oci_free_statement($stidPersonnel);

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un nourrissage</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .page-container {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 650px;
        }

        .form-group {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }

        select, input[type="date"], input[type="text"] {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
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

        .top-links {
            margin-top: 20px;
        }

        .top-links a {
            margin-right: 15px;
        }

        .success {
            color: green;
            font-weight: bold;
        }

        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="page-container">
        <h1>Ajouter un nourrissage</h1>

        <?php if (!empty($message)): ?>
            <p class="success"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if (!empty($erreur)): ?>
            <p class="error"><?php echo htmlspecialchars($erreur); ?></p>
        <?php endif; ?>

        <form method="post" action="ajout_nourrissage.php<?php echo !empty($idAnimalPreselectionne) ? '?id=' . urlencode($idAnimalPreselectionne) : ''; ?>">
            <div class="form-group">
                <label for="animal">Animal :</label>
                <select name="animal" id="animal" required>
                    <?php foreach ($animaux as $a): ?>
                        <option value="<?php echo htmlspecialchars($a['ID_ANIMAL']); ?>"
                            <?php if ((string)$a['ID_ANIMAL'] === (string)$idAnimalPreselectionne) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($a['NOM'] . " (ID " . $a['ID_ANIMAL'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="personnel">Personnel :</label>
                <select name="personnel" id="personnel" required>
                    <?php foreach ($personnelListe as $p): ?>
                        <option value="<?php echo htmlspecialchars($p['ID_PERSONNEL']); ?>">
                            <?php echo htmlspecialchars($p['PRENOM'] . " " . $p['NOM'] . " - " . $p['FONCTION']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="nourriture">Type de nourriture :</label>
                <input type="text" name="nourriture" id="nourriture" placeholder="Ex : Viande" required>
            </div>

            <div class="form-group">
                <label for="date">Date de nourrissage :</label>
                <input type="date" name="date" id="date" required>
            </div>

            <button type="submit" class="btn">Ajouter</button>
        </form>

        <div class="top-links">
            <a href="nourrir.php">Retour au nourrissage</a>
            <a href="animaux.php">Retour aux animaux</a>
            <a href="accueil.php">Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>
