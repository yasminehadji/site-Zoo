<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_personnel'])) {
    header("Location: login2.php");
    exit();
}

require_once("connexion.php");

$fonction = trim($_SESSION['fonction'] ?? '');
$erreur = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_enclos'])) {

    if ($fonction !== "Gestionnaire") {
        $erreur = "Seul le gestionnaire peut ajouter un enclos.";
    } else {
        $id_enclos = trim($_POST['id_enclos'] ?? '');
        $latitude = trim($_POST['latitude'] ?? '');
        $longitude = trim($_POST['longitude'] ?? '');
        $surface = trim($_POST['surface'] ?? '');
        $id_zone = trim($_POST['id_zone'] ?? '');

        if ($id_enclos === '' || $latitude === '' || $longitude === '' || $surface === '' || $id_zone === '') {
            $erreur = "Veuillez remplir tous les champs.";
        } else {
            $checkSql = "SELECT id_enclos FROM enclos WHERE id_enclos = :id_enclos";
            $checkStmt = oci_parse($conn, $checkSql);

            if ($checkStmt) {
                oci_bind_by_name($checkStmt, ':id_enclos', $id_enclos);
                oci_execute($checkStmt);
                $existe = oci_fetch_assoc($checkStmt);
                oci_free_statement($checkStmt);
            } else {
                $e = oci_error($conn);
                $erreur = "Erreur préparation vérification enclos : " . htmlentities($e['message'], ENT_QUOTES);
                $existe = false;
            }

            if (!$erreur && $existe) {
                $erreur = "Cet identifiant d'enclos existe déjà.";
            } elseif (!$erreur) {
                $zoneSql = "SELECT id_zone FROM zone WHERE id_zone = :id_zone";
                $zoneStmt = oci_parse($conn, $zoneSql);

                if ($zoneStmt) {
                    oci_bind_by_name($zoneStmt, ':id_zone', $id_zone);
                    oci_execute($zoneStmt);
                    $zoneExiste = oci_fetch_assoc($zoneStmt);
                    oci_free_statement($zoneStmt);
                } else {
                    $e = oci_error($conn);
                    $erreur = "Erreur préparation vérification zone : " . htmlentities($e['message'], ENT_QUOTES);
                    $zoneExiste = false;
                }

                if (!$erreur && !$zoneExiste) {
                    $erreur = "La zone indiquée n'existe pas.";
                } elseif (!$erreur) {
                    $insertSql = "INSERT INTO enclos (id_enclos, latitude, longitude, surface, id_zone)
                                  VALUES (:id_enclos, :latitude, :longitude, :surface, :id_zone)";
                    $insertStmt = oci_parse($conn, $insertSql);

                    if ($insertStmt) {
                        oci_bind_by_name($insertStmt, ':id_enclos', $id_enclos);
                        oci_bind_by_name($insertStmt, ':latitude', $latitude);
                        oci_bind_by_name($insertStmt, ':longitude', $longitude);
                        oci_bind_by_name($insertStmt, ':surface', $surface);
                        oci_bind_by_name($insertStmt, ':id_zone', $id_zone);

                        $rInsert = oci_execute($insertStmt, OCI_COMMIT_ON_SUCCESS);

                        if ($rInsert) {
                            $success = "Enclos ajouté avec succès.";
                        } else {
                            $e = oci_error($insertStmt);
                            $erreur = "Erreur insertion : " . htmlentities($e['message'], ENT_QUOTES);
                        }

                        oci_free_statement($insertStmt);
                    } else {
                        $e = oci_error($conn);
                        $erreur = "Erreur préparation insertion : " . htmlentities($e['message'], ENT_QUOTES);
                    }
                }
            }
        }
    }
}

$sql = "SELECT id_enclos, latitude, longitude, surface, id_zone
        FROM enclos
        ORDER BY id_enclos";

$stmt = oci_parse($conn, $sql);

if (!$stmt) {
    $e = oci_error($conn);
    die("Erreur préparation requête : " . htmlentities($e['message'], ENT_QUOTES));
}

$r = oci_execute($stmt);

if (!$r) {
    $e = oci_error($stmt);
    die("Erreur exécution requête : " . htmlentities($e['message'], ENT_QUOTES));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Enclos</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #eef6f0, #dceee3);
            margin: 0;
            padding: 30px;
            color: #243126;
        }

        .container {
            max-width: 1100px;
            margin: auto;
        }

        h1, h2 {
            color: #1f4d3b;
            margin-bottom: 20px;
        }

        .top-link {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            background: #1f4d3b;
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            transition: 0.3s;
        }

        .top-link:hover {
            background: #2d6a4f;
        }

        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .error {
            background: #fde8e8;
            color: #b42318;
            border: 1px solid #f5c2c2;
        }

        .success {
            background: #e8f7ee;
            color: #1d7a46;
            border: 1px solid #b7e4c7;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            padding: 25px;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            border-radius: 12px;
        }

        th {
            background: #1f4d3b;
            color: white;
            padding: 14px;
            text-align: center;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
        }

        tr:nth-child(even) {
            background: #f8fbf9;
        }

        tr:hover {
            background: #eef6f0;
        }

        .detail-link {
            display: inline-block;
            padding: 8px 12px;
            background: #2d6a4f;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: 0.3s;
            font-size: 14px;
        }

        .detail-link:hover {
            background: #1f4d3b;
        }

        form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            margin-bottom: 6px;
            font-weight: bold;
            color: #1f4d3b;
        }

        input {
            padding: 11px 12px;
            border: 1px solid #cbd5d1;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
        }

        input:focus {
            border-color: #2d6a4f;
            box-shadow: 0 0 0 3px rgba(45, 106, 79, 0.12);
        }

        button {
            grid-column: 1 / -1;
            background: #1f4d3b;
            color: white;
            border: none;
            padding: 13px 18px;
            border-radius: 10px;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: #2d6a4f;
        }

        @media (max-width: 768px) {
            form {
                grid-template-columns: 1fr;
            }

            body {
                padding: 15px;
            }

            .card {
                padding: 18px;
            }

            table {
                font-size: 14px;
            }

            th, td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Liste des enclos</h1>

        <a class="top-link" href="accueil.php">← Retour à l'accueil</a>

        <?php if ($erreur): ?>
            <p class="message error"><?php echo htmlspecialchars($erreur); ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="message success"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <div class="card">
            <table>
                <tr>
                    <th>ID Enclos</th>
                    <th>Latitude</th>
                    <th>Longitude</th>
                    <th>Surface</th>
                    <th>ID Zone</th>
                    <th>Détail</th>
                </tr>

                <?php while ($row = oci_fetch_assoc($stmt)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['ID_ENCLOS']); ?></td>
                        <td><?php echo htmlspecialchars($row['LATITUDE']); ?></td>
                        <td><?php echo htmlspecialchars($row['LONGITUDE']); ?></td>
                        <td><?php echo htmlspecialchars($row['SURFACE']); ?></td>
                        <td><?php echo htmlspecialchars($row['ID_ZONE']); ?></td>
                        <td>
                            <a class="detail-link" href="enclos_detail.php?id=<?php echo urlencode($row['ID_ENCLOS']); ?>">
                                Voir
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <?php if ($fonction === "Gestionnaire"): ?>
            <div class="card">
                <h2>Ajouter un enclos</h2>

                <form method="POST">
                    <div class="form-group">
                        <label>ID Enclos :</label>
                        <input type="number" name="id_enclos" required>
                    </div>

                    <div class="form-group">
                        <label>ID Zone :</label>
                        <input type="number" name="id_zone" required>
                    </div>

                    <div class="form-group">
                        <label>Latitude :</label>
                        <input type="number" step="0.000001" name="latitude" required>
                    </div>

                    <div class="form-group">
                        <label>Longitude :</label>
                        <input type="number" step="0.000001" name="longitude" required>
                    </div>

                    <div class="form-group full">
                        <label>Surface :</label>
                        <input type="number" step="0.01" name="surface" required>
                    </div>

                    <button type="submit" name="ajouter_enclos">Ajouter l'enclos</button>
                </form>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>

<?php
oci_free_statement($stmt);
oci_close($conn);
?>
