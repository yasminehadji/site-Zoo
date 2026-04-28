<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_personnel'])) {
    header("Location: login2.php");
    exit();
}

if (!isset($_SESSION['fonction']) || !in_array($_SESSION['fonction'], ['Gestionnaire', 'Dirigeant'])) {
    header("Location: accueil.php");
    exit();
}

require_once("connexion.php");

$filtreNomParrain = trim($_GET['nom_parrain'] ?? '');
$filtreAnimal = trim($_GET['animal'] ?? '');
$filtreNiveau = trim($_GET['niveau'] ?? '');

$sql = "SELECT
            p.id_parrainage,
            p.nom AS nom_parrain,
            p.prenom AS prenom_parrain,
            a.id_animal,
            a.nom AS nom_animal,
            pa.niveau,
            pa.prestation
        FROM parraine p
        JOIN parrainage pa ON p.id_parrainage = pa.id_parrainage
        JOIN animal a ON pa.id_animal = a.id_animal
        WHERE (:nom_parrain IS NULL OR LOWER(p.nom) LIKE LOWER('%' || :nom_parrain || '%'))
          AND (:animal IS NULL OR LOWER(a.nom) LIKE LOWER('%' || :animal || '%'))
          AND (:niveau IS NULL OR pa.niveau = :niveau)
        ORDER BY p.id_parrainage DESC, a.nom";

$stmt = oci_parse($conn, $sql);

$nomParrainBind = ($filtreNomParrain === '') ? null : $filtreNomParrain;
$animalBind = ($filtreAnimal === '') ? null : $filtreAnimal;
$niveauBind = ($filtreNiveau === '') ? null : $filtreNiveau;

oci_bind_by_name($stmt, ':nom_parrain', $nomParrainBind);
oci_bind_by_name($stmt, ':animal', $animalBind);
oci_bind_by_name($stmt, ':niveau', $niveauBind);

$r = oci_execute($stmt);

if (!$r) {
    $e = oci_error($stmt);
    die("Erreur exécution requête : " . htmlentities($e['message'], ENT_QUOTES));
}

$parrainages = [];
while ($row = oci_fetch_assoc($stmt)) {
    $parrainages[] = $row;
}

oci_free_statement($stmt);
oci_close($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion des parrainages</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <div class="page-container">
        <h1>Gestion des parrainages</h1>

        <div class="top-links">
            <a href="accueil.php" class="btn btn-light">← Retour accueil</a>
            <a href="logout.php" class="btn btn-logout">Se déconnecter</a>
        </div>

        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label for="nom_parrain">Nom du parrain</label>
                <input type="text" name="nom_parrain" id="nom_parrain" value="<?php echo htmlspecialchars($filtreNomParrain); ?>">
            </div>

            <div class="filter-group">
                <label for="animal">Animal</label>
                <input type="text" name="animal" id="animal" value="<?php echo htmlspecialchars($filtreAnimal); ?>">
            </div>

            <div class="filter-group">
                <label for="niveau">Niveau</label>
                <select name="niveau" id="niveau">
                    <option value="">-- Tous --</option>
                    <option value="Bronze" <?php if ($filtreNiveau === 'Bronze') echo 'selected'; ?>>Bronze</option>
                    <option value="Argent" <?php if ($filtreNiveau === 'Argent') echo 'selected'; ?>>Argent</option>
                    <option value="Or" <?php if ($filtreNiveau === 'Or') echo 'selected'; ?>>Or</option>
                </select>
            </div>

            <div>
                <button type="submit" class="btn">Filtrer</button>
                <a href="parrainage_gestion.php" class="btn btn-outline">Réinitialiser</a>
            </div>
        </form>

        <?php if (empty($parrainages)): ?>
            <p>Aucun parrainage trouvé.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>ID Parrain</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Animal</th>
                    <th>Niveau</th>
                    <th>Prestation</th>
                </tr>

                <?php foreach ($parrainages as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['ID_PARRAINAGE']); ?></td>
                        <td><?php echo htmlspecialchars($row['NOM_PARRAIN']); ?></td>
                        <td><?php echo htmlspecialchars($row['PRENOM_PARRAIN']); ?></td>
                        <td><?php echo htmlspecialchars($row['NOM_ANIMAL']); ?> (ID <?php echo htmlspecialchars($row['ID_ANIMAL']); ?>)</td>
                        <td><?php echo htmlspecialchars($row['NIVEAU']); ?></td>
                        <td><?php echo htmlspecialchars($row['PRESTATION']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>