<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_personnel'])) {
    header("Location: login2.php");
    exit();
}

require_once("connexion.php");

$id_personnel = $_SESSION['id_personnel'];
$fonction = $_SESSION['fonction'];

$erreur = "";
$success = "";


/* Vérifier si l'utilisateur est responsable d'une boutique */
$id_boutique_responsable = null;

$sqlResp = "SELECT id_boutique
            FROM employe_boutique
            WHERE id_personnel = :id_personnel
            AND est_responsable = 1";

$stmtResp = oci_parse($conn, $sqlResp);
oci_bind_by_name($stmtResp, ':id_personnel', $id_personnel);
oci_execute($stmtResp);

$resp = oci_fetch_assoc($stmtResp);
if ($resp) {
    $id_boutique_responsable = $resp['ID_BOUTIQUE'];
}
oci_free_statement($stmtResp);


/* Autorisation */
$autorise = in_array($fonction, ["Responsable Boutique", "Gestionnaire", "Comptable","Dirigeant"]);

if (!$autorise) {
    oci_close($conn);
    header("Location: accueil.php");
    exit();
}

/* Insertion et  mise à jour du CA journalier */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enregistrer_ca'])) {

    if ($fonction !== "Responsable Boutique") {
        $erreur = "Seul le responsable de sa propre boutique peut enregistrer le chiffre d'affaires.";
    } else {
        $id_boutique = trim($_POST['id_boutique'] ?? '');
        $date_ca = trim($_POST['date_ca'] ?? '');
        $chiffre_affaire = trim($_POST['chiffre_affaire'] ?? '');

        if ($id_boutique === '' || $date_ca === '' || $chiffre_affaire === '') {
            $erreur = "Veuillez remplir tous les champs.";
        } elseif (!is_numeric($chiffre_affaire) || $chiffre_affaire < 0) {
            $erreur = "Le chiffre d'affaires doit être un nombre positif.";
        } else {


            /* Si c responsable boutique, il ne peut saisir que pour SA boutique */
            if ($fonction === "Responsable Boutique" && $id_boutique != $id_boutique_responsable) {
                $erreur = "Vous ne pouvez saisir le CA que pour votre boutique.";
            } else {


                /* Vérifier si la date existe dans calendrier_ca */
                $sqlCal = "SELECT date_jour
                           FROM calendrier_ca
                           WHERE date_jour = TO_DATE(:date_ca, 'YYYY-MM-DD')";
                $stmtCal = oci_parse($conn, $sqlCal);
                oci_bind_by_name($stmtCal, ':date_ca', $date_ca);
                oci_execute($stmtCal);

                $dateExiste = oci_fetch_assoc($stmtCal);
                oci_free_statement($stmtCal);


                /* Si la date n'existe pas, on l'ajoute */
                if (!$dateExiste) {
                    $sqlInsertCal = "INSERT INTO calendrier_ca (date_jour)
                                     VALUES (TO_DATE(:date_ca, 'YYYY-MM-DD'))";
                    $stmtInsertCal = oci_parse($conn, $sqlInsertCal);
                    oci_bind_by_name($stmtInsertCal, ':date_ca', $date_ca);

                    $rCal = oci_execute($stmtInsertCal, OCI_NO_AUTO_COMMIT);

                    if (!$rCal) {
                        $e = oci_error($stmtInsertCal);
                        $erreur = "Erreur insertion calendrier : " . htmlentities($e['message'], ENT_QUOTES);
                    }

                    oci_free_statement($stmtInsertCal);
                }

                if (empty($erreur)) {
                    /* Vérifier si une ligne existe déjà */
                    $sqlCheck = "SELECT id_boutique
                                 FROM ca_journalier
                                 WHERE id_boutique = :id_boutique
                                 AND date_ca = TO_DATE(:date_ca, 'YYYY-MM-DD')";
                    $stmtCheck = oci_parse($conn, $sqlCheck);
                    oci_bind_by_name($stmtCheck, ':id_boutique', $id_boutique);
                    oci_bind_by_name($stmtCheck, ':date_ca', $date_ca);
                    oci_execute($stmtCheck);

                    $existe = oci_fetch_assoc($stmtCheck);
                    oci_free_statement($stmtCheck);

                    if ($existe) {
                        $sqlUpdate = "UPDATE ca_journalier
                                      SET chiffre_affaire = :chiffre_affaire
                                      WHERE id_boutique = :id_boutique
                                      AND date_ca = TO_DATE(:date_ca, 'YYYY-MM-DD')";
                        $stmtUpdate = oci_parse($conn, $sqlUpdate);
                        oci_bind_by_name($stmtUpdate, ':chiffre_affaire', $chiffre_affaire);
                        oci_bind_by_name($stmtUpdate, ':id_boutique', $id_boutique);
                        oci_bind_by_name($stmtUpdate, ':date_ca', $date_ca);

                        $r = oci_execute($stmtUpdate, OCI_NO_AUTO_COMMIT);

                        if ($r) {
                            oci_commit($conn);
                            $success = "Chiffre d'affaires mis à jour avec succès.";
                        } else {
                            oci_rollback($conn);
                            $e = oci_error($stmtUpdate);
                            $erreur = "Erreur mise à jour : " . htmlentities($e['message'], ENT_QUOTES);
                        }

                        oci_free_statement($stmtUpdate);
                    } else {
                        $sqlInsert = "INSERT INTO ca_journalier (id_boutique, date_ca, chiffre_affaire)
                                      VALUES (:id_boutique, TO_DATE(:date_ca, 'YYYY-MM-DD'), :chiffre_affaire)";
                        $stmtInsert = oci_parse($conn, $sqlInsert);
                        oci_bind_by_name($stmtInsert, ':id_boutique', $id_boutique);
                        oci_bind_by_name($stmtInsert, ':date_ca', $date_ca);
                        oci_bind_by_name($stmtInsert, ':chiffre_affaire', $chiffre_affaire);

                        $r = oci_execute($stmtInsert, OCI_NO_AUTO_COMMIT);

                        if ($r) {
                            oci_commit($conn);
                            if (!$dateExiste) {
                                $success = "Date ajoutée au calendrier et chiffre d'affaires enregistré avec succès.";
                            } else {
                                $success = "Chiffre d'affaires enregistré avec succès.";
                            }
                        } else {
                            oci_rollback($conn);
                            $e = oci_error($stmtInsert);
                            $erreur = "Erreur insertion : " . htmlentities($e['message'], ENT_QUOTES);
                        }

                        oci_free_statement($stmtInsert);
                    }
                }
            }
        }
    }
}

/* Récupérer les boutiques */
if ($fonction === "Responsable Boutique") {
    $sqlBoutiques = "SELECT id_boutique, nom_boutique
                     FROM boutique
                     WHERE id_boutique = :id_boutique
                     ORDER BY id_boutique";
    $stmtBoutiques = oci_parse($conn, $sqlBoutiques);
    oci_bind_by_name($stmtBoutiques, ':id_boutique', $id_boutique_responsable);
} else {
    $sqlBoutiques = "SELECT id_boutique, nom_boutique
                     FROM boutique
                     ORDER BY id_boutique";
    $stmtBoutiques = oci_parse($conn, $sqlBoutiques);
}
oci_execute($stmtBoutiques);

/* Liste du CA */
if ($fonction === "Responsable Boutique") {
    $sqlListe = "SELECT cj.id_boutique,
                        b.nom_boutique,
                        TO_CHAR(cj.date_ca, 'YYYY-MM-DD') AS date_ca,
                        cj.chiffre_affaire
                 FROM ca_journalier cj
                 JOIN boutique b ON cj.id_boutique = b.id_boutique
                 WHERE cj.id_boutique = :id_boutique
                 ORDER BY cj.date_ca DESC";
    $stmtListe = oci_parse($conn, $sqlListe);
    oci_bind_by_name($stmtListe, ':id_boutique', $id_boutique_responsable);
} else {
    $sqlListe = "SELECT cj.id_boutique,
                        b.nom_boutique,
                        TO_CHAR(cj.date_ca, 'YYYY-MM-DD') AS date_ca,
                        cj.chiffre_affaire
                 FROM ca_journalier cj
                 JOIN boutique b ON cj.id_boutique = b.id_boutique
                 ORDER BY cj.id_boutique, cj.date_ca DESC";
    $stmtListe = oci_parse($conn, $sqlListe);
}
oci_execute($stmtListe);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Chiffre d'affaires</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #eef6f0, #dceee3);
            margin: 0;
            padding: 30px;
            color: #243126;
        }
        .container {
            max-width: 1150px;
            margin: auto;
        }
        h1, h2 {
            color: #1f4d3b;
        }
        .top-actions {
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            text-decoration: none;
            background: #1f4d3b;
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            margin-right: 10px;
        }
        .btn:hover {
            background: #2d6a4f;
        }
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            padding: 25px;
            margin-bottom: 30px;
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
        form {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 180px;
            gap: 16px;
            align-items: end;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
            color: #1f4d3b;
        }
        input, select {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid #cbd5d1;
            border-radius: 8px;
            font-size: 14px;
        }
        button {
            background: #1f4d3b;
            color: white;
            border: none;
            padding: 13px 18px;
            border-radius: 10px;
            font-size: 15px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background: #2d6a4f;
        }
        table {
            width: 100%;
            border-collapse: collapse;
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
        @media (max-width: 900px) {
            form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Chiffre d'affaires des boutiques</h1>

    <div class="top-actions">
        <a class="btn" href="accueil.php">Retour accueil</a>
        <a class="btn" href="ca_mensuel.php">Voir CA mensuel</a>
        <a class="btn" href="ca_annuel.php">Voir CA annuel</a>
    </div>

    <?php if ($erreur): ?>
        <p class="message error"><?php echo htmlspecialchars($erreur); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="message success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <?php if ($fonction === "Responsable Boutique"): ?>
        <div class="card">
            <h2>Saisir le chiffre d'affaires journalier</h2>

            <form method="POST">
                <div>
                    <label for="id_boutique">Boutique</label>
                    <select name="id_boutique" id="id_boutique" required>
                        <option value="">-- Choisir une boutique --</option>
                        <?php while ($b = oci_fetch_assoc($stmtBoutiques)): ?>
                            <option value="<?php echo htmlspecialchars($b['ID_BOUTIQUE']); ?>">
                                <?php echo htmlspecialchars($b['NOM_BOUTIQUE']); ?> (ID : <?php echo htmlspecialchars($b['ID_BOUTIQUE']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div>
                    <label for="date_ca">Date</label>
                    <input type="date" name="date_ca" id="date_ca" required>
                </div>

                <div>
                    <label for="chiffre_affaire">Chiffre d'affaires</label>
                    <input type="number" step="0.01" min="0" name="chiffre_affaire" id="chiffre_affaire" required>
                </div>

                <div>
                    <button type="submit" name="enregistrer_ca">Enregistrer</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <?php oci_free_statement($stmtBoutiques); ?>
    <?php endif; ?>

    <div class="card">
        <h2>Historique journalier</h2>

        <table>
            <tr>
                <th>ID Boutique</th>
                <th>Nom boutique</th>
                <th>Date</th>
                <th>Chiffre d'affaires</th>
            </tr>

            <?php while ($row = oci_fetch_assoc($stmtListe)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['ID_BOUTIQUE']); ?></td>
                    <td><?php echo htmlspecialchars($row['NOM_BOUTIQUE']); ?></td>
                    <td><?php echo htmlspecialchars($row['DATE_CA']); ?></td>
                    <td><?php echo htmlspecialchars($row['CHIFFRE_AFFAIRE']); ?> €</td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>
</body>
</html>

<?php
oci_free_statement($stmtListe);
oci_close($conn);
?>
