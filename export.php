<?php
require_once 'config.php';
requireLogin();

$format = $_GET['format'] ?? 'csv';
$type = $_GET['type'] ?? null;

// Récupérer les données
$query = "SELECT measurement_date, measurement_time, measurement_type, value, notes 
          FROM measurements 
          WHERE user_id = ?";
$params = [$_SESSION['user_id']];

if ($type) {
    $query .= " AND measurement_type = ?";
    $params[] = $type;
}

$query .= " ORDER BY measurement_date DESC, measurement_time DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$measurements = $stmt->fetchAll();

// Export selon le format
if ($format === 'json') {
    // Export JSON
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="measurements_' . date('Y-m-d') . '.json"');
    
    echo json_encode($measurements, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} else {
    // Export CSV (format par défaut)
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="measurements_' . date('Y-m-d') . '.csv"');
    
    // BOM pour Excel
    echo "\xEF\xBB\xBF";
    
    // Ouvrir la sortie
    $output = fopen('php://output', 'w');
    
    // En-têtes
    fputcsv($output, ['Date', 'Heure', 'Mensuration', 'Valeur', 'Notes'], ';');
    
    // Données
    foreach ($measurements as $row) {
        // Formater la date au format DD.MM.YY
        $date = date('d.m.y', strtotime($row['measurement_date']));
        
        // Formater l'heure au format HH:MM
        $time = substr($row['measurement_time'], 0, 5);
        
        // Formater la valeur selon le type
        $unit = $row['measurement_type'] === 'Poids' ? 'kg' : 
                (strpos($row['measurement_type'], 'masse') !== false || 
                 strpos($row['measurement_type'], 'Eau') !== false ? '%' : 'cm');
        
        $value = number_format($row['value'], 2, ',', '') . ' ' . $unit;
        
        fputcsv($output, [
            $date,
            $time,
            $row['measurement_type'],
            $value,
            $row['notes']
        ], ';');
    }
    
    fclose($output);
}
exit;
?>