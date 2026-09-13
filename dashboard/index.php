<?php
require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Tableau de bord';
$active_menu = 'dashboard';

$pdo = getConnection();

// === Statistiques générales ===
$stats = [
    'projects' => $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn(),
    'projects_active' => $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'in_progress'")->fetchColumn(),
    'plans' => $pdo->query("SELECT COUNT(*) FROM plans")->fetchColumn(),
    'reports' => $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn(),
    'documents' => $pdo->query("SELECT COUNT(*) FROM documents")->fetchColumn(),
    'users' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = TRUE")->fetchColumn(),
];

// === Données pour graphiques ===

// 1. Projets par statut
$projectsByStatus = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM projects 
    GROUP BY status
")->fetchAll();

// 2. Plans par type
$plansByType = $pdo->query("
    SELECT plan_type, COUNT(*) as count 
    FROM plans 
    GROUP BY plan_type
")->fetchAll();

// 3. Activité des 30 derniers jours
$activityData = $pdo->query("
    SELECT DATE(created_at) as day, COUNT(*) as count 
    FROM activity_logs 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day ASC
")->fetchAll();

// 4. Documents par type
$docsByType = $pdo->query("
    SELECT document_type, COUNT(*) as count 
    FROM documents 
    GROUP BY document_type
")->fetchAll();

// === Derniers projets ===
$recentProjects = $pdo->query("
    SELECT id, project_code, name, client_name, status, created_at 
    FROM projects 
    ORDER BY created_at DESC 
    LIMIT 5
")->fetchAll();

// === Activité récente ===
$recentActivity = $pdo->query("
    SELECT l.*, u.full_name AS user_name 
    FROM activity_logs l
    LEFT JOIN users u ON u.id = l.user_id
    ORDER BY l.created_at DESC 
    LIMIT 8
")->fetchAll();

// === Préparation des données pour Chart.js ===

// Traductions
function translateStatusForChart($s) {
    $labels = [
        'planning' => 'Planification',
        'in_progress' => 'En cours',
        'on_hold' => 'En pause',
        'completed' => 'Terminé',
        'cancelled' => 'Annulé',
    ];
    return $labels[$s] ?? ucfirst($s);
}

function translatePlanTypeForChart($t) {
    $labels = [
        'architectural' => 'Architectural',
        'structural' => 'Structurel',
        'electrical' => 'Électrique',
        'plumbing' => 'Plomberie',
        'other' => 'Autre',
    ];
    return $labels[$t] ?? ucfirst($t);
}

function translateDocTypeForChart($t) {
    $labels = [
        'specification' => 'Spécification',
        'contract' => 'Contrat',
        'certificate' => 'Certificat',
        'photo' => 'Photo',
        'other' => 'Autre',
    ];
    return $labels[$t] ?? ucfirst($t);
}

// Projets par statut
$projectStatusLabels = [];
$projectStatusValues = [];
$projectStatusColors = [];
$statusColorMap = [
    'planning' => '#3498db',
    'in_progress' => '#1a2a3a',
    'on_hold' => '#f39c12',
    'completed' => '#27ae60',
    'cancelled' => '#e74c3c',
];
foreach ($projectsByStatus as $row) {
    $projectStatusLabels[] = translateStatusForChart($row['status']);
    $projectStatusValues[] = (int)$row['count'];
    $projectStatusColors[] = $statusColorMap[$row['status']] ?? '#95a5a6';
}

// Plans par type
$planTypeLabels = [];
$planTypeValues = [];
foreach ($plansByType as $row) {
    $planTypeLabels[] = translatePlanTypeForChart($row['plan_type']);
    $planTypeValues[] = (int)$row['count'];
}

// Activité 30 jours - on remplit les jours manquants
$activityLabels = [];
$activityValues = [];
$activityMap = [];
foreach ($activityData as $row) {
    $activityMap[$row['day']] = (int)$row['count'];
}
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $activityLabels[] = date('d/m', strtotime($date));
    $activityValues[] = $activityMap[$date] ?? 0;
}

// Documents par type
$docTypeLabels = [];
$docTypeValues = [];
$docTypeColors = ['#3498db', '#1a2a3a', '#27ae60', '#f39c12', '#95a5a6'];
foreach ($docsByType as $idx => $row) {
    $docTypeLabels[] = translateDocTypeForChart($row['document_type']);
    $docTypeValues[] = (int)$row['count'];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Tableau de bord</h1>
        <p class="page-subtitle">Bienvenue, <?= htmlspecialchars($_SESSION['user_name']) ?> · <?= date('d/m/Y') ?></p>
    </div>
</div>

<!-- Statistiques principales -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-label">Projets totaux</div>
        <div class="stat-value"><?= $stats['projects'] ?></div>
        <div class="stat-description"><?= $stats['projects_active'] ?> en cours</div>
    </div>
    <div class="stat-card success">
        <div class="stat-label">Plans</div>
        <div class="stat-value"><?= $stats['plans'] ?></div>
        <div class="stat-description">Tous projets confondus</div>
    </div>
    <div class="stat-card warning">
        <div class="stat-label">Rapports</div>
        <div class="stat-value"><?= $stats['reports'] ?></div>
        <div class="stat-description">Tous types</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Documents</div>
        <div class="stat-value"><?= $stats['documents'] ?></div>
        <div class="stat-description">Fichiers importés</div>
    </div>
</div>

<!-- Graphiques -->
<div class="charts-grid">
    <!-- Graphique 1 : Projets par statut -->
    <div class="chart-card">
        <div class="chart-card-header">
            <h3 class="chart-card-title">🏗️ Répartition des projets par statut</h3>
        </div>
        <div class="chart-container-doughnut">
            <?php if (empty($projectStatusValues)): ?>
                <div class="empty-state" style="padding:2rem 0;">
                    <p class="text-muted">Aucun projet</p>
                </div>
            <?php else: ?>
                <canvas id="chartProjectsStatus"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <!-- Graphique 2 : Documents par type -->
    <div class="chart-card">
        <div class="chart-card-header">
            <h3 class="chart-card-title">📁 Répartition des documents</h3>
        </div>
        <div class="chart-container-doughnut">
            <?php if (empty($docTypeValues)): ?>
                <div class="empty-state" style="padding:2rem 0;">
                    <p class="text-muted">Aucun document</p>
                </div>
            <?php else: ?>
                <canvas id="chartDocsType"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Graphique pleine largeur : Activité 30 jours -->
<div class="chart-card" style="margin-bottom:1.5rem;">
    <div class="chart-card-header">
        <h3 class="chart-card-title">📈 Activité des 30 derniers jours</h3>
    </div>
    <div class="chart-container">
        <canvas id="chartActivity"></canvas>
    </div>
</div>

<!-- Graphique pleine largeur : Plans par type -->
<div class="chart-card" style="margin-bottom:1.5rem;">
    <div class="chart-card-header">
        <h3 class="chart-card-title">📐 Plans par type</h3>
    </div>
    <div class="chart-container">
        <?php if (empty($planTypeValues)): ?>
            <div class="empty-state" style="padding:2rem 0;">
                <p class="text-muted">Aucun plan</p>
            </div>
        <?php else: ?>
            <canvas id="chartPlansType"></canvas>
        <?php endif; ?>
    </div>
</div>

<!-- Derniers projets + Activité récente -->
<div class="charts-grid">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Derniers projets</h3>
            <a href="/projects/index.php" class="btn btn-secondary btn-sm">Voir tout</a>
        </div>
        <?php if (empty($recentProjects)): ?>
            <div class="empty-state" style="padding:2rem 1rem;">
                <p class="text-muted">Aucun projet.</p>
            </div>
        <?php else: ?>
            <ul style="list-style:none;padding:0;">
                <?php foreach ($recentProjects as $p): ?>
                    <li style="padding:0.75rem 0;border-bottom:1px solid var(--border-light);">
                        <a href="/projects/view.php?id=<?= $p['id'] ?>" style="font-weight:600;">
                            <?= htmlspecialchars($p['name']) ?>
                        </a>
                        <div class="text-muted" style="font-size:0.85rem;margin-top:0.25rem;">
                            <?= htmlspecialchars($p['project_code']) ?>
                            <?php if ($p['client_name']): ?> · <?= htmlspecialchars($p['client_name']) ?><?php endif; ?>
                            · <span class="badge <?= statusBadgeClass($p['status']) ?>"><?= translateStatus($p['status']) ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Activité récente</h3>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="/logs/index.php" class="btn btn-secondary btn-sm">Voir tout</a>
            <?php endif; ?>
        </div>
        <?php if (empty($recentActivity)): ?>
            <div class="empty-state" style="padding:2rem 1rem;">
                <p class="text-muted">Aucune activité.</p>
            </div>
        <?php else: ?>
            <ul style="list-style:none;padding:0;">
                <?php foreach ($recentActivity as $log): ?>
                    <li style="padding:0.6rem 0;border-bottom:1px solid var(--border-light);">
                        <div style="font-size:0.9rem;">
                            <strong><?= htmlspecialchars($log['user_name'] ?? 'Système') ?></strong>
                            <span class="text-muted">· <?= date('d/m H:i', strtotime($log['created_at'])) ?></span>
                        </div>
                        <div class="text-muted" style="font-size:0.85rem;margin-top:0.2rem;">
                            <?= htmlspecialchars($log['description'] ?? '') ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Configuration globale
Chart.defaults.font.family = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
Chart.defaults.color = '#6c757d';

// Palette de couleurs
const colors = {
    navy: '#1a2a3a',
    navyLight: '#2c3e50',
    accent: '#3498db',
    success: '#27ae60',
    warning: '#f39c12',
    danger: '#e74c3c',
    info: '#3498db',
    secondary: '#95a5a6',
    ivory: '#f5f5f0',
    white: '#ffffff'
};

<?php if (!empty($projectStatusValues)): ?>
// === Graphique 1 : Projets par statut (Doughnut) ===
new Chart(document.getElementById('chartProjectsStatus'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($projectStatusLabels) ?>,
        datasets: [{
            data: <?= json_encode($projectStatusValues) ?>,
            backgroundColor: <?= json_encode($projectStatusColors) ?>,
            borderWidth: 2,
            borderColor: colors.white
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: { size: 12 },
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            }
        },
        cutout: '60%'
    }
});
<?php endif; ?>

<?php if (!empty($docTypeValues)): ?>
// === Graphique 2 : Documents par type (Doughnut) ===
new Chart(document.getElementById('chartDocsType'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($docTypeLabels) ?>,
        datasets: [{
            data: <?= json_encode($docTypeValues) ?>,
            backgroundColor: <?= json_encode(array_slice($docTypeColors, 0, count($docTypeValues))) ?>,
            borderWidth: 2,
            borderColor: colors.white
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: { size: 12 },
                    usePointStyle: true,
                    pointStyle: 'circle'
                }
            }
        },
        cutout: '60%'
    }
});
<?php endif; ?>

// === Graphique 3 : Activité 30 jours (Line) ===
new Chart(document.getElementById('chartActivity'), {
    type: 'line',
    data: {
        labels: <?= json_encode($activityLabels) ?>,
        datasets: [{
            label: 'Actions',
            data: <?= json_encode($activityValues) ?>,
            borderColor: colors.navy,
            backgroundColor: 'rgba(26, 42, 58, 0.08)',
            borderWidth: 2,
            fill: true,
            tension: 0.35,
            pointRadius: 3,
            pointHoverRadius: 6,
            pointBackgroundColor: colors.navy,
            pointBorderColor: colors.white,
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: colors.navy,
                titleColor: colors.white,
                bodyColor: colors.white,
                padding: 10,
                cornerRadius: 6,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        return context.parsed.y + ' action' + (context.parsed.y > 1 ? 's' : '');
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    precision: 0
                },
                grid: {
                    color: 'rgba(224, 224, 224, 0.5)'
                }
            },
            x: {
                grid: { display: false },
                ticks: {
                    maxRotation: 0,
                    autoSkip: true,
                    maxTicksLimit: 10
                }
            }
        }
    }
});

<?php if (!empty($planTypeValues)): ?>
// === Graphique 4 : Plans par type (Bar) ===
new Chart(document.getElementById('chartPlansType'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($planTypeLabels) ?>,
        datasets: [{
            label: 'Nombre de plans',
            data: <?= json_encode($planTypeValues) ?>,
            backgroundColor: colors.accent,
            borderRadius: 6,
            borderSkipped: false,
            maxBarThickness: 60
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: colors.navy,
                titleColor: colors.white,
                bodyColor: colors.white,
                padding: 10,
                cornerRadius: 6,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        return context.parsed.y + ' plan' + (context.parsed.y > 1 ? 's' : '');
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1,
                    precision: 0
                },
                grid: {
                    color: 'rgba(224, 224, 224, 0.5)'
                }
            },
            x: {
                grid: { display: false }
            }
        }
    }
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>