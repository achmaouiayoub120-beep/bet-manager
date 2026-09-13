<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!in_array($_SESSION['user_role'], ['admin', 'engineer'])) {
    setFlash('danger', 'Vous n\'avez pas la permission de supprimer un plan.');
    header('Location: /plans/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /plans/index.php');
    exit();
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'Plan invalide.');
    header('Location: /plans/index.php');
    exit();
}

$pdo = getConnection();

$stmt = $pdo->prepare("SELECT title, plan_code FROM plans WHERE id = ?");
$stmt->execute([$id]);
$plan = $stmt->fetch();

if (!$plan) {
    setFlash('danger', 'Plan introuvable.');
    header('Location: /plans/index.php');
    exit();
}

try {
    // Supprimer les fichiers physiques
    $stmt = $pdo->prepare("SELECT file_path FROM plan_versions WHERE plan_id = ?");
    $stmt->execute([$id]);
    foreach ($stmt->fetchAll() as $v) {
        $fullPath = __DIR__ . '/../' . $v['file_path'];
        if (file_exists($fullPath) && is_file($fullPath)) {
            @unlink($fullPath);
        }
    }
    
    // Supprimer le plan (les versions sont en cascade)
    $stmt = $pdo->prepare("DELETE FROM plans WHERE id = ?");
    $stmt->execute([$id]);
    
    logActivity($pdo, 'delete', 'plan', $id, 
        "Suppression du plan : {$plan['title']} ({$plan['plan_code']})");
    
    setFlash('success', 'Le plan a été supprimé.');
    
} catch (PDOException $e) {
    setFlash('danger', 'Erreur : ' . $e->getMessage());
}

header('Location: /plans/index.php');
exit();
?>