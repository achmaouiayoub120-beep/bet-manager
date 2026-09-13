<?php
// Fonctions utilitaires communes à toute l'application

// Journaliser une action dans la base
function logActivity($pdo, $actionType, $entityType, $entityId, $description) {
    try {
        $userId = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        $sql = "INSERT INTO activity_logs (user_id, action_type, entity_type, entity_id, description, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId, $actionType, $entityType, $entityId, $description, $ip]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Afficher un message d'alerte depuis la session
function showFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        $type = $flash['type'] ?? 'info';
        $message = $flash['message'] ?? '';
        echo "<div class='alert alert-{$type}'>{$message}</div>";
        unset($_SESSION['flash']);
    }
}

// Définir un message flash
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

// Formater une date en français
function formatDateFr($date) {
    if (!$date || $date === '0000-00-00') return '—';
    return date('d/m/Y', strtotime($date));
}

// Formater la taille d'un fichier
function formatFileSize($bytes) {
    if (!$bytes) return '—';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' Mo';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' Ko';
    return $bytes . ' octets';
}

// Traduire un statut de projet
function translateStatus($status) {
    $labels = [
        'planning' => 'Planification',
        'in_progress' => 'En cours',
        'on_hold' => 'En pause',
        'completed' => 'Terminé',
        'cancelled' => 'Annulé',
        'draft' => 'Brouillon',
        'review' => 'En révision',
        'approved' => 'Approuvé',
        'rejected' => 'Rejeté',
        'obsolete' => 'Obsolète',
        'submitted' => 'Soumis',
    ];
    return $labels[$status] ?? ucfirst($status);
}

// Retourner la classe CSS du badge selon le statut
function statusBadgeClass($status) {
    $classes = [
        'planning' => 'badge-info',
        'in_progress' => 'badge-primary',
        'on_hold' => 'badge-warning',
        'completed' => 'badge-success',
        'cancelled' => 'badge-danger',
        'draft' => 'badge-secondary',
        'review' => 'badge-warning',
        'approved' => 'badge-success',
        'rejected' => 'badge-danger',
        'obsolete' => 'badge-dark',
        'submitted' => 'badge-info',
    ];
    return $classes[$status] ?? 'badge-secondary';
}

// Traduire un rôle
function translateRole($role) {
    $labels = [
        'admin' => 'Administrateur',
        'engineer' => 'Ingénieur',
        'technician' => 'Technicien',
        'viewer' => 'Consultant',
    ];
    return $labels[$role] ?? ucfirst($role);
}
?>