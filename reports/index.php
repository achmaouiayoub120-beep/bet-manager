<?php
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Rapports';
$active_menu = 'reports';

$pdo = getConnection();

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$projectId = (int)($_GET['project_id'] ?? 0);
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(r.title LIKE :search OR r.report_code LIKE :search OR r.content LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if ($status !== '' && in_array($status, ['draft', 'submitted', 'approved', 'rejected'])) {
    $where[] = "r.status = :status";
    $params[':status'] = $status;
}
if ($type !== '' && in_array($type, ['technical', 'progress', 'inspection', 'calculation', 'other'])) {
    $where[] = "r.report_type = :type";
    $params[':type'] = $type;
}
if ($projectId > 0) {
    $where[] = "r.project_id = :project_id";
    $params[':project_id'] = $projectId;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT COUNT(*) FROM reports r $whereSql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT r.*, pr.name AS project_name, pr.project_code, u.full_name AS creator_name
        FROM reports r
        LEFT JOIN projects pr ON pr.id = r.project_id
        LEFT JOIN users u ON u.id = r.created_by
        $whereSql
        ORDER BY r.created_at DESC
        LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

$projects = $pdo->query("SELECT id, project_code, name FROM projects ORDER BY name ASC")->fetchAll();

function translateReportType($t) {
    $labels = [
        'technical' => 'Technique',
        'progress' => 'Avancement',
        'inspection' => 'Inspection',
        'calculation' => 'Calcul',
        'other' => 'Autre',
    ];
    return $labels[$t] ?? ucfirst($t);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Rapports</h1>
        <p class="page-subtitle"><?= $total ?> rapport<?= $total > 1 ? 's' : '' ?></p>
    </div>
    <a href="/reports/create.php" class="btn btn-primary">+ Nouveau rapport</a>
</div>

<form method="GET" action="" class="filters-bar">
    <div class="form-group">
        <label class="form-label">Recherche</label>
        <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Titre, code...">
    </div>
    <div class="form-group">
        <label class="form-label">Projet</label>
        <select name="project_id" class="form-control">
            <option value="">Tous</option>
            <?php foreach ($projects as $pr): ?>
                <option value="<?= $pr['id'] ?>" <?= $projectId == $pr['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($pr['project_code'] . ' - ' . $pr['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Type</label>
        <select name="type" class="form-control">
            <option value="">Tous</option>
            <option value="technical" <?= $type === 'technical' ? 'selected' : '' ?>>Technique</option>
            <option value="progress" <?= $type === 'progress' ? 'selected' : '' ?>>Avancement</option>
            <option value="inspection" <?= $type === 'inspection' ? 'selected' : '' ?>>Inspection</option>
            <option value="calculation" <?= $type === 'calculation' ? 'selected' : '' ?>>Calcul</option>
            <option value="other" <?= $type === 'other' ? 'selected' : '' ?>>Autre</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Statut</label>
        <select name="status" class="form-control">
            <option value="">Tous</option>
            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Brouillon</option>
            <option value="submitted" <?= $status === 'submitted' ? 'selected' : '' ?>>Soumis</option>
            <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approuvé</option>
            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejeté</option>
        </select>
    </div>
    <div>
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <a href="/reports/index.php" class="btn btn-secondary">Réinitialiser</a>
    </div>
</form>

<?php if (empty($reports)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon">📄</div>
            <h3>Aucun rapport trouvé</h3>
            <p><?= $search || $status || $type || $projectId ? 'Essayez d\'autres filtres.' : 'Créez votre premier rapport.' ?></p>
            <?php if (!$search && !$status && !$type && !$projectId): ?>
                <a href="/reports/create.php" class="btn btn-primary mt-2">+ Créer un rapport</a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Titre</th>
                    <th>Projet</th>
                    <th>Type</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $r): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['report_code']) ?></strong></td>
                        <td>
                            <a href="/reports/view.php?id=<?= $r['id'] ?>">
                                <?= htmlspecialchars($r['title']) ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($r['project_code']): ?>
                                <div><?= htmlspecialchars($r['project_code']) ?></div>
                                <div class="text-muted"><?= htmlspecialchars($r['project_name']) ?></div>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td><?= translateReportType($r['report_type']) ?></td>
                        <td><span class="badge <?= statusBadgeClass($r['status']) ?>"><?= translateStatus($r['status']) ?></span></td>
                        <td><?= formatDateFr($r['created_at']) ?></td>
                        <td>
                            <div class="table-actions">
                                <a href="/reports/view.php?id=<?= $r['id'] ?>" class="btn btn-secondary btn-sm">Voir</a>
                                <a href="/reports/export_pdf.php?id=<?= $r['id'] ?>" class="btn btn-secondary btn-sm" target="_blank">PDF</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php $qp = $_GET; $build = function($p) use ($qp) { $qp['page'] = $p; return '?' . http_build_query($qp); }; ?>
            <?php if ($page > 1): ?><a href="<?= htmlspecialchars($build($page-1)) ?>">‹ Précédent</a><?php endif; ?>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page): ?><span class="current"><?= $i ?></span>
                <?php else: ?><a href="<?= htmlspecialchars($build($i)) ?>"><?= $i ?></a><?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?><a href="<?= htmlspecialchars($build($page+1)) ?>">Suivant ›</a><?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>