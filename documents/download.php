<?php
require_once __DIR__ . '/../includes/auth_check.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'Document invalide.');
    header('Location: /documents/index.php');
    exit();
}

$pdo = getConnection();

$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc) {
    setFlash('danger', 'Document introuvable.');
    header('Location: /documents/index.php');
    exit();
}

$fullPath = __DIR__ . '/../' . $doc['file_path'];

if (!file_exists($fullPath) || !is_file($fullPath)) {
    setFlash('danger', 'Fichier physique introuvable.');
    header('Location: /documents/index.php');
    exit();
}

// Log
logActivity($pdo, 'download', 'document', $id, "Téléchargement du document : {$doc['title']}");

// Envoi
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($doc['file_name']) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($fullPath));

if (ob_get_level()) {
    ob_end_clean();
}

readfile($fullPath);
exit();
?>