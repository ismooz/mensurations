<?php
require_once 'config.php';

// Vérifier que l'utilisateur est connecté
requireLogin();

// Set header for JSON response
header('Content-Type: application/json');

// Récupérer l'action
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_measurements':
        $type = $_POST['type'] ?? null;
        $period = $_POST['period'] ?? 'all';
        
        $query = "SELECT * FROM measurements WHERE user_id = ?";
        $params = [$_SESSION['user_id']];
        
        if ($type) {
            $query .= " AND measurement_type = ?";
            $params[] = $type;
        }
        
        if ($period !== 'all') {
            $days = intval($period);
            $query .= " AND measurement_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)";
            $params[] = $days;
        }
        
        $query .= " ORDER BY measurement_date DESC, measurement_time DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $measurements = $stmt->fetchAll();
        
        echo json_encode($measurements);
        break;
        
    case 'add_measurement':
        $stmt = $pdo->prepare("INSERT INTO measurements (user_id, measurement_date, measurement_time, measurement_type, value, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([
            $_SESSION['user_id'],
            $_POST['date'],
            $_POST['time'],
            $_POST['type'],
            $_POST['value'],
            $_POST['notes'] ?? ''
        ]);
        
        echo json_encode([
            'success' => $result,
            'id' => $pdo->lastInsertId()
        ]);
        break;
        
    case 'update_measurement':
        $stmt = $pdo->prepare("UPDATE measurements SET measurement_date = ?, measurement_time = ?, value = ?, notes = ? WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([
            $_POST['date'],
            $_POST['time'],
            $_POST['value'],
            $_POST['notes'] ?? '',
            $_POST['id'],
            $_SESSION['user_id']
        ]);
        
        echo json_encode(['success' => $result]);
        break;
        
    case 'delete_measurement':
        $stmt = $pdo->prepare("DELETE FROM measurements WHERE id = ? AND user_id = ?");
        $result = $stmt->execute([$_POST['id'], $_SESSION['user_id']]);
        
        echo json_encode(['success' => $result]);
        break;
        
    case 'get_latest_values':
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
        $latest = $stmt->fetchAll();
        
        echo json_encode($latest);
        break;
        
    case 'import_csv':
        if (isset($_FILES['csvFile']) && $_FILES['csvFile']['error'] === UPLOAD_ERR_OK) {
            $uploadedFile = $_FILES['csvFile']['tmp_name'];
            $successCount = 0;
            $errorCount = 0;
            
            if (($handle = fopen($uploadedFile, "r")) !== FALSE) {
                // Détecter l'encodage
                $content = file_get_contents($uploadedFile);
                $encoding = mb_detect_encoding($content, ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1']);
                
                if ($encoding !== 'UTF-8') {
                    $content = mb_convert_encoding($content, 'UTF-8', $encoding);
                    $handle = fopen('data://text/plain,' . $content, 'r');
                }
                
                // Skip header
                fgetcsv($handle);
                
                $stmt = $pdo->prepare("INSERT INTO measurements (user_id, measurement_date, measurement_time, measurement_type, value, notes) VALUES (?, ?, ?, ?, ?, ?)");
                
                while (($data = fgetcsv($handle)) !== FALSE) {
                    if (count($data) < 4) continue;
                    
                    try {
                        // Format date from DD.MM.YY to YYYY-MM-DD
                        $dateParts = explode('.', $data[0]);
                        if (count($dateParts) == 3) {
                            $year = strlen($dateParts[2]) == 2 ? '20' . $dateParts[2] : $dateParts[2];
                            $date = $year . '-' . str_pad($dateParts[1], 2, '0', STR_PAD_LEFT) . '-' . str_pad($dateParts[0], 2, '0', STR_PAD_LEFT);
                            
                            // Extract numeric value
                            $valueStr = $data[3];
                            $valueStr = str_replace(['"', ' cm', ' kg', '%'], '', $valueStr);
                            $valueStr = str_replace(',', '.', $valueStr);
                            $value = floatval($valueStr);
                            
                            $stmt->execute([
                                $_SESSION['user_id'],
                                $date,
                                $data[1],
                                $data[2],
                                $value,
                                $data[4] ?? ''
                            ]);
                            $successCount++;
                        }
                    } catch (Exception $e) {
                        $errorCount++;
                    }
                }
                fclose($handle);
            }
            
            echo json_encode([
                'success' => true,
                'imported' => $successCount,
                'errors' => $errorCount
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Erreur lors de l\'upload du fichier'
            ]);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Action non reconnue']);
}
?>