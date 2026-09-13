<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!in_array($_SESSION['user_role'], ['admin', 'engineer'])) {
    setFlash('danger', 'Permission refusée.');
    header('Location: /plans/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /plans/index.php');
    exit();
}

$id = (int)($_POST['id'] ?? 0);
$planId = (int)($_POST['plan_id'] ?? 0);

if ($id <= 0 || $planId <= 0) {
    setFlash('danger', 'Paramètres invalides.');
    header('Location: /plans/index.php');
    exit();
}

$pdo = getConnection();

$stmt = $pdo->prepare("SELECT * FROM plan_versions WHERE id = ? AND plan_id = ?");
$stmt->execute([$id, $planId]);
$version = $stmt->fetch();

if (!$version) {
    setFlash('danger', 'Version introuvable.');
    header('Location: /plans/view.php?id=' . $planId);
    exit();
}

try {
    // Supprimer le fichier physique
    $fullPath = __DIR__ . '/../' . $version['file_path'];
    if (file_exists($fullPath) && is_file($fullPath)) {
        @unlink($fullPath);
    }
    
    // Supprimer l'entrée en base
    $stmt = $pdo->prepare("DELETE FROM plan_versions WHERE id = ?");
    $stmt->execute([$id]);
    
    logActivity($pdo, 'delete', 'plan_version', $id, 
        "Suppression de la version {$version['version_number']} (plan ID {$planId})");
    
    setFlash('success', 'Version supprimée.');
    
} catch (PDOException $e) {
    setFlash('danger', 'Erreur : ' . $e->getMessage());
}

header('Location: /plans/view.php?id=' . $planId);
exit();
?>