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
$fonction = "";

$id = $_GET['id'] ?? $_POST['id_personnel'] ?? '';

if (empty($id)) {
    die("Identifiant manquant.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $fonction = trim($_POST['fonction'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    if (empty($nom) || empty($prenom) || empty($fonction)) {
        $erreur = "Veuillez remplir les champs obligatoires.";
    } else {
        if (!empty($mot_de_passe)) {
            $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

            $sql = "UPDATE personnel
                    SET nom = :nom,
                        prenom = :prenom,
                        fonction = :fonction,
                        mot_de_pass = :mot_de_pass
                    WHERE id_personnel = :id";

            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ':nom', $nom);
            oci_bind_by_name($stmt, ':prenom', $prenom);
            oci_bind_by_name($stmt, ':fonction', $fonction);
            oci_bind_by_name($stmt, ':mot_de_pass', $hash);
            oci_bind_by_name($stmt, ':id', $id);
        } else {
            $sql = "UPDATE personnel
                    SET nom = :nom,
                        prenom = :prenom,
                        fonction = :fonction
                    WHERE id_personnel = :id";

            $stmt = oci_parse($conn, $sql);
            oci_bind_by_name($stmt, ':nom', $nom);
            oci_bind_by_name($stmt, ':prenom', $prenom);
            oci_bind_by_name($stmt, ':fonction', $fonction);
            oci_bind_by_name($stmt, ':id', $id);
        }

        $r = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

        if ($r) {
            $succes = "Personnel modifié avec succès.";
        } else {
            $e = oci_error($stmt);
            $erreur = "Erreur : " . htmlentities($e['message'], ENT_QUOTES);
        }

        oci_free_statement($stmt);
    }
}

$sql = "SELECT id_personnel, nom, prenom, fonction
        FROM personnel
        WHERE id_personnel = :id";

$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':id', $id);
oci_execute($stmt);

$row = oci_fetch_assoc($stmt);

if (!$row) {
    die("Personnel introuvable.");
}

oci_free_statement($stmt);
oci_close($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier un personnel</title>
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
        <h1>Modifier un personnel</h1>

        <?php if ($erreur): ?>
            <p class="erreur"><?php echo htmlspecialchars($erreur); ?></p>
        <?php endif; ?>

        <?php if ($succes): ?>
            <p class="succes"><?php echo htmlspecialchars($succes); ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="id_personnel" value="<?php echo htmlspecialchars($row['ID_PERSONNEL']); ?>">

            <label>ID personnel</label>
            <input type="text" value="<?php echo htmlspecialchars($row['ID_PERSONNEL']); ?>" disabled>

            <label for="nom">Nom</label>
            <input type="text" name="nom" id="nom" value="<?php echo htmlspecialchars($row['NOM']); ?>" required>

            <label for="prenom">Prénom</label>
            <input type="text" name="prenom" id="prenom" value="<?php echo htmlspecialchars($row['PRENOM']); ?>" required>

            <label for="fonction">Fonction</label>
            <select name="fonction" id="fonction" required>
               <option value="">-- Choisir --</option>
                <option value="Gestionnaire" <?php if ($fonction === 'Gestionnaire') echo 'selected'; ?>>Gestionnaire</option>
                <option value="Soigneur" <?php if ($fonction === 'Soigneur') echo 'selected'; ?>>Soigneur</option>
                <option value="Vétérinaire" <?php if ($fonction === 'Vétérinaire') echo 'selected'; ?>>Vétérinaire</option>
               <option value="Dirigeant" <?php if ($filtreFonction === 'Dirigeant') echo 'selected'; ?>>Dirigeant</option>
                <option value="Comptable" <?php if ($fonction === 'Comptable') echo 'selected'; ?>>Comptable</option>
                <option value="Chef Soigneur" <?php if ($fonction === 'Chef Soigneur') echo 'selected'; ?>>Chef soigneur</option>
                <option value="Personnel Entretien" <?php if ($fonction === 'Personnel Entretien') echo 'selected'; ?>>Personnel Entretien</option>
                <option value="Technicien" <?php if ($fonction === 'Technicien') echo 'selected'; ?>>Technicien</option>
                <option value="Responsable Boutique" <?php if ($fonction === 'Responsable Boutique') echo 'selected'; ?>>Responsable Boutique</option>
                <option value="Employé Boutique" <?php if ($fonction === 'Employé Boutique') echo 'selected'; ?>>Employé Boutique</option>
            </select>

            <label for="mot_de_passe">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
            <input type="password" name="mot_de_passe" id="mot_de_passe">

            <button type="submit">Modifier</button>
            <a href="gestion_personnel.php">Retour</a>
        </form>
    </div>
</body>
</html>
