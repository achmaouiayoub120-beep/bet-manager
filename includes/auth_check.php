<?php
// Vérification d'authentification
// À inclure au début de chaque page protégée

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

startSession();

if (!isLoggedIn()) {
    header('Location: /auth/login.php');
    exit();
}

// Vérifier que l'utilisateur est toujours actif en base
try {
    $pdo = getConnection();
    $stmt = $pdo->prepare("SELECT id, username, full_name, role, is_active FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['is_active']) {
        session_destroy();
        header('Location: /auth/login.php');
        exit();
    }
    
    // Rafraîchir les données de session
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_role'] = $user['role'];
    
} catch (PDOException $e) {
    die("Erreur système : " . $e->getMessage());
}
?>