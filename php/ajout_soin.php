<?php
session_start();

if (!isset($_SESSION['id_personnel'])) {
    header("Location: login2.php");
    exit;
}

$rolesAutorises = ["Chef Soigneur", "Soigneur", "Vétérinaire", "Veterinaire", "V‚t‚rinaire"];

if (!in_array($_SESSION['fonction'], $rolesAutorises)) {
    die("Accès refusé.");
}

require_once("connexion.php");

$message = "";
$erreur = "";
$idAnimalPreselectionne = isset($_GET['id']) ? trim($_GET['id']) : "";

$fonctionSession = trim($_SESSION['fonction']);
$estVeterinaire = (
    $fonctionSession === "Vétérinaire" ||
    $fonctionSession === "Veterinaire" ||
    $fonctionSession === "V‚t‚rinaire"
);

$estSoigneur = (
    $fonctionSession === "Chef Soigneur" ||
    $fonctionSession === "Soigneur"
);

/* =========================
   Fonction utilitaire
   ========================= */
function nettoyerTexte($texte) {
    if ($texte === null) {
        return "";
    }

    $texte = trim($texte);

    if (!mb_check_encoding($texte, 'UTF-8')) {
        $converti = @utf8_encode($texte);
        if ($converti !== false) {
            return $converti;
        }
    }

    return $texte;
}

/* Soins autorisés fixes */
$soinsSimples = [
    "Vaccination" => "Vaccination",
    "Brossage" => "Brossage",
    "Nettoyage" => "Nettoyage"
];

$soinsComplexes = [
    "Examen" => "Examen",
    "Chirurgie" => "Chirurgie",
    "Radiographie" => "Radiographie"
]; 

/* Traitement du formulaire */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_animal = trim($_POST['animal'] ?? '');
    $id_personnel = trim($_POST['personnel'] ?? '');
    $type_soin = trim($_POST['soin'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $attitre = isset($_POST['attitre']) ? 1 : 0;

    if ($id_animal !== "" && $id_personnel !== "" && $type_soin !== "" && $date !== "") {

        /* Vérification du type de soin selon le rôle connecté */
        if ($estVeterinaire && !array_key_exists($type_soin, $soinsComplexes)) {
            $erreur = "Vous ne pouvez sélectionner qu'un soin complexe.";
        }

        if ($estSoigneur && !array_key_exists($type_soin, $soinsSimples)) {
            $erreur = "Vous ne pouvez sélectionner qu'un soin simple.";
        }

        /* Vérifier que le personnel choisi correspond bien au type autorisé */
        if (empty($erreur)) {
            if ($estVeterinaire) {
                $sqlCheckPersonnel = "SELECT id_personnel
                                      FROM personnel
                                      WHERE id_personnel = :id_personnel";
            } else {
                $sqlCheckPersonnel = "SELECT id_personnel
                                      FROM personnel
                                      WHERE id_personnel = :id_personnel
                                        AND fonction IN ('Chef Soigneur', 'Soigneur')";
            }

            $stidCheckPersonnel = oci_parse($conn, $sqlCheckPersonnel);

            if (!$stidCheckPersonnel) {
                $e = oci_error($conn);
                die("Erreur préparation vérification personnel : " . htmlentities($e['message'], ENT_QUOTES));
            }

            oci_bind_by_name($stidCheckPersonnel, ":id_personnel", $id_personnel);
            $rCheckPersonnel = oci_execute($stidCheckPersonnel);

            if (!$rCheckPersonnel) {
                $e = oci_error($stidCheckPersonnel);
                die("Erreur exécution vérification personnel : " . htmlentities($e['message'], ENT_QUOTES));
            }

            $personnelValide = oci_fetch_assoc($stidCheckPersonnel);
            oci_free_statement($stidCheckPersonnel);

            if (!$personnelValide) {
                $erreur = $estVeterinaire
                    ? "Pour un soin complexe, vous devez sélectionner un vétérinaire."
                    : "Pour un soin simple, vous devez sélectionner un soigneur ou un chef soigneur.";
            }
        }

        if ($attitre == 1 && empty($erreur)) {
            $sqlCheck = "SELECT id_personnel
                         FROM soigner
                         WHERE id_animal = :id_animal
                           AND est_attitre = 1";

            $stidCheck = oci_parse($conn, $sqlCheck);

            if (!$stidCheck) {
                $e = oci_error($conn);
                die("Erreur préparation vérification : " . htmlentities($e['message'], ENT_QUOTES));
            }

            oci_bind_by_name($stidCheck, ":id_animal", $id_animal);
            $rCheck = oci_execute($stidCheck);

            if (!$rCheck) {
                $e = oci_error($stidCheck);
                die("Erreur exécution vérification : " . htmlentities($e['message'], ENT_QUOTES));
            }

            $dejaAttitre = oci_fetch_assoc($stidCheck);
            oci_free_statement($stidCheck);

            if ($dejaAttitre) {
                $erreur = "Cet animal a déjà un soigneur attitré. Vous ne pouvez pas en ajouter un deuxième.";
            }
        }

        if (empty($erreur)) {
            /* Vérifier si le soin existe déjà */
            $sqlSoin = "SELECT id_soin
                        FROM soin
                        WHERE type_soin = :type_soin";

            $stidSoin = oci_parse($conn, $sqlSoin);

            if (!$stidSoin) {
                $e = oci_error($conn);
                die("Erreur préparation recherche soin : " . htmlentities($e['message'], ENT_QUOTES));
            }

            oci_bind_by_name($stidSoin, ":type_soin", $type_soin);
            $rSoin = oci_execute($stidSoin);

            if (!$rSoin) {
                $e = oci_error($stidSoin);
                die("Erreur exécution recherche soin : " . htmlentities($e['message'], ENT_QUOTES));
            }

            $rowSoin = oci_fetch_assoc($stidSoin);
            oci_free_statement($stidSoin);

            if ($rowSoin) {
                $id_soin = $rowSoin['ID_SOIN'];
            } else {
                /* Générer un nouvel id_soin */
                $sqlNewId = "SELECT NVL(MAX(id_soin), 0) + 1 AS NEW_ID FROM soin";
                $stidNewId = oci_parse($conn, $sqlNewId);

                if (!$stidNewId) {
                    $e = oci_error($conn);
                    die("Erreur préparation nouvel id soin : " . htmlentities($e['message'], ENT_QUOTES));
                }

                $rNewId = oci_execute($stidNewId);

                if (!$rNewId) {
                    $e = oci_error($stidNewId);
                    die("Erreur exécution nouvel id soin : " . htmlentities($e['message'], ENT_QUOTES));
                }

                $rowNewId = oci_fetch_assoc($stidNewId);
                oci_free_statement($stidNewId);

                $id_soin = $rowNewId['NEW_ID'];
                $libelle = $type_soin;

                /* Insérer le soin dans la table soin */
                $sqlInsertSoin = "INSERT INTO soin (id_soin, type_soin, libelle)
                                  VALUES (:id_soin, :type_soin, :libelle)";

                $stidInsertSoin = oci_parse($conn, $sqlInsertSoin);

                if (!$stidInsertSoin) {
                    $e = oci_error($conn);
                    die("Erreur préparation insertion soin : " . htmlentities($e['message'], ENT_QUOTES));
                }

                oci_bind_by_name($stidInsertSoin, ":id_soin", $id_soin);
                oci_bind_by_name($stidInsertSoin, ":type_soin", $type_soin);
                oci_bind_by_name($stidInsertSoin, ":libelle", $libelle);

                $rInsertSoin = oci_execute($stidInsertSoin, OCI_NO_AUTO_COMMIT);

                if (!$rInsertSoin) {
                    $e = oci_error($stidInsertSoin);
                    oci_rollback($conn);
                    die("Erreur insertion nouveau soin : " . htmlentities($e['message'], ENT_QUOTES));
                }

                oci_free_statement($stidInsertSoin);
            }

            $sqlInsert = "INSERT INTO soigner (id_animal, id_personnel, id_soin, date_intervention, est_attitre)
                          VALUES (:id_animal, :id_personnel, :id_soin, TO_DATE(:date_intervention, 'YYYY-MM-DD'), :est_attitre)";

            $stidInsert = oci_parse($conn, $sqlInsert);

            if (!$stidInsert) {
                $e = oci_error($conn);
                die("Erreur préparation insertion : " . htmlentities($e['message'], ENT_QUOTES));
            }

            oci_bind_by_name($stidInsert, ":id_animal", $id_animal);
            oci_bind_by_name($stidInsert, ":id_personnel", $id_personnel);
            oci_bind_by_name($stidInsert, ":id_soin", $id_soin);
            oci_bind_by_name($stidInsert, ":date_intervention", $date);
            oci_bind_by_name($stidInsert, ":est_attitre", $attitre);

            $rInsert = oci_execute($stidInsert, OCI_NO_AUTO_COMMIT);

            if ($rInsert) {
                oci_commit($conn);
                $message = "Soin ajouté avec succès.";
            } else {
                $e = oci_error($stidInsert);
                oci_rollback($conn);
                $erreur = "Erreur lors de l'ajout : " . htmlentities($e['message'], ENT_QUOTES);
            }

            oci_free_statement($stidInsert);
        }
    } else {
        $erreur = "Veuillez remplir tous les champs.";
    }
}

/* Récupérer les animaux */
$animaux = [];
$sqlAnimaux = "SELECT id_animal, nom
               FROM animal
               ORDER BY nom";

$stidAnimaux = oci_parse($conn, $sqlAnimaux);
oci_execute($stidAnimaux);

while ($row = oci_fetch_array($stidAnimaux, OCI_ASSOC + OCI_RETURN_NULLS)) {
    $animaux[] = $row;
}

oci_free_statement($stidAnimaux);

/* Récupérer le personnel autorisé */
$personnelListe = [];

if ($estVeterinaire) {
    $sqlPersonnel = "SELECT id_personnel, nom, prenom, fonction
                     FROM personnel
                     WHERE id_personnel = :id_personnel";
} else {
    $sqlPersonnel = "SELECT id_personnel, nom, prenom, fonction
                     FROM personnel
                     WHERE fonction IN ('Chef Soigneur', 'Soigneur')
                     ORDER BY prenom, nom";
}

$stidPersonnel = oci_parse($conn, $sqlPersonnel);

if ($estVeterinaire) {
    $idPersonnelConnecte = $_SESSION['id_personnel'];
    oci_bind_by_name($stidPersonnel, ":id_personnel", $idPersonnelConnecte);
}

oci_execute($stidPersonnel);

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
    <title>Ajouter un soin</title>
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

        select, input[type="date"] {
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
        <h1>Ajouter un soin</h1>

        <?php if (!empty($message)): ?>
            <p class="success"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if (!empty($erreur)): ?>
            <p class="error"><?php echo htmlspecialchars($erreur); ?></p>
        <?php endif; ?>

        <form method="post" action="ajout_soin.php<?php echo !empty($idAnimalPreselectionne) ? '?id=' . urlencode($idAnimalPreselectionne) : ''; ?>">
            <div class="form-group">
                <label for="animal">Animal :</label>
                <select name="animal" id="animal" required>
                    <?php foreach ($animaux as $a): ?>
                        <?php
                            $nomAnimal = nettoyerTexte($a['NOM']);
                        ?>
                        <option value="<?php echo htmlspecialchars($a['ID_ANIMAL']); ?>"
                            <?php if ((string)$a['ID_ANIMAL'] === (string)$idAnimalPreselectionne) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($nomAnimal . " (ID " . $a['ID_ANIMAL'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="personnel">Personnel :</label>
                <select name="personnel" id="personnel" required>
                    <?php foreach ($personnelListe as $p): ?>
                        <?php
                            $prenom = nettoyerTexte($p['PRENOM']);
                            $nom = nettoyerTexte($p['NOM']);
                            $fonction = nettoyerTexte($p['FONCTION']);
                        ?>
                        <option value="<?php echo htmlspecialchars($p['ID_PERSONNEL']); ?>">
                            <?php echo htmlspecialchars($prenom . " " . $nom . " - " . $fonction); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="soin">Type de soin :</label>
                <select name="soin" id="soin" required>
                    <?php if ($estSoigneur): ?>
                        <?php foreach ($soinsSimples as $type => $libelle): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>">
                                <?php echo htmlspecialchars($type . " - " . $libelle); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if ($estVeterinaire): ?>
                        <?php foreach ($soinsComplexes as $type => $libelle): ?>
                            <option value="<?php echo htmlspecialchars($type); ?>">
                                <?php echo htmlspecialchars($type . " - " . $libelle); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="date">Date d'intervention :</label>
                <input type="date" name="date" id="date" required>
            </div>

            <?php if ($estSoigneur): ?>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="attitre">
                        Soigneur attitré
                    </label>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn">Ajouter</button>
        </form>

        <div class="top-links">
            <a href="soins.php">Retour aux soins</a>
            <a href="animaux.php">Retour aux animaux</a>
            <a href="accueil.php">Retour à l'accueil</a>
        </div>
    </div>
</body>
</html>
