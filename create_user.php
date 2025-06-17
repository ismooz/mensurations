<?php
require_once 'config.php';

// Protection : ce script ne doit être accessible qu'en étant connecté en tant qu'admin
requireLogin();
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    die('Accès non autorisé');
}

function createUser($username, $password, $birthdate, $gender) {
    global $pdo;
    
    try {
        // Vérifier si l'utilisateur existe déjà
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Ce nom d\'utilisateur existe déjà'];
        }
        
        // Hasher le mot de passe
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Gérer les valeurs vides pour birthdate et gender
        $birthdate = !empty($birthdate) ? $birthdate : null;
        $gender = !empty($gender) ? $gender : null;
        
        // Créer l'utilisateur avec les nouvelles informations
        $stmt = $pdo->prepare("INSERT INTO users (username, password, birthdate, gender) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $hashedPassword, $birthdate, $gender]);
        
        return ['success' => true, 'message' => 'Utilisateur créé avec succès'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Erreur : ' . $e->getMessage()];
    }
}

// Utilisation en ligne de commande (mise à jour pour info, mais moins pertinent pour les champs supplémentaires)
if (php_sapi_name() === 'cli') {
    if ($argc < 3) {
        echo "Usage: php create_user.php <username> <password> [YYYY-MM-DD] [gender]\n";
        exit(1);
    }
    
    $result = createUser($argv[1], $argv[2], $argv[3] ?? null, $argv[4] ?? null);
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
    <header class="header">
        <h1>Créer un utilisateur</h1>
        <div class="header-actions">
            <a href="admin.php" class="back-btn" style="margin:0;">← Admin</a>
        </div>
    </header>

    <div class="container" style="max-width: 600px; margin: 20px auto;">
        <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = createUser($_POST['username'], $_POST['password'], $_POST['birthdate'], $_POST['gender']);
                echo '<div class="import-result ' . ($result['success'] ? 'success' : 'error') . '" style="display: block; margin-bottom: 20px;">';
                echo htmlspecialchars($result['message']);
                echo '</div>';
            }
            ?>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Nom d'utilisateur</label>
                    <input type="text" name="username" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" name="password" class="form-input" required minlength="6">
                </div>
                <div class="form-group">
                    <label class="form-label">Date de naissance</label>
                    <input type="date" name="birthdate" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Sexe</label>
                    <select name="gender" class="form-select">
                        <option value="">Non spécifié</option>
                        <option value="Homme">Homme</option>
                        <option value="Femme">Femme</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                <button type="submit" class="submit-btn">Créer l'utilisateur</button>
            </form>
        </div>
    </div>
</body>
</html>
