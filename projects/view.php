<?php
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Détail du projet';
$active_menu = 'projects';

$pdo = getConnection();
$id = (int)($_GET['id'] ?? 0);

// Récupérer le projet avec le créateur
$stmt = $pdo->prepare("SELECT p.*, u.full_name AS creator_name 
                       FROM projects p
                       LEFT JOIN users u ON u.id = p.created_by
                       WHERE p.id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    setFlash('danger', 'Projet introuvable.');
    header('Location: /projects/index.php');
    exit();
}

// Récupérer les plans du projet
$stmt = $pdo->prepare("SELECT id, plan_code, title, status, plan_type, created_at 
                       FROM plans WHERE project_id = ? 
                       ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$id]);
$plans = $stmt->fetchAll();

// Récupérer les rapports
$stmt = $pdo->prepare("SELECT id, report_code, title, status, report_type, created_at 
                       FROM reports WHERE project_id = ? 
                       ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$id]);
$reports = $stmt->fetchAll();

// Récupérer les documents
$stmt = $pdo->prepare("SELECT id, title, document_type, file_size, uploaded_at 
                       FROM documents WHERE project_id = ? 
                       ORDER BY uploaded_at DESC LIMIT 10");
$stmt->execute([$id]);
$documents = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= htmlspecialchars($project['name']) ?></h1>
        <p class="page-subtitle">
            <a href="/projects/index.php">← Retour aux projets</a>
            · Code : <strong><?= htmlspecialchars($project['project_code']) ?></strong>
        </p>
    </div>
    <div class="d-flex gap-1">
        <a href="/projects/edit.php?id=<?= $id ?>" class="btn btn-primary">Modifier</a>
        <button type="button" class="btn btn-danger" onclick="openDeleteModal()">Supprimer</button>
    </div>
</div>

<!-- Informations générales -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Informations générales</h3>
        <span class="badge <?= statusBadgeClass($project['status']) ?>">
            <?= translateStatus($project['status']) ?>
        </span>
    </div>

    <div class="detail-grid">
        <div class="detail-item">
            <div class="detail-label">Client</div>
            <div class="detail-value"><?= htmlspecialchars($project['client_name'] ?: '—') ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Lieu</div>
            <div class="detail-value"><?= htmlspecialchars($project['location'] ?: '—') ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Date de début</div>
            <div class="detail-value"><?= formatDateFr($project['start_date']) ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Date de fin</div>
            <div class="detail-value"><?= formatDateFr($project['end_date']) ?></div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Budget</div>
            <div class="detail-value">
                <?= $project['budget'] ? number_format((float)$project['budget'], 2, ',', ' ') . ' MAD' : '—' ?>
            </div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Créé par</div>
            <div class="detail-value">
                <?= htmlspecialchars($project['creator_name'] ?? '—') ?>
                <div class="text-muted">Le <?= formatDateFr($project['created_at']) ?></div>
            </div>
        </div>
    </div>

    <?php if ($project['description']): ?>
        <div class="detail-item mt-3">
            <div class="detail-label">Description</div>
            <div class="detail-value"><?= nl2br(htmlspecialchars($project['description'])) ?></div>
        </div>
    <?php endif; ?>
</div>

<!-- Plans -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Plans (<?= count($plans) ?>)</h3>
        <a href="/plans/create.php?project_id=<?= $id ?>" class="btn btn-secondary btn-sm">+ Nouveau plan</a>
    </div>

    <?php if (empty($plans)): ?>
        <div class="empty-state">
            <p class="text-secondary mb-0">Aucun plan pour ce projet.</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Créé le</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $pl): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($pl['plan_code']) ?></strong></td>
                            <td><?= htmlspecialchars($pl['title']) ?></td>
                            <td><?= htmlspecialchars($pl['plan_type']) ?></td>
                            <td>
                                <span class="badge <?= statusBadgeClass($pl['status']) ?>">
                                    <?= translateStatus($pl['status']) ?>
                                </span>
                            </td>
                            <td><?= formatDateFr($pl['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Rapports -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Rapports (<?= count($reports) ?>)</h3>
        <a href="/reports/create.php?project_id=<?= $id ?>" class="btn btn-secondary btn-sm">+ Nouveau rapport</a>
    </div>

    <?php if (empty($reports)): ?>
        <div class="empty-state">
            <p class="text-secondary mb-0">Aucun rapport pour ce projet.</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Créé le</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reports as $r): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['report_code']) ?></strong></td>
                            <td><?= htmlspecialchars($r['title']) ?></td>
                            <td><?= htmlspecialchars($r['report_type']) ?></td>
                            <td>
                                <span class="badge <?= statusBadgeClass($r['status']) ?>">
                                    <?= translateStatus($r['status']) ?>
                                </span>
                            </td>
                            <td><?= formatDateFr($r['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Documents -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Documents (<?= count($documents) ?>)</h3>
        <a href="/documents/upload.php?project_id=<?= $id ?>" class="btn btn-secondary btn-sm">+ Importer</a>
    </div>

    <?php if (empty($documents)): ?>
        <div class="empty-state">
            <p class="text-secondary mb-0">Aucun document pour ce projet.</p>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Titre</th>
                        <th>Type</th>
                        <th>Taille</th>
                        <th>Importé le</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($documents as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['title']) ?></td>
                            <td><?= htmlspecialchars($d['document_type']) ?></td>
                            <td><?= formatFileSize($d['file_size']) ?></td>
                            <td><?= formatDateFr($d['uploaded_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <h3>Confirmer la suppression</h3>
        <p>
            Êtes-vous sûr de vouloir supprimer le projet
            <strong><?= htmlspecialchars($project['name']) ?></strong> ?
            <br><br>
            ⚠️ Tous les plans, rapports et documents associés seront également supprimés. Cette action est irréversible.
        </p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Annuler</button>
            <form method="POST" action="/projects/delete.php" style="display:inline;">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn btn-danger">Supprimer définitivement</button>
            </form>
        </div>
    </div>
</div>

<script>
function openDeleteModal() {
    document.getElementById('deleteModal').classList.add('active');
}
function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>