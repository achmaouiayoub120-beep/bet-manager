<?php
require_once __DIR__ . '/../includes/auth_check.php';

// Seuls les admins et ingénieurs peuvent supprimer
if (!in_array($_SESSION['user_role'], ['admin', 'engineer'])) {
    setFlash('danger', 'Vous n\'avez pas la permission de supprimer un projet.');
    header('Location: /projects/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /projects/index.php');
    exit();
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'Projet invalide.');
    header('Location: /projects/index.php');
    exit();
}

$pdo = getConnection();

// Récupérer les infos pour le log
$stmt = $pdo->prepare("SELECT name, project_code FROM projects WHERE id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    setFlash('danger', 'Projet introuvable.');
    header('Location: /projects/index.php');
    exit();
}

try {
    // Les plans, documents seront supprimés en cascade (ON DELETE CASCADE)
    $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$id]);
    
    logActivity($pdo, 'delete', 'project', $id, 
        "Suppression du projet : {$project['name']} ({$project['project_code']})");
    
    setFlash('success', 'Le projet a été supprimé.');
    
} catch (PDOException $e) {
    setFlash('danger', 'Erreur lors de la suppression : ' . $e->getMessage());
}

header('Location: /projects/index.php');
exit();
?>