<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_personnel'])) {
    header('Location: login2.php');
    exit();
}

if (!isset($_SESSION['fonction']) || $_SESSION['fonction'] !== 'Gestionnaire') {
    header('Location: accueil.php');
    exit();
}
require_once("connexion.php");

$erreur = "";
$succes = "";

$id = "";
$nom = "";
$prenom = "";
$fonction = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id_personnel'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $fonction = trim($_POST['fonction'] ?? '');

    if (empty($id) || empty($nom) || empty($prenom) || empty($mot_de_passe) || empty($fonction)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

        $check = oci_parse($conn, "SELECT id_personnel FROM personnel WHERE id_personnel = :id");
        oci_bind_by_name($check, ':id', $id);
        oci_execute($check);

        if (oci_fetch_assoc($check)) {
            $erreur = "Cet identifiant existe déjà.";
        } else {
            $sql = "INSERT INTO personnel (id_personnel, nom, prenom, mot_de_pass, fonction)
                    VALUES (:id, :nom, :prenom, :mot_de_pass, :fonction)";

            $stmt = oci_parse($conn, $sql);

            if (!$stmt) {
                $e = oci_error($conn);
                die("Erreur préparation requête : " . htmlentities($e['message'], ENT_QUOTES));
            }

            oci_bind_by_name($stmt, ':id', $id);
            oci_bind_by_name($stmt, ':nom', $nom);
            oci_bind_by_name($stmt, ':prenom', $prenom);
            oci_bind_by_name($stmt, ':mot_de_pass', $hash);
            oci_bind_by_name($stmt, ':fonction', $fonction);

            $r = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

            if ($r) {
                $succes = "Personnel ajouté avec succès.";
                $id = "";
                $nom = "";
                $prenom = "";
                $fonction = "";
            } else {
                $e = oci_error($stmt);
                $erreur = "Erreur lors de l'ajout : " . htmlentities($e['message'], ENT_QUOTES);
            }

            oci_free_statement($stmt);
        }

        oci_free_statement($check);
    }
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter un personnel</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7f6;
            padding: 30px;
        }

        .container {
            max-width: 500px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        h1 {
            text-align: center;
            color: #1f4d3b;
        }

        label {
            display: block;
            margin-top: 12px;
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

        .erreur {
            color: red;
        }

        .succes {
            color: green;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Ajouter un personnel</h1>

        <?php if ($erreur): ?>
            <p class="erreur"><?php echo htmlspecialchars($erreur); ?></p>
        <?php endif; ?>

        <?php if ($succes): ?>
            <p class="succes"><?php echo htmlspecialchars($succes); ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="id_personnel">Identifiant</label>
            <input type="text" name="id_personnel" id="id_personnel" value="<?php echo htmlspecialchars($id); ?>" required>

            <label for="nom">Nom</label>
            <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars($nom); ?>" required>

            <label for="prenom">Prénom</label>
            <input type="text" name="prenom" id="prenom" value="<?php echo htmlspecialchars($prenom); ?>" required>

            <label for="mot_de_passe">Mot de passe</label>
            <input type="password" name="mot_de_passe" id="mot_de_passe" required>

            <label for="fonction">Fonction</label>
            <select name="fonction" id="fonction" required>
    <option value="">-- Choisir --</option>

    <option value="Gestionnaire" <?php if ($fonction === 'Gestionnaire') echo 'selected'; ?>>Gestionnaire</option>

    <option value="Dirigeant" <?php if ($fonction === 'Dirigeant') echo 'selected'; ?>>Dirigeant</option>

    <option value="Chef Soigneur" <?php if ($fonction === 'Chef Soigneur') echo 'selected'; ?>>Chef Soigneur</option>

    <option value="Soigneur" <?php if ($fonction === 'Soigneur') echo 'selected'; ?>>Soigneur</option>

    <option value="Vétérinaire" <?php if ($fonction === 'Vétérinaire') echo 'selected'; ?>>Vétérinaire</option>

    <option value="Personnel Entretien" <?php if ($fonction === 'Personnel Entretien') echo 'selected'; ?>>Personnel Entretien</option>

    <option value="Technicien" <?php if ($fonction === 'Technicien') echo 'selected'; ?>>Technicien</option>

    <option value="Comptable" <?php if ($fonction === 'Comptable') echo 'selected'; ?>>Comptable</option>

    <option value="Responsable Boutique" <?php if ($fonction === 'Responsable Boutique') echo 'selected'; ?>>Responsable Boutique</option>

    <option value="Employé Boutique" <?php if ($fonction === 'Employé Boutique') echo 'selected'; ?>>Employé Boutique</option>
</select>
            <button type="submit">Ajouter</button>
            <a href="gestion_personnel.php">Retour</a>
        </form>
    </div>
</body>
</html>
