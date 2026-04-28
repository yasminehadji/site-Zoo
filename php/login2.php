<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['id_personnel'])) {
    header('Location: accueil.php');
    exit();
}

require_once("connexion.php");

$erreur = "";
$id = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = trim($_POST['id_personnel'] ?? '');
    $mdp = trim($_POST['mot_de_passe'] ?? '');

    if (empty($id) || empty($mdp)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        $stmt = oci_parse($conn, "SELECT 
    TRIM(id_personnel) AS id_personnel,
    TRIM(nom) AS nom,
    TRIM(prenom) AS prenom,
    TRIM(mot_de_pass) AS mot_de_pass,
    TRIM(fonction) AS fonction
FROM personnel
WHERE TRIM(id_personnel) = :id
         ");

        if (!$stmt) {
            $e = oci_error($conn);
            die("Erreur préparation requête : " . htmlentities($e['message'], ENT_QUOTES));
        }

        oci_bind_by_name($stmt,':id',$id);
        $r = oci_execute($stmt);

        if (!$r) {
            $e = oci_error($stmt);
            die("Erreur exécution requête : " . htmlentities($e['message'], ENT_QUOTES));
        }

        $row = oci_fetch_assoc($stmt);


        if ($row && password_verify($mdp, $row['MOT_DE_PASS'])) {
            $_SESSION['id_personnel'] = $row['ID_PERSONNEL'];
            $_SESSION['nom'] = $row['NOM'];
            $_SESSION['prenom'] = $row['PRENOM'];
            $_SESSION['fonction'] = $row['FONCTION'];

            oci_free_statement($stmt);
            oci_close($conn);

            header('Location: accueil.php');
            exit();
        } else {
            $erreur = "Identifiant ou mot de passe incorrect.";
        }

        oci_free_statement($stmt);
        oci_close($conn);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <div class="login-container">
        <h2>Connexion au Zoo</h2>

        <?php if ($erreur): ?>
            <p style="color:red;"><?php echo htmlspecialchars($erreur); ?></p>
        <?php endif; ?>
<h1>Connexion</h1>

<div style="margin-bottom: 15px;">
    <a href="index.php">← Retour à l'accueil</a>
</div>

<form method="POST">
        <form method="POST">
            <div class="form-group">
                <label for="id_personnel">Identifiant :</label>
                <input type="text" name="id_personnel" id="id_personnel"
                       value="<?php echo htmlspecialchars($id); ?>" required>
            </div>

            <div class="form-group">
                <label for="mot_de_passe">Mot de passe :</label>
                <input type="password" name="mot_de_passe" id="mot_de_passe" required>
            </div>

            <button type="submit">Se connecter</button>
        </form>
    </div>
</body>
</html>
