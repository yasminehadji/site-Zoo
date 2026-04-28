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
$success = "";

$id_boutique = "";
$id_personnel = "";
//remplir la liste deroulante des boutiques 
$sqlBoutiques = "SELECT id_boutique, nom_boutique
                 FROM boutique
                 ORDER BY nom_boutique";
$stmtBoutiques = oci_parse($conn, $sqlBoutiques);
oci_execute($stmtBoutiques);

$sqlResponsables = "SELECT id_personnel, nom, prenom
                    FROM personnel
                    WHERE fonction = 'Responsable Boutique'
                    ORDER BY nom, prenom";
$stmtResponsables = oci_parse($conn, $sqlResponsables);
oci_execute($stmtResponsables);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_boutique = trim($_POST['id_boutique'] ?? '');
    $id_personnel = trim($_POST['id_personnel'] ?? '');

    if ($id_boutique === '' || $id_personnel === '') {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        $sqlCheckResp = "SELECT id_personnel
                         FROM employe_boutique
                         WHERE id_boutique = :id_boutique
                           AND est_responsable = 1";
        $stmtCheckResp = oci_parse($conn, $sqlCheckResp);
        oci_bind_by_name($stmtCheckResp, ':id_boutique', $id_boutique);
        oci_execute($stmtCheckResp);

        if (oci_fetch_assoc($stmtCheckResp)) {
            $erreur = "Cette boutique a déjà un responsable.";
        } else {
            oci_free_statement($stmtCheckResp);

            $sqlCheckLien = "SELECT id_personnel
                             FROM employe_boutique
                             WHERE id_personnel = :id_personnel
                               AND id_boutique = :id_boutique";
            $stmtCheckLien = oci_parse($conn, $sqlCheckLien);
            oci_bind_by_name($stmtCheckLien, ':id_personnel', $id_personnel);
            oci_bind_by_name($stmtCheckLien, ':id_boutique', $id_boutique);
            oci_execute($stmtCheckLien);

            if (oci_fetch_assoc($stmtCheckLien)) {
                $erreur = "Cet employé est déjà affecté à cette boutique.";
            } else {
                oci_free_statement($stmtCheckLien);

                $sqlInsert = "INSERT INTO employe_boutique (id_personnel, id_boutique, est_responsable)
                              VALUES (:id_personnel, :id_boutique, 1)";
                $stmtInsert = oci_parse($conn, $sqlInsert);
                oci_bind_by_name($stmtInsert, ':id_personnel', $id_personnel);
                oci_bind_by_name($stmtInsert, ':id_boutique', $id_boutique);

                $r = oci_execute($stmtInsert, OCI_COMMIT_ON_SUCCESS);

                if ($r) {
                    $success = "Responsable affecté avec succès.";
                    $id_boutique = "";
                    $id_personnel = "";
                } else {
                    $e = oci_error($stmtInsert);
                    $erreur = "Erreur affectation : " . htmlentities($e['message'], ENT_QUOTES);
                }

                oci_free_statement($stmtInsert);
            }

            if (isset($stmtCheckLien)) {
                oci_free_statement($stmtCheckLien);
            }
        }

        if (isset($stmtCheckResp)) {
            @oci_free_statement($stmtCheckResp);
        }
    }

    oci_free_statement($stmtBoutiques);
    oci_free_statement($stmtResponsables);

    $stmtBoutiques = oci_parse($conn, $sqlBoutiques);
    oci_execute($stmtBoutiques);

    $stmtResponsables = oci_parse($conn, $sqlResponsables);
    oci_execute($stmtResponsables);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affecter un responsable de boutique</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
<div class="page-container assignment-page">
    <section class="hero-panel">
        <div class="hero-top">
            <div>
                <span class="hero-kicker">🛍️ Gestion boutique</span>
                <h1>Affecter un responsable</h1>
                <p class="hero-text">Associe un responsable à une boutique avec une présentation plus élégante, sans toucher à la logique PHP existante.</p>
                <div class="inline-stack">
                    <span class="meta-chip">👤 <?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></span>
                    <span class="meta-chip is-gold">Fonction : <?php echo htmlspecialchars($_SESSION['fonction']); ?></span>
                </div>
            </div>

            <div class="hero-actions">
                <a href="boutique.php" class="btn btn-light">🏪 Retour boutique</a>
                <a href="accueil.php" class="btn btn-outline">🏠 Accueil</a>
                <a href="logout.php" class="btn btn-logout">🚪 Déconnexion</a>
            </div>
        </div>
    </section>

    <?php if ($erreur): ?>
        <p class="erreur"><?php echo htmlspecialchars($erreur); ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>

    <div class="form-shell">
        <section class="management-panel">
            <div class="section-intro">
                <h2>Formulaire d'affectation</h2>
                <p class="muted-text">Sélectionne la boutique et le responsable à associer.</p>
            </div>

            <form method="POST" id="assignResponsableForm">
                <div class="form-grid single-column">
                    <div class="form-group">
                        <label for="id_boutique">Boutique</label>
                        <select name="id_boutique" id="id_boutique" required>
                            <option value="">-- Choisir une boutique --</option>
                            <?php while ($b = oci_fetch_assoc($stmtBoutiques)): ?>
                                <option value="<?php echo htmlspecialchars($b['ID_BOUTIQUE']); ?>"
                                    <?php if ($id_boutique == $b['ID_BOUTIQUE']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($b['NOM_BOUTIQUE']); ?> (ID : <?php echo htmlspecialchars($b['ID_BOUTIQUE']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="id_personnel">Responsable</label>
                        <select name="id_personnel" id="id_personnel" required>
                            <option value="">-- Choisir un responsable --</option>
                            <?php while ($p = oci_fetch_assoc($stmtResponsables)): ?>
                                <option value="<?php echo htmlspecialchars($p['ID_PERSONNEL']); ?>"
                                    <?php if ($id_personnel == $p['ID_PERSONNEL']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($p['NOM'] . ' ' . $p['PRENOM']); ?> (<?php echo htmlspecialchars($p['ID_PERSONNEL']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn">✨ Affecter le responsable</button>
                    <a href="boutique.php" class="btn btn-light">↩ Retour</a>
                </div>
            </form>
        </section>

        <aside class="summary-card" id="assignmentPreview">
            <h3>✨ Aperçu de l'affectation</h3>
            <ul class="compact-list">
                <li><strong>Boutique :</strong> Non sélectionnée</li>
                <li><strong>Responsable :</strong> Non sélectionné</li>
            </ul>
            <p class="page-note">Les effets visuels et l'aperçu se font côté interface uniquement.</p>
        </aside>
    </div>
</div>

<script src="js/common.js"></script>
<script src="js/pages.js"></script>
</body>
</html>

<?php
oci_free_statement($stmtBoutiques);
oci_free_statement($stmtResponsables);
oci_close($conn);
?>