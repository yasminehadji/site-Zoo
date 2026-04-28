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

$id = trim($_GET['id'] ?? '');

if ($id === '') {
    die("Identifiant manquant.");
}

if ($id == $_SESSION['id_personnel']) {
    die("Vous ne pouvez pas supprimer votre propre compte.");
}

/* Vérifier que le personnel existe */
$sqlCheck = "SELECT id_personnel, nom, prenom
             FROM personnel
             WHERE id_personnel = :id";

$stmtCheck = oci_parse($conn, $sqlCheck);

if (!$stmtCheck) {
    $e = oci_error($conn);
    die("Erreur préparation vérification : " . htmlentities($e['message'], ENT_QUOTES));
}

oci_bind_by_name($stmtCheck, ':id', $id);
$rCheck = oci_execute($stmtCheck);

if (!$rCheck) {
    $e = oci_error($stmtCheck);
    die("Erreur exécution vérification : " . htmlentities($e['message'], ENT_QUOTES));
}

$personnel = oci_fetch_assoc($stmtCheck);
oci_free_statement($stmtCheck);

if (!$personnel) {
    oci_close($conn);
    die("Personnel introuvable.");
}

/* 1. Mettre à NULL id_personnel_1 dans PERSONNEL (référence superviseur) */
$sqlUpdateSup1 = "UPDATE personnel SET id_personnel_1 = NULL WHERE id_personnel_1 = :id";
$stmtUpdateSup1 = oci_parse($conn, $sqlUpdateSup1);

if (!$stmtUpdateSup1) {
    $e = oci_error($conn);
    oci_close($conn);
    die("Erreur préparation mise à NULL id_personnel_1 : " . htmlentities($e['message'], ENT_QUOTES));
}

oci_bind_by_name($stmtUpdateSup1, ':id', $id);
$rUpdateSup1 = oci_execute($stmtUpdateSup1, OCI_NO_AUTO_COMMIT);

if (!$rUpdateSup1) {
    $e = oci_error($stmtUpdateSup1);
    oci_free_statement($stmtUpdateSup1);
    oci_rollback($conn);
    oci_close($conn);
    die("Erreur mise à NULL id_personnel_1 : " . htmlentities($e['message'], ENT_QUOTES));
}

oci_free_statement($stmtUpdateSup1);

/* 2. Mettre à NULL id_personnel_2 dans PERSONNEL (référence superviseur) */
$sqlUpdateSup2 = "UPDATE personnel SET id_personnel_2 = NULL WHERE id_personnel_2 = :id";
$stmtUpdateSup2 = oci_parse($conn, $sqlUpdateSup2);

if (!$stmtUpdateSup2) {
    $e = oci_error($conn);
    oci_rollback($conn);
    oci_close($conn);
    die("Erreur préparation mise à NULL id_personnel_2 : " . htmlentities($e['message'], ENT_QUOTES));
}

oci_bind_by_name($stmtUpdateSup2, ':id', $id);
$rUpdateSup2 = oci_execute($stmtUpdateSup2, OCI_NO_AUTO_COMMIT);

if (!$rUpdateSup2) {
    $e = oci_error($stmtUpdateSup2);
    oci_free_statement($stmtUpdateSup2);
    oci_rollback($conn);
    oci_close($conn);
    die("Erreur mise à NULL id_personnel_2 : " . htmlentities($e['message'], ENT_QUOTES));
}

oci_free_statement($stmtUpdateSup2);

/* 3. Supprimer les liens dans SOIGNER */
$sqlDeleteSoigner = "DELETE FROM soigner WHERE id_personnel = :id";
$stmtDeleteSoigner = oci_parse($conn, $sqlDeleteSoigner);

if (!$stmtDeleteSoigner) {
    $e = oci_error($conn);
    oci_rollback($conn);
    oci_close($conn);
    die("Erreur préparation suppression soigner : " . htmlentities($e['message'], ENT_QUOTES));
}

oci_bind_by_name($stmtDeleteSoigner, ':id', $id);
$rDeleteSoigner = oci_execute($stmtDeleteSoigner, OCI_NO_AUTO_COMMIT);

if (!$rDeleteSoigner) {
    $e = oci_error($stmtDeleteSoigner);
    oci_free_statement($stmtDeleteSoigner);
    oci_rollback($conn);
    oci_close($conn);
    die("Erreur suppression dans soigner : " . htmlentities($e['message'], ENT_QUOTES));
}

oci_free_statement($stmtDeleteSoigner);

/* 4. Supprimer les liens dans NOURRIR */
$sqlDeleteNourrir = "DELETE FROM nourrir WHERE id_personnel = :id";
$stmtDeleteNourrir = oci_parse($conn, $sqlDeleteNourrir);

if (!$stmtDeleteNourrir) {
    $e = oci_error($conn);
    oci_rollback($conn);
    oci_close($conn);
    die("Erreur préparation suppression nourrir : " . htmlentities($e['message'], ENT_QUOTES));
}

oci_bind_by_name($stmtDeleteNourrir, ':id', $id);
$rDeleteNourrir = oci_execute($stmtDeleteNourrir, OCI_NO_AUTO_COMMIT);

if (!$rDeleteNourrir) {
    $e = oci_error($stmtDeleteNourrir);
    oci_free_statement($stmtDeleteNourrir);
    oci_rollback($conn);
    oci_close($conn);
    die("Erreur suppression dans nourrir : " . htmlentities($e['message'], ENT_QUOTES));
}

oci_free_statement($stmtDeleteNourrir);

/* 5. Supprimer les liens dans PERSONNEL_TECHNIQUE */
$sqlDeleteTechnique = "DELETE FROM personnel_technique WHERE id_personnel = :id";
$stmtDeleteTechnique = oci_parse($conn, $sqlDeleteTechnique);

if (!$stmtDeleteTechnique) {
    $e = oci_error($conn);
    oci_rollback($conn);
    oci_close($conn);
    die("Erreur préparation suppression personnel_technique : " . htmlentities($e['message'], ENT_QUOTES));
}

oci_bind_by_name($stmtDeleteTechnique, ':id', $id);
$rDeleteTechnique = oci_execute($stmtDeleteTechnique, OCI_NO_AUTO_COMMIT);

if (!$rDeleteTechnique) {
    $e = oci_error($stmtDeleteTechnique);
    oci_free_statement($stmtDeleteTechnique);
    oci_rollback($conn);
    oci_close($conn);
    die("Erreur suppression dans personnel_technique : " . htmlentities($e['message'], ENT_QUOTES));
}

oci_free_statement($stmtDeleteTechnique);

/* 6. Supprimer les liens dans EMPLOYE_BOUTIQUE */
$sqlDeleteBoutique = "DELETE FROM employe_boutique WHERE id_personnel = :id";
$stmtDeleteBoutique = oci_parse($conn, $sqlDeleteBoutique);

if (!$stmtDeleteBoutique) {
    $e = oci_error($conn);
    oci_rollback($conn);
    oci_close($conn);
    die("Erreur préparation suppression employe_boutique : " . htmlentities($e['message'], ENT_QUOTES));
}

oci_bind_by_name($stmtDeleteBoutique, ':id', $id);
$rDeleteBoutique = oci_execute($stmtDeleteBoutique, OCI_NO_AUTO_COMMIT);

if (!$rDeleteBoutique) {
    $e = oci_error($stmtDeleteBoutique);
    oci_free_statement($stmtDeleteBoutique);
    oci_rollback($conn);
    oci_close($conn);
    die("Erreur suppression dans employe_boutique : " . htmlentities($e['message'], ENT_QUOTES));
}

oci_free_statement($stmtDeleteBoutique);

/* 7. Supprimer le personnel */
$sqlDeletePersonnel = "DELETE FROM personnel WHERE id_personnel = :id";
$stmtDeletePersonnel = oci_parse($conn, $sqlDeletePersonnel);

if (!$stmtDeletePersonnel) {
    $e = oci_error($conn);
    oci_rollback($conn);
    oci_close($conn);
    die("Erreur préparation suppression personnel : " . htmlentities($e['message'], ENT_QUOTES));
}

oci_bind_by_name($stmtDeletePersonnel, ':id', $id);
$rDeletePersonnel = oci_execute($stmtDeletePersonnel, OCI_NO_AUTO_COMMIT);

if (!$rDeletePersonnel) {
    $e = oci_error($stmtDeletePersonnel);
    oci_free_statement($stmtDeletePersonnel);
    oci_rollback($conn);
    oci_close($conn);
    die("Erreur suppression personnel : " . htmlentities($e['message'], ENT_QUOTES));
}

oci_free_statement($stmtDeletePersonnel);

/* 8. Valider */
oci_commit($conn);
oci_close($conn);

header('Location: gestion_personnel.php');
exit();
?>