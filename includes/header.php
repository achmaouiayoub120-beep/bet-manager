<?php
// En-tête commun - à inclure après auth_check.php
// Variables attendues : $page_title (string), $active_menu (string)
$page_title = $page_title ?? 'BET Manager';
$active_menu = $active_menu ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - BET Manager</title>
<link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <!-- En-tête -->
    <header class="header">
        <div class="container-fluid header-content">
            <a href="/dashboard/index.php" class="logo">
    <img src="/assets/img/logo-icon.svg" alt="BET Manager" style="width:42px;height:42px;">
    <span>BET Manager</span>
</a>
            <div class="header-user">
                <span><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></span>
                <span class="badge badge-info"><?= htmlspecialchars(translateRole($_SESSION['user_role'] ?? '')) ?></span>
                <a href="/auth/logout.php" class="btn btn-secondary btn-sm">Déconnexion</a>
            </div>
        </div>
    </header>

    <!-- Layout principal -->
    <div class="layout">
        <!-- Barre latérale -->
        <aside class="sidebar">
            <ul class="sidebar-nav">
                <li class="sidebar-section-title">Principal</li>
                <li>
                    <a href="/dashboard/index.php" class="<?= $active_menu === 'dashboard' ? 'active' : '' ?>">
                        📊 Tableau de bord
                    </a>
                </li>
                <li>
                    <a href="/projects/index.php" class="<?= $active_menu === 'projects' ? 'active' : '' ?>">
                        🏗️ Projets
                    </a>
                </li>
                <li>
                    <a href="/plans/index.php" class="<?= $active_menu === 'plans' ? 'active' : '' ?>">
                        📐 Plans
                    </a>
                </li>
                <li>
                    <a href="/reports/index.php" class="<?= $active_menu === 'reports' ? 'active' : '' ?>">
                        📄 Rapports
                    </a>
                </li>
                <li>
                    <a href="/documents/index.php" class="<?= $active_menu === 'documents' ? 'active' : '' ?>">
                        📁 Documents
                    </a>
                </li>

                <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                    <li class="sidebar-section-title">Administration</li>
                    <li>
                        <a href="/users/index.php" class="<?= $active_menu === 'users' ? 'active' : '' ?>">
                            👥 Utilisateurs
                        </a>
                    </li>
                    <li>
                        <a href="/logs/index.php" class="<?= $active_menu === 'logs' ? 'active' : '' ?>">
                            📜 Historique
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </aside>

        <!-- Contenu principal -->
        <main class="main-content">
            <?php showFlashMessage(); ?>