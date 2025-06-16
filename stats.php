<?php
require_once 'config.php';
requireLogin();

// Récupérer les statistiques
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
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetchAll();

// Récupérer les dernières valeurs pour calculer les tendances
$stmt = $pdo->prepare("
    SELECT m1.* FROM measurements m1
    INNER JOIN (
        SELECT measurement_type, MAX(CONCAT(measurement_date, ' ', measurement_time)) as max_datetime
        FROM measurements
        WHERE user_id = ?
        GROUP BY measurement_type
    ) m2 ON m1.measurement_type = m2.measurement_type 
    AND CONCAT(m1.measurement_date, ' ', m1.measurement_time) = m2.max_datetime
    WHERE m1.user_id = ?
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$latestValues = $stmt->fetchAll();

// Organiser les dernières valeurs par type
$latest = [];
foreach ($latestValues as $val) {
    $latest[$val['measurement_type']] = $val['value'];
}

// Calculer les tendances (30 derniers jours)
$trends = [];
$stmt = $pdo->prepare("
    SELECT measurement_type, value, measurement_date
    FROM measurements
    WHERE user_id = ? AND measurement_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY measurement_type, measurement_date ASC
");
$stmt->execute([$_SESSION['user_id']]);
$recentData = $stmt->fetchAll();

$groupedData = [];
foreach ($recentData as $data) {
    $groupedData[$data['measurement_type']][] = $data;
}

foreach ($groupedData as $type => $measurements) {
    if (count($measurements) >= 2) {
        $first = floatval($measurements[0]['value']);
        $last = floatval($measurements[count($measurements) - 1]['value']);
        $change = $last - $first;
        $percentChange = ($first != 0) ? ($change / $first) * 100 : 0;
        
        $trends[$type] = [
            'change' => $change,
            'percent' => $percentChange,
            'direction' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable')
        ];
    }
}

function formatValueWithUnit($value, $type) {
    $unit = $type === 'Poids' ? 'kg' : (strpos($type, 'masse') !== false || strpos($type, 'Eau') !== false ? '%' : 'cm');
    return number_format($value, 2, ',', '') . ' ' . $unit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques - Suivi des Mensurations</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="header">
        <h1>Statistiques</h1>
        <div class="header-actions">
            <a href="index.php" class="back-btn">← Retour</a>
            <a href="logout.php" class="logout-btn">Déconnexion</a>
        </div>
    </div>

    <div class="container">
        <h2 style="margin-bottom: 20px;">Vue d'ensemble de vos progrès</h2>
        
        <div class="stats-container">
            <?php foreach ($stats as $stat): 
                $type = $stat['measurement_type'];
                $latestValue = isset($latest[$type]) ? $latest[$type] : $stat['avg_value'];
                $trend = isset($trends[$type]) ? $trends[$type] : null;
            ?>
            <div class="stat-box">
                <h3><?= htmlspecialchars($type) ?></h3>
                
                <div class="stat-row">
                    <span class="stat-label">Valeur actuelle</span>
                    <span class="stat-value stat-current"><?= formatValueWithUnit($latestValue, $type) ?></span>
                </div>
                
                <?php if ($trend): ?>
                <div class="stat-row">
                    <span class="stat-label">Évolution (30j)</span>
                    <span class="stat-value stat-trend <?= $trend['direction'] ?>">
                        <?php if ($trend['direction'] === 'up'): ?>
                            ↑ +<?= number_format(abs($trend['change']), 2, ',', '') ?>
                        <?php elseif ($trend['direction'] === 'down'): ?>
                            ↓ <?= number_format($trend['change'], 2, ',', '') ?>
                        <?php else: ?>
                            → 0,00
                        <?php endif; ?>
                        <small>(<?= number_format($trend['percent'], 1, ',', '') ?>%)</small>
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="stat-row">
                    <span class="stat-label">Minimum</span>
                    <span class="stat-value"><?= formatValueWithUnit($stat['min_value'], $type) ?></span>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">Maximum</span>
                    <span class="stat-value"><?= formatValueWithUnit($stat['max_value'], $type) ?></span>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">Moyenne</span>
                    <span class="stat-value"><?= formatValueWithUnit($stat['avg_value'], $type) ?></span>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">Nombre de mesures</span>
                    <span class="stat-value"><?= $stat['count'] ?></span>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">Première mesure</span>
                    <span class="stat-value"><?= date('d/m/Y', strtotime($stat['first_date'])) ?></span>
                </div>
                
                <div class="stat-row">
                    <span class="stat-label">Dernière mesure</span>
                    <span class="stat-value"><?= date('d/m/Y', strtotime($stat['last_date'])) ?></span>
                </div>
                
                <a href="index.php#<?= urlencode($type) ?>" class="stat-link">
                    Voir l'évolution →
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($stats)): ?>
        <div style="text-align: center; padding: 40px; color: #999;">
            <p>Aucune donnée disponible pour le moment.</p>
            <p>Commencez par ajouter des mesures dans l'application.</p>
            <a href="index.php" class="submit-btn" style="display: inline-block; margin-top: 20px;">
                Ajouter des mesures
            </a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>