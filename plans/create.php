<?php
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Nouveau plan';
$active_menu = 'plans';

$pdo = getConnection();

// Récupérer les projets
$projects = $pdo->query("SELECT id, project_code, name FROM projects ORDER BY name ASC")->fetchAll();

// Pré-remplir le projet si passé en paramètre
$defaultProject = (int)($_GET['project_id'] ?? 0);

$errors = [];
$data = [
    'plan_code' => '',
    'title' => '',
    'description' => '',
    'project_id' => $defaultProject,
    'plan_type' => 'structural',
    'scale' => '',
    'format' => 'A3',
    'status' => 'draft',
];

// Génération d'un code par défaut
if (empty($data['plan_code'])) {
    $data['plan_code'] = 'PLN-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $key => $_) {
        $data[$key] = trim($_POST[$key] ?? '');
    }
    $data['project_id'] = (int)$data['project_id'];

    // Validation
    if ($data['title'] === '') $errors['title'] = 'Le titre est obligatoire.';
    if ($data['plan_code'] === '') $errors['plan_code'] = 'Le code est obligatoire.';
    if ($data['project_id'] <= 0) $errors['project_id'] = 'Veuillez sélectionner un projet.';
    if (!in_array($data['plan_type'], ['architectural', 'structural', 'electrical', 'plumbing', 'other'])) {
        $data['plan_type'] = 'structural';
    }
    if (!in_array($data['format'], ['A0', 'A1', 'A2', 'A3', 'A4'])) {
        $data['format'] = 'A3';
    }
    if (!in_array($data['status'], ['draft', 'review', 'approved', 'rejected', 'obsolete'])) {
        $data['status'] = 'draft';
    }

    // Vérifier l'unicité du code
    if (empty($errors['plan_code'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM plans WHERE plan_code = ?");
        $stmt->execute([$data['plan_code']]);
        if ($stmt->fetchColumn() > 0) {
            $errors['plan_code'] = 'Ce code plan existe déjà.';
        }
    }

    // Vérifier que le projet existe
    if ($data['project_id'] > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE id = ?");
        $stmt->execute([$data['project_id']]);
        if (!$stmt->fetchColumn()) {
            $errors['project_id'] = 'Le projet sélectionné n\'existe pas.';
        }
    }

    // Insertion
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO plans 
                    (plan_code, title, description, project_id, plan_type, scale, format, status, created_by) 
                    VALUES (:code, :title, :desc, :project, :type, :scale, :format, :status, :created_by)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':code' => $data['plan_code'],
                ':title' => $data['title'],
                ':desc' => $data['description'] ?: null,
                ':project' => $data['project_id'],
                ':type' => $data['plan_type'],
                ':scale' => $data['scale'] ?: null,
                ':format' => $data['format'],
                ':status' => $data['status'],
                ':created_by' => $_SESSION['user_id'],
            ]);
            
            $newId = $pdo->lastInsertId();
            
            logActivity($pdo, 'create', 'plan', $newId, 
                "Création du plan : {$data['title']} ({$data['plan_code']})");
            
            setFlash('success', 'Plan créé. Vous pouvez maintenant ajouter la première version du fichier.');
            header('Location: /plans/view.php?id=' . $newId);
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
        <h1 class="page-title">Nouveau plan</h1>
        <p class="page-subtitle">
            <a href="/plans/index.php">← Retour aux plans</a>
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
                <label class="form-label required">Code plan</label>
                <input type="text" name="plan_code" class="form-control"
                       value="<?= htmlspecialchars($data['plan_code']) ?>" required>
                <?php if (isset($errors['plan_code'])): ?>
                    <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['plan_code']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label required">Projet associé</label>
                <select name="project_id" class="form-control" required>
                    <option value="">— Sélectionner un projet —</option>
                    <?php foreach ($projects as $pr): ?>
                        <option value="<?= $pr['id'] ?>" <?= $data['project_id'] == $pr['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pr['project_code'] . ' - ' . $pr['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['project_id'])): ?>
                    <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['project_id']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label required">Titre du plan</label>
            <input type="text" name="title" class="form-control"
                   value="<?= htmlspecialchars($data['title']) ?>"
                   placeholder="Ex: Plan de coffrage niveau R+1" required>
            <?php if (isset($errors['title'])): ?>
                <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['title']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Type de plan</label>
                <select name="plan_type" class="form-control">
                    <option value="architectural" <?= $data['plan_type'] === 'architectural' ? 'selected' : '' ?>>Architectural</option>
                    <option value="structural" <?= $data['plan_type'] === 'structural' ? 'selected' : '' ?>>Structurel</option>
                    <option value="electrical" <?= $data['plan_type'] === 'electrical' ? 'selected' : '' ?>>Électrique</option>
                    <option value="plumbing" <?= $data['plan_type'] === 'plumbing' ? 'selected' : '' ?>>Plomberie</option>
                    <option value="other" <?= $data['plan_type'] === 'other' ? 'selected' : '' ?>>Autre</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Échelle</label>
                <input type="text" name="scale" class="form-control"
                       value="<?= htmlspecialchars($data['scale']) ?>"
                       placeholder="Ex: 1/50, 1/100">
            </div>

            <div class="form-group">
                <label class="form-label">Format</label>
                <select name="format" class="form-control">
                    <option value="A0" <?= $data['format'] === 'A0' ? 'selected' : '' ?>>A0</option>
                    <option value="A1" <?= $data['format'] === 'A1' ? 'selected' : '' ?>>A1</option>
                    <option value="A2" <?= $data['format'] === 'A2' ? 'selected' : '' ?>>A2</option>
                    <option value="A3" <?= $data['format'] === 'A3' ? 'selected' : '' ?>>A3</option>
                    <option value="A4" <?= $data['format'] === 'A4' ? 'selected' : '' ?>>A4</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Statut</label>
                <select name="status" class="form-control">
                    <option value="draft" <?= $data['status'] === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                    <option value="review" <?= $data['status'] === 'review' ? 'selected' : '' ?>>En révision</option>
                    <option value="approved" <?= $data['status'] === 'approved' ? 'selected' : '' ?>>Approuvé</option>
                    <option value="rejected" <?= $data['status'] === 'rejected' ? 'selected' : '' ?>>Rejeté</option>
                    <option value="obsolete" <?= $data['status'] === 'obsolete' ? 'selected' : '' ?>>Obsolète</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"
                      placeholder="Description du plan..."><?= htmlspecialchars($data['description']) ?></textarea>
        </div>

        <div class="d-flex gap-1 mt-3">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="/plans/index.php" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>