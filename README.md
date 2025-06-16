# Application de Suivi des Mensurations

Application web PHP/MySQL pour suivre l'évolution de vos mensurations corporelles avec graphiques et statistiques.

## 🚀 Installation

### 1. Prérequis
- Serveur web avec PHP 7.4+ 
- MySQL 5.7+
- Extension PDO PHP activée

### 2. Base de données
Exécutez le script SQL suivant pour créer les tables :

```sql
-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des mensurations
CREATE TABLE IF NOT EXISTS measurements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    measurement_date DATE NOT NULL,
    measurement_time TIME NOT NULL,
    measurement_type VARCHAR(100) NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, measurement_date),
    INDEX idx_user_type (user_id, measurement_type)
);

-- Utilisateur par défaut (mot de passe: admin123)
INSERT INTO users (username, password) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
```

### 3. Configuration
1. Modifiez `config.php` avec vos informations de connexion :
```php
define('DB_PASS', 'VOTRE_MOT_DE_PASSE');
```

2. Uploadez tous les fichiers sur votre serveur

3. Assurez-vous que le dossier est accessible via votre serveur web

## 📱 Utilisation

### Connexion
- URL : `http://votre-domaine.com/login.php`
- Utilisateur par défaut : `admin` / `admin123`

### Fonctionnalités principales

#### 1. **Vue d'ensemble**
- Statistiques principales (Poids, Poitrine, Taille, Bras)
- Graphique d'évolution du poids
- Import de fichiers CSV

#### 2. **Circonférences**
Suivi de toutes les mesures corporelles :
- Cou, Épaules, Poitrine
- Bras (Gauche/Droite)
- Avant-bras (Gauche/Droite)
- Taille, Hanches
- Cuisses (Gauche/Droite)
- Mollets (Gauche/Droite)

#### 3. **Composition corporelle**
- Poids
- Indice de masse grasse
- Eau corporelle
- Masse musculaire

### Ajouter une mesure
1. Cliquez sur le bouton **+** rouge
2. Sélectionnez le type de mensuration
3. Entrez la valeur
4. Ajoutez une note (optionnel)
5. Cliquez sur "Enregistrer"

### Voir l'évolution
1. Cliquez sur n'importe quelle mesure dans la liste
2. Visualisez le graphique d'évolution
3. Consultez l'historique complet
4. Filtrez par période (Tout, 2 ans, 1 an, 6 mois, 1 mois)

### Modifier/Supprimer
1. Dans la vue détaillée, cliquez sur une entrée de l'historique
2. Modifiez les valeurs dans le formulaire
3. Cliquez sur "Enregistrer" ou "Supprimer"

## 📊 Import/Export

### Import CSV
Le fichier CSV doit avoir la structure suivante :
```csv
Date,Heure,Mensuration,Valeur,Notes
17.08.24,08:44,Cou,"44,00 cm",
17.08.24,08:42,Poids,"93,70 kg",Note optionnelle
```

### Export
- Depuis la vue détaillée d'une mesure, utilisez le bouton d'export
- Formats disponibles : CSV, JSON
- URL directe : `export.php?format=csv` ou `export.php?format=json`

## 👥 Gestion des utilisateurs

### Créer un utilisateur (ligne de commande)
```bash
php create_user.php nom_utilisateur mot_de_passe
```

### Créer un utilisateur (interface web)
Accédez à `create_user.php` en étant connecté comme administrateur

## 🛡️ Sécurité

- Mots de passe hashés avec bcrypt
- Protection contre les injections SQL (PDO avec requêtes préparées)
- Sessions PHP sécurisées
- Protection XSS
- Headers de sécurité via .htaccess

## 📂 Structure des fichiers

```
├── index.php          # Page principale
├── login.php          # Page de connexion
├── logout.php         # Script de déconnexion
├── config.php         # Configuration BDD
├── api.php            # API AJAX
├── export.php         # Export des données
├── create_user.php    # Création d'utilisateurs
├── app.js             # JavaScript principal
├── styles.css         # Styles CSS
├── .htaccess          # Configuration Apache
└── README.md          # Documentation
```

## 🔧 Dépannage

### Erreur de connexion à la base de données
- Vérifiez les informations de connexion dans `config.php`
- Assurez-vous que l'extension PDO est activée
- Vérifiez que les tables ont été créées

### Problèmes d'import CSV
- Vérifiez l'encodage du fichier (UTF-8 recommandé)
- Respectez le format des dates (DD.MM.YY)
- Les valeurs décimales doivent utiliser la virgule

### Graphiques non affichés
- Vérifiez que JavaScript est activé
- Effacez le cache du navigateur
- Vérifiez la console pour les erreurs

## 📱 Responsive Design

L'application est optimisée pour :
- 📱 Smartphones (320px+)
- 📱 Tablettes (768px+)
- 💻 Desktop (1024px+)

## 🤝 Support

Pour toute question ou problème :
1. Vérifiez cette documentation
2. Consultez les logs d'erreur PHP
3. Vérifiez la console JavaScript du navigateur

## 📄 Licence

Cette application est fournie telle quelle pour usage personnel.
