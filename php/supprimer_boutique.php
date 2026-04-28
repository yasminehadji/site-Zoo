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

$id = $_GET['id'] ?? '';

if ($id === '') {
    die("Identifiant boutique manquant.");
}

$sql = "DELETE FROM boutique WHERE id_boutique = :id";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':id', $id);

$r = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);

if (!$r) {
    $e = oci_error($stmt);
    die("Erreur suppression : " . htmlentities($e['message'], ENT_QUOTES));
}

oci_free_statement($stmt);
oci_close($conn);

header("Location: boutique.php?success=supp");
exit();
?>
