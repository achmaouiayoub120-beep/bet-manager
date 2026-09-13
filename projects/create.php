<?php
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Nouveau projet';
$active_menu = 'projects';

$pdo = getConnection();
$errors = [];
$data = [
    'project_code' => '',
    'name' => '',
    'description' => '',
    'client_name' => '',
    'location' => '',
    'start_date' => '',
    'end_date' => '',
    'status' => 'planning',
    'budget' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération
    foreach ($data as $key => $_) {
        $data[$key] = trim($_POST[$key] ?? '');
    }

    // Validation
    if ($data['name'] === '') {
        $errors['name'] = 'Le nom du projet est obligatoire.';
    }
    if ($data['project_code'] === '') {
        $errors['project_code'] = 'Le code projet est obligatoire.';
    }
    if ($data['start_date'] && $data['end_date'] && $data['end_date'] < $data['start_date']) {
        $errors['end_date'] = 'La date de fin doit être après la date de début.';
    }
    if ($data['budget'] !== '' && !is_numeric($data['budget'])) {
        $errors['budget'] = 'Le budget doit être un nombre.';
    }
    if (!in_array($data['status'], ['planning', 'in_progress', 'on_hold', 'completed', 'cancelled'])) {
        $data['status'] = 'planning';
    }

    // Vérifier l'unicité du code projet
    if (empty($errors['project_code'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE project_code = ?");
        $stmt->execute([$data['project_code']]);
        if ($stmt->fetchColumn() > 0) {
            $errors['project_code'] = 'Ce code projet existe déjà.';
        }
    }

    // Insertion
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO projects 
                    (project_code, name, description, client_name, location, start_date, end_date, status, budget, created_by) 
                    VALUES (:code, :name, :description, :client, :location, :start, :end, :status, :budget, :created_by)";
            
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
                ':created_by' => $_SESSION['user_id'],
            ]);
            
            $newId = $pdo->lastInsertId();
            
            logActivity($pdo, 'create', 'project', $newId, 
                "Création du projet : {$data['name']} ({$data['project_code']})");
            
            setFlash('success', 'Projet créé avec succès.');
            header('Location: /projects/view.php?id=' . $newId);
            exit();
            
        } catch (PDOException $e) {
            $errors['general'] = 'Erreur lors de l\'enregistrement : ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Nouveau projet</h1>
        <p class="page-subtitle">
            <a href="/projects/index.php">← Retour à la liste</a>
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
                <label class="form-label required" for="project_code">Code projet</label>
                <input type="text" id="project_code" name="project_code" 
                       class="form-control <?= isset($errors['project_code']) ? 'is-invalid' : '' ?>"
                       value="<?= htmlspecialchars($data['project_code']) ?>"
                       placeholder="Ex: PRJ-2024-004" required>
                <?php if (isset($errors['project_code'])): ?>
                    <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['project_code']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Statut</label>
                <select id="status" name="status" class="form-control">
                    <option value="planning" <?= $data['status'] === 'planning' ? 'selected' : '' ?>>Planification</option>
                    <option value="in_progress" <?= $data['status'] === 'in_progress' ? 'selected' : '' ?>>En cours</option>
                    <option value="on_hold" <?= $data['status'] === 'on_hold' ? 'selected' : '' ?>>En pause</option>
                    <option value="completed" <?= $data['status'] === 'completed' ? 'selected' : '' ?>>Terminé</option>
                    <option value="cancelled" <?= $data['status'] === 'cancelled' ? 'selected' : '' ?>>Annulé</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label required" for="name">Nom du projet</label>
            <input type="text" id="name" name="name" 
                   class="form-control"
                   value="<?= htmlspecialchars($data['name']) ?>"
                   placeholder="Ex: Tour résidentielle Al Madina" required>
            <?php if (isset($errors['name'])): ?>
                <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['name']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="client_name">Client</label>
                <input type="text" id="client_name" name="client_name" 
                       class="form-control"
                       value="<?= htmlspecialchars($data['client_name']) ?>"
                       placeholder="Nom du client">
            </div>

            <div class="form-group">
                <label class="form-label" for="location">Lieu</label>
                <input type="text" id="location" name="location" 
                       class="form-control"
                       value="<?= htmlspecialchars($data['location']) ?>"
                       placeholder="Ville, pays">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="start_date">Date de début</label>
                <input type="date" id="start_date" name="start_date" 
                       class="form-control"
                       value="<?= htmlspecialchars($data['start_date']) ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="end_date">Date de fin</label>
                <input type="date" id="end_date" name="end_date" 
                       class="form-control"
                       value="<?= htmlspecialchars($data['end_date']) ?>">
                <?php if (isset($errors['end_date'])): ?>
                    <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['end_date']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="budget">Budget (MAD)</label>
                <input type="number" step="0.01" id="budget" name="budget" 
                       class="form-control"
                       value="<?= htmlspecialchars($data['budget']) ?>"
                       placeholder="0.00">
                <?php if (isset($errors['budget'])): ?>
                    <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['budget']) ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <textarea id="description" name="description" class="form-control" 
                      placeholder="Description détaillée du projet..."><?= htmlspecialchars($data['description']) ?></textarea>
        </div>

        <div class="d-flex gap-1 mt-3">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="/projects/index.php" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>