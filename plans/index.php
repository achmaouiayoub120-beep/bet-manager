<?php
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Plans';
$active_menu = 'plans';

$pdo = getConnection();

// Paramètres
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$type = $_GET['type'] ?? '';
$projectId = (int)($_GET['project_id'] ?? 0);
$sort = $_GET['sort'] ?? 'recent';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Construction de la requête
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(pl.title LIKE :search OR pl.plan_code LIKE :search OR pl.description LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if ($status !== '' && in_array($status, ['draft', 'review', 'approved', 'rejected', 'obsolete'])) {
    $where[] = "pl.status = :status";
    $params[':status'] = $status;
}
if ($type !== '' && in_array($type, ['architectural', 'structural', 'electrical', 'plumbing', 'other'])) {
    $where[] = "pl.plan_type = :type";
    $params[':type'] = $type;
}
if ($projectId > 0) {
    $where[] = "pl.project_id = :project_id";
    $params[':project_id'] = $projectId;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$orderBy = match($sort) {
    'oldest' => 'pl.created_at ASC',
    'title' => 'pl.title ASC',
    'code' => 'pl.plan_code ASC',
    default => 'pl.created_at DESC',
};

// Total
$countSql = "SELECT COUNT(*) FROM plans pl $whereSql";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// Récupérer les plans
$sql = "SELECT pl.*, 
        pr.name AS project_name, pr.project_code,
        u.full_name AS creator_name,
        (SELECT COUNT(*) FROM plan_versions WHERE plan_id = pl.id) AS versions_count
        FROM plans pl
        INNER JOIN projects pr ON pr.id = pl.project_id
        LEFT JOIN users u ON u.id = pl.created_by
        $whereSql
        ORDER BY $orderBy
        LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$plans = $stmt->fetchAll();

// Récupérer la liste des projets pour le filtre
$projects = $pdo->query("SELECT id, project_code, name FROM projects ORDER BY name ASC")->fetchAll();

// Traductions types
function translatePlanType($t) {
    $labels = [
        'architectural' => 'Architectural',
        'structural' => 'Structurel',
        'electrical' => 'Électrique',
        'plumbing' => 'Plomberie',
        'other' => 'Autre',
    ];
    return $labels[$t] ?? ucfirst($t);
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Plans techniques</h1>
        <p class="page-subtitle"><?= $total ?> plan<?= $total > 1 ? 's' : '' ?> au total</p>
    </div>
    <a href="/plans/create.php" class="btn btn-primary">+ Nouveau plan</a>
</div>

<!-- Filtres -->
<form method="GET" action="" class="filters-bar">
    <div class="form-group">
        <label class="form-label">Recherche</label>
        <input type="text" name="search" class="form-control" 
               placeholder="Titre, code..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="form-group">
        <label class="form-label">Projet</label>
        <select name="project_id" class="form-control">
            <option value="">Tous les projets</option>
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
            <option value="architectural" <?= $type === 'architectural' ? 'selected' : '' ?>>Architectural</option>
            <option value="structural" <?= $type === 'structural' ? 'selected' : '' ?>>Structurel</option>
            <option value="electrical" <?= $type === 'electrical' ? 'selected' : '' ?>>Électrique</option>
            <option value="plumbing" <?= $type === 'plumbing' ? 'selected' : '' ?>>Plomberie</option>
            <option value="other" <?= $type === 'other' ? 'selected' : '' ?>>Autre</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Statut</label>
        <select name="status" class="form-control">
            <option value="">Tous</option>
            <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Brouillon</option>
            <option value="review" <?= $status === 'review' ? 'selected' : '' ?>>En révision</option>
            <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approuvé</option>
            <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejeté</option>
            <option value="obsolete" <?= $status === 'obsolete' ? 'selected' : '' ?>>Obsolète</option>
        </select>
    </div>
    <div>
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <a href="/plans/index.php" class="btn btn-secondary">Réinitialiser</a>
    </div>
</form>

<?php if (empty($plans)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon">📐</div>
            <h3>Aucun plan trouvé</h3>
            <p><?= $search || $status || $type || $projectId ? 'Essayez de modifier vos filtres.' : 'Créez votre premier plan.' ?></p>
            <?php if (!$search && !$status && !$type && !$projectId): ?>
                <a href="/plans/create.php" class="btn btn-primary mt-2">+ Créer un plan</a>
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
                    <th>Version</th>
                    <th>Statut</th>
                    <th>Créé le</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plans as $pl): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($pl['plan_code']) ?></strong></td>
                        <td>
                            <a href="/plans/view.php?id=<?= $pl['id'] ?>">
                                <?= htmlspecialchars($pl['title']) ?>
                            </a>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($pl['project_code']) ?></div>
                            <div class="text-muted"><?= htmlspecialchars($pl['project_name']) ?></div>
                        </td>
                        <td><?= translatePlanType($pl['plan_type']) ?></td>
                        <td>
                            <span class="badge badge-secondary">
                                <?= (int)$pl['versions_count'] ?> version<?= $pl['versions_count'] > 1 ? 's' : '' ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge <?= statusBadgeClass($pl['status']) ?>">
                                <?= translateStatus($pl['status']) ?>
                            </span>
                        </td>
                        <td><?= formatDateFr($pl['created_at']) ?></td>
                        <td>
                            <div class="table-actions">
                                <a href="/plans/view.php?id=<?= $pl['id'] ?>" 
                                   class="btn btn-secondary btn-sm">Voir</a>
                                <a href="/plans/edit.php?id=<?= $pl['id'] ?>" 
                                   class="btn btn-secondary btn-sm">Modifier</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php
            $queryParams = $_GET;
            $buildUrl = function($p) use ($queryParams) {
                $queryParams['page'] = $p;
                return '?' . http_build_query($queryParams);
            };
            ?>
            <?php if ($page > 1): ?>
                <a href="<?= htmlspecialchars($buildUrl($page - 1)) ?>">‹ Précédent</a>
            <?php else: ?>
                <span class="disabled">‹ Précédent</span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($buildUrl($i)) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= htmlspecialchars($buildUrl($page + 1)) ?>">Suivant ›</a>
            <?php else: ?>
                <span class="disabled">Suivant ›</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>