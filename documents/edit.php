<?php
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Modifier le document';
$active_menu = 'documents';

$pdo = getConnection();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM documents WHERE id = ?");
$stmt->execute([$id]);
$document = $stmt->fetch();

if (!$document) {
    setFlash('danger', 'Document introuvable.');
    header('Location: /documents/index.php');
    exit();
}

$projects = $pdo->query("SELECT id, project_code, name FROM projects ORDER BY name ASC")->fetchAll();
$errors = [];
$data = $document;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['title', 'description', 'project_id', 'document_type'] as $key) {
        $data[$key] = trim($_POST[$key] ?? '');
    }
    $data['project_id'] = (int)$data['project_id'];

    if ($data['title'] === '') $errors['title'] = 'Le titre est obligatoire.';
    if (!in_array($data['document_type'], ['specification', 'contract', 'certificate', 'photo', 'other'])) {
        $data['document_type'] = 'other';
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE documents SET 
                    title=:title, description=:desc, project_id=:project, document_type=:type
                    WHERE id=:id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title' => $data['title'],
                ':desc' => $data['description'] ?: null,
                ':project' => $data['project_id'] > 0 ? $data['project_id'] : null,
                ':type' => $data['document_type'],
                ':id' => $id,
            ]);
            
            logActivity($pdo, 'update', 'document', $id, "Modification du document : {$data['title']}");
            setFlash('success', 'Document modifié.');
            header('Location: /documents/index.php');
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
        <h1 class="page-title">Modifier le document</h1>
        <p class="page-subtitle">
            <a href="/documents/index.php">← Retour aux documents</a>
        </p>
    </div>
</div>

<?php if (!empty($errors['general'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label required">Titre</label>
            <input type="text" name="title" class="form-control" 
                   value="<?= htmlspecialchars($data['title']) ?>" required>
            <?php if (isset($errors['title'])): ?>
                <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['title']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Projet</label>
                <select name="project_id" class="form-control">
                    <option value="0">— Aucun —</option>
                    <?php foreach ($projects as $pr): ?>
                        <option value="<?= $pr['id'] ?>" <?= $data['project_id'] == $pr['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pr['project_code'] . ' - ' . $pr['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Type</label>
                <select name="document_type" class="form-control">
                    <option value="specification" <?= $data['document_type'] === 'specification' ? 'selected' : '' ?>>Spécification</option>
                    <option value="contract" <?= $data['document_type'] === 'contract' ? 'selected' : '' ?>>Contrat</option>
                    <option value="certificate" <?= $data['document_type'] === 'certificate' ? 'selected' : '' ?>>Certificat</option>
                    <option value="photo" <?= $data['document_type'] === 'photo' ? 'selected' : '' ?>>Photo</option>
                    <option value="other" <?= $data['document_type'] === 'other' ? 'selected' : '' ?>>Autre</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
        </div>

        <div class="alert alert-info">
            <strong>Fichier actuel :</strong> <?= htmlspecialchars($document['file_name']) ?> 
            (<?= formatFileSize($document['file_size']) ?>)
            <br>
            <small>Le fichier ne peut pas être remplacé. Pour mettre à jour le fichier, supprimez ce document et importez-en un nouveau.</small>
        </div>

        <div class="d-flex gap-1 mt-3">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="/documents/index.php" class="btn btn-secondary">Annuler</a>
            <?php if (in_array($_SESSION['user_role'], ['admin', 'engineer'])): ?>
                <button type="button" class="btn btn-danger" style="margin-left:auto;" 
                        onclick="if(confirm('Supprimer ce document ? Cette action est irréversible.')) { document.getElementById('deleteForm').submit(); }">
                    Supprimer le document
                </button>
            <?php endif; ?>
        </div>
    </form>

    <?php if (in_array($_SESSION['user_role'], ['admin', 'engineer'])): ?>
        <form id="deleteForm" method="POST" action="/documents/delete.php" style="display:none;">
            <input type="hidden" name="id" value="<?= $id ?>">
        </form>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>