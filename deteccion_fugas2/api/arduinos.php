<?php
require_once '../config.php';
require_once '../auth.php';

Auth::requireLogin();
$user = Auth::getCurrentUser();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

// GET - Obtener todos los arduinos del usuario
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $db->prepare("
            SELECT 
                a.id,
                a.codigo_arduino,
                a.nombre,
                a.ubicacion,
                a.fecha_registro,
                a.ultima_lectura,
                c.cantidad_presion,
                c.cantidad_flujo,
                CASE 
                    WHEN a.ultima_lectura IS NULL THEN 0
                    WHEN TIMESTAMPDIFF(SECOND, a.ultima_lectura, NOW()) > ? THEN 0
                    ELSE 1
                END as is_online
            FROM arduinos a
            LEFT JOIN configuracion_sensores c ON a.id = c.arduino_id
            WHERE a.user_id = ? AND a.activo = 1
            ORDER BY a.nombre
        ");
        $stmt->execute([ARDUINO_TIMEOUT, $user['id']]);
        $arduinos = $stmt->fetchAll();
        
        // Obtener última lectura de cada sensor de cada arduino
        foreach ($arduinos as &$arduino) {
            $sensores = [];
            
            // Obtener rangos de alerta
            $stmt = $db->prepare("
                SELECT tipo_sensor, numero_sensor, valor_minimo, valor_maximo
                FROM rangos_alertas
                WHERE arduino_id = ?
            ");
            $stmt->execute([$arduino['id']]);
            $rangos = $stmt->fetchAll(PDO::FETCH_GROUP);
            
            // Sensores de presión
            for ($i = 1; $i <= $arduino['cantidad_presion']; $i++) {
                $stmt = $db->prepare("
                    SELECT valor, unidad, timestamp
                    FROM lecturas
                    WHERE arduino_id = ? AND tipo_sensor = 'presion' AND numero_sensor = ?
                    ORDER BY timestamp DESC
                    LIMIT 1
                ");
                $stmt->execute([$arduino['id'], $i]);
                $lectura = $stmt->fetch();
                
                // Verificar si está en alerta
                $en_alerta = false;
                if ($lectura && isset($rangos['presion'])) {
                    foreach ($rangos['presion'] as $rango) {
                        if ($rango['numero_sensor'] == $i) {
                            $valor = floatval($lectura['valor']);
                            $min = floatval($rango['valor_minimo']);
                            $max = floatval($rango['valor_maximo']);
                            
                            if ($valor < $min || $valor > $max) {
                                $en_alerta = true;
                            }
                            break;
                        }
                    }
                }
                
                $sensores[] = [
                    'tipo' => 'presion',
                    'numero' => $i,
                    'valor' => $lectura ? round($lectura['valor'], 2) : null,
                    'unidad' => $lectura ? $lectura['unidad'] : 'PSI',
                    'timestamp' => $lectura ? $lectura['timestamp'] : null,
                    'en_alerta' => $en_alerta
                ];
            }
            
            // Sensores de flujo
            for ($i = 1; $i <= $arduino['cantidad_flujo']; $i++) {
                $stmt = $db->prepare("
                    SELECT valor, unidad, timestamp
                    FROM lecturas
                    WHERE arduino_id = ? AND tipo_sensor = 'flujo' AND numero_sensor = ?
                    ORDER BY timestamp DESC
                    LIMIT 1
                ");
                $stmt->execute([$arduino['id'], $i]);
                $lectura = $stmt->fetch();
                
                // Verificar si está en alerta
                $en_alerta = false;
                if ($lectura && isset($rangos['flujo'])) {
                    foreach ($rangos['flujo'] as $rango) {
                        if ($rango['numero_sensor'] == $i) {
                            $valor = floatval($lectura['valor']);
                            $min = floatval($rango['valor_minimo']);
                            $max = floatval($rango['valor_maximo']);
                            
                            if ($valor < $min || $valor > $max) {
                                $en_alerta = true;
                            }
                            break;
                        }
                    }
                }
                
                $sensores[] = [
                    'tipo' => 'flujo',
                    'numero' => $i,
                    'valor' => $lectura ? round($lectura['valor'], 2) : null,
                    'unidad' => $lectura ? $lectura['unidad'] : 'L/min',
                    'timestamp' => $lectura ? $lectura['timestamp'] : null,
                    'en_alerta' => $en_alerta
                ];
            }
            
            $arduino['ultima_lectura'] = [
                'sensores' => $sensores,
                'timestamp' => $arduino['ultima_lectura']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'arduinos' => $arduinos
        ]);
        
    } catch(PDOException $e) {
        error_log("Error en GET arduinos: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener arduinos'
        ]);
    }
}

// POST - Crear nuevo arduino
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar datos
        if (empty($data['codigo_arduino']) || empty($data['nombre'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Código y nombre son requeridos'
            ]);
            exit;
        }
        
        $cantidad_presion = isset($data['cantidad_presion']) ? intval($data['cantidad_presion']) : 1;
        $cantidad_flujo = isset($data['cantidad_flujo']) ? intval($data['cantidad_flujo']) : 1;
        
        if ($cantidad_presion < 0 || $cantidad_presion > 10) {
            echo json_encode(['success' => false, 'message' => 'Cantidad de presión inválida']);
            exit;
        }
        
        if ($cantidad_flujo < 0 || $cantidad_flujo > 10) {
            echo json_encode(['success' => false, 'message' => 'Cantidad de flujo inválida']);
            exit;
        }
        
        if ($cantidad_presion == 0 && $cantidad_flujo == 0) {
            echo json_encode(['success' => false, 'message' => 'Debe tener al menos un sensor']);
            exit;
        }
        
        // Verificar que el código no exista
        $stmt = $db->prepare("SELECT id FROM arduinos WHERE codigo_arduino = ?");
        $stmt->execute([$data['codigo_arduino']]);
        if ($stmt->fetch()) {
            echo json_encode([
                'success' => false,
                'message' => 'El código de Arduino ya está registrado'
            ]);
            exit;
        }
        
        // Iniciar transacción
        $db->beginTransaction();
        
        // Insertar arduino
        $stmt = $db->prepare("
            INSERT INTO arduinos (user_id, codigo_arduino, nombre, ubicacion)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $user['id'],
            strtoupper(sanitize($data['codigo_arduino'])),
            sanitize($data['nombre']),
            sanitize($data['ubicacion'] ?? null)
        ]);
        
        $arduinoId = $db->lastInsertId();
        
        // Insertar configuración de sensores
        $stmt = $db->prepare("
            INSERT INTO configuracion_sensores 
            (arduino_id, cantidad_presion, cantidad_flujo)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$arduinoId, $cantidad_presion, $cantidad_flujo]);
        
        // Crear rangos de alerta por defecto para cada sensor
        for ($i = 1; $i <= $cantidad_presion; $i++) {
            $stmt = $db->prepare("
                INSERT INTO rangos_alertas (arduino_id, tipo_sensor, numero_sensor, valor_minimo, valor_maximo)
                VALUES (?, 'presion', ?, 0, 100)
            ");
            $stmt->execute([$arduinoId, $i]);
        }
        
        for ($i = 1; $i <= $cantidad_flujo; $i++) {
            $stmt = $db->prepare("
                INSERT INTO rangos_alertas (arduino_id, tipo_sensor, numero_sensor, valor_minimo, valor_maximo)
                VALUES (?, 'flujo', ?, 0, 50)
            ");
            $stmt->execute([$arduinoId, $i]);
        }
        
        $db->commit();
        
        logActivity($user['id'], $arduinoId, 'ARDUINO_CREADO', "Arduino {$data['codigo_arduino']} con {$cantidad_presion} presión y {$cantidad_flujo} flujo");
        
        echo json_encode([
            'success' => true,
            'message' => 'Arduino agregado exitosamente',
            'arduino_id' => $arduinoId
        ]);
        
    } catch(PDOException $e) {
        $db->rollBack();
        error_log("Error en POST arduinos: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error al agregar Arduino'
        ]);
    }
}
// DELETE - Eliminar arduino (soft delete)
elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    try {
        $arduinoId = $_GET['id'] ?? null;
        
        if (!$arduinoId) {
            echo json_encode(['success' => false, 'message' => 'ID requerido']);
            exit;
        }
        
        // Verificar que pertenece al usuario
        $stmt = $db->prepare("
            SELECT id FROM arduinos 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$arduinoId, $user['id']]);
        
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Arduino no encontrado']);
            exit;
        }
        
        // Soft delete
        $stmt = $db->prepare("UPDATE arduinos SET activo = 0 WHERE id = ?");
        $stmt->execute([$arduinoId]);
        
        logActivity($user['id'], $arduinoId, 'ARDUINO_ELIMINADO', 'Arduino desactivado');
        
        echo json_encode([
            'success' => true,
            'message' => 'Arduino eliminado exitosamente'
        ]);
        
    } catch(PDOException $e) {
        error_log("Error en DELETE arduinos: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error al eliminar Arduino'
        ]);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>