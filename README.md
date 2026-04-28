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
![Accueil](images/index.png)

### 🔐 Connexion sécurisée
![Connexion](images/login.png)

### 👑 Tableau de bord principal
![Dashboard](images/accueil.png)

### 💛 Système de parrainage
![Parrainage](images/parrainage.png)

### 🐾 Gestion des animaux
![Animaux](images/animaux.png)

### 💉 Gestion des soins
![Soins](images/soins.png)

### 👨‍💼 Gestion du personnel
![Gestion du personnel](images/gestion_personnel.png)

### 🛍️ Gestion des employés boutique
![Employés boutique](images/employe.png)

### 📈 Gestion du chiffre d’affaires
![Chiffre d'affaires](images/ca.png)

### 🔧 Gestion des réparations
![Réparations](images/reparation.png)

---

## ⚙️ Installation

### 1. Base de données

Exécuter :

```bash
database/bd.sql
