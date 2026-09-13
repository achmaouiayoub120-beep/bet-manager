<?php
require_once __DIR__ . '/../includes/auth_check.php';

if (!in_array($_SESSION['user_role'], ['admin', 'engineer'])) {
    setFlash('danger', 'Permission refusée.');
    header('Location: /reports/index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /reports/index.php');
    exit();
}

$id = (int)($_POST['id'] ?? 0);
$pdo = getConnection();

$stmt = $pdo->prepare("SELECT title, report_code FROM reports WHERE id = ?");
$stmt->execute([$id]);
$report = $stmt->fetch();

if (!$report) {
    setFlash('danger', 'Rapport introuvable.');
    header('Location: /reports/index.php');
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
    $stmt->execute([$id]);
    logActivity($pdo, 'delete', 'report', $id, "Suppression du rapport : {$report['title']}");
    setFlash('success', 'Rapport supprimé.');
} catch (PDOException $e) {
    setFlash('danger', 'Erreur : ' . $e->getMessage());
}

header('Location: /reports/index.php');
exit();
?>