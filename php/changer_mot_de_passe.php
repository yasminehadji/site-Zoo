<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_personnel'])) {
    header('Location: login2.php');
    exit();
}

require_once("connexion.php");

$erreur = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ancien_mdp = $_POST['ancien_mot_de_passe'] ?? '';
    $nouveau_mdp = $_POST['nouveau_mot_de_passe'] ?? '';
    $confirmer_mdp = $_POST['confirmer_mot_de_passe'] ?? '';

    if (empty($ancien_mdp) || empty($nouveau_mdp) || empty($confirmer_mdp)) {
        $erreur = "Veuillez remplir tous les champs.";
    } elseif ($nouveau_mdp !== $confirmer_mdp) {
        $erreur = "Le nouveau mot de passe et sa confirmation ne correspondent pas.";
    } elseif (strlen($nouveau_mdp) < 6) {
        $erreur = "Le nouveau mot de passe doit contenir au moins 6 caractères.";
    } else {
        $id = $_SESSION['id_personnel'];

        $sql = "SELECT mot_de_pass FROM personnel WHERE id_personnel = :id";
        $stmt = oci_parse($conn, $sql);

        if (!$stmt) {
            $e = oci_error($conn);
            die("Erreur préparation requête : " . htmlentities($e['message'], ENT_QUOTES));
        }

        oci_bind_by_name($stmt, ':id', $id);
        $r = oci_execute($stmt);

        if (!$r) {
            $e = oci_error($stmt);
            die("Erreur exécution requête : " . htmlentities($e['message'], ENT_QUOTES));
        }

        $row = oci_fetch_assoc($stmt);

        if (!$row || !password_verify($ancien_mdp, $row['MOT_DE_PASS'])) {
            $erreur = "Ancien mot de passe incorrect.";
        } else {
            $nouveau_hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);

            $update = "UPDATE personnel SET mot_de_pass = :mdp WHERE id_personnel = :id";
            $stmtUpdate = oci_parse($conn, $update);

            if (!$stmtUpdate) {
                $e = oci_error($conn);
                die("Erreur préparation mise à jour : " . htmlentities($e['message'], ENT_QUOTES));
            }

            oci_bind_by_name($stmtUpdate, ':mdp', $nouveau_hash);
            oci_bind_by_name($stmtUpdate, ':id', $id);

            $r2 = oci_execute($stmtUpdate, OCI_COMMIT_ON_SUCCESS);

            if (!$r2) {
                $e = oci_error($stmtUpdate);
                die("Erreur mise à jour : " . htmlentities($e['message'], ENT_QUOTES));
            }

            $success = "Mot de passe modifié avec succès.";
            oci_free_statement($stmtUpdate);
        }

        oci_free_statement($stmt);
    }
}

oci_close($conn);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changer le mot de passe</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <div class="page-container password-page">
        <div class="hero-badge">Sécurité</div>

        <h1>Changer mon mot de passe 🔐</h1>
        <p class="hero-text">
            Modifiez votre mot de passe pour sécuriser votre espace personnel.
        </p>

        <?php if ($erreur): ?>
            <p class="error"><?php echo htmlspecialchars($erreur); ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" id="passwordPageForm">
                <div class="form-group">
                    <label for="ancien_mot_de_passe">Ancien mot de passe</label>
                    <input type="password" name="ancien_mot_de_passe" id="ancien_mot_de_passe" required>
                </div>

                <div class="form-group">
                    <label for="nouveau_mot_de_passe">Nouveau mot de passe</label>
                    <input type="password" name="nouveau_mot_de_passe" id="nouveau_mot_de_passe" required>
                </div>

                <div class="form-group">
                    <label for="confirmer_mot_de_passe">Confirmer le nouveau mot de passe</label>
                    <input type="password" name="confirmer_mot_de_passe" id="confirmer_mot_de_passe" required>
                </div>

                <div class="top-links password-actions">
                    <button type="submit" class="btn btn-password">
                        ✅ Modifier
                    </button>

                    <a href="accueil.php" class="btn btn-outline">
                        ← Retour à l'accueil
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="js/common.js"></script>
    <script src="js/pages.js"></script>
</body>
</html>