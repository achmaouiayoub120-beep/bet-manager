<?php
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Nouvelle version';
$active_menu = 'plans';

$pdo = getConnection();
$planId = (int)($_GET['plan_id'] ?? $_POST['plan_id'] ?? 0);

$stmt = $pdo->prepare("SELECT id, plan_code, title FROM plans WHERE id = ?");
$stmt->execute([$planId]);
$plan = $stmt->fetch();

if (!$plan) {
    setFlash('danger', 'Plan introuvable.');
    header('Location: /plans/index.php');
    exit();
}

// Déterminer le numéro de version proposé
$stmt = $pdo->prepare("SELECT version_number FROM plan_versions WHERE plan_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$planId]);
$lastVersion = $stmt->fetchColumn();

// Suggérer la version suivante (ex: 1.0 -> 1.1, 1.1 -> 1.2, ou V1 -> V2)
$suggestedVersion = '1.0';
if ($lastVersion) {
    if (preg_match('/^(\d+)\.(\d+)$/', $lastVersion, $m)) {
        $suggestedVersion = $m[1] . '.' . ((int)$m[2] + 1);
    } elseif (preg_match('/^V(\d+)$/i', $lastVersion, $m)) {
        $suggestedVersion = 'V' . ((int)$m[1] + 1);
    } else {
        $suggestedVersion = $lastVersion . '-2';
    }
}

$errors = [];
$data = [
    'version_number' => $suggestedVersion,
    'change_description' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['version_number'] = trim($_POST['version_number'] ?? '');
    $data['change_description'] = trim($_POST['change_description'] ?? '');
    
    // Validation
    if ($data['version_number'] === '') {
        $errors['version_number'] = 'Le numéro de version est obligatoire.';
    }
    
    // Vérifier unicité
    if (empty($errors['version_number'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM plan_versions WHERE plan_id = ? AND version_number = ?");
        $stmt->execute([$planId, $data['version_number']]);
        if ($stmt->fetchColumn() > 0) {
            $errors['version_number'] = 'Cette version existe déjà pour ce plan.';
        }
    }
    
    // Vérifier le fichier
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errors['file'] = 'Veuillez sélectionner un fichier à uploader.';
    } else {
        $file = $_FILES['file'];
        
        // Taille max 50 Mo
        $maxSize = 50 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $errors['file'] = 'Le fichier dépasse la taille maximale de 50 Mo.';
        }
        
        // Extensions autorisées (plans techniques)
        $allowedExt = ['pdf', 'dwg', 'dxf', 'jpg', 'jpeg', 'png', 'svg', 'zip'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) {
            $errors['file'] = 'Extension non autorisée. Autorisées : ' . implode(', ', $allowedExt);
        }
    }
    
    // Insertion
    if (empty($errors)) {
        try {
            $file = $_FILES['file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Nom de fichier unique
            $storedName = 'plan_' . $planId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            
            $uploadDir = __DIR__ . '/../uploads/plans/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $targetPath = $uploadDir . $storedName;
            $relativePath = 'uploads/plans/' . $storedName;
            
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception('Impossible de déplacer le fichier uploadé.');
            }
            
            $sql = "INSERT INTO plan_versions 
                    (plan_id, version_number, file_path, file_name, file_size, change_description, created_by)
                    VALUES (:plan_id, :vnum, :fpath, :fname, :fsize, :desc, :user)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':plan_id' => $planId,
                ':vnum' => $data['version_number'],
                ':fpath' => $relativePath,
                ':fname' => $file['name'],
                ':fsize' => $file['size'],
                ':desc' => $data['change_description'] ?: null,
                ':user' => $_SESSION['user_id'],
            ]);
            
            $newId = $pdo->lastInsertId();
            
            logActivity($pdo, 'create', 'plan_version', $newId, 
                "Ajout de la version {$data['version_number']} pour le plan {$plan['plan_code']}");
            
            setFlash('success', 'Version ' . htmlspecialchars($data['version_number']) . ' importée avec succès.');
            header('Location: /plans/view.php?id=' . $planId);
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
        <h1 class="page-title">Nouvelle version</h1>
        <p class="page-subtitle">
            <a href="/plans/view.php?id=<?= $planId ?>">← Retour au plan</a>
            · Plan : <strong><?= htmlspecialchars($plan['plan_code']) ?> - <?= htmlspecialchars($plan['title']) ?></strong>
        </p>
    </div>
</div>

<?php if (!empty($errors['general'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errors['general']) ?></div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="plan_id" value="<?= $planId ?>">

        <div class="form-group">
            <label class="form-label required">Numéro de version</label>
            <input type="text" name="version_number" class="form-control"
                   value="<?= htmlspecialchars($data['version_number']) ?>"
                   placeholder="Ex: 1.0, 1.1, V2" required>
            <div class="form-help">Format suggéré : 1.0, 1.1, 2.0... (dernière version : <?= htmlspecialchars($lastVersion ?: 'aucune') ?>)</div>
            <?php if (isset($errors['version_number'])): ?>
                <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['version_number']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label required">Fichier</label>
            <label class="file-upload-zone" id="uploadZone" for="fileInput">
                <input type="file" id="fileInput" name="file" accept=".pdf,.dwg,.dxf,.jpg,.jpeg,.png,.svg,.zip">
                <div class="file-upload-icon">📎</div>
                <div class="file-upload-text">Cliquez pour sélectionner un fichier</div>
                <div class="file-upload-text" style="font-size:0.8rem;margin-top:0.5rem;">
                    Formats acceptés : PDF, DWG, DXF, JPG, PNG, SVG, ZIP (max 50 Mo)
                </div>
                <div class="file-upload-filename" id="fileName" style="display:none;"></div>
            </label>
            <?php if (isset($errors['file'])): ?>
                <div class="form-help" style="color:var(--danger)"><?= htmlspecialchars($errors['file']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="form-label">Description des modifications</label>
            <textarea name="change_description" class="form-control"
                      placeholder="Décrivez les changements apportés dans cette version..."><?= htmlspecialchars($data['change_description']) ?></textarea>
        </div>

        <div class="d-flex gap-1 mt-3">
            <button type="submit" class="btn btn-primary">Importer la version</button>
            <a href="/plans/view.php?id=<?= $planId ?>" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>

<script>
document.getElementById('fileInput').addEventListener('change', function(e) {
    const zone = document.getElementById('uploadZone');
    const fileName = document.getElementById('fileName');
    
    if (this.files && this.files[0]) {
        zone.classList.add('has-file');
        fileName.textContent = '✓ ' + this.files[0].name + ' (' + (this.files[0].size / 1024 / 1024).toFixed(2) + ' Mo)';
        fileName.style.display = 'block';
    } else {
        zone.classList.remove('has-file');
        fileName.style.display = 'none';
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>