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

$autorise = in_array($fonction, ["Responsable Boutique", "Gestionnaire", "Comptable"]);

if (!$autorise) {
    oci_close($conn);
    header("Location: accueil.php");
    exit();
}

$id_boutique_responsable = null;

if ($fonction === "Responsable Boutique") {
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
}

if ($fonction === "Responsable Boutique") {
    $sql = "SELECT cj.id_boutique,
                   b.nom_boutique,
                   EXTRACT(YEAR FROM cj.date_ca) AS annee,
                   SUM(cj.chiffre_affaire) AS ca_annuel
            FROM ca_journalier cj
            JOIN boutique b ON cj.id_boutique = b.id_boutique
            WHERE cj.id_boutique = :id_boutique
            GROUP BY cj.id_boutique, b.nom_boutique,
                     EXTRACT(YEAR FROM cj.date_ca)
            ORDER BY annee DESC";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id_boutique', $id_boutique_responsable);
} else {
    $sql = "SELECT cj.id_boutique,
                   b.nom_boutique,
                   EXTRACT(YEAR FROM cj.date_ca) AS annee,
                   SUM(cj.chiffre_affaire) AS ca_annuel
            FROM ca_journalier cj
            JOIN boutique b ON cj.id_boutique = b.id_boutique
            GROUP BY cj.id_boutique, b.nom_boutique,
                     EXTRACT(YEAR FROM cj.date_ca)
            ORDER BY cj.id_boutique, annee DESC";
    $stmt = oci_parse($conn, $sql);
}

oci_execute($stmt);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CA annuel</title>
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
        h1 {
            color: #1f4d3b;
        }
        .btn {
            display: inline-block;
            text-decoration: none;
            background: #1f4d3b;
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            margin-right: 10px;
            margin-bottom: 20px;
        }
        .btn:hover {
            background: #2d6a4f;
        }
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            padding: 25px;
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
    </style>
</head>
<body>
<div class="container">
    <h1>Chiffre d'affaires annuel</h1>

    <a class="btn" href="ca.php">Retour</a>
    <a class="btn" href="ca_mensuel.php">Voir CA mensuel</a>

    <div class="card">
        <table>
            <tr>
                <th>ID Boutique</th>
                <th>Nom boutique</th>
                <th>Année</th>
                <th>CA annuel</th>
            </tr>

            <?php while ($row = oci_fetch_assoc($stmt)): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['ID_BOUTIQUE']); ?></td>
                    <td><?php echo htmlspecialchars($row['NOM_BOUTIQUE']); ?></td>
                    <td><?php echo htmlspecialchars($row['ANNEE']); ?></td>
                    <td><?php echo htmlspecialchars($row['CA_ANNUEL']); ?> €</td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>
</body>
</html>

<?php
oci_free_statement($stmt);
oci_close($conn);
?>
