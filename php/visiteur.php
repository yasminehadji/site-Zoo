<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once("connexion.php");

$erreur = "";
$succes = "";

$nom = "";
$prenom = "";
$id_animal = "";
$niveau = "";

// Récupérer la liste des animaux 
$animaux = [];
$sqlAnimaux = "SELECT id_animal, nom
               FROM animal
               ORDER BY nom";
$stmtAnimaux = oci_parse($conn, $sqlAnimaux);
oci_execute($stmtAnimaux);

while ($row = oci_fetch_assoc($stmtAnimaux)) {
    $animaux[] = $row;
}
oci_free_statement($stmtAnimaux);



//Prestations selon le niveau 
$prestations = [
    "Bronze" => "Photo de l'animal",
    "Argent" => "Photo + fond d'écran",
    "Or" => "Photo + fond d'écran + visite gratuite"
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $id_animal = trim($_POST['id_animal'] ?? '');
    $niveau = trim($_POST['niveau'] ?? '');

    if ($nom === '' || $prenom === '' || $id_animal === '' || $niveau === '') {
        $erreur = "Veuillez remplir tous les champs.";
    } elseif (!isset($prestations[$niveau])) {
        $erreur = "Niveau de parrainage invalide.";
    } else {
        $prestation = $prestations[$niveau];
        

        // Générer un nouvel id_parrainage 
        $sqlId = "SELECT NVL(MAX(id_parrainage), 0) + 1 AS NEW_ID FROM parraine";
        $stmtId = oci_parse($conn, $sqlId);
        oci_execute($stmtId);
        $rowId = oci_fetch_assoc($stmtId);
        oci_free_statement($stmtId);

        $id_parrainage = $rowId['NEW_ID'];

        $ok = true;

        //Insérer TOUJOURS le nouveau parrain dans parraine 
        
        $sqlInsertParrain = "INSERT INTO parraine (id_parrainage, nom, prenom)
                             VALUES (:id_parrainage, :nom, :prenom)";
        $stmtInsertParrain = oci_parse($conn, $sqlInsertParrain);
        oci_bind_by_name($stmtInsertParrain, ":id_parrainage", $id_parrainage);
        oci_bind_by_name($stmtInsertParrain, ":nom", $nom);
        oci_bind_by_name($stmtInsertParrain, ":prenom", $prenom);

        if (!oci_execute($stmtInsertParrain, OCI_NO_AUTO_COMMIT)) {
            $ok = false;
            $e = oci_error($stmtInsertParrain);
            $erreur = "Erreur lors de la création du parrain : " . htmlentities($e['message'], ENT_QUOTES);
        }
        oci_free_statement($stmtInsertParrain);
        
        

        // Insérer le parrainage
        if ($ok) {
            $sqlInsertParrainage = "INSERT INTO parrainage (id_animal, id_parrainage, niveau, prestation)
                                    VALUES (:id_animal, :id_parrainage, :niveau, :prestation)";
            $stmtInsertParrainage = oci_parse($conn, $sqlInsertParrainage);
            oci_bind_by_name($stmtInsertParrainage, ":id_animal", $id_animal);
            oci_bind_by_name($stmtInsertParrainage, ":id_parrainage", $id_parrainage);
            oci_bind_by_name($stmtInsertParrainage, ":niveau", $niveau);
            oci_bind_by_name($stmtInsertParrainage, ":prestation", $prestation);

            if (!oci_execute($stmtInsertParrainage, OCI_NO_AUTO_COMMIT)) {
                $ok = false;
                $e = oci_error($stmtInsertParrainage);
                $erreur = "Erreur lors de l'ajout du parrainage : " . htmlentities($e['message'], ENT_QUOTES);
            }
            oci_free_statement($stmtInsertParrainage);
        }

        if ($ok) {
            oci_commit($conn);
            $succes = "Votre parrainage a bien été enregistré. Merci pour votre soutien !";
            $nom = "";
            $prenom = "";
            $id_animal = "";
            $niveau = "";
        } else {
            oci_rollback($conn);
        }
    }
}

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Espace Visiteur - Parrainage</title>
    <link rel="stylesheet" href="index.css">
</head>
<body class="sponsorship-page">
    <div class="page-container home-hero">
        <div class="hero-badge">Parrainez un animal</div>
        <h1>Devenez parrain ou marraine d’un pensionnaire du zoo</h1>
        <p class="hero-text">
            Votre contribution aide le zoo à préserver les espèces, améliorer le bien-être animal
            et soutenir les soins du quotidien. Choisissez votre filleul, sélectionnez un niveau,
            et laissez une empreinte douce dans cette petite arche vivante.
        </p>

        <div class="top-links">
            <a href="index.php" class="btn btn-light">← Retour à l'accueil</a>
            <a href="login2.php" class="btn btn-outline">Espace personnel</a>
        </div>

        <?php if ($erreur): ?>
            <p class="erreur"><?php echo htmlspecialchars($erreur); ?></p>
        <?php endif; ?>

        <?php if ($succes): ?>
            <p class="succes"><?php echo htmlspecialchars($succes); ?></p>
        <?php endif; ?>

        <div class="offers-grid">
            <div class="offer-card bronze">
                <h3>Bronze</h3>
                <p class="price">20 €</p>
                <p>Photo de votre filleul</p>
            </div>

            <div class="offer-card silver">
                <h3>Argent</h3>
                <p class="price">50 €</p>
                <p>Photo + fond d’écran</p>
            </div>

            <div class="offer-card gold">
                <h3>Or</h3>
                <p class="price">100 €</p>
                <p>Photo + fond d’écran + visite gratuite</p>
            </div>
        </div>

        <div class="sponsorship-layout">
            <div class="form-panel">
                <h2>Formulaire de parrainage</h2>

                <form method="POST">
                    <div class="form-group">
                        <label for="nom">Nom</label>
                        <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars($nom); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="prenom">Prénom</label>
                        <input type="text" name="prenom" id="prenom" value="<?php echo htmlspecialchars($prenom); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="id_animal">Animal à parrainer</label>
                        <select name="id_animal" id="id_animal" required>
                            <option value="">-- Choisir un animal --</option>
                            <?php foreach ($animaux as $animal): ?>
                                <option value="<?php echo htmlspecialchars($animal['ID_ANIMAL']); ?>"
                                    <?php if ($id_animal == $animal['ID_ANIMAL']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($animal['NOM']); ?> (ID <?php echo htmlspecialchars($animal['ID_ANIMAL']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="niveau">Niveau de parrainage</label>
                        <select name="niveau" id="niveau" required>
                            <option value="">-- Choisir un niveau --</option>
                            <option value="Bronze" <?php if ($niveau === 'Bronze') echo 'selected'; ?>>Bronze</option>
                            <option value="Argent" <?php if ($niveau === 'Argent') echo 'selected'; ?>>Argent</option>
                            <option value="Or" <?php if ($niveau === 'Or') echo 'selected'; ?>>Or</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-large">Valider le parrainage</button>
                </form>
            </div>

            <div class="visual-panel">
                <div class="animal-highlight">
                    <div class="animal-emoji">🦁</div>
                    <h2>Un geste qui compte</h2>
                    <p>
                        Chaque parrainage aide à financer l’alimentation, les soins et l’entretien
                        des espaces de vie. Une promesse discrète, mais lumineuse.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
