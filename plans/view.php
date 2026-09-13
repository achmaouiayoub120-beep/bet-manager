<?php
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Détail du plan';
$active_menu = 'plans';

$pdo = getConnection();
$id = (int)($_GET['id'] ?? 0);

// Récupérer le plan
$stmt = $pdo->prepare("SELECT pl.*, pr.name AS project_name, pr.project_code, u.full_name AS creator_name
                       FROM plans pl
                       INNER JOIN projects pr ON pr.id = pl.project_id
                       LEFT JOIN users u ON u.id = pl.created_by
                       WHERE pl.id = ?");
$stmt->execute([$id]);
$plan = $stmt->fetch();

if (!$plan) {
    setFlash('danger', 'Plan introuvable.');
    header('Location: /plans/index.php');
    exit();
}

// Récupérer les versions
$stmt = $pdo->prepare("SELECT pv.*, u.full_name AS uploader_name
                       FROM plan_versions pv
                       LEFT JOIN users u ON u.id = pv.created_by
                       WHERE pv.plan_id = ?
                       ORDER BY pv.created_at DESC");
$stmt->execute([$id]);
$versions = $stmt->fetchAll();

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
        <h1 class="page-title"><?= htmlspecialchars($plan['title']) ?></h1>
        <p class="page-subtitle">
            <a href="/plans/index.php">← Retour aux plans</a>
            · Code : <strong><?= htmlspecialchars($plan['plan_code']) ?></strong>
        </p>
    </div>
    <div class="d-flex gap-1">
        <a href="/versions/create.php?plan_id=<?= $id ?>" class="btn btn-accent">+ Ajouter une version</a>
        <a href="/plans/edit.php?id=<?= $id ?>" class="btn btn-primary">Modifier</a>
        <button type="button" class="btn btn-danger" onclick="openDeleteModal()">Supprimer</button>
    </div>
</div>

<!-- Info plan -->
<div class="plan-header-info">
    <span>Projet : <strong><?= htmlspecialchars($plan['project_code']) ?></strong></span>
    <span>Type : <strong><?= translatePlanType($plan['plan_type']) ?></strong></span>
    <span>Échelle : <strong><?= htmlspecialchars($plan['scale'] ?: '—') ?></strong></span>
    <span>Format : <strong><?= htmlspecialchars($plan['format']) ?></strong></span>
    <span>Statut : <span class="badge <?= statusBadgeClass($plan['status']) ?>"><?= translateStatus($plan['status']) ?></span></span>
</div>

<?php if ($plan['description']): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Description</h3>
        </div>
        <p><?= nl2br(htmlspecialchars($plan['description'])) ?></p>
    </div>
<?php endif; ?>

<!-- Versions -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Versions (<?= count($versions) ?>)</h3>
        <a href="/versions/create.php?plan_id=<?= $id ?>" class="btn btn-accent btn-sm">
            + Nouvelle version
        </a>
    </div>

    <?php if (empty($versions)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📎</div>
            <h3>Aucune version</h3>
            <p>Importez le premier fichier de ce plan pour démarrer le suivi des versions.</p>
            <a href="/versions/create.php?plan_id=<?= $id ?>" class="btn btn-accent mt-2">
                Importer un fichier
            </a>
        </div>
    <?php else: ?>
        <ul class="version-list">
            <?php foreach ($versions as $v): ?>
                <li class="version-item">
                    <div class="version-info">
                        <div class="version-number">
                            Version <?= htmlspecialchars($v['version_number']) ?>
                            <?php if ($v === $versions[0]): ?>
                                <span class="badge badge-success">Actuelle</span>
                            <?php endif; ?>
                        </div>
                        <div class="version-meta">
                            📄 <?= htmlspecialchars($v['file_name']) ?>
                            · <?= formatFileSize($v['file_size']) ?>
                            · Par <?= htmlspecialchars($v['uploader_name'] ?? 'Inconnu') ?>
                            · <?= formatDateFr($v['created_at']) ?>
                        </div>
                        <?php if ($v['change_description']): ?>
                            <div class="version-meta" style="margin-top:0.4rem;">
                                💬 <?= htmlspecialchars($v['change_description']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="version-actions">
                        <a href="/versions/download.php?id=<?= $v['id'] ?>" 
                           class="btn btn-secondary btn-sm">Télécharger</a>
                        <?php if (in_array($_SESSION['user_role'], ['admin', 'engineer'])): ?>
                            <form method="POST" action="/versions/delete.php" 
                                  style="display:inline;"
                                  onsubmit="return confirm('Supprimer cette version ? Cette action est irréversible.');">
                                <input type="hidden" name="id" value="<?= $v['id'] ?>">
                                <input type="hidden" name="plan_id" value="<?= $id ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<!-- Modal de suppression du plan -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <h3>Confirmer la suppression</h3>
        <p>
            Êtes-vous sûr de vouloir supprimer le plan
            <strong><?= htmlspecialchars($plan['title']) ?></strong> ?
            <br><br>
            ⚠️ Toutes les versions et fichiers associés seront également supprimés. Cette action est irréversible.
        </p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Annuler</button>
            <form method="POST" action="/plans/delete.php" style="display:inline;">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
            </form>
        </div>
    </div>
</div>

<script>
function openDeleteModal() { document.getElementById('deleteModal').classList.add('active'); }
function closeDeleteModal() { document.getElementById('deleteModal').classList.remove('active'); }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>