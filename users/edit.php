<?php
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SESSION['user_role'] !== 'admin') {
    setFlash('danger', 'Accès réservé aux administrateurs.');
    header('Location: /dashboard/index.php');
    exit();
}

$page_title = 'Modifier l\'utilisateur';
$active_menu = 'users';

$pdo = getConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('danger', 'Utilisateur introuvable.');
    header('Location: /users/index.php');
    exit();
}

$errors = [];
$data = $user;
$isSelf = ($id === $_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['username'] = trim($_POST['username'] ?? '');
    $data['email'] = trim($_POST['email'] ?? '');
    $data['full_name'] = trim($_POST['full_name'] ?? '');
    $data['role'] = $_POST['role'] ?? 'viewer';
    $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    
    $newPassword = $_POST['new_password'] ?? '';
    $newPasswordConfirm = $_POST['new_password_confirm'] ?? '';

    // Validations
    if ($data['username'] === '') {
        $errors['username'] = 'L\'identifiant est obligatoire.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $data['username'])) {
        $errors['username'] = 'Format d\'identifiant invalide.';
    }
    
    if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email invalide.';
    }
    
    if ($data['full_name'] === '') {
        $errors['full_name'] = 'Le nom complet est obligatoire.';
    }
    
    if (!in_array($data['role'], ['admin', 'engineer', 'technician', 'viewer'])) {
        $data['role'] = 'viewer';
    }

    // Sécurité : un admin ne peut pas se retirer son propre rôle admin
    if ($isSelf && $user['role'] === 'admin' && $data['role'] !== 'admin') {
        $errors['role'] = 'Vous ne pouvez pas retirer votre propre rôle administrateur.';
    }
    
    // Sécurité : un admin ne peut pas se désactiver lui-même
    if ($isSelf && !$data['is_active']) {
        $errors['is_active'] = 'Vous ne pouvez pas désactiver votre propre compte.';
    }

    // Unicité
    if (empty($errors['username'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$data['username'], $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors['username'] = 'Cet identifiant existe déjà.';
        }
    }
    if (empty($errors['email'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$data['email'], $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors['email'] = 'Cet email est déjà utilisé.';
        }
    }

    // Mot de passe (optionnel)
    if ($newPassword !== '') {
        if (strlen($newPassword) < 8) {
            $errors['new_password'] = 'Le mot de passe doit contenir au moins 8 caractères.';
        } elseif ($newPassword !== $newPasswordConfirm) {
            $errors['new_password_confirm'] = 'Les mots de passe ne correspondent pas.';
        }
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE users SET username=:username, email=:email, full_name=:fullname, 
                    role=:role, is_active=:active";
            $params = [
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':fullname' => $data['full_name'],
                ':role' => $data['role'],
                ':active' => $data['is_active'],
                ':id' => $id,
            ];
            
            if ($newPassword !== '') {
                $sql .= ", password_hash=:hash";
                $params[':hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id=:id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $changes = [];
            if ($user['role'] !== $data['role']) {
                $changes[] = "rôle: " . translateRole($user['role']) . " → " . translateRole($data['role']);
            }
            if ($user['is_active'] != $data['is_active']) {
                $changes[] = "statut: " . ($data['is_active'] ? 'activé' : 'désactivé');
            }
            if ($newPassword !== '') {
                $changes[] = "mot de passe modifié";
            }
            
            $desc = "Modification de l'utilisateur : {$data['full_name']}";
            if ($changes) $desc .= " (" . implode(', ', $changes) . ")";
            
            logActivity($pdo, 'update', 'user', $id, $desc);
            
            setFlash('success', 'Utilisateur modifié avec succès.');
            header('Location: /users/index.php');
            exit();
            
        } catch (PDOException $e) {
            $errors['general'] = 'Erreur : ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Modifier l'utilisateur</h1>
        <p class="page-subtitle">
            <a href="/users/index.php">← Retour</a>
            · <?= htmlspecialchars($user['full_name']) ?>
        </p>
    </div>
</div>

<?php if (!empty($errors['general'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
<?php endif; ?>

<?php if ($isSelf): ?>
    <div class="alert alert-info">
        ℹ️ Vous modifiez votre propre compte. Certaines restrictions s'appliquent (vous ne pouvez pas retirer votre rôle admin ni désactiver votre compte).
    </div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label required">Identifiant</label>
                <input type="text" name="username" class="form-control" 
                       value="<?= htmlspecialchars($data['username']) ?>" required>
                <?php if (isset($errors['username'])): ?>
                    <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['username']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label required">Nom complet</label>
                <input type="text" name="full_name" class="form-control"
                       value="<?= htmlspecialchars($data['full_name']) ?>" required>
                <?php if (isset($errors['full_name'])): ?>
                    <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['full_name']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label required">Email</label>
            <input type="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($data['email']) ?>" required>
            <?php if (isset($errors['email'])): ?>
                <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['email']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label required">Rôle</label>
                <select name="role" class="form-control">
                    <option value="viewer" <?= $data['role'] === 'viewer' ? 'selected' : '' ?>>Consultant</option>
                    <option value="technician" <?= $data['role'] === 'technician' ? 'selected' : '' ?>>Technicien</option>
                    <option value="engineer" <?= $data['role'] === 'engineer' ? 'selected' : '' ?>>Ingénieur</option>
                    <option value="admin" <?= $data['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                </select>
                <?php if (isset($errors['role'])): ?>
                    <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['role']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Statut du compte</label>
                <label style="display:flex;align-items:center;gap:0.5rem;padding:0.65rem 0;">
                    <input type="checkbox" name="is_active" value="1" <?= $data['is_active'] ? 'checked' : '' ?> <?= $isSelf ? 'disabled' : '' ?>>
                    Compte actif
                    <?php if ($isSelf): ?>
                        <input type="hidden" name="is_active" value="1">
                    <?php endif; ?>
                </label>
                <?php if (isset($errors['is_active'])): ?>
                    <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['is_active']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div style="border-top:1px solid var(--border-light); padding-top:1.5rem; margin-top:1.5rem;">
            <h3 style="font-size:1rem; margin-bottom:1rem;">Changer le mot de passe (optionnel)</h3>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Nouveau mot de passe</label>
                    <input type="password" name="new_password" class="form-control" 
                           placeholder="Laisser vide pour ne pas changer">
                    <div class="form-help">8 caractères minimum</div>
                    <?php if (isset($errors['new_password'])): ?>
                        <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['new_password']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirmer</label>
                    <input type="password" name="new_password_confirm" class="form-control">
                    <?php if (isset($errors['new_password_confirm'])): ?>
                        <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['new_password_confirm']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="d-flex gap-1 mt-3">
            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            <a href="/users/index.php" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>