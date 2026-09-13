<?php
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Projets';
$active_menu = 'projects';

$pdo = getConnection();

// Paramètres de recherche et filtres
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'recent';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Construction de la requête
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(p.name LIKE :search OR p.project_code LIKE :search OR p.client_name LIKE :search OR p.location LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

if ($status !== '' && in_array($status, ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled'])) {
    $where[] = "p.status = :status";
    $params[':status'] = $status;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Tri
$orderBy = match($sort) {
    'name' => 'p.name ASC',
    'code' => 'p.project_code ASC',
    'oldest' => 'p.created_at ASC',
    default => 'p.created_at DESC',
};

// Compter le total
$countSql = "SELECT COUNT(*) FROM projects p $whereSql";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// Récupérer les projets
$sql = "SELECT p.*, u.full_name AS creator_name,
        (SELECT COUNT(*) FROM plans WHERE project_id = p.id) AS plans_count,
        (SELECT COUNT(*) FROM documents WHERE project_id = p.id) AS documents_count
        FROM projects p
        LEFT JOIN users u ON u.id = p.created_by
        $whereSql
        ORDER BY $orderBy
        LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Projets</h1>
        <p class="page-subtitle"><?= $total ?> projet<?= $total > 1 ? 's' : '' ?> au total</p>
    </div>
    <a href="/projects/create.php" class="btn btn-primary">
        + Nouveau projet
    </a>
</div>

<!-- Filtres -->
<form method="GET" action="" class="filters-bar">
    <div class="form-group">
        <label class="form-label">Recherche</label>
        <input type="text" name="search" class="form-control" 
               placeholder="Nom, code, client, lieu..." 
               value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="form-group">
        <label class="form-label">Statut</label>
        <select name="status" class="form-control">
            <option value="">Tous</option>
            <option value="planning" <?= $status === 'planning' ? 'selected' : '' ?>>Planification</option>
            <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>En cours</option>
            <option value="on_hold" <?= $status === 'on_hold' ? 'selected' : '' ?>>En pause</option>
            <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Terminé</option>
            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Annulé</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Trier par</label>
        <select name="sort" class="form-control">
            <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Plus récent</option>
            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Plus ancien</option>
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Nom (A-Z)</option>
            <option value="code" <?= $sort === 'code' ? 'selected' : '' ?>>Code</option>
        </select>
    </div>
    <div>
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <a href="/projects/index.php" class="btn btn-secondary">Réinitialiser</a>
    </div>
</form>

<?php if (empty($projects)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon">🏗️</div>
            <h3>Aucun projet trouvé</h3>
            <p><?= $search || $status ? 'Essayez de modifier vos filtres de recherche.' : 'Créez votre premier projet pour commencer.' ?></p>
            <?php if (!$search && !$status): ?>
                <a href="/projects/create.php" class="btn btn-primary mt-2">+ Créer un projet</a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Nom du projet</th>
                    <th>Client</th>
                    <th>Statut</th>
                    <th>Plans</th>
                    <th>Documents</th>
                    <th>Période</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $p): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($p['project_code']) ?></strong></td>
                        <td>
                            <a href="/projects/view.php?id=<?= $p['id'] ?>">
                                <?= htmlspecialchars($p['name']) ?>
                            </a>
                            <?php if ($p['location']): ?>
                                <div class="text-muted"><?= htmlspecialchars($p['location']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($p['client_name'] ?: '—') ?></td>
                        <td>
                            <span class="badge <?= statusBadgeClass($p['status']) ?>">
                                <?= translateStatus($p['status']) ?>
                            </span>
                        </td>
                        <td><?= (int)$p['plans_count'] ?></td>
                        <td><?= (int)$p['documents_count'] ?></td>
                        <td>
                            <?php if ($p['start_date'] || $p['end_date']): ?>
                                <span class="text-muted">
                                    <?= formatDateFr($p['start_date']) ?> → <?= formatDateFr($p['end_date']) ?>
                                </span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="/projects/view.php?id=<?= $p['id'] ?>" 
                                   class="btn btn-secondary btn-sm">Voir</a>
                                <a href="/projects/edit.php?id=<?= $p['id'] ?>" 
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