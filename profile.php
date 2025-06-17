<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Récupérer les informations actuelles de l'utilisateur
$stmt = $pdo->prepare("SELECT username, birthdate, gender FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];

    // Vérifier si le nom d'utilisateur est déjà pris par un autre utilisateur
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
    $stmt->execute([$username, $user_id]);
    if ($stmt->fetch()) {
        $error_message = "Ce nom d'utilisateur est déjà pris.";
    } else {
        $query = "UPDATE users SET username = ?, birthdate = ?, gender = ? WHERE id = ?";
        $params = [$username, $birthdate, $gender, $user_id];

        // Mettre à jour le mot de passe seulement s'il est fourni
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET username = ?, password = ?, birthdate = ?, gender = ? WHERE id = ?";
            $params = [$username, $hashedPassword, $birthdate, $gender, $user_id];
        }

        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $_SESSION['username'] = $username; // Mettre à jour le nom dans la session
            $success_message = "Votre profil a été mis à jour avec succès.";
            // Re-fetch user data to display updated info
            $stmt = $pdo->prepare("SELECT username, birthdate, gender FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } catch (PDOException $e) {
            $error_message = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Suivi des Mensurations</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="header">
        <h1>Mon Profil</h1>
        <div class="header-actions">
            <a href="index.php" class="back-btn" style="margin:0;">← Retour à l'accueil</a>
            <a href="logout.php" class="logout-btn">Déconnexion</a>
        </div>
    </header>

    <div class="container">
        <div class="profile-container" style="max-width: 600px; margin: 20px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <?php if ($success_message): ?>
                <div class="alert-success" style="padding: 15px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; margin-bottom: 20px;"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert-error" style="padding: 15px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin-bottom: 20px;"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>

            <form method="POST" action="profile.php">
                <div class="form-group">
                    <label class="form-label">Nom d'utilisateur</label>
                    <input type="text" name="username" class="form-input" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                    <input type="password" name="password" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">Date de naissance</label>
                    <input type="date" name="birthdate" class="form-input" value="<?= htmlspecialchars($user['birthdate']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Sexe</label>
                    <select name="gender" class="form-select">
                        <option value="">Non spécifié</option>
                        <option value="Homme" <?= ($user['gender'] === 'Homme') ? 'selected' : '' ?>>Homme</option>
                        <option value="Femme" <?= ($user['gender'] === 'Femme') ? 'selected' : '' ?>>Femme</option>
                        <option value="Autre" <?= ($user['gender'] === 'Autre') ? 'selected' : '' ?>>Autre</option>
                    </select>
                </div>
                <button type="submit" class="submit-btn">Mettre à jour le profil</button>
            </form>
        </div>
    </div>
</body>
</html>
