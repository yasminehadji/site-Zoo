<?php
session_start();

if (!isset($_SESSION['id_personnel'])) {
    header("Location: login2.php");
    exit;
}

$nom = $_SESSION['nom'];
$prenom = $_SESSION['prenom'];
$fonction = trim($_SESSION['fonction']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Accueil - Zoo</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <div class="page-container">
        <h1>Bienvenue <?php echo htmlspecialchars($prenom . " " . $nom); ?> 🐾</h1>
        <p>Fonction : <strong><?php echo htmlspecialchars($fonction); ?></strong></p>

        <hr>

        <h2>Menu</h2>

        <div class="top-links">
            <?php
            if ($fonction == "Chef Soigneur" || $fonction == "Soigneur") {
                echo "<a href='animaux.php' class='btn'>Voir les animaux</a>";
                echo "<a href='soins.php' class='btn'>Gérer les soins</a>";
                echo "<a href='nourrir.php' class='btn'>Voir le nourrissage</a>";
            }

            if ($fonction == "Responsable Boutique") {
                echo "<a href='boutique.php' class='btn'>Gérer la boutique</a>";
                echo "<a href='ca.php' class='btn'>Voir le chiffre d'affaires</a>";
                echo "<a href='employes_boutique.php' class='btn'>Gestion des employés boutique</a>";
            }

            if ($fonction == "Comptable") {
                echo "<a href='ca.php' class='btn'>Consulter le chiffre d'affaires</a>";
            }

            if ($fonction == "Gestionnaire" || $fonction == "Dirigeant") {
                echo "<a href='animaux.php' class='btn'>Gestion des animaux</a>";
                echo "<a href='gestion_personnel.php' class='btn'>Gestion du personnel</a>";
                echo "<a href='enclos.php' class='btn'>Gestion des enclos</a>";
                echo "<a href='boutique.php' class='btn'>Gérer la boutique</a>";
                echo "<a href='ca.php' class='btn'>Voir / saisir le chiffre d'affaires</a>";
                echo "<a href='employes_boutique.php' class='btn'>Gestion des employés boutique</a>";
                echo "<a href='reparations.php' class='btn'>Gestion des réparations</a>";
                echo "<a href='personnel_entretien.php' class='btn'>Personnel d'entretien</a>";
                echo "<a href='parrainage_gestion.php' class='btn'>Gestion du parrainage</a>";
            }

            if ($fonction == "Vétérinaire" || $fonction == "Veterinaire" || $fonction == "V‚t‚rinaire") {
    echo "<a href='soins.php' class='btn'>Suivi des soins</a>";
}

            if ($fonction == "Technicien") {
                echo "<a href='reparations.php' class='btn'>Voir les réparations</a>";
                echo "<a href='enclos.php' class='btn'>Voir les enclos</a>";
                echo "<a href='ajout_reparation.php' class='btn'>Ajouter une réparation</a>";
            }
            
            if ($fonction == "Personnel Entretien" ) {
    echo "<a href='enclos.php' class='btn'>Voir les enclos</a>";
}if ($fonction == "Employé Boutique" || $fonction == "Employe Boutique") {
    echo "<a href='boutique.php' class='btn'>Voir la boutique</a>";
}
            ?>
        </div>

        <hr>

        <div class="top-links">
            <a href="changer_mot_de_passe.php" class="btn btn-password">
                🔑 Changer mon mot de passe
            </a>

            <a href="logout.php" class="btn btn-logout">
                🚪 Se déconnecter
            </a>
        </div>
    </div>

    <script src="js/common.js"></script>
    <script src="js/pages.js"></script>
</body>
</html>
