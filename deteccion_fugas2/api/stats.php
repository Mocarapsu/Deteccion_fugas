<?php
require_once '../config.php';
require_once '../auth.php';

Auth::requireLogin();
$user = Auth::getCurrentUser();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

try {
    // Total de arduinos activos
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM arduinos 
        WHERE user_id = ? AND activo = 1
    ");
    $stmt->execute([$user['id']]);
    $totalArduinos = $stmt->fetch()['total'];
    
    // Total de lecturas
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM lecturas l
        INNER JOIN arduinos a ON l.arduino_id = a.id
        WHERE a.user_id = ?
    ");
    $stmt->execute([$user['id']]);
    $totalLecturas = $stmt->fetch()['total'];
    
    // Última lectura
    $stmt = $db->prepare("
        SELECT MAX(l.timestamp) as ultima
        FROM lecturas l
        INNER JOIN arduinos a ON l.arduino_id = a.id
        WHERE a.user_id = ?
    ");
    $stmt->execute([$user['id']]);
    $ultimaLectura = $stmt->fetch()['ultima'];
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_arduinos' => (int)$totalArduinos,
            'total_lecturas' => (int)$totalLecturas,
            'ultima_lectura' => $ultimaLectura
        ]
    ]);
    
} catch(PDOException $e) {
    error_log("Error en stats: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener estadísticas'
    ]);
}
?>