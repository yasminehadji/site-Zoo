<?php
session_start();

if (!isset($_SESSION['id_personnel'])) {
    header("Location: login2.php");
    exit;
}

if ($_SESSION['fonction'] !== "Gestionnaire") {
    die("Accès refusé.");
}


require_once("connexion.php");

$message = "";
$erreur = "";

// Valeurs par défaut pour réafficher le formulaire en cas d'erreur
$id_animal = "";
$nom = "";
$poids = "";
$date_naissance = "";
$regime_alimentaire = "";
$id_espece = "";
$id_enclos = "";


/* Traitement du formulaire */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_animal = trim($_POST['id_animal'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $poids = trim($_POST['poids'] ?? '');
    $date_naissance = trim($_POST['date_naissance'] ?? '');
    $regime_alimentaire = trim($_POST['regime_alimentaire'] ?? '');
    $id_espece = trim($_POST['id_espece'] ?? '');
    $id_enclos = trim($_POST['id_enclos'] ?? '');

    if (
        $id_animal === "" ||
        $nom === "" ||
        $poids === "" ||
        $date_naissance === "" ||
        $regime_alimentaire === "" ||
        $id_espece === "" ||
        $id_enclos === ""
    ) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        $sqlInsert = "INSERT INTO animal
                      (id_animal, nom, poids, date_naissance, regime_alimentaire, id_espece, id_enclos)
                      VALUES
                      (:id_animal, :nom, :poids, TO_DATE(:date_naissance, 'YYYY-MM-DD'), :regime_alimentaire, :id_espece, :id_enclos)";

        $stidInsert = oci_parse($conn, $sqlInsert);

        if (!$stidInsert) {
            $e = oci_error($conn);
            die("Erreur préparation insertion : " . htmlentities($e['message'], ENT_QUOTES));
        }

        oci_bind_by_name($stidInsert, ":id_animal", $id_animal);
        oci_bind_by_name($stidInsert, ":nom", $nom);
        oci_bind_by_name($stidInsert, ":poids", $poids);
        oci_bind_by_name($stidInsert, ":date_naissance", $date_naissance);
        oci_bind_by_name($stidInsert, ":regime_alimentaire", $regime_alimentaire);
        oci_bind_by_name($stidInsert, ":id_espece", $id_espece);
        oci_bind_by_name($stidInsert, ":id_enclos", $id_enclos);

        $rInsert = oci_execute($stidInsert, OCI_COMMIT_ON_SUCCESS);

        if ($rInsert) {
            $message = "Animal ajouté avec succès.";

            // on vide les champs après succès
            $id_animal = "";
            $nom = "";
            $poids = "";
            $date_naissance = "";
            $regime_alimentaire = "";
            $id_espece = "";
            $id_enclos = "";
        } else {
            $e = oci_error($stidInsert);
            $erreur = "Erreur lors de l'ajout : " . htmlentities($e['message'], ENT_QUOTES);
        }

        oci_free_statement($stidInsert);
    }
}


/* Liste des espèces */
$especes = [];
$sqlEspeces = "SELECT id_espece, nom_usuel
               FROM espece
               ORDER BY nom_usuel";

$stidEspeces = oci_parse($conn, $sqlEspeces);
if (!$stidEspeces) {
    $e = oci_error($conn);
    die("Erreur préparation requête espèces : " . htmlentities($e['message'], ENT_QUOTES));
}
oci_execute($stidEspeces);

while ($row = oci_fetch_array($stidEspeces, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $especes[] = $row;
}

oci_free_statement($stidEspeces);
 
 
/* Liste des enclos */
$enclos = [];
$sqlEnclos = "SELECT e.id_enclos, z.nom_zone
              FROM enclos e
              JOIN zone z ON e.id_zone = z.id_zone
              ORDER BY e.id_enclos";

$stidEnclos = oci_parse($conn, $sqlEnclos);
if (!$stidEnclos) {
    $e = oci_error($conn);
    die("Erreur préparation requête enclos : " . htmlentities($e['message'], ENT_QUOTES));
}
oci_execute($stidEnclos);

while ($row = oci_fetch_array($stidEnclos, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $enclos[] = $row;
}

oci_free_statement($stidEnclos);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un animal</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .page-container {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 700px;
        }

        .form-group {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }

        input, select {
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
        <h1>Ajouter un animal</h1>

        <p>
            Connecté en tant que
            <strong><?php echo htmlspecialchars($_SESSION['prenom'] . " " . $_SESSION['nom']); ?></strong>
            (<?php echo htmlspecialchars($_SESSION['fonction']); ?>)
        </p>

        <?php if (!empty($message)): ?>
            <p class="success"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if (!empty($erreur)): ?>
            <p class="error"><?php echo htmlspecialchars($erreur); ?></p>
        <?php endif; ?>

        <form method="post" action="ajout_animal.php">
            <div class="form-group">
                <label for="id_animal">ID animal :</label>
                <input type="number" name="id_animal" id="id_animal" value="<?php echo htmlspecialchars($id_animal); ?>" required>
            </div>

            <div class="form-group">
                <label for="nom">Nom :</label>
                <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars($nom); ?>" required>
            </div>

            <div class="form-group">
                <label for="poids">Poids :</label>
                <input type="number" step="0.01" name="poids" id="poids" value="<?php echo htmlspecialchars($poids); ?>" required>
            </div>

            <div class="form-group">
                <label for="date_naissance">Date de naissance :</label>
                <input type="date" name="date_naissance" id="date_naissance" value="<?php echo htmlspecialchars($date_naissance); ?>" required>
            </div>

            <div class="form-group">
                <label for="regime_alimentaire">Régime alimentaire :</label>
                <input type="text" name="regime_alimentaire" id="regime_alimentaire" value="<?php echo htmlspecialchars($regime_alimentaire); ?>" required>
            </div>

            <div class="form-group">
                <label for="id_espece">Espèce :</label>
                <select name="id_espece" id="id_espece" required>
                    <option value="">-- Choisir une espèce --</option>
                    <?php foreach ($especes as $e): ?>
                        <option value="<?php echo htmlspecialchars($e['ID_ESPECE']); ?>"
                            <?php if ((string)$id_espece === (string)$e['ID_ESPECE']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($e['NOM_USUEL'] . " (ID " . $e['ID_ESPECE'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="id_enclos">Enclos :</label>
                <select name="id_enclos" id="id_enclos" required>
                    <option value="">-- Choisir un enclos --</option>
                    <?php foreach ($enclos as $en): ?>
                        <option value="<?php echo htmlspecialchars($en['ID_ENCLOS']); ?>"
                            <?php if ((string)$id_enclos === (string)$en['ID_ENCLOS']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars("Enclos " . $en['ID_ENCLOS'] . " - " . $en['NOM_ZONE']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn">Ajouter l'animal</button>
        </form>

        <div class="top-links">
            <a href="animaux.php">Retour aux animaux</a>
            <a href="accueil.php">Retour à l'accueil</a>
            <a href="logout.php">Se déconnecter</a>
        </div>
    </div>
</body>
</html>
