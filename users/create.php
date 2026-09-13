<?php
require_once __DIR__ . '/../includes/auth_check.php';

if ($_SESSION['user_role'] !== 'admin') {
    setFlash('danger', 'Accès réservé aux administrateurs.');
    header('Location: /dashboard/index.php');
    exit();
}

$page_title = 'Nouvel utilisateur';
$active_menu = 'users';

$pdo = getConnection();
$errors = [];

$data = [
    'username' => '',
    'email' => '',
    'full_name' => '',
    'role' => 'viewer',
    'is_active' => 1,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['username'] = trim($_POST['username'] ?? '');
    $data['email'] = trim($_POST['email'] ?? '');
    $data['full_name'] = trim($_POST['full_name'] ?? '');
    $data['role'] = $_POST['role'] ?? 'viewer';
    $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    // Validations
    if ($data['username'] === '') {
        $errors['username'] = 'L\'identifiant est obligatoire.';
    } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $data['username'])) {
        $errors['username'] = 'L\'identifiant doit contenir 3-50 caractères (lettres, chiffres, . _ -).';
    }
    
    if ($data['email'] === '') {
        $errors['email'] = 'L\'email est obligatoire.';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'L\'email n\'est pas valide.';
    }
    
    if ($data['full_name'] === '') {
        $errors['full_name'] = 'Le nom complet est obligatoire.';
    }
    
    if (!in_array($data['role'], ['admin', 'engineer', 'technician', 'viewer'])) {
        $data['role'] = 'viewer';
    }
    
    if ($password === '') {
        $errors['password'] = 'Le mot de passe est obligatoire.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
    }

    // Unicité
    if (empty($errors['username'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$data['username']]);
        if ($stmt->fetchColumn() > 0) {
            $errors['username'] = 'Cet identifiant existe déjà.';
        }
    }
    if (empty($errors['email'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetchColumn() > 0) {
            $errors['email'] = 'Cet email est déjà utilisé.';
        }
    }

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO users (username, email, password_hash, full_name, role, is_active)
                    VALUES (:username, :email, :hash, :fullname, :role, :active)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':hash' => password_hash($password, PASSWORD_DEFAULT),
                ':fullname' => $data['full_name'],
                ':role' => $data['role'],
                ':active' => $data['is_active'],
            ]);
            
            $newId = $pdo->lastInsertId();
            
            logActivity($pdo, 'create', 'user', $newId, 
                "Création de l'utilisateur : {$data['full_name']} ({$data['username']})");
            
            setFlash('success', 'Utilisateur créé avec succès.');
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
        <h1 class="page-title">Nouvel utilisateur</h1>
        <p class="page-subtitle">
            <a href="/users/index.php">← Retour</a>
        </p>
    </div>
</div>

<?php if (!empty($errors['general'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label required">Identifiant</label>
                <input type="text" name="username" class="form-control" 
                       value="<?= htmlspecialchars($data['username']) ?>"
                       placeholder="ex: j.dupont" required>
                <?php if (isset($errors['username'])): ?>
                    <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['username']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label required">Nom complet</label>
                <input type="text" name="full_name" class="form-control"
                       value="<?= htmlspecialchars($data['full_name']) ?>"
                       placeholder="ex: Jean Dupont" required>
                <?php if (isset($errors['full_name'])): ?>
                    <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['full_name']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label required">Email</label>
            <input type="email" name="email" class="form-control"
                   value="<?= htmlspecialchars($data['email']) ?>"
                   placeholder="ex: j.dupont@bet-manager.fr" required>
            <?php if (isset($errors['email'])): ?>
                <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['email']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label required">Mot de passe</label>
                <input type="password" name="password" class="form-control" required>
                <div class="form-help">8 caractères minimum</div>
                <?php if (isset($errors['password'])): ?>
                    <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['password']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label required">Confirmer le mot de passe</label>
                <input type="password" name="password_confirm" class="form-control" required>
                <?php if (isset($errors['password_confirm'])): ?>
                    <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['password_confirm']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label required">Rôle</label>
                <select name="role" class="form-control">
                    <option value="viewer" <?= $data['role'] === 'viewer' ? 'selected' : '' ?>>Consultant (lecture seule)</option>
                    <option value="technician" <?= $data['role'] === 'technician' ? 'selected' : '' ?>>Technicien</option>
                    <option value="engineer" <?= $data['role'] === 'engineer' ? 'selected' : '' ?>>Ingénieur</option>
                    <option value="admin" <?= $data['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
                </select>
                <div class="form-help">
                    <strong>Consultant :</strong> lecture uniquement<br>
                    <strong>Technicien :</strong> peut créer et modifier<br>
                    <strong>Ingénieur :</strong> + peut supprimer<br>
                    <strong>Admin :</strong> tous les droits
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Statut du compte</label>
                <label style="display:flex;align-items:center;gap:0.5rem;padding:0.65rem 0;">
                    <input type="checkbox" name="is_active" value="1" <?= $data['is_active'] ? 'checked' : '' ?>>
                    Compte actif (peut se connecter)
                </label>
            </div>
        </div>

        <div class="d-flex gap-1 mt-3">
            <button type="submit" class="btn btn-primary">Créer l'utilisateur</button>
            <a href="/users/index.php" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>