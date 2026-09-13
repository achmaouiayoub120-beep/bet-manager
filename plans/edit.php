<?php
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Modifier le plan';
$active_menu = 'plans';

$pdo = getConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM plans WHERE id = ?");
$stmt->execute([$id]);
$plan = $stmt->fetch();

if (!$plan) {
    setFlash('danger', 'Plan introuvable.');
    header('Location: /plans/index.php');
    exit();
}

$projects = $pdo->query("SELECT id, project_code, name FROM projects ORDER BY name ASC")->fetchAll();

$errors = [];
$data = $plan;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['plan_code', 'title', 'description', 'project_id', 'plan_type', 'scale', 'format', 'status'] as $key) {
        $data[$key] = trim($_POST[$key] ?? '');
    }
    $data['project_id'] = (int)$data['project_id'];

    if ($data['title'] === '') $errors['title'] = 'Le titre est obligatoire.';
    if ($data['plan_code'] === '') $errors['plan_code'] = 'Le code est obligatoire.';
    if ($data['project_id'] <= 0) $errors['project_id'] = 'Veuillez sélectionner un projet.';

    if (empty($errors['plan_code'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM plans WHERE plan_code = ? AND id != ?");
        $stmt->execute([$data['plan_code'], $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors['plan_code'] = 'Ce code existe déjà.';
        }
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE plans SET plan_code=:code, title=:title, description=:desc, 
                    project_id=:project, plan_type=:type, scale=:scale, format=:format, status=:status
                    WHERE id=:id";
            
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
                ':id' => $id,
            ]);
            
            logActivity($pdo, 'update', 'plan', $id, 
                "Modification du plan : {$data['title']} ({$data['plan_code']})");
            
            setFlash('success', 'Plan modifié.');
            header('Location: /plans/view.php?id=' . $id);
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
        <h1 class="page-title">Modifier le plan</h1>
        <p class="page-subtitle">
            <a href="/plans/view.php?id=<?= $id ?>">← Retour au plan</a>
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
                    <?php foreach ($projects as $pr): ?>
                        <option value="<?= $pr['id'] ?>" <?= $data['project_id'] == $pr['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pr['project_code'] . ' - ' . $pr['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label required">Titre du plan</label>
            <input type="text" name="title" class="form-control"
                   value="<?= htmlspecialchars($data['title']) ?>" required>
            <?php if (isset($errors['title'])): ?>
                <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['title']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Type</label>
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
                       value="<?= htmlspecialchars($data['scale'] ?? '') ?>">
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
            <textarea name="description" class="form-control"><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
        </div>

        <div class="d-flex gap-1 mt-3">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="/plans/view.php?id=<?= $id ?>" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>