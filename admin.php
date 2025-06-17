<?php
require_once 'config.php';
requireLogin();

// Seul l'administrateur peut accéder à cette page
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    die("Accès non autorisé.");
}

// Récupérer tous les utilisateurs
$stmt = $pdo->query("SELECT id, username FROM users ORDER BY username ASC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Suivi des Mensurations</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="header">
        <h1>Administration</h1>
        <div class="header-actions">
            <a href="index.php" class="back-btn" style="margin:0;">← Retour à l'accueil</a>
            <a href="create_user.php" class="stats-link-header">Créer un utilisateur</a>
        </div>
    </header>

    <div class="container">
        <h2>Liste des utilisateurs</h2>
        <div class="measurement-list" style="margin-top: 20px;">
            <?php foreach ($users as $user): ?>
                <div class="measurement-item">
                    <span class="measurement-name"><?= htmlspecialchars($user['username']) ?></span>
                    <a href="stats.php?user_id=<?= $user['id'] ?>" class="btn-secondary" style="text-decoration: none;">Voir les stats</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
