<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_personnel'])) {
    header("Location: login2.php");
    exit();
}

$fonction = trim($_SESSION['fonction']);

if (!in_array($fonction, ['Gestionnaire', 'Dirigeant', 'Technicien'])) {
    header("Location: accueil.php");
    exit();
}

require_once("connexion.php");

$erreur = "";
$succes = "";

$id_reparation = "";
$nature = "";
$libelle = "";
$id_enclos = "";
$id_personnel = "";
$id_prestataire = "";

/* Si technicien connecté, on peut préremplir son id */
if ($fonction === "Technicien") {
    $id_personnel = $_SESSION['id_personnel'];
}

/* Récupérer enclos */
$enclos = [];
$stmtEnclos = oci_parse($conn, "SELECT id_enclos FROM enclos ORDER BY id_enclos");
oci_execute($stmtEnclos);
while ($row = oci_fetch_assoc($stmtEnclos)) {
    $enclos[] = $row;
}
oci_free_statement($stmtEnclos);

/* Récupérer techniciens */
$techniciens = [];
$stmtTech = oci_parse($conn, "SELECT id_personnel, nom, prenom FROM personnel WHERE fonction = 'Technicien' ORDER BY prenom, nom");
oci_execute($stmtTech);
while ($row = oci_fetch_assoc($stmtTech)) {
    $techniciens[] = $row;
}
oci_free_statement($stmtTech);

/* Récupérer prestataires */
$prestataires = [];
$stmtPrest = oci_parse($conn, "SELECT id_prestataire, contact FROM prestataires ORDER BY id_prestataire");
oci_execute($stmtPrest);
while ($row = oci_fetch_assoc($stmtPrest)) {
    $prestataires[] = $row;
}
oci_free_statement($stmtPrest);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_reparation = trim($_POST['id_reparation'] ?? '');
    $nature = trim($_POST['nature'] ?? '');
    $libelle = trim($_POST['libelle'] ?? '');
    $id_enclos = trim($_POST['id_enclos'] ?? '');
    $id_personnel = trim($_POST['id_personnel'] ?? '');
    $id_prestataire = trim($_POST['id_prestataire'] ?? '');

    /* Sécurité métier */
    if ($fonction === "Technicien" && $nature === "Gros") {
        $erreur = "Vous ne pouvez pas créer une réparation de type Gros.";
    } elseif ($nature === 'Petit' && $id_prestataire !== '') {
        $erreur = "Une réparation 'Petit' ne doit pas avoir de prestataire.";
    } elseif ($nature === 'Gros' && $id_personnel !== '') {
        $erreur = "Une réparation 'Gros' ne doit pas avoir de technicien.";
    } elseif ($id_reparation === '' || $nature === '' || $libelle === '' || $id_enclos === '') {
        $erreur = "Veuillez remplir les champs obligatoires.";
    } elseif ($nature === 'Petit' && $id_personnel === '') {
        $erreur = "Pour une réparation de type Petit, il faut choisir un technicien.";
    } elseif ($nature === 'Gros' && $id_prestataire === '') {
        $erreur = "Pour une réparation de type Gros, il faut choisir un prestataire.";
    } else {
        $ok = true;

        /* 1. insérer reparation */
        $sqlRep = "INSERT INTO reparation (id_reparation, nature, libelle)
                   VALUES (:id_reparation, :nature, :libelle)";
        $stmtRep = oci_parse($conn, $sqlRep);
        oci_bind_by_name($stmtRep, ":id_reparation", $id_reparation);
        oci_bind_by_name($stmtRep, ":nature", $nature);
        oci_bind_by_name($stmtRep, ":libelle", $libelle);

        if (!oci_execute($stmtRep, OCI_NO_AUTO_COMMIT)) {
            $ok = false;
            $e = oci_error($stmtRep);
            $erreur = "Erreur insertion réparation : " . htmlentities($e['message'], ENT_QUOTES);
        }
        oci_free_statement($stmtRep);

        /* 2. relier à enclos */
        if ($ok) {
            $sqlFaite = "INSERT INTO faite (id_enclos, id_reparation)
                         VALUES (:id_enclos, :id_reparation)";
            $stmtFaite = oci_parse($conn, $sqlFaite);
            oci_bind_by_name($stmtFaite, ":id_enclos", $id_enclos);
            oci_bind_by_name($stmtFaite, ":id_reparation", $id_reparation);

            if (!oci_execute($stmtFaite, OCI_NO_AUTO_COMMIT)) {
                $ok = false;
                $e = oci_error($stmtFaite);
                $erreur = "Erreur liaison enclos : " . htmlentities($e['message'], ENT_QUOTES);
            }
            oci_free_statement($stmtFaite);
        }

        /* 3. relier technicien ou prestataire */
        if ($ok && $nature === 'Petit') {
            $sqlTech = "INSERT INTO personnel_technique (id_personnel, id_reparation)
                        VALUES (:id_personnel, :id_reparation)";
            $stmtTechInsert = oci_parse($conn, $sqlTech);
            oci_bind_by_name($stmtTechInsert, ":id_personnel", $id_personnel);
            oci_bind_by_name($stmtTechInsert, ":id_reparation", $id_reparation);

            if (!oci_execute($stmtTechInsert, OCI_NO_AUTO_COMMIT)) {
                $ok = false;
                $e = oci_error($stmtTechInsert);
                $erreur = "Erreur liaison technicien : " . htmlentities($e['message'], ENT_QUOTES);
            }
            oci_free_statement($stmtTechInsert);
        }

        if ($ok && $nature === 'Gros') {
            $sqlPrest = "INSERT INTO realise (id_reparation, id_prestataire)
                         VALUES (:id_reparation, :id_prestataire)";
            $stmtPrestInsert = oci_parse($conn, $sqlPrest);
            oci_bind_by_name($stmtPrestInsert, ":id_reparation", $id_reparation);
            oci_bind_by_name($stmtPrestInsert, ":id_prestataire", $id_prestataire);

            if (!oci_execute($stmtPrestInsert, OCI_NO_AUTO_COMMIT)) {
                $ok = false;
                $e = oci_error($stmtPrestInsert);
                $erreur = "Erreur liaison prestataire : " . htmlentities($e['message'], ENT_QUOTES);
            }
            oci_free_statement($stmtPrestInsert);
        }

        if ($ok) {
            oci_commit($conn);
            $succes = "Réparation ajoutée avec succès.";
            $id_reparation = "";
            $nature = "";
            $libelle = "";
            $id_enclos = "";
            $id_personnel = ($fonction === "Technicien") ? $_SESSION['id_personnel'] : "";
            $id_prestataire = "";
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
    <title>Ajouter une réparation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7f6;
            padding: 30px;
        }
        .container {
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
        }
        h1 {
            color: #1f4d3b;
        }
        label {
            display: block;
            margin-top: 12px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
        }
        button, a {
            margin-top: 20px;
            display: inline-block;
            padding: 10px 15px;
            text-decoration: none;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        button {
            background: #1f4d3b;
            color: white;
        }
        a {
            background: #777;
            color: white;
        }
        .erreur { color: red; }
        .succes { color: green; }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Ajouter une réparation</h1>

    <?php if ($erreur): ?>
        <p class="erreur"><?php echo htmlspecialchars($erreur); ?></p>
    <?php endif; ?>

    <?php if ($succes): ?>
        <p class="succes"><?php echo htmlspecialchars($succes); ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="id_reparation">ID réparation</label>
        <input type="number" name="id_reparation" id="id_reparation" value="<?php echo htmlspecialchars($id_reparation); ?>" required>

        <label for="nature">Nature</label>
        <select name="nature" id="nature" required>
            <option value="">-- Choisir --</option>

            <option value="Petit" <?php if ($nature === 'Petit') echo 'selected'; ?>>
                Petit
            </option>

            <?php if ($fonction !== "Technicien"): ?>
                <option value="Gros" <?php if ($nature === 'Gros') echo 'selected'; ?>>
                    Gros
                </option>
            <?php endif; ?>
        </select>

        <label for="libelle">Libellé</label>
        <input type="text" name="libelle" id="libelle" value="<?php echo htmlspecialchars($libelle); ?>" required>

        <label for="id_enclos">Enclos</label>
        <select name="id_enclos" id="id_enclos" required>
            <option value="">-- Choisir --</option>
            <?php foreach ($enclos as $e): ?>
                <option value="<?php echo htmlspecialchars($e['ID_ENCLOS']); ?>" <?php if ($id_enclos == $e['ID_ENCLOS']) echo 'selected'; ?>>
                    Enclos <?php echo htmlspecialchars($e['ID_ENCLOS']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div id="bloc-technicien">
            <label for="id_personnel">Technicien (si Petit)</label>
            <select name="id_personnel" id="id_personnel">
                <option value="">-- Aucun --</option>
                <?php foreach ($techniciens as $t): ?>
                    <option value="<?php echo htmlspecialchars($t['ID_PERSONNEL']); ?>" <?php if ($id_personnel === $t['ID_PERSONNEL']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($t['PRENOM'] . ' ' . $t['NOM'] . ' (' . $t['ID_PERSONNEL'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="bloc-prestataire">
            <label for="id_prestataire">Prestataire (si Gros)</label>
            <select name="id_prestataire" id="id_prestataire">
                <option value="">-- Aucun --</option>
                <?php foreach ($prestataires as $p): ?>
                    <option value="<?php echo htmlspecialchars($p['ID_PRESTATAIRE']); ?>" <?php if ($id_prestataire == $p['ID_PRESTATAIRE']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($p['ID_PRESTATAIRE'] . ' - ' . $p['CONTACT']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit">Ajouter</button>
        <a href="reparations.php">Retour</a>
    </form>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const nature = document.getElementById("nature");
    const blocTechnicien = document.getElementById("bloc-technicien");
    const blocPrestataire = document.getElementById("bloc-prestataire");

    function majAffichage() {
        if (nature.value === "Petit") {
            blocTechnicien.style.display = "block";
            blocPrestataire.style.display = "none";
        } else if (nature.value === "Gros") {
            blocTechnicien.style.display = "none";
            blocPrestataire.style.display = "block";
        } else {
            blocTechnicien.style.display = "none";
            blocPrestataire.style.display = "none";
        }
    }

    majAffichage();
    nature.addEventListener("change", majAffichage);
});
</script>
</body>
</html>