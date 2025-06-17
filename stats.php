<?php
require_once 'config.php';
requireLogin();

// --- Logique de l'administrateur ---
$target_user_id = $_SESSION['user_id'];
$target_username = $_SESSION['username'];
$is_admin_view = false;

// Si l'utilisateur est un admin et qu'un user_id est passé en paramètre
if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] && isset($_GET['user_id'])) {
    $target_user_id = intval($_GET['user_id']);
    
    // Récupérer le nom d'utilisateur pour l'affichage
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$target_user_id]);
    $user = $stmt->fetch();

    if ($user) {
        $target_username = $user['username'];
        $is_admin_view = true;
    } else {
        // Rediriger si l'user_id n'est pas valide
        header('Location: admin.php');
        exit;
    }
}

// --- Fin de la logique de l'administrateur ---


// Récupérer les statistiques globales pour l'utilisateur cible
$stmt = $pdo->prepare("
    SELECT 
        measurement_type,
        COUNT(*) as count,
        MIN(value) as min_value,
        MAX(value) as max_value,
        AVG(value) as avg_value,
        MIN(measurement_date) as first_date,
        MAX(measurement_date) as last_date
    FROM measurements
    WHERE user_id = ?
    GROUP BY measurement_type
    ORDER BY measurement_type ASC
");
$stmt->execute([$target_user_id]); // Utilise l'ID de l'utilisateur cible
$stats = $stmt->fetchAll();

// Récupérer les dernières et avant-dernières valeurs pour les tendances
$stmt = $pdo->prepare("
    WITH RankedMeasurements AS (
        SELECT 
            *,
            ROW_NUMBER() OVER(PARTITION BY measurement_type ORDER BY measurement_date DESC, measurement_time DESC) as rn
        FROM measurements
        WHERE user_id = ?
    )
    SELECT measurement_type, value, rn
    FROM RankedMeasurements
    WHERE rn <= 2
");
$stmt->execute([$target_user_id]); // Utilise l'ID de l'utilisateur cible
$recentValues = $stmt->fetchAll();

// Organiser les valeurs pour un accès facile
$trends = [];
foreach ($recentValues as $row) {
    if ($row['rn'] == 1) {
        $trends[$row['measurement_type']]['current'] = $row['value'];
    } else {
        $trends[$row['measurement_type']]['previous'] = $row['value'];
    }
}

function formatValueWithUnit($value, $type) {
    if ($value === null || !is_numeric($value)) return '--';
    $unit = $type === 'Poids' ? 'kg' : (strpos($type, 'masse') !== false || strpos($type, 'Eau') !== false ? '%' : 'cm');
    return number_format(floatval($value), 2, ',', ' ') . ' ' . $unit;
}

// Map des icônes pour chaque type de mesure
$icons = [
    'Poids' => '⚖️',
    'Indice de masse grasse' => '📊',
    'Eau corporelle' => '💧', // Correction de l'icône
    'Masse musculaire' => '💪',
    'Cou' => '🧣',
    'Épaules' => '🤷‍♂️',
    'Poitrine' => '👕',
    'Bras (Gauche)' => '💪',
    'Bras (Droite)' => '💪',
    'Avant-bras (Gauche)' => '✊',
    'Avant-bras (Droite)' => '✊',
    'Taille' => '👖',
    'Hanches' => '💃',
    'Cuisse (Gauche)' => '🦵',
    'Cuisse (Droite)' => '🦵',
    'Mollet (Gauche)' => '🦵',
    'Mollet (Droite)' => '�',
];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques - <?= htmlspecialchars($target_username) ?></title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Styles spécifiques pour la page de statistiques */
        body {
            background-color: #f8f9fa;
        }
        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .stats-header h1 {
            font-size: 22px;
            font-weight: 600;
        }
        .stats-header .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            padding: 20px;
        }
        .stat-card-new {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 20px;
            display: flex;
            flex-direction: column;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card-new:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }
        .stat-card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .stat-icon {
            font-size: 28px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f0f0f0;
        }
        .stat-card-header h3 {
            font-size: 18px;
            font-weight: 600;
            margin: 0;
            color: #333;
        }
        .stat-main-value {
            font-size: 28px;
            font-weight: 700;
            color: #e74c3c;
            margin-bottom: 5px;
        }
        .stat-trend {
            display: flex;
            align-items: center;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 20px;
        }
        .stat-trend.up { color: #27ae60; }
        .stat-trend.down { color: #c0392b; }
        .stat-trend.stable { color: #7f8c8d; }
        .stat-trend-icon {
            margin-right: 5px;
            font-weight: bold;
        }
        .stat-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: auto;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }
        .stat-detail {
            font-size: 13px;
        }
        .stat-detail-label {
            color: #666;
            display: block;
        }
        .stat-detail-value {
            color: #333;
            font-weight: 500;
        }
        .stat-card-footer {
            margin-top: 20px;
        }
        .stat-link-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background-color: #e74c3c;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        .stat-link-btn:hover {
            background-color: #c0392b;
        }
        .no-data-message {
            grid-column: 1 / -1;
            text-align: center;
            padding: 50px 20px;
            background: #fff;
            border-radius: 12px;
            color: #999;
        }

        @media (max-width: 767px) {
            .stats-header {
                padding: 10px 15px;
            }
            .stats-header h1 {
                font-size: 20px;
            }
            .stats-container {
                padding: 15px;
                gap: 15px;
            }
        }
        @media (max-width: 374px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="stats-header">
        <h1>Statistiques de <?= htmlspecialchars($target_username) ?></h1>
        <div class="header-actions">
            <?php if ($is_admin_view): ?>
                 <a href="admin.php" class="back-btn" style="margin:0;">← Admin</a>
            <?php else: ?>
                <a href="index.php" class="back-btn" style="margin:0;">← Retour</a>
            <?php endif; ?>
            <a href="logout.php" class="logout-btn">Déconnexion</a>
        </div>
    </div>

    <div class="stats-container">
        <?php if (empty($stats)): ?>
            <div class="no-data-message">
                <p>Aucune donnée disponible pour cet utilisateur.</p>
            </div>
        <?php else: ?>
            <?php foreach ($stats as $stat): 
                $type = $stat['measurement_type'];
                $trendInfo = $trends[$type] ?? ['current' => null, 'previous' => null];
                $currentValue = $trendInfo['current'];
                $previousValue = $trendInfo['previous'];
                $change = null;
                $direction = 'stable';
                
                if ($currentValue !== null && $previousValue !== null) {
                    $change = floatval($currentValue) - floatval($previousValue);
                    if ($change > 0.01) $direction = 'up';
                    if ($change < -0.01) $direction = 'down';
                }
            ?>
            <div class="stat-card-new">
                <div class="stat-card-header">
                    <div class="stat-icon"><?= $icons[$type] ?? '❓' ?></div>
                    <h3><?= htmlspecialchars($type) ?></h3>
                </div>

                <div class="stat-main-value">
                    <?= formatValueWithUnit($currentValue, $type) ?>
                </div>

                <div class="stat-trend <?= $direction ?>">
                    <?php if ($direction === 'up'): ?>
                        <span class="stat-trend-icon">▲</span> En hausse
                    <?php elseif ($direction === 'down'): ?>
                        <span class="stat-trend-icon">▼</span> En baisse
                    <?php else: ?>
                        <span class="stat-trend-icon">▬</span> Stable
                    <?php endif; ?>
                    <?php if ($change !== null): ?>
                         (<?= ($change > 0 ? '+' : '') . formatValueWithUnit($change, $type) ?>)
                    <?php endif; ?>
                </div>

                <div class="stat-details-grid">
                    <div class="stat-detail">
                        <span class="stat-detail-label">Minimum</span>
                        <span class="stat-detail-value"><?= formatValueWithUnit($stat['min_value'], $type) ?></span>
                    </div>
                     <div class="stat-detail">
                        <span class="stat-detail-label">Maximum</span>
                        <span class="stat-detail-value"><?= formatValueWithUnit($stat['max_value'], $type) ?></span>
                    </div>
                     <div class="stat-detail">
                        <span class="stat-detail-label">Moyenne</span>
                        <span class="stat-detail-value"><?= formatValueWithUnit($stat['avg_value'], $type) ?></span>
                    </div>
                     <div class="stat-detail">
                        <span class="stat-detail-label">Mesures</span>
                        <span class="stat-detail-value"><?= $stat['count'] ?></span>
                    </div>
                </div>

                <div class="stat-card-footer">
                    <a href="index.php#<?= rawurlencode($type) ?>" class="stat-link-btn">
                        Voir le graphique détaillé →
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
