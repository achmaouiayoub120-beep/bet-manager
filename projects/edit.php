<?php
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Modifier le projet';
$active_menu = 'projects';

$pdo = getConnection();
$id = (int)($_GET['id'] ?? 0);

// Récupérer le projet
$stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->execute([$id]);
$project = $stmt->fetch();

if (!$project) {
    setFlash('danger', 'Projet introuvable.');
    header('Location: /projects/index.php');
    exit();
}

$errors = [];
$data = $project;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['project_code', 'name', 'description', 'client_name', 'location', 'start_date', 'end_date', 'status', 'budget'] as $key) {
        $data[$key] = trim($_POST[$key] ?? '');
    }

    // Validation
    if ($data['name'] === '') $errors['name'] = 'Le nom du projet est obligatoire.';
    if ($data['project_code'] === '') $errors['project_code'] = 'Le code projet est obligatoire.';
    if ($data['start_date'] && $data['end_date'] && $data['end_date'] < $data['start_date']) {
        $errors['end_date'] = 'La date de fin doit être après la date de début.';
    }
    if ($data['budget'] !== '' && !is_numeric($data['budget'])) {
        $errors['budget'] = 'Le budget doit être un nombre.';
    }
    if (!in_array($data['status'], ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled'])) {
        $data['status'] = 'planning';
    }

    // Vérifier l'unicité du code (sauf pour ce projet)
    if (empty($errors['project_code'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE project_code = ? AND id != ?");
        $stmt->execute([$data['project_code'], $id]);
        if ($stmt->fetchColumn() > 0) {
            $errors['project_code'] = 'Ce code projet est déjà utilisé.';
        }
    }

    // Mise à jour
    if (empty($errors)) {
        try {
            $sql = "UPDATE projects SET 
                    project_code = :code, name = :name, description = :description,
                    client_name = :client, location = :location,
                    start_date = :start, end_date = :end,
                    status = :status, budget = :budget
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':code' => $data['project_code'],
                ':name' => $data['name'],
                ':description' => $data['description'] ?: null,
                ':client' => $data['client_name'] ?: null,
                ':location' => $data['location'] ?: null,
                ':start' => $data['start_date'] ?: null,
                ':end' => $data['end_date'] ?: null,
                ':status' => $data['status'],
                ':budget' => $data['budget'] !== '' ? $data['budget'] : null,
                ':id' => $id,
            ]);
            
            logActivity($pdo, 'update', 'project', $id, 
                "Modification du projet : {$data['name']} ({$data['project_code']})");
            
            setFlash('success', 'Projet modifié avec succès.');
            header('Location: /projects/view.php?id=' . $id);
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
        <h1 class="page-title">Modifier le projet</h1>
        <p class="page-subtitle">
            <a href="/projects/view.php?id=<?= $id ?>">← Retour au projet</a>
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
                <label class="form-label required">Code projet</label>
                <input type="text" name="project_code" class="form-control"
                       value="<?= htmlspecialchars($data['project_code']) ?>" required>
                <?php if (isset($errors['project_code'])): ?>
                    <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['project_code']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Statut</label>
                <select name="status" class="form-control">
                    <option value="planning" <?= $data['status'] === 'planning' ? 'selected' : '' ?>>Planification</option>
                    <option value="in_progress" <?= $data['status'] === 'in_progress' ? 'selected' : '' ?>>En cours</option>
                    <option value="on_hold" <?= $data['status'] === 'on_hold' ? 'selected' : '' ?>>En pause</option>
                    <option value="completed" <?= $data['status'] === 'completed' ? 'selected' : '' ?>>Terminé</option>
                    <option value="cancelled" <?= $data['status'] === 'cancelled' ? 'selected' : '' ?>>Annulé</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label required">Nom du projet</label>
            <input type="text" name="name" class="form-control"
                   value="<?= htmlspecialchars($data['name']) ?>" required>
            <?php if (isset($errors['name'])): ?>
                <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['name']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Client</label>
                <input type="text" name="client_name" class="form-control"
                       value="<?= htmlspecialchars($data['client_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Lieu</label>
                <input type="text" name="location" class="form-control"
                       value="<?= htmlspecialchars($data['location'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Date de début</label>
                <input type="date" name="start_date" class="form-control"
                       value="<?= htmlspecialchars($data['start_date'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Date de fin</label>
                <input type="date" name="end_date" class="form-control"
                       value="<?= htmlspecialchars($data['end_date'] ?? '') ?>">
                <?php if (isset($errors['end_date'])): ?>
                    <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['end_date']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label">Budget (MAD)</label>
                <input type="number" step="0.01" name="budget" class="form-control"
                       value="<?= htmlspecialchars($data['budget'] ?? '') ?>">
                <?php if (isset($errors['budget'])): ?>
                    <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['budget']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
        </div>

        <div class="d-flex gap-1 mt-3">
            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            <a href="/projects/view.php?id=<?= $id ?>" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>