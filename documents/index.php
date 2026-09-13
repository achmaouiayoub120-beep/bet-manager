<?php
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Documents';
$active_menu = 'documents';

$pdo = getConnection();

$search = trim($_GET['search'] ?? '');
$type = $_GET['type'] ?? '';
$projectId = (int)($_GET['project_id'] ?? 0);
$sort = $_GET['sort'] ?? 'recent';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(d.title LIKE :search OR d.description LIKE :search OR d.file_name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if ($type !== '' && in_array($type, ['specification', 'contract', 'certificate', 'photo', 'other'])) {
    $where[] = "d.document_type = :type";
    $params[':type'] = $type;
}
if ($projectId > 0) {
    $where[] = "d.project_id = :project_id";
    $params[':project_id'] = $projectId;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$orderBy = match($sort) {
    'oldest' => 'd.uploaded_at ASC',
    'title' => 'd.title ASC',
    'size' => 'd.file_size DESC',
    default => 'd.uploaded_at DESC',
};

$stmt = $pdo->prepare("SELECT COUNT(*) FROM documents d $whereSql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

$sql = "SELECT d.*, pr.name AS project_name, pr.project_code, u.full_name AS uploader_name
        FROM documents d
        LEFT JOIN projects pr ON pr.id = d.project_id
        LEFT JOIN users u ON u.id = d.uploaded_by
        $whereSql
        ORDER BY $orderBy
        LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$documents = $stmt->fetchAll();

$projects = $pdo->query("SELECT id, project_code, name FROM projects ORDER BY name ASC")->fetchAll();

function translateDocType($t) {
    $labels = [
        'specification' => 'Spécification',
        'contract' => 'Contrat',
        'certificate' => 'Certificat',
        'photo' => 'Photo',
        'other' => 'Autre',
    ];
    return $labels[$t] ?? ucfirst($t);
}

function docTypeIcon($t) {
    $icons = [
        'specification' => '📋',
        'contract' => '📜',
        'certificate' => '🎓',
        'photo' => '🖼️',
        'other' => '📎',
    ];
    return $icons[$t] ?? '📎';
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Documents</h1>
        <p class="page-subtitle"><?= $total ?> document<?= $total > 1 ? 's' : '' ?></p>
    </div>
    <a href="/documents/upload.php" class="btn btn-primary">+ Importer un document</a>
</div>

<form method="GET" action="" class="filters-bar">
    <div class="form-group">
        <label class="form-label">Recherche</label>
        <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Titre, nom de fichier...">
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
            <option value="specification" <?= $type === 'specification' ? 'selected' : '' ?>>Spécification</option>
            <option value="contract" <?= $type === 'contract' ? 'selected' : '' ?>>Contrat</option>
            <option value="certificate" <?= $type === 'certificate' ? 'selected' : '' ?>>Certificat</option>
            <option value="photo" <?= $type === 'photo' ? 'selected' : '' ?>>Photo</option>
            <option value="other" <?= $type === 'other' ? 'selected' : '' ?>>Autre</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Trier par</label>
        <select name="sort" class="form-control">
            <option value="recent" <?= $sort === 'recent' ? 'selected' : '' ?>>Plus récent</option>
            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Plus ancien</option>
            <option value="title" <?= $sort === 'title' ? 'selected' : '' ?>>Titre</option>
            <option value="size" <?= $sort === 'size' ? 'selected' : '' ?>>Taille</option>
        </select>
    </div>
    <div>
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <a href="/documents/index.php" class="btn btn-secondary">Réinitialiser</a>
    </div>
</form>

<?php if (empty($documents)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon">📁</div>
            <h3>Aucun document trouvé</h3>
            <p><?= $search || $type || $projectId ? 'Essayez d\'autres filtres.' : 'Importez votre premier document.' ?></p>
            <?php if (!$search && !$type && !$projectId): ?>
                <a href="/documents/upload.php" class="btn btn-primary mt-2">+ Importer un document</a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th></th>
                    <th>Titre</th>
                    <th>Projet</th>
                    <th>Type</th>
                    <th>Fichier</th>
                    <th>Taille</th>
                    <th>Importé le</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $d): ?>
                    <tr>
                        <td style="font-size:1.4rem;text-align:center;">
                            <?= docTypeIcon($d['document_type']) ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($d['title']) ?></strong>
                            <?php if ($d['description']): ?>
                                <div class="text-muted" style="font-size:0.85rem;">
                                    <?= htmlspecialchars(mb_strimwidth($d['description'], 0, 60, '...')) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($d['project_code']): ?>
                                <div><?= htmlspecialchars($d['project_code']) ?></div>
                                <div class="text-muted"><?= htmlspecialchars($d['project_name']) ?></div>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td><?= translateDocType($d['document_type']) ?></td>
                        <td class="text-muted" style="font-size:0.85rem;">
                            <?= htmlspecialchars($d['file_name']) ?>
                        </td>
                        <td><?= formatFileSize($d['file_size']) ?></td>
                        <td><?= formatDateFr($d['uploaded_at']) ?></td>
                        <td>
                            <div class="table-actions">
                                <a href="/documents/download.php?id=<?= $d['id'] ?>" 
                                   class="btn btn-secondary btn-sm">Télécharger</a>
                                <a href="/documents/edit.php?id=<?= $d['id'] ?>" 
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