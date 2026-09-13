<?php
// Page d'accueil : redirige vers login ou dashboard selon l'état de connexion

require_once __DIR__ . '/config/database.php';

startSession();

if (isLoggedIn()) {
    header('Location: /dashboard/index.php');
} else {
    header('Location: /auth/login.php');
}
exit();
?>