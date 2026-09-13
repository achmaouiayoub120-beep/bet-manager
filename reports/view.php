<?php
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Détail du rapport';
$active_menu = 'reports';

$pdo = getConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT r.*, pr.name AS project_name, pr.project_code, u.full_name AS creator_name
                       FROM reports r
                       LEFT JOIN projects pr ON pr.id = r.project_id
                       LEFT JOIN users u ON u.id = r.created_by
                       WHERE r.id = ?");
$stmt->execute([$id]);
$report = $stmt->fetch();

if (!$report) {
    setFlash('danger', 'Rapport introuvable.');
    header('Location: /reports/index.php');
    exit();
}

function translateReportType($t) {
    $labels = ['technical'=>'Technique','progress'=>'Avancement','inspection'=>'Inspection','calculation'=>'Calcul','other'=>'Autre'];
    return $labels[$t] ?? ucfirst($t);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= htmlspecialchars($report['title']) ?></h1>
        <p class="page-subtitle">
            <a href="/reports/index.php">← Retour</a>
            · Code : <strong><?= htmlspecialchars($report['report_code']) ?></strong>
        </p>
    </div>
    <div class="d-flex gap-1 flex-wrap">
        <a href="/reports/export_pdf.php?id=<?= $id ?>" class="btn btn-accent" target="_blank">📄 Export PDF</a>
        <a href="/reports/edit.php?id=<?= $id ?>" class="btn btn-primary">Modifier</a>
        <button class="btn btn-danger" onclick="document.getElementById('deleteModal').classList.add('active')">Supprimer</button>
    </div>
</div>

<div class="plan-header-info">
    <span>Type : <strong><?= translateReportType($report['report_type']) ?></strong></span>
    <span>Statut : <span class="badge <?= statusBadgeClass($report['status']) ?>"><?= translateStatus($report['status']) ?></span></span>
    <span>Projet : <strong><?= htmlspecialchars($report['project_code'] ?: '—') ?></strong></span>
    <span>Créé par : <strong><?= htmlspecialchars($report['creator_name'] ?? '—') ?></strong></span>
    <span>Le : <strong><?= formatDateFr($report['created_at']) ?></strong></span>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Contenu</h3>
    </div>
    <?php if ($report['content']): ?>
        <div style="white-space: pre-wrap; line-height: 1.7;"><?= htmlspecialchars($report['content']) ?></div>
    <?php else: ?>
        <p class="text-muted">Aucun contenu.</p>
    <?php endif; ?>
</div>

<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <h3>Confirmer la suppression</h3>
        <p>Supprimer le rapport <strong><?= htmlspecialchars($report['title']) ?></strong> ?</p>
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="document.getElementById('deleteModal').classList.remove('active')">Annuler</button>
            <form method="POST" action="/reports/delete.php" style="display:inline;">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn btn-danger">Supprimer</button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>