<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();

// Journaliser la déconnexion avant de détruire la session
if (isLoggedIn()) {
    try {
        $pdo = getConnection();
        logActivity($pdo, 'logout', 'user', $_SESSION['user_id'], 'Déconnexion du système');
    } catch (PDOException $e) {
        // Ignorer les erreurs
    }
}

// Détruire la session
$_SESSION = [];
session_destroy();

header('Location: /auth/login.php');
exit();
?>