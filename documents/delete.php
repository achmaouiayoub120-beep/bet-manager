<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!in_array($_SESSION['user_role'], ['admin', 'engineer'])) {
    setFlash('danger', 'Permission refusée.');
    header('Location: /documents/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /documents/index.php');
    exit();
}

$id = (int)($_POST['id'] ?? 0);
$pdo = getConnection();

$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc) {
    setFlash('danger', 'Document introuvable.');
    header('Location: /documents/index.php');
    exit();
}

try {
    // Supprimer le fichier physique
    $fullPath = __DIR__ . '/../' . $doc['file_path'];
    if (file_exists($fullPath) && is_file($fullPath)) {
        @unlink($fullPath);
    }
    
    // Supprimer l'entrée
    $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
    $stmt->execute([$id]);
    
    logActivity($pdo, 'delete', 'document', $id, "Suppression du document : {$doc['title']}");
    setFlash('success', 'Document supprimé.');
    
} catch (PDOException $e) {
    setFlash('danger', 'Erreur : ' . $e->getMessage());
}

header('Location: /documents/index.php');
exit();
?>