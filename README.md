# site-Zoo
gestion complète d’un parc zoologique développé en PHP et Oracle SQL, intégrant gestion des animaux, personnel, soins, boutiques, chiffre d’affaires, réparations et parrainages, avec système sécurisé par rôles et sessions
# 🦁 ZooLand — Système de gestion de zoo (PHP / Oracle SQL)

Projet universitaire de développement d’une application web complète dédiée à la gestion d’un parc zoologique.

---

## 📌 Présentation

ZooLand est une plateforme permettant :

- la gestion des animaux
- la gestion du personnel
- la gestion des enclos
- la gestion des soins
- la gestion des nourrissages
- la gestion des boutiques
- le suivi du chiffre d’affaires
- la gestion des réparations
- la gestion des parrainages

L’objectif est de centraliser toutes les activités du zoo dans une seule application sécurisée.

---

## 🛠️ Technologies utilisées

- PHP
- Oracle SQL
- OCI8
- HTML5
- CSS3
- JavaScript
- Sessions PHP

---

## 🔐 Sécurité

- Authentification sécurisée par session
- Gestion des rôles utilisateurs
- Requêtes préparées avec `oci_bind_by_name`
- Hashage des mots de passe
- Protection contre les injections SQL
- Protection XSS via `htmlspecialchars`

---

## 👥 Gestion des rôles

Le système adapte automatiquement l’accès selon le rôle :

- Dirigeant
- Gestionnaire
- Responsable Boutique
- Comptable
- Vétérinaire
- Chef Soigneur
- Soigneur

---

## 📸 Captures d’écran

### 🌍 Page d’accueil publique
![Accueil](docs/captures/index.png)

### 🔐 Connexion sécurisée
![Connexion](docs/captures/login.png)

### 👑 Tableau de bord principal
![Dashboard](docs/captures/accueil.png)

### 💛 Système de parrainage
![Parrainage](docs/captures/parrainage.png)

### 🐾 Gestion des animaux
![Animaux](docs/captures/animaux.png)

### 💉 Gestion des soins
![Soins](docs/captures/soins.png)

### 👨‍💼 Gestion du personnel
![Gestion du personnel](docs/captures/gestion_personnel.png)

### 🛍️ Gestion des employés boutique
![Employés boutique](docs/captures/employe.png)

### 📈 Gestion du chiffre d’affaires
![Chiffre d'affaires](docs/captures/ca.png)

### 🔧 Gestion des réparations
![Réparations](docs/captures/reparation.png)

---

## ⚙️ Installation

### 1. Base de données

Exécuter :

```bash
database/bd.sql
