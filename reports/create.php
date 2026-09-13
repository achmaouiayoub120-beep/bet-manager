<?php
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Nouveau rapport';
$active_menu = 'reports';

$pdo = getConnection();
$projects = $pdo->query("SELECT id, project_code, name FROM projects ORDER BY name ASC")->fetchAll();
$defaultProject = (int)($_GET['project_id'] ?? 0);

$errors = [];
$data = [
    'report_code' => 'RPT-' . date('Y') . '-' . strtoupper(substr(uniqid(), -4)),
    'title' => '',
    'content' => '',
    'project_id' => $defaultProject,
    'report_type' => 'technical',
    'status' => 'draft',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $key => $_) $data[$key] = trim($_POST[$key] ?? '');
    $data['project_id'] = (int)$data['project_id'];

    if ($data['title'] === '') $errors['title'] = 'Le titre est obligatoire.';
    if ($data['report_code'] === '') $errors['report_code'] = 'Le code est obligatoire.';

    if (empty($errors['report_code'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE report_code = ?");
        $stmt->execute([$data['report_code']]);
        if ($stmt->fetchColumn() > 0) $errors['report_code'] = 'Ce code existe déjà.';
    }

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO reports (report_code, title, content, project_id, report_type, status, created_by)
                    VALUES (:code, :title, :content, :project, :type, :status, :user)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':code' => $data['report_code'],
                ':title' => $data['title'],
                ':content' => $data['content'] ?: null,
                ':project' => $data['project_id'] > 0 ? $data['project_id'] : null,
                ':type' => $data['report_type'],
                ':status' => $data['status'],
                ':user' => $_SESSION['user_id'],
            ]);
            $newId = $pdo->lastInsertId();
            logActivity($pdo, 'create', 'report', $newId, "Création du rapport : {$data['title']}");
            setFlash('success', 'Rapport créé.');
            header('Location: /reports/view.php?id=' . $newId);
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
        <h1 class="page-title">Nouveau rapport</h1>
        <p class="page-subtitle"><a href="/reports/index.php">← Retour</a></p>
    </div>
</div>

<?php if (!empty($errors['general'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label required">Code rapport</label>
                <input type="text" name="report_code" class="form-control" value="<?= htmlspecialchars($data['report_code']) ?>" required>
                <?php if (isset($errors['report_code'])): ?><div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['report_code']) ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">Projet associé</label>
                <select name="project_id" class="form-control">
                    <option value="0">— Aucun projet —</option>
                    <?php foreach ($projects as $pr): ?>
                        <option value="<?= $pr['id'] ?>" <?= $data['project_id'] == $pr['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pr['project_code'] . ' - ' . $pr['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label required">Titre du rapport</label>
            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($data['title']) ?>" required>
            <?php if (isset($errors['title'])): ?><div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['title']) ?></div><?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Type</label>
                <select name="report_type" class="form-control">
                    <option value="technical" <?= $data['report_type'] === 'technical' ? 'selected' : '' ?>>Technique</option>
                    <option value="progress" <?= $data['report_type'] === 'progress' ? 'selected' : '' ?>>Avancement</option>
                    <option value="inspection" <?= $data['report_type'] === 'inspection' ? 'selected' : '' ?>>Inspection</option>
                    <option value="calculation" <?= $data['report_type'] === 'calculation' ? 'selected' : '' ?>>Calcul</option>
                    <option value="other" <?= $data['report_type'] === 'other' ? 'selected' : '' ?>>Autre</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Statut</label>
                <select name="status" class="form-control">
                    <option value="draft" <?= $data['status'] === 'draft' ? 'selected' : '' ?>>Brouillon</option>
                    <option value="submitted" <?= $data['status'] === 'submitted' ? 'selected' : '' ?>>Soumis</option>
                    <option value="approved" <?= $data['status'] === 'approved' ? 'selected' : '' ?>>Approuvé</option>
                    <option value="rejected" <?= $data['status'] === 'rejected' ? 'selected' : '' ?>>Rejeté</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Contenu du rapport</label>
            <textarea name="content" class="form-control" style="min-height:300px;" placeholder="Rédigez le contenu ici..."><?= htmlspecialchars($data['content']) ?></textarea>
        </div>

        <div class="d-flex gap-1 mt-3">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="/reports/index.php" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>