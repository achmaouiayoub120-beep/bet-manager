<?php
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Importer un document';
$active_menu = 'documents';

$pdo = getConnection();
$projects = $pdo->query("SELECT id, project_code, name FROM projects ORDER BY name ASC")->fetchAll();
$defaultProject = (int)($_GET['project_id'] ?? 0);

$errors = [];
$data = [
    'title' => '',
    'description' => '',
    'project_id' => $defaultProject,
    'document_type' => 'other',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($data as $key => $_) $data[$key] = trim($_POST[$key] ?? '');
    $data['project_id'] = (int)$data['project_id'];

    if ($data['title'] === '') $errors['title'] = 'Le titre est obligatoire.';
    if (!in_array($data['document_type'], ['specification', 'contract', 'certificate', 'photo', 'other'])) {
        $data['document_type'] = 'other';
    }

    // Vérifier le fichier
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errors['file'] = 'Veuillez sélectionner un fichier.';
    } else {
        $file = $_FILES['file'];
        $maxSize = 50 * 1024 * 1024;
        
        if ($file['size'] > $maxSize) {
            $errors['file'] = 'Le fichier dépasse 50 Mo.';
        }
        
        $allowedExt = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 
                       'jpg', 'jpeg', 'png', 'gif', 'svg', 'zip', 'rar', 'txt', 'csv', 'dwg', 'dxf'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) {
            $errors['file'] = 'Extension non autorisée.';
        }
    }

    // Insertion
    if (empty($errors)) {
        try {
            $file = $_FILES['file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            $storedName = 'doc_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $uploadDir = __DIR__ . '/../uploads/documents/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $targetPath = $uploadDir . $storedName;
            $relativePath = 'uploads/documents/' . $storedName;
            
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception('Impossible de déplacer le fichier.');
            }
            
            $sql = "INSERT INTO documents 
                    (title, description, project_id, document_type, file_path, file_name, file_size, uploaded_by)
                    VALUES (:title, :desc, :project, :type, :fpath, :fname, :fsize, :user)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title' => $data['title'],
                ':desc' => $data['description'] ?: null,
                ':project' => $data['project_id'] > 0 ? $data['project_id'] : null,
                ':type' => $data['document_type'],
                ':fpath' => $relativePath,
                ':fname' => $file['name'],
                ':fsize' => $file['size'],
                ':user' => $_SESSION['user_id'],
            ]);
            
            $newId = $pdo->lastInsertId();
            
            logActivity($pdo, 'create', 'document', $newId, 
                "Import du document : {$data['title']} ({$file['name']})");
            
            setFlash('success', 'Document importé avec succès.');
            header('Location: /documents/index.php');
            exit();
            
        } catch (Exception $e) {
            $errors['general'] = 'Erreur : ' . $e->getMessage();
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Importer un document</h1>
        <p class="page-subtitle">
            <a href="/documents/index.php">← Retour aux documents</a>
        </p>
    </div>
</div>

<?php if (!empty($errors['general'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label class="form-label required">Titre du document</label>
            <input type="text" name="title" class="form-control" 
                   value="<?= htmlspecialchars($data['title']) ?>"
                   placeholder="Ex: Contrat de maîtrise d'œuvre" required>
            <?php if (isset($errors['title'])): ?>
                <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['title']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-row">
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

            <div class="form-group">
                <label class="form-label">Type de document</label>
                <select name="document_type" class="form-control">
                    <option value="specification" <?= $data['document_type'] === 'specification' ? 'selected' : '' ?>>📋 Spécification</option>
                    <option value="contract" <?= $data['document_type'] === 'contract' ? 'selected' : '' ?>>📜 Contrat</option>
                    <option value="certificate" <?= $data['document_type'] === 'certificate' ? 'selected' : '' ?>>🎓 Certificat</option>
                    <option value="photo" <?= $data['document_type'] === 'photo' ? 'selected' : '' ?>>🖼️ Photo</option>
                    <option value="other" <?= $data['document_type'] === 'other' ? 'selected' : '' ?>>📎 Autre</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label required">Fichier</label>
            <label class="file-upload-zone" id="uploadZone" for="fileInput">
                <input type="file" id="fileInput" name="file" 
                       accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.odp,.jpg,.jpeg,.png,.gif,.svg,.zip,.rar,.txt,.csv,.dwg,.dxf">
                <div class="file-upload-icon">📁</div>
                <div class="file-upload-text">Cliquez pour sélectionner un fichier</div>
                <div class="file-upload-text" style="font-size:0.8rem;margin-top:0.5rem;">
                    PDF, Word, Excel, PowerPoint, ODF, images, ZIP, DWG, DXF... (max 50 Mo)
                </div>
                <div class="file-upload-filename" id="fileName" style="display:none;"></div>
            </label>
            <?php if (isset($errors['file'])): ?>
                <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['file']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" 
                      placeholder="Description optionnelle du document..."><?= htmlspecialchars($data['description']) ?></textarea>
        </div>

        <div class="d-flex gap-1 mt-3">
            <button type="submit" class="btn btn-primary">Importer</button>
            <a href="/documents/index.php" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<script>
document.getElementById('fileInput').addEventListener('change', function() {
    const zone = document.getElementById('uploadZone');
    const fileName = document.getElementById('fileName');
    
    if (this.files && this.files[0]) {
        const f = this.files[0];
        zone.classList.add('has-file');
        fileName.textContent = '✓ ' + f.name + ' (' + (f.size / 1024 / 1024).toFixed(2) + ' Mo)';
        fileName.style.display = 'block';
    } else {
        zone.classList.remove('has-file');
        fileName.style.display = 'none';
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>