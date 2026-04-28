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
$id_personnel_supprime = trim($_GET['id_personnel'] ?? '');
$id_boutique_get = trim($_GET['id_boutique'] ?? '');

if ($id_personnel_supprime === '') {
    oci_close($conn);
    header("Location: employes_boutique.php");
    exit();
}

// Empêcher de se supprimer soi-même
if ((string)$id_personnel_supprime === (string)$id_personnel_connecte) {
    oci_close($conn);
    header("Location: employes_boutique.php");
    exit();
}

$id_boutique = null;


//Gestionnaire 
if ($fonction === "Gestionnaire") {
    if ($id_boutique_get === '') {
        oci_close($conn);
        header("Location: employes_boutique.php");
        exit();
    }

    $id_boutique = $id_boutique_get;
}


// Responsable Boutique 
elseif ($fonction === "Responsable Boutique") {
    $sqlResp = "SELECT id_boutique
                FROM employe_boutique
                WHERE id_personnel = :id_personnel
                  AND est_responsable = 1";

    $stmtResp = oci_parse($conn, $sqlResp);
    oci_bind_by_name($stmtResp, ':id_personnel', $id_personnel_connecte);
    oci_execute($stmtResp);

    $responsable = oci_fetch_assoc($stmtResp);
    oci_free_statement($stmtResp);

    if (!$responsable) {
        oci_close($conn);
        header("Location: accueil.php");
        exit();
    }

    $id_boutique = $responsable['ID_BOUTIQUE'];
}

// Autres rôles interdits 
else {
    oci_close($conn);
    header("Location: accueil.php");
    exit();
}



// Vérifier que l'employé appartient bien à la boutique ciblée 
$sqlCheck = "SELECT id_personnel
             FROM employe_boutique
             WHERE id_personnel = :id_personnel
               AND id_boutique = :id_boutique";

$stmtCheck = oci_parse($conn, $sqlCheck);
oci_bind_by_name($stmtCheck, ':id_personnel', $id_personnel_supprime);
oci_bind_by_name($stmtCheck, ':id_boutique', $id_boutique);
oci_execute($stmtCheck);

$existe = oci_fetch_assoc($stmtCheck);
oci_free_statement($stmtCheck);

if (!$existe) {
    oci_close($conn);
    header("Location: employes_boutique.php");
    exit();
}

//Supprimer l'affectation boutique 
$sqlDelete = "DELETE FROM employe_boutique
              WHERE id_personnel = :id_personnel
                AND id_boutique = :id_boutique";

$stmtDelete = oci_parse($conn, $sqlDelete);
oci_bind_by_name($stmtDelete, ':id_personnel', $id_personnel_supprime);
oci_bind_by_name($stmtDelete, ':id_boutique', $id_boutique);

$rDelete = oci_execute($stmtDelete, OCI_NO_AUTO_COMMIT);

if (!$rDelete) {
    oci_rollback($conn);
    oci_free_statement($stmtDelete);
    oci_close($conn);
    die("Erreur suppression.");
}

oci_free_statement($stmtDelete);



//Optionnel : remettre la fonction du personnel 
$sqlUpdate = "UPDATE personnel
              SET fonction = 'Employé Boutique'
              WHERE id_personnel = :id_personnel";

$stmtUpdate = oci_parse($conn, $sqlUpdate);
oci_bind_by_name($stmtUpdate, ':id_personnel', $id_personnel_supprime);
$rUpdate = oci_execute($stmtUpdate, OCI_NO_AUTO_COMMIT);

if (!$rUpdate) {
    oci_rollback($conn);
    oci_free_statement($stmtUpdate);
    oci_close($conn);
    die("Erreur mise à jour fonction.");
}

oci_free_statement($stmtUpdate);
oci_commit($conn);
oci_close($conn);

header("Location: employes_boutique.php");
exit();
?>
