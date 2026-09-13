# BET Manager - Application de gestion pour bureau d'études techniques

## 📋 Présentation

Application web de gestion documentaire pour bureau d'études en génie civil.
Permet la gestion des projets, plans versionnés, rapports, documents et utilisateurs
avec un système d'historique complet des actions.

## 🛠️ Technologies utilisées

- PHP 8 (POO, PDO)
- MySQL 8
- HTML5 / CSS3 / JavaScript
- Chart.js (graphiques)
- FPDF (génération PDF)
- Apache (XAMPP)

## ⚙️ Prérequis

- XAMPP (avec PHP 8.0+, MySQL, Apache)
- Un navigateur web moderne

## 🚀 Installation

### 1. Cloner le projet

Placer le dossier `bet-manager` dans `C:\xampp\htdocs\`

### 2. Créer la base de données

- Ouvrir phpMyAdmin : http://localhost/phpmyadmin
- Importer le fichier `sql/database.sql`
- La base `bet_manager_db` sera créée automatiquement

### 3. Créer l'utilisateur admin

- Ouvrir : http://localhost/bet-manager/sql/create_admin.php
- Puis supprimer ce fichier

### 4. Configuration du domaine local (optionnel)

Voir le fichier `INSTALLATION-DOMAINE.md`

## 🔑 Identifiants de connexion

- Utilisateur : `admin`
- Mot de passe : `Admin@123`

## 📁 Structure du projet
