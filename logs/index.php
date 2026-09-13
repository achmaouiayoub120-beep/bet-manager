<?php
require_once __DIR__ . '/../includes/auth_check.php';

// Accès admin uniquement
if ($_SESSION['user_role'] !== 'admin') {
    setFlash('danger', 'Accès réservé aux administrateurs.');
    header('Location: /dashboard/index.php');
    exit();
}

$page_title = 'Historique des actions';
$active_menu = 'logs';

$pdo = getConnection();

// Paramètres de filtre
$search = trim($_GET['search'] ?? '');
$userId = (int)($_GET['user_id'] ?? 0);
$actionType = $_GET['action_type'] ?? '';
$entityType = $_GET['entity_type'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

// Construction de la requête
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(l.description LIKE :search OR u.full_name LIKE :search OR u.username LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if ($userId > 0) {
    $where[] = "l.user_id = :user_id";
    $params[':user_id'] = $userId;
}
if ($actionType !== '') {
    $where[] = "l.action_type = :action_type";
    $params[':action_type'] = $actionType;
}
if ($entityType !== '') {
    $where[] = "l.entity_type = :entity_type";
    $params[':entity_type'] = $entityType;
}
if ($dateFrom !== '') {
    $where[] = "DATE(l.created_at) >= :date_from";
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = "DATE(l.created_at) <= :date_to";
    $params[':date_to'] = $dateTo;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Compter le total
$countSql = "SELECT COUNT(*) FROM activity_logs l LEFT JOIN users u ON u.id = l.user_id $whereSql";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));

// Récupérer les logs
$sql = "SELECT l.*, u.full_name AS user_name, u.username, u.role AS user_role
        FROM activity_logs l
        LEFT JOIN users u ON u.id = l.user_id
        $whereSql
        ORDER BY l.created_at DESC
        LIMIT $perPage OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Liste des utilisateurs pour le filtre
$users = $pdo->query("SELECT id, username, full_name FROM users ORDER BY full_name ASC")->fetchAll();

// Liste des types d'action distincts
$actionTypes = $pdo->query("SELECT DISTINCT action_type FROM activity_logs ORDER BY action_type ASC")->fetchAll(PDO::FETCH_COLUMN);

// Liste des types d'entité distincts
$entityTypes = $pdo->query("SELECT DISTINCT entity_type FROM activity_logs ORDER BY entity_type ASC")->fetchAll(PDO::FETCH_COLUMN);

// Fonctions de traduction
function translateActionType($type) {
    $labels = [
        'login' => 'Connexion',
        'logout' => 'Déconnexion',
        'create' => 'Création',
        'update' => 'Modification',
        'delete' => 'Suppression',
        'download' => 'Téléchargement',
        'export' => 'Export',
        'activate' => 'Activation',
        'deactivate' => 'Désactivation',
    ];
    return $labels[$type] ?? ucfirst($type);
}

function actionBadgeClass($type) {
    $classes = [
        'login' => 'badge-info',
        'logout' => 'badge-secondary',
        'create' => 'badge-success',
        'update' => 'badge-warning',
        'delete' => 'badge-danger',
        'download' => 'badge-info',
        'export' => 'badge-primary',
        'activate' => 'badge-success',
        'deactivate' => 'badge-danger',
    ];
    return $classes[$type] ?? 'badge-secondary';
}

function translateEntityType($type) {
    $labels = [
        'user' => 'Utilisateur',
        'project' => 'Projet',
        'plan' => 'Plan',
        'plan_version' => 'Version de plan',
        'report' => 'Rapport',
        'document' => 'Document',
    ];
    return $labels[$type] ?? ucfirst($type);
}

function entityIcon($type) {
    $icons = [
        'user' => '👤',
        'project' => '🏗️',
        'plan' => '📐',
        'plan_version' => '📎',
        'report' => '📄',
        'document' => '📁',
    ];
    return $icons[$type] ?? '📌';
}

function actionIcon($type) {
    $icons = [
        'login' => '🔓',
        'logout' => '🔒',
        'create' => '➕',
        'update' => '✏️',
        'delete' => '🗑️',
        'download' => '⬇️',
        'export' => '📤',
        'activate' => '✅',
        'deactivate' => '🚫',
    ];
    return $icons[$type] ?? '•';
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Historique des actions</h1>
        <p class="page-subtitle">
            <?= number_format($total, 0, ',', ' ') ?> action<?= $total > 1 ? 's' : '' ?> enregistrée<?= $total > 1 ? 's' : '' ?>
        </p>
    </div>
</div>

<!-- Statistiques rapides -->
<?php
// Compter les actions des 7 derniers jours
$stats = [];
$stats['today'] = $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$stats['week'] = $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
$stats['month'] = $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
$stats['total'] = $pdo->query("SELECT COUNT(*) FROM activity_logs")->fetchColumn();
?>
<div class="stats-grid" style="margin-bottom:1.5rem;">
    <div class="stat-card">
        <div class="stat-label">Aujourd'hui</div>
        <div class="stat-value"><?= $stats['today'] ?></div>
        <div class="stat-description">actions</div>
    </div>
    <div class="stat-card success">
        <div class="stat-label">7 derniers jours</div>
        <div class="stat-value"><?= $stats['week'] ?></div>
        <div class="stat-description">actions</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-label">30 derniers jours</div>
        <div class="stat-value"><?= $stats['month'] ?></div>
        <div class="stat-description">actions</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total</div>
        <div class="stat-value"><?= number_format($stats['total'], 0, ',', ' ') ?></div>
        <div class="stat-description">depuis le début</div>
    </div>
</div>

<!-- Filtres -->
<form method="GET" action="" class="filters-bar">
    <div class="form-group">
        <label class="form-label">Recherche</label>
        <input type="text" name="search" class="form-control" 
               value="<?= htmlspecialchars($search) ?>" 
               placeholder="Description, utilisateur...">
    </div>
    
    <div class="form-group">
        <label class="form-label">Utilisateur</label>
        <select name="user_id" class="form-control">
            <option value="">Tous</option>
            <?php foreach ($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= $userId == $u['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($u['full_name']) ?> (<?= htmlspecialchars($u['username']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label">Type d'action</label>
        <select name="action_type" class="form-control">
            <option value="">Toutes</option>
            <?php foreach ($actionTypes as $at): ?>
                <option value="<?= htmlspecialchars($at) ?>" <?= $actionType === $at ? 'selected' : '' ?>>
                    <?= translateActionType($at) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label">Type d'élément</label>
        <select name="entity_type" class="form-control">
            <option value="">Tous</option>
            <?php foreach ($entityTypes as $et): ?>
                <option value="<?= htmlspecialchars($et) ?>" <?= $entityType === $et ? 'selected' : '' ?>>
                    <?= translateEntityType($et) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label">Du</label>
        <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
    </div>

    <div class="form-group">
        <label class="form-label">Au</label>
        <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
    </div>

    <div>
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <a href="/logs/index.php" class="btn btn-secondary">Réinitialiser</a>
    </div>
</form>

<?php if (empty($logs)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon">📜</div>
            <h3>Aucune action trouvée</h3>
            <p><?= $search || $userId || $actionType || $entityType || $dateFrom || $dateTo 
                ? 'Essayez de modifier vos filtres.' 
                : 'Aucune action enregistrée pour le moment.' ?></p>
        </div>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th style="width:40px;"></th>
                    <th>Date</th>
                    <th>Utilisateur</th>
                    <th>Action</th>
                    <th>Élément</th>
                    <th>Description</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="text-align:center;font-size:1.2rem;">
                            <?= actionIcon($log['action_type']) ?>
                        </td>
                        <td>
                            <div><?= date('d/m/Y', strtotime($log['created_at'])) ?></div>
                            <div class="text-muted" style="font-size:0.8rem;">
                                <?= date('H:i:s', strtotime($log['created_at'])) ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($log['user_name']): ?>
                                <strong><?= htmlspecialchars($log['user_name']) ?></strong>
                                <div class="text-muted" style="font-size:0.8rem;">
                                    @<?= htmlspecialchars($log['username']) ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">Système</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= actionBadgeClass($log['action_type']) ?>">
                                <?= translateActionType($log['action_type']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($log['entity_type']): ?>
                                <?= entityIcon($log['entity_type']) ?>
                                <?= translateEntityType($log['entity_type']) ?>
                                <?php if ($log['entity_id']): ?>
                                    <span class="text-muted">#<?= $log['entity_id'] ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td style="max-width:400px;">
                            <?= htmlspecialchars($log['description'] ?? '') ?>
                        </td>
                        <td class="text-muted" style="font-size:0.8rem;">
                            <?= htmlspecialchars($log['ip_address'] ?? '—') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php 
            $qp = $_GET; 
            $build = function($p) use ($qp) { 
                $qp['page'] = $p; 
                return '?' . http_build_query($qp); 
            }; 
            ?>
            
            <?php if ($page > 1): ?>
                <a href="<?= htmlspecialchars($build(1)) ?>">« Premier</a>
                <a href="<?= htmlspecialchars($build($page - 1)) ?>">‹ Précédent</a>
            <?php else: ?>
                <span class="disabled">« Premier</span>
                <span class="disabled">‹ Précédent</span>
            <?php endif; ?>

            <?php
            // Afficher 5 pages max autour de la page actuelle
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            
            if ($startPage > 1) {
                echo '<span class="disabled">...</span>';
            }
            
            for ($i = $startPage; $i <= $endPage; $i++): 
            ?>
                <?php if ($i == $page): ?>
                    <span class="current"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($build($i)) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($endPage < $totalPages): ?>
                <span class="disabled">...</span>
            <?php endif; ?>

            <?php if ($page < $totalPages): ?>
                <a href="<?= htmlspecialchars($build($page + 1)) ?>">Suivant ›</a>
                <a href="<?= htmlspecialchars($build($totalPages)) ?>">Dernier »</a>
            <?php else: ?>
                <span class="disabled">Suivant ›</span>
                <span class="disabled">Dernier »</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>