<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

startSession();

// Si déjà connecté, redirection vers le dashboard
if (isLoggedIn()) {
    header('Location: /dashboard/index.php');
    exit();
}

$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username === '' || $password === '') {
        $error = 'Veuillez renseigner votre nom d\'utilisateur et votre mot de passe.';
    } else {
        try {
            $pdo = getConnection();
            $stmt = $pdo->prepare("SELECT id, username, password_hash, full_name, role, is_active 
                                   FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = 'Identifiants incorrects.';
            } elseif (!$user['is_active']) {
                $error = 'Ce compte est désactivé. Contactez un administrateur.';
            } elseif (!password_verify($password, $user['password_hash'])) {
                $error = 'Identifiants incorrects.';
            } else {
                // Connexion réussie
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                
                // Journaliser la connexion
                logActivity($pdo, 'login', 'user', $user['id'], 'Connexion au système');
                
                header('Location: /dashboard/index.php');
                exit();
            }
        } catch (PDOException $e) {
            $error = 'Erreur système. Veuillez réessayer.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - BET Manager</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="login-page">
        <div class="login-card">
            <div class="login-header">
                <img src="/assets/img/logo.svg" alt="BET Manager" style="width:90px;height:90px;margin:0 auto 1rem;display:block;">
                </div>
                <h1>BET Manager</h1>
                <p>Connectez-vous à votre espace</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label required" for="username">Nom d'utilisateur ou email</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           class="form-control" 
                           placeholder="admin"
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                           required 
                           autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label required" for="password">Mot de passe</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="••••••••"
                           required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    Se connecter
                </button>
            </form>

            <p class="text-center text-muted mt-3" style="font-size:0.8rem;">
                © <?= date('Y') ?> BET Manager - Bureau d'études
            </p>
        </div>
    </div>
</body>
</html>