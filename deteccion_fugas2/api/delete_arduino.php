<?php
require_once '../config.php';
require_once '../auth.php';

header('Content-Type: application/json');

Auth::requireLogin();
$user = Auth::getCurrentUser();

// Obtener datos del request
$input = json_decode(file_get_contents('php://input'), true);
$arduinoId = $input['arduino_id'] ?? null;

if (!$arduinoId) {
    echo json_encode(['success' => false, 'error' => 'ID de Arduino no proporcionado']);
    exit();
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar que el Arduino pertenece al usuario
    $stmt = $db->prepare("
        SELECT id FROM arduinos 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$arduinoId, $user['id']]);
    $arduino = $stmt->fetch();
    
    if (!$arduino) {
        echo json_encode(['success' => false, 'error' => 'Arduino no encontrado o no tienes permiso']);
        exit();
    }
    
    // Iniciar transacción
    $db->beginTransaction();
    
    // Eliminar lecturas
    $stmt = $db->prepare("DELETE FROM lecturas WHERE arduino_id = ?");
    $stmt->execute([$arduinoId]);
    
    // Eliminar rangos de alertas
    $stmt = $db->prepare("DELETE FROM rangos_alertas WHERE arduino_id = ?");
    $stmt->execute([$arduinoId]);
    
    // Eliminar configuración de sensores
    $stmt = $db->prepare("DELETE FROM configuracion_sensores WHERE arduino_id = ?");
    $stmt->execute([$arduinoId]);
    
    // Marcar Arduino como inactivo (soft delete) o eliminarlo completamente
    $stmt = $db->prepare("DELETE FROM arduinos WHERE id = ?");
    $stmt->execute([$arduinoId]);
    
    // Commit de la transacción
    $db->commit();
    
    echo json_encode(['success' => true, 'message' => 'Arduino eliminado correctamente']);
    
} catch (Exception $e) {
    // Rollback en caso de error
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Error al eliminar: ' . $e->getMessage()]);
}
?>
