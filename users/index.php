<?php
require_once __DIR__ . '/../includes/auth_check.php';

// Accès admin uniquement
if ($_SESSION['user_role'] !== 'admin') {
    setFlash('danger', 'Accès réservé aux administrateurs.');
    header('Location: /dashboard/index.php');
    exit();
}

$page_title = 'Utilisateurs';
$active_menu = 'users';

$pdo = getConnection();

$search = trim($_GET['search'] ?? '');
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(u.username LIKE :search OR u.email LIKE :search OR u.full_name LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if ($role !== '' && in_array($role, ['admin', 'engineer', 'technician', 'viewer'])) {
    $where[] = "u.role = :role";
    $params[':role'] = $role;
}
if ($status === 'active') {
    $where[] = "u.is_active = TRUE";
} elseif ($status === 'inactive') {
    $where[] = "u.is_active = FALSE";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM projects WHERE created_by = u.id) AS projects_count
        FROM users u
        $whereSql
        ORDER BY u.is_active DESC, u.full_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

function roleBadgeClass($role) {
    $classes = [
        'admin' => 'badge-danger',
        'engineer' => 'badge-primary',
        'technician' => 'badge-info',
        'viewer' => 'badge-secondary',
    ];
    return $classes[$role] ?? 'badge-secondary';
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Utilisateurs</h1>
        <p class="page-subtitle"><?= count($users) ?> utilisateur<?= count($users) > 1 ? 's' : '' ?></p>
    </div>
    <a href="/users/create.php" class="btn btn-primary">+ Nouvel utilisateur</a>
</div>

<form method="GET" action="" class="filters-bar">
    <div class="form-group">
        <label class="form-label">Recherche</label>
        <input type="text" name="search" class="form-control" value="<?= htmlspecialchars($search) ?>" placeholder="Nom, email, identifiant...">
    </div>
    <div class="form-group">
        <label class="form-label">Rôle</label>
        <select name="role" class="form-control">
            <option value="">Tous</option>
            <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrateur</option>
            <option value="engineer" <?= $role === 'engineer' ? 'selected' : '' ?>>Ingénieur</option>
            <option value="technician" <?= $role === 'technician' ? 'selected' : '' ?>>Technicien</option>
            <option value="viewer" <?= $role === 'viewer' ? 'selected' : '' ?>>Consultant</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Statut</label>
        <select name="status" class="form-control">
            <option value="">Tous</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Actifs</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Désactivés</option>
        </select>
    </div>
    <div>
        <button type="submit" class="btn btn-primary">Filtrer</button>
        <a href="/users/index.php" class="btn btn-secondary">Réinitialiser</a>
    </div>
</form>

<?php if (empty($users)): ?>
    <div class="card">
        <div class="empty-state">
            <div class="empty-state-icon">👥</div>
            <h3>Aucun utilisateur trouvé</h3>
        </div>
    </div>
<?php else: ?>
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Nom complet</th>
                    <th>Identifiant</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Créé le</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr style="<?= !$u['is_active'] ? 'opacity:0.6;' : '' ?>">
                        <td>
                            <strong><?= htmlspecialchars($u['full_name']) ?></strong>
                            <?php if ($u['id'] == $_SESSION['user_id']): ?>
                                <span class="badge badge-info" style="margin-left:0.5rem;">Vous</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <span class="badge <?= roleBadgeClass($u['role']) ?>">
                                <?= translateRole($u['role']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="badge badge-success">Actif</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Désactivé</span>
                            <?php endif; ?>
                        </td>
                        <td><?= formatDateFr($u['created_at']) ?></td>
                        <td>
                            <div class="table-actions">
                                <a href="/users/edit.php?id=<?= $u['id'] ?>" 
                                   class="btn btn-secondary btn-sm">Modifier</a>
                                
                                <?php if ($u['is_active'] && $u['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" action="/users/delete.php" 
                                          style="display:inline;"
                                          onsubmit="return confirm('Désactiver cet utilisateur ? Il ne pourra plus se connecter.');">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Désactiver</button>
                                    </form>
                                <?php elseif (!$u['is_active']): ?>
                                    <form method="POST" action="/users/delete.php" 
                                          style="display:inline;"
                                          onsubmit="return confirm('Réactiver cet utilisateur ?');">
                                        <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="reactivate" value="1">
                                        <button type="submit" class="btn btn-success btn-sm">Réactiver</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>