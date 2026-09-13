<?php
require_once __DIR__ . '/../includes/auth_check.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    setFlash('danger', 'Version invalide.');
    header('Location: /plans/index.php');
    exit();
}

$pdo = getConnection();

$stmt = $pdo->prepare("SELECT pv.*, pl.plan_code, pl.id AS plan_id
                       FROM plan_versions pv
                       INNER JOIN plans pl ON pl.id = pv.plan_id
                       WHERE pv.id = ?");
$stmt->execute([$id]);
$version = $stmt->fetch();

if (!$version) {
    setFlash('danger', 'Version introuvable.');
    header('Location: /plans/index.php');
    exit();
}

$fullPath = __DIR__ . '/../' . $version['file_path'];

if (!file_exists($fullPath) || !is_file($fullPath)) {
    setFlash('danger', 'Fichier physique introuvable sur le serveur.');
    header('Location: /plans/view.php?id=' . $version['plan_id']);
    exit();
}

// Journaliser le téléchargement
logActivity($pdo, 'download', 'plan_version', $id, 
    "Téléchargement de la version {$version['version_number']} du plan {$version['plan_code']}");

// Envoyer le fichier
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($version['file_name']) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($fullPath));

// Nettoyer le buffer pour éviter les fichiers corrompus
if (ob_get_level()) {
    ob_end_clean();
}

readfile($fullPath);
exit();
?>