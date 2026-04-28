<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_personnel'])) {
    header("Location: login2.php");
    exit();
}

if (!isset($_SESSION['fonction']) || $_SESSION['fonction'] !== "Gestionnaire") {
    header("Location: accueil.php");
    exit();
}

require_once("connexion.php");

$erreur = "";
$id_boutique = "";
$nom_boutique = "";
$id_zone = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_boutique = trim($_POST['id_boutique'] ?? '');
    $nom_boutique = trim($_POST['nom_boutique'] ?? '');
    $id_zone = trim($_POST['id_zone'] ?? '');

    if ($id_boutique === '' || $nom_boutique === '' || $id_zone === '') {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        $checkBoutique = oci_parse($conn, "SELECT id_boutique FROM boutique WHERE id_boutique = :id");
        oci_bind_by_name($checkBoutique, ':id', $id_boutique);
        oci_execute($checkBoutique);

        if (oci_fetch_assoc($checkBoutique)) {
            $erreur = "Cet identifiant de boutique existe déjà.";
        } else {
            $checkZone = oci_parse($conn, "SELECT id_zone FROM zone WHERE id_zone = :id_zone");
            oci_bind_by_name($checkZone, ':id_zone', $id_zone);
            oci_execute($checkZone);

            if (!oci_fetch_assoc($checkZone)) {
                $erreur = "La zone indiquée n'existe pas.";
            } else {
                $sql = "INSERT INTO boutique (id_boutique, nom_boutique, id_zone)
                        VALUES (:id_boutique, :nom_boutique, :id_zone)";
                $stmt = oci_parse($conn, $sql);

                oci_bind_by_name($stmt, ':id_boutique', $id_boutique);
                oci_bind_by_name($stmt, ':nom_boutique', $nom_boutique);
                oci_bind_by_name($stmt, ':id_zone', $id_zone);

                $r = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

                if ($r) {
                    oci_free_statement($stmt);
                    oci_free_statement($checkBoutique);
                    oci_free_statement($checkZone);
                    oci_close($conn);
                    header("Location: boutique.php?success=ajout");
                    exit();
                } else {
                    $e = oci_error($stmt);
                    $erreur = "Erreur ajout : " . htmlentities($e['message'], ENT_QUOTES);
                    oci_free_statement($stmt);
                }
            }

            oci_free_statement($checkZone);
        }

        oci_free_statement($checkBoutique);
    }
}

oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ajouter une boutique</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eef6f0;
            padding: 30px;
        }

        .container {
            max-width: 600px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
        }

        h1 {
            color: #1f4d3b;
            text-align: center;
        }

        label {
            display: block;
            margin-top: 12px;
            font-weight: bold;
        }

        input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
        }

        button, a {
            margin-top: 20px;
            display: inline-block;
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            border: none;
        }

        button {
            background: #1f4d3b;
            color: white;
            cursor: pointer;
        }

        a {
            background: #777;
            color: white;
        }

        .erreur {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Ajouter une boutique</h1>

    <?php if ($erreur): ?>
        <p class="erreur"><?php echo htmlspecialchars($erreur); ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>ID Boutique</label>
        <input type="number" name="id_boutique" value="<?php echo htmlspecialchars($id_boutique); ?>" required>

        <label>Nom Boutique</label>
        <input type="text" name="nom_boutique" value="<?php echo htmlspecialchars($nom_boutique); ?>" required>

        <label>ID Zone</label>
        <input type="number" name="id_zone" value="<?php echo htmlspecialchars($id_zone); ?>" required>

        <button type="submit">Ajouter</button>
        <a href="boutique.php">Retour</a>
    </form>
</div>
</body>
</html>
