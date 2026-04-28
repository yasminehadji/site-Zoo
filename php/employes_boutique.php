<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_personnel'])) {
    header("Location: login2.php");
    exit();
}

require_once("connexion.php");

$id_personnel_connecte = $_SESSION['id_personnel'];
$fonction = $_SESSION['fonction'] ?? '';

$erreur = "";
$success = "";
$id_boutique = null;
$id_boutique_get = trim($_GET['id_boutique'] ?? '');
$boutiqueNom = "";
$listeEmployes = [];
$listeEmployesGlobale = [];
$boutiquesGestionnaire = [];
$id_personnel_ajout = "";
$est_responsable = 0;

$estGestionnaire = ($fonction === "Gestionnaire");
$estResponsableBoutique = ($fonction === "Responsable Boutique");
$estDirigeant = ($fonction === "Dirigeant");

if (!$estGestionnaire && !$estResponsableBoutique && !$estDirigeant) {
    header("Location: accueil.php");
    exit();
}


// RESPONSABLE BOUTIQUE
if ($estResponsableBoutique) {
    $sqlResp = "SELECT b.id_boutique, b.nom_boutique
                FROM employe_boutique eb
                JOIN boutique b ON eb.id_boutique = b.id_boutique
                WHERE eb.id_personnel = :id_personnel
                  AND eb.est_responsable = 1";

    $stmtResp = oci_parse($conn, $sqlResp);
    oci_bind_by_name($stmtResp, ':id_personnel', $id_personnel_connecte);
    oci_execute($stmtResp);

    $resp = oci_fetch_assoc($stmtResp);
    oci_free_statement($stmtResp);

    if (!$resp) {
        oci_close($conn);
        header("Location: accueil.php");
        exit();
    }

    $id_boutique = $resp['ID_BOUTIQUE'];
    $boutiqueNom = $resp['NOM_BOUTIQUE'];
}


/* GESTIONNAIRE + DIRIGEANT -> chargent les boutiques*/

if ($estGestionnaire || $estDirigeant) {
    $sqlBoutiques = "SELECT id_boutique, nom_boutique
                     FROM boutique
                     ORDER BY nom_boutique";
    $stmtBoutiques = oci_parse($conn, $sqlBoutiques);
    oci_execute($stmtBoutiques);

    while ($row = oci_fetch_assoc($stmtBoutiques)) {
        $boutiquesGestionnaire[] = $row;
    }

    oci_free_statement($stmtBoutiques);

    if ($id_boutique_get !== '') {
        $id_boutique = $id_boutique_get;
    } elseif (!empty($boutiquesGestionnaire)) {
        $id_boutique = $boutiquesGestionnaire[0]['ID_BOUTIQUE'];
    }
}

/* AJOUT EMPLOYÉ : Gestionnaire + Responsable + Dirigeant */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_employe'])) {
    $id_personnel_ajout = trim($_POST['id_personnel'] ?? '');
    $est_responsable = isset($_POST['est_responsable']) ? 1 : 0;

    $id_boutique_form = ($estGestionnaire || $estDirigeant)
        ? trim($_POST['id_boutique'] ?? '')
        : $id_boutique;

    if ($id_personnel_ajout === '' || $id_boutique_form === '' || $id_boutique_form === null) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        $sqlCheckPers = "SELECT id_personnel
                         FROM personnel
                         WHERE id_personnel = :id_personnel";
        $stmtCheckPers = oci_parse($conn, $sqlCheckPers);
        oci_bind_by_name($stmtCheckPers, ':id_personnel', $id_personnel_ajout);
        oci_execute($stmtCheckPers);

        if (!oci_fetch_assoc($stmtCheckPers)) {
            $erreur = "Cet employé n'existe pas.";
        } else {
            $sqlCheckLien = "SELECT id_personnel
                             FROM employe_boutique
                             WHERE id_personnel = :id_personnel
                               AND id_boutique = :id_boutique";
            $stmtCheckLien = oci_parse($conn, $sqlCheckLien);
            oci_bind_by_name($stmtCheckLien, ':id_personnel', $id_personnel_ajout);
            oci_bind_by_name($stmtCheckLien, ':id_boutique', $id_boutique_form);
            oci_execute($stmtCheckLien);

            if (oci_fetch_assoc($stmtCheckLien)) {
                $erreur = "Déjà affecté à cette boutique.";
            } else {
                if ($est_responsable == 1) {
                    $sqlCheckResp = "SELECT id_personnel
                                     FROM employe_boutique
                                     WHERE id_boutique = :id_boutique
                                       AND est_responsable = 1";
                    $stmtCheckResp = oci_parse($conn, $sqlCheckResp);
                    oci_bind_by_name($stmtCheckResp, ':id_boutique', $id_boutique_form);
                    oci_execute($stmtCheckResp);

                    if (oci_fetch_assoc($stmtCheckResp)) {
                        $erreur = "Cette boutique a déjà un responsable.";
                    }

                    oci_free_statement($stmtCheckResp);
                }

                if (empty($erreur)) {
                    $nouvelle_fonction = ($est_responsable == 1)
                        ? "Responsable Boutique"
                        : "Employé Boutique";

                    $sqlUpdatePers = "UPDATE personnel
                                      SET fonction = :fonction
                                      WHERE id_personnel = :id_personnel";
                    $stmtUpdatePers = oci_parse($conn, $sqlUpdatePers);
                    oci_bind_by_name($stmtUpdatePers, ':fonction', $nouvelle_fonction);
                    oci_bind_by_name($stmtUpdatePers, ':id_personnel', $id_personnel_ajout);

                    $rUpdate = oci_execute($stmtUpdatePers, OCI_NO_AUTO_COMMIT);

                    if (!$rUpdate) {
                        $e = oci_error($stmtUpdatePers);
                        $erreur = "Erreur mise à jour personnel : " . htmlentities($e['message'], ENT_QUOTES);
                    }

                    oci_free_statement($stmtUpdatePers);

                    if (empty($erreur)) {
                        $sqlInsert = "INSERT INTO employe_boutique (id_personnel, id_boutique, est_responsable)
                                      VALUES (:id_personnel, :id_boutique, :est_responsable)";
                        $stmtInsert = oci_parse($conn, $sqlInsert);
                        oci_bind_by_name($stmtInsert, ':id_personnel', $id_personnel_ajout);
                        oci_bind_by_name($stmtInsert, ':id_boutique', $id_boutique_form);
                        oci_bind_by_name($stmtInsert, ':est_responsable', $est_responsable);

                        $rInsert = oci_execute($stmtInsert, OCI_NO_AUTO_COMMIT);

                        if ($rInsert) {
                            oci_commit($conn);
                            $success = "Employé ajouté avec succès.";
                            $id_personnel_ajout = "";
                            $est_responsable = 0;
                            $id_boutique = $id_boutique_form;
                        } else {
                            $e = oci_error($stmtInsert);
                            oci_rollback($conn);
                            $erreur = "Erreur : " . htmlentities($e['message'], ENT_QUOTES);
                        }

                        oci_free_statement($stmtInsert);
                    } else {
                        oci_rollback($conn);
                    }
                }
            }

            oci_free_statement($stmtCheckLien);
        }

        oci_free_statement($stmtCheckPers);
    }
}


/* NOM DE LA BOUTIQUE ACTIVE */
if (!empty($id_boutique)) {
    $sqlBoutiqueNom = "SELECT nom_boutique
                       FROM boutique
                       WHERE id_boutique = :id_boutique";
    $stmtBoutiqueNom = oci_parse($conn, $sqlBoutiqueNom);
    oci_bind_by_name($stmtBoutiqueNom, ':id_boutique', $id_boutique);
    oci_execute($stmtBoutiqueNom);

    $boutiqueRow = oci_fetch_assoc($stmtBoutiqueNom);
    $boutiqueNom = $boutiqueRow ? $boutiqueRow['NOM_BOUTIQUE'] : "";

    oci_free_statement($stmtBoutiqueNom);
}


/* LISTE DES EMPLOYÉS DE LA BOUTIQUE ACTIVE (pour tout le monde)*/
if (!empty($id_boutique)) {
    $sqlListe = "SELECT eb.id_personnel, p.nom, p.prenom, p.fonction, eb.est_responsable
                 FROM employe_boutique eb
                 JOIN personnel p ON eb.id_personnel = p.id_personnel
                 WHERE eb.id_boutique = :id_boutique
                 ORDER BY eb.est_responsable DESC, p.nom, p.prenom";
    $stmtListe = oci_parse($conn, $sqlListe);
    oci_bind_by_name($stmtListe, ':id_boutique', $id_boutique);
    oci_execute($stmtListe);

    while ($row = oci_fetch_assoc($stmtListe)) {
        $listeEmployes[] = $row;
    }

    oci_free_statement($stmtListe);
}


/* LISTE GLOBALE DIRIGEANT */
if ($estDirigeant) {
    $sqlListeGlobale = "SELECT eb.id_personnel,
                               p.nom,
                               p.prenom,
                               p.fonction,
                               eb.est_responsable,
                               b.id_boutique,
                               b.nom_boutique
                        FROM employe_boutique eb
                        JOIN personnel p ON eb.id_personnel = p.id_personnel
                        JOIN boutique b ON eb.id_boutique = b.id_boutique
                        ORDER BY b.nom_boutique, eb.est_responsable DESC, p.nom, p.prenom";

    $stmtListeGlobale = oci_parse($conn, $sqlListeGlobale);
    oci_execute($stmtListeGlobale);

    while ($row = oci_fetch_assoc($stmtListeGlobale)) {
        $listeEmployesGlobale[] = $row;
    }

    oci_free_statement($stmtListeGlobale);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les employés de boutique</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
<div class="page-container assignment-page">
    <section class="hero-panel">
        <div class="hero-top">
            <div>
                <span class="hero-kicker">🏪 Gestion boutique</span>
                <h1>Gérer les employés</h1>
                <p class="hero-text">
                    Ajoute, affiche et supprime les employés d'une boutique.
                    Une seule couronne par boutique, sinon ça finit en petit drame administratif.
                </p>

                <div class="inline-stack">
                    <span class="meta-chip">👤 <?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></span>
                    <span class="meta-chip is-gold">Fonction : <?php echo htmlspecialchars($_SESSION['fonction']); ?></span>
                    <?php if (!empty($boutiqueNom)): ?>
                        <span class="meta-chip">Boutique : <?php echo htmlspecialchars($boutiqueNom); ?></span>
                    <?php endif; ?>
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
                <h3>Formulaire d'ajout</h3>
                <p class="muted-text">Saisis l'identifiant du personnel et choisis son rôle dans la boutique.</p>
            </div>

            <form method="POST" id="employesBoutiqueForm" data-boutique-nom="<?php echo htmlspecialchars($boutiqueNom); ?>">
                <div class="form-grid single-column">
                    <?php if ($estGestionnaire || $estDirigeant): ?>
                        <div class="form-group">
                            <label for="id_boutique">Boutique</label>
                            <select
                                name="id_boutique"
                                id="id_boutique"
                                required
                                onchange="window.location='employes_boutique.php?id_boutique=' + this.value">
                                <option value="">-- Choisir une boutique --</option>
                                <?php foreach ($boutiquesGestionnaire as $b): ?>
                                    <option value="<?php echo htmlspecialchars($b['ID_BOUTIQUE']); ?>"
                                        <?php if ((string)$id_boutique === (string)$b['ID_BOUTIQUE']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($b['NOM_BOUTIQUE']); ?> (ID : <?php echo htmlspecialchars($b['ID_BOUTIQUE']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="id_personnel">ID employé</label>
                        <input type="text" name="id_personnel" id="id_personnel" value="<?php echo htmlspecialchars($id_personnel_ajout); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label" for="est_responsable">
                            <input type="checkbox" name="est_responsable" id="est_responsable" value="1" <?php if ($est_responsable == 1) echo 'checked'; ?>>
                            Définir comme responsable
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" name="ajouter_employe" class="btn">✨ Ajouter l'employé</button>
                    <a href="boutique.php" class="btn btn-light">↩ Retour</a>
                </div>
            </form>
        </section>

        <aside class="summary-card" id="employeBoutiquePreview">
            <h3>🧾 Aperçu</h3>
            <ul class="compact-list">
                <li><strong>Boutique :</strong> <?php echo !empty($boutiqueNom) ? htmlspecialchars($boutiqueNom) : 'Non sélectionnée'; ?></li>
                <li><strong>ID employé :</strong> <?php echo !empty($id_personnel_ajout) ? htmlspecialchars($id_personnel_ajout) : 'Non renseigné'; ?></li>
                <li><strong>Rôle attribué :</strong> <?php echo $est_responsable == 1 ? 'Responsable boutique' : 'Employé boutique'; ?></li>
            </ul>
            <p class="page-note">Un seul responsable est autorisé par boutique.</p>
        </aside>
    </div>

    <?php if (!empty($id_boutique)): ?>
        <section class="management-panel status-panel">
            <div class="section-intro">
                <h2>Équipe de la boutique</h2>
                <p class="muted-text"><?php echo htmlspecialchars($boutiqueNom); ?></p>
            </div>

            <?php if (!empty($listeEmployes)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Fonction</th>
                            <th>Responsable</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listeEmployes as $emp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($emp['ID_PERSONNEL']); ?></td>
                                <td><?php echo htmlspecialchars($emp['NOM']); ?></td>
                                <td><?php echo htmlspecialchars($emp['PRENOM']); ?></td>
                                <td><?php echo htmlspecialchars($emp['FONCTION']); ?></td>
                                <td><?php echo $emp['EST_RESPONSABLE'] == 1 ? 'Oui' : 'Non'; ?></td>
                                <td>
                                    <?php if ((string)$emp['ID_PERSONNEL'] !== (string)$_SESSION['id_personnel']): ?>
                                        <a class="btn btn-delete"
                                           href="supprimer_employe_boutique.php?id_personnel=<?php echo urlencode($emp['ID_PERSONNEL']); ?>&id_boutique=<?php echo urlencode($id_boutique); ?>"
                                           onclick="return confirm('Confirmer la suppression de cet employé de la boutique ?');">
                                            Supprimer
                                        </a>
                                    <?php else: ?>
                                        <span class="muted-text">Impossible</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="muted-text">Aucun employé n'est encore rattaché à cette boutique.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($estDirigeant): ?>
        <section class="management-panel status-panel">
            <div class="section-intro">
                <h2>Vue globale des boutiques</h2>
                <p class="muted-text">Tous les employés de toutes les boutiques.</p>
            </div>

            <?php if (!empty($listeEmployesGlobale)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Prénom</th>
                            <th>Fonction</th>
                            <th>Boutique</th>
                            <th>Responsable</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listeEmployesGlobale as $emp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($emp['ID_PERSONNEL']); ?></td>
                                <td><?php echo htmlspecialchars($emp['NOM']); ?></td>
                                <td><?php echo htmlspecialchars($emp['PRENOM']); ?></td>
                                <td><?php echo htmlspecialchars($emp['FONCTION']); ?></td>
                                <td><?php echo htmlspecialchars($emp['NOM_BOUTIQUE']) . " (ID : " . htmlspecialchars($emp['ID_BOUTIQUE']) . ")"; ?></td>
                                <td><?php echo $emp['EST_RESPONSABLE'] == 1 ? 'Oui' : 'Non'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="muted-text">Aucun employé n'est encore rattaché à une boutique.</p>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</div>

<script src="js/common.js"></script>
<script src="js/pages.js"></script>
</body>
</html>
<?php
oci_close($conn);
?>
