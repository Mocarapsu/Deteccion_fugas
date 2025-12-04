<?php
// api/live_data.php
require_once '../config.php';
require_once '../auth.php';

// Requiere autenticación de usuario (para el dashboard)
Auth::requireLogin();
$user = Auth::getCurrentUser();

header('Content-Type: application/json');

$arduinoId = $_GET['id'] ?? null;

if (!$arduinoId) {
    echo json_encode(['success' => false, 'error' => 'Falta ID de Arduino']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Consulta ULTRA-RÁPIDA (usa las columnas de caché de la tabla arduinos)
    $stmt = $db->prepare("
        SELECT 
            ultima_presion, 
            ultima_flujo, 
            ultima_lectura 
        FROM arduinos 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$arduinoId, $user['id']]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        // Retorna los datos cacheados
        echo json_encode([
            'success' => true,
            'latest_data' => [
                // Presión
                'presion_valor' => $data['ultima_presion'] ?? '0.00',
                'presion_unidad' => 'PSI',
                
                // Flujo
                'flujo_valor' => $data['ultima_flujo'] ?? '0.00',
                'flujo_unidad' => 'L/min',
                
                // Tiempo
                'ultima_lectura' => $data['ultima_lectura']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Datos no encontrados']);
    }

} catch (Exception $e) {
    error_log("Error fetching live data: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error de servidor']);
}
?>