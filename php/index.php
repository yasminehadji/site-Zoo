<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoo - Accueil</title>
    <link rel="stylesheet" href="index.css">
    <style>
        .hero-split {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 24px;
            align-items: stretch;
            margin-top: 20px;
        }

        .hero-panel {
            background: linear-gradient(135deg, rgba(255,255,255,0.98), rgba(246,251,247,0.95));
            border-radius: 28px;
            padding: 34px;
            box-shadow: 0 18px 40px rgba(24, 57, 43, 0.10);
            border: 1px solid rgba(36, 49, 38, 0.08);
        }

        .hero-panel h1 {
            margin-bottom: 14px;
        }

        .hero-panel p {
            font-size: 1.05rem;
            color: #5f6f65;
            max-width: 680px;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 24px;
        }

        .hero-photo {
            min-height: 420px;
            border-radius: 28px;
            overflow: hidden;
            position: relative;
            box-shadow: 0 18px 40px rgba(24, 57, 43, 0.12);
            background:
                linear-gradient(rgba(16, 37, 28, 0.18), rgba(16, 37, 28, 0.34)),
                url('https://images.unsplash.com/photo-1517849845537-4d257902454a?auto=format&fit=crop&w=1200&q=80') center/cover;
        }

        .hero-photo-overlay {
            position: absolute;
            inset: auto 0 0 0;
            padding: 26px;
            background: linear-gradient(to top, rgba(16, 37, 28, 0.85), rgba(16, 37, 28, 0.15));
            color: white;
        }

        .hero-photo-overlay h2,
        .hero-photo-overlay p {
            color: white;
            margin: 0 0 8px 0;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin: 26px 0 10px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 10px 24px rgba(24, 57, 43, 0.08);
            border: 1px solid rgba(36, 49, 38, 0.08);
        }

        .stat-card strong {
            display: block;
            font-size: 1.9rem;
            color: #1f4d3b;
            margin-bottom: 8px;
        }

        .section-block {
            margin-top: 34px;
        }

        .section-title {
            margin-bottom: 18px;
        }

        .animal-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }

        .animal-card {
            background: white;
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 10px 24px rgba(24, 57, 43, 0.08);
            border: 1px solid rgba(36, 49, 38, 0.08);
        }

        .animal-card img {
            width: 100%;
            height: 210px;
            object-fit: cover;
            display: block;
        }

        .animal-card .content {
            padding: 18px;
        }

        .animal-card h3 {
            margin-bottom: 8px;
            color: #1f4d3b;
        }

        .animal-card p {
            margin: 0;
            color: #66756b;
            font-size: 0.96rem;
        }

        .highlight-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .highlight-card {
            background: linear-gradient(180deg, #ffffff, #f7fbf8);
            border-radius: 22px;
            padding: 24px;
            box-shadow: 0 10px 24px rgba(24, 57, 43, 0.08);
            border: 1px solid rgba(36, 49, 38, 0.08);
        }

        .highlight-card .icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .parrainage-note {
            margin-top: 28px;
            background: linear-gradient(135deg, rgba(36,83,61,0.96), rgba(47,107,78,0.95));
            color: white;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 18px 36px rgba(24, 57, 43, 0.16);
        }

        .parrainage-note h2,
        .parrainage-note p {
            color: white;
        }

        .parrainage-note .btn {
            margin-top: 12px;
        }

        @media (max-width: 900px) {
            .hero-split {
                grid-template-columns: 1fr;
            }

            .stats-row {
                grid-template-columns: 1fr;
            }

            .hero-photo {
                min-height: 300px;
            }

            .hero-panel {
                padding: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="page-container home-hero">
        <div class="hero-badge">Bienvenue au Zoo</div>

        <div class="hero-split">
            <div class="hero-panel">
                <h1>Un lieu où la nature raconte ses plus belles histoires</h1>
                <p>
                    Bienvenue dans notre univers animalier. Derrière chaque regard,
                    chaque plume, chaque rugissement, il y a une vie à découvrir.
                    Le personnel peut accéder à l’administration du zoo, et les visiteurs
                    peuvent parrainer leur animal préféré pour participer à cette aventure.
                </p>

                <div class="hero-actions">
                    <a href="login2.php" class="btn btn-large">🧑‍💼 Espace Personnel</a>
                    <a href="visiteur.php" class="btn btn-secondary btn-large">🦁 Espace Visiteur</a>
                </div>

                <div class="stats-row">
                    <div class="stat-card">
                        <strong>120+</strong>
                        <span>Animaux à découvrir</span>
                    </div>
                    <div class="stat-card">
                        <strong>3</strong>
                        <span>Niveaux de parrainage</span>
                    </div>
                    <div class="stat-card">
                        <strong>100%</strong>
                        <span>Passion pour le vivant</span>
                    </div>
                </div>
            </div>

            <div class="hero-photo">
                <div class="hero-photo-overlay">
                    <h2>Une expérience vivante</h2>
                    <p>Approchez la beauté sauvage, sans quitter l’émerveillement.</p>
                </div>
            </div>
        </div>

        <div class="section-block">
            <h2 class="section-title">Choisissez votre espace</h2>

            <div class="home-grid">
                <div class="choice-card">
                    <div class="choice-icon">🧑‍💼</div>
                    <h2>Espace Personnel</h2>
                    <p>
                        Connectez-vous pour gérer les animaux, les soins,
                        le personnel, les enclos, la boutique et l’organisation du zoo.
                    </p>
                    <a href="login2.php" class="btn btn-large">Accéder au personnel</a>
                </div>

                <div class="choice-card">
                    <div class="choice-icon">🦁</div>
                    <h2>Espace Visiteur</h2>
                    <p>
                        Parrainer un animal, contribuer à son bien-être
                        et recevoir des avantages selon votre formule.
                    </p>
                    <a href="visiteur.php" class="btn btn-secondary btn-large">Je suis visiteur</a>
                </div>
            </div>
        </div>

        <div class="section-block">
            <h2 class="section-title">Quelques habitants du zoo</h2>

            <div class="animal-gallery">
                <div class="animal-card">
                    <img src="https://images.unsplash.com/photo-1546182990-dffeafbe841d?auto=format&fit=crop&w=900&q=80" alt="Lion">
                    <div class="content">
                        <h3>Le lion</h3>
                        <p>Majesté fauve, regard de feu, silence royal.</p>
                    </div>
                </div>

                <div class="animal-card">
                   
                    <img src="https://img.freepik.com/photos-gratuite/tigre-regardant-bouche-ouverte_1150-18083.jpg?semt=ais_hybrid&w=740&q=80" alt="Tigre">
                    <div class="content">
                        <h3>Le tigre</h3>
                        <p>Élégance tendue, muscles et poésie rayée.</p>
                    </div>
                </div>

                <div class="animal-card">
                  <img src="https://images.unsplash.com/photo-1557050543-4d5f4e07ef46?auto=format&fit=crop&w=900&q=80"  alt="Éléphant">
                    <div class="content">
                        <h3>L’éléphant</h3>
                        <p>Une mémoire ancienne dans un corps de montagne.</p>
                    </div>
                </div>

                <div class="animal-card">
                    <img src="https://img.freepik.com/photos-gratuite/belle-girafe-dans-nature_23-2151708860.jpg?semt=ais_hybrid&w=740&q=80" alt="Girafe">
                    <div class="content">
                        <h3>La girafe</h3>
                        <p>Une grâce tranquille qui tutoie les nuages.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-block">
            <h2 class="section-title">Pourquoi soutenir le zoo ?</h2>

            <div class="highlight-grid">
                <div class="highlight-card">
                    <div class="icon">🌿</div>
                    <h3>Préserver</h3>
                    <p>Participer à la protection d’animaux fascinants et de leurs espaces de vie.</p>
                </div>

                <div class="highlight-card">
                    <div class="icon">📚</div>
                    <h3>Apprendre</h3>
                    <p>Découvrir les espèces, leurs habitudes, leurs besoins et leur richesse biologique.</p>
                </div>

                <div class="highlight-card">
                    <div class="icon">💚</div>
                    <h3>S’engager</h3>
                    <p>Le parrainage transforme l’admiration en geste concret et utile.</p>
                </div>
            </div>
        </div>

        <div class="parrainage-note">
            <h2>Parrainage des animaux</h2>
            <p>
                Choisissez la formule qui vous ressemble. Bronze, Argent ou Or :
                chaque niveau ouvre une porte différente vers votre filleul.
            </p>

            <div class="home-info">
                <div class="info-card">
                    <h3>Bronze</h3>
                    <p>Photo de votre filleul</p>
                </div>
                <div class="info-card">
                    <h3>Argent</h3>
                    <p>Photo + fond d’écran</p>
                </div>
                <div class="info-card">
                    <h3>Or</h3>
                    <p>Photo + fond d’écran + visite gratuite</p>
                </div>
            </div>

            <a href="visiteur.php" class="btn btn-light">Découvrir le parrainage</a>
        </div>
    </div>
</body>
</html>
