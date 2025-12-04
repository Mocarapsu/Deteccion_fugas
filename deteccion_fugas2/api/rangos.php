<?php
require_once '../config.php';
require_once '../auth.php';

Auth::requireLogin();
$user = Auth::getCurrentUser();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

// POST - Guardar o actualizar rango de alerta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar datos
        if (empty($data['arduino_id']) || empty($data['tipo_sensor']) || 
            !isset($data['numero_sensor']) || !isset($data['valor_minimo']) || 
            !isset($data['valor_maximo'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Datos incompletos'
            ]);
            exit;
        }
        
        $arduinoId = intval($data['arduino_id']);
        $tipoSensor = $data['tipo_sensor'];
        $numeroSensor = intval($data['numero_sensor']);
        $valorMinimo = floatval($data['valor_minimo']);
        $valorMaximo = floatval($data['valor_maximo']);
        
        // Verificar que el Arduino pertenece al usuario
        $stmt = $db->prepare("
            SELECT id FROM arduinos 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$arduinoId, $user['id']]);
        
        if (!$stmt->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'Arduino no encontrado o no tienes permisos'
            ]);
            exit;
        }
        
        // Validar valores
        if ($valorMinimo >= $valorMaximo) {
            echo json_encode([
                'success' => false,
                'message' => 'El valor mínimo debe ser menor que el máximo'
            ]);
            exit;
        }
        
        // Verificar si ya existe el rango
        $stmt = $db->prepare("
            SELECT id FROM rangos_alertas 
            WHERE arduino_id = ? AND tipo_sensor = ? AND numero_sensor = ?
        ");
        $stmt->execute([$arduinoId, $tipoSensor, $numeroSensor]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Actualizar rango existente
            $stmt = $db->prepare("
                UPDATE rangos_alertas 
                SET valor_minimo = ?, valor_maximo = ?
                WHERE arduino_id = ? AND tipo_sensor = ? AND numero_sensor = ?
            ");
            $stmt->execute([$valorMinimo, $valorMaximo, $arduinoId, $tipoSensor, $numeroSensor]);
            $mensaje = 'Rango de alerta actualizado';
        } else {
            // Insertar nuevo rango
            $stmt = $db->prepare("
                INSERT INTO rangos_alertas 
                (arduino_id, tipo_sensor, numero_sensor, valor_minimo, valor_maximo)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$arduinoId, $tipoSensor, $numeroSensor, $valorMinimo, $valorMaximo]);
            $mensaje = 'Rango de alerta creado';
        }
        
        logActivity($user['id'], $arduinoId, 'RANGO_CONFIGURADO', 
            "Rango {$tipoSensor}{$numeroSensor}: {$valorMinimo}-{$valorMaximo}");
        
        echo json_encode([
            'success' => true,
            'message' => $mensaje
        ]);
        
    } catch(PDOException $e) {
        error_log("Error en POST rangos: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error al guardar rango'
        ]);
    }
}

// GET - Obtener rangos de alerta de un Arduino
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $arduinoId = $_GET['arduino_id'] ?? null;
        
        if (!$arduinoId) {
            echo json_encode(['success' => false, 'message' => 'Arduino ID requerido']);
            exit;
        }
        
        // Verificar que el Arduino pertenece al usuario
        $stmt = $db->prepare("
            SELECT id FROM arduinos 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$arduinoId, $user['id']]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Arduino no encontrado']);
            exit;
        }
        
        // Obtener todos los rangos
        $stmt = $db->prepare("
            SELECT tipo_sensor, numero_sensor, valor_minimo, valor_maximo
            FROM rangos_alertas
            WHERE arduino_id = ?
            ORDER BY tipo_sensor, numero_sensor
        ");
        $stmt->execute([$arduinoId]);
        $rangos = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'rangos' => $rangos
        ]);
        
    } catch(PDOException $e) {
        error_log("Error en GET rangos: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener rangos'
        ]);
    }
}

// DELETE - Eliminar rango de alerta
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $arduinoId = $_GET['arduino_id'] ?? null;
        $tipoSensor = $_GET['tipo_sensor'] ?? null;
        $numeroSensor = $_GET['numero_sensor'] ?? null;
        
        if (!$arduinoId || !$tipoSensor || !$numeroSensor) {
            echo json_encode(['success' => false, 'message' => 'Parámetros incompletos']);
            exit;
        }
        
        // Verificar que el Arduino pertenece al usuario
        $stmt = $db->prepare("
            SELECT id FROM arduinos 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$arduinoId, $user['id']]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Arduino no encontrado']);
            exit;
        }
        
        // Eliminar rango
        $stmt = $db->prepare("
            DELETE FROM rangos_alertas 
            WHERE arduino_id = ? AND tipo_sensor = ? AND numero_sensor = ?
        ");
        $stmt->execute([$arduinoId, $tipoSensor, $numeroSensor]);
        
        logActivity($user['id'], $arduinoId, 'RANGO_ELIMINADO', 
            "Rango {$tipoSensor}{$numeroSensor} eliminado");
        
        echo json_encode([
            'success' => true,
            'message' => 'Rango eliminado exitosamente'
        ]);
        
    } catch(PDOException $e) {
        error_log("Error en DELETE rangos: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error al eliminar rango'
        ]);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
