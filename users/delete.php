<?php
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SESSION['user_role'] !== 'admin') {
    setFlash('danger', 'Accès réservé aux administrateurs.');
    header('Location: /dashboard/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /users/index.php');
    exit();
}

$id = (int)($_POST['id'] ?? 0);

// Sécurité : ne pas pouvoir se désactiver soi-même
if ($id === $_SESSION['user_id']) {
    setFlash('danger', 'Vous ne pouvez pas désactiver votre propre compte.');
    header('Location: /users/index.php');
    exit();
}

$pdo = getConnection();

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('danger', 'Utilisateur introuvable.');
    header('Location: /users/index.php');
    exit();
}

try {
    $reactivate = isset($_POST['reactivate']);
    
    if ($reactivate) {
        // Réactivation
        $stmt = $pdo->prepare("UPDATE users SET is_active = TRUE WHERE id = ?");
        $stmt->execute([$id]);
        
        logActivity($pdo, 'activate', 'user', $id, 
            "Réactivation du compte utilisateur : {$user['full_name']} ({$user['username']})");
        
        setFlash('success', 'Utilisateur réactivé avec succès.');
    } else {
        // Désactivation
        $stmt = $pdo->prepare("UPDATE users SET is_active = FALSE WHERE id = ?");
        $stmt->execute([$id]);
        
        logActivity($pdo, 'deactivate', 'user', $id, 
            "Désactivation du compte utilisateur : {$user['full_name']} ({$user['username']})");
        
        setFlash('success', 'Utilisateur désactivé. Il ne peut plus se connecter.');
    }
    
} catch (PDOException $e) {
    setFlash('danger', 'Erreur : ' . $e->getMessage());
}

header('Location: /users/index.php');
exit();
?>