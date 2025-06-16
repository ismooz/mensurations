<?php
require_once 'config.php';

// Protection : ce script ne doit être accessible qu'en ligne de commande ou par un admin
if (php_sapi_name() !== 'cli' && !isset($_SESSION['is_admin'])) {
    die('Accès non autorisé');
}

function createUser($username, $password) {
    global $pdo;
    
    try {
        // Vérifier si l'utilisateur existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Cet utilisateur existe déjà'];
        }
        
        // Hasher le mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Créer l'utilisateur
        $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $hashedPassword]);
        
        return ['success' => true, 'message' => 'Utilisateur créé avec succès'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
    }
}

// Utilisation en ligne de commande
if (php_sapi_name() === 'cli') {
    if ($argc < 3) {
        echo "Usage: php create_user.php <username> <password>\n";
        exit(1);
    }
    
    $result = createUser($argv[1], $argv[2]);
    echo $result['message'] . "\n";
    exit($result['success'] ? 0 : 1);
}

// Interface web pour les admins
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un utilisateur</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container" style="max-width: 500px; margin: 50px auto;">
        <h2>Créer un nouvel utilisateur</h2>
        
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = createUser($_POST['username'], $_POST['password']);
            echo '<div class="import-result ' . ($result['success'] ? 'success' : 'error') . '" style="display: block; margin-bottom: 20px;">';
            echo htmlspecialchars($result['message']);
            echo '</div>';
        }
        ?>
        
        <form method="POST" style="background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            <div class="form-group">
                <label class="form-label">Nom d'utilisateur</label>
                <input type="text" name="username" class="form-input" required>
            </div>
            <div class="form-group">
                <label class="form-label">Mot de passe</label>
                <input type="password" name="password" class="form-input" required minlength="6">
            </div>
            <button type="submit" class="submit-btn">Créer l'utilisateur</button>
        </form>
        
        <a href="index.php" class="back-btn" style="margin-top: 20px;">← Retour à l'application</a>
    </div>
</body>
</html>