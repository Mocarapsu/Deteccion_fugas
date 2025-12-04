<?php
header("Access-Control-Allow-Origin: *");
require_once '../config.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

// POST - Recibir lectura desde Arduino
// POST - Recibir lectura desde Arduino
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Validar datos requeridos
        if (empty($data['codigo_arduino'])) {
            echo json_encode([
                'success' => false,
                'error' => 'Código de Arduino requerido',
                'code' => 400
            ]);
            exit;
        }
        
        // Buscar Arduino por código
        $stmt = $db->prepare("
            SELECT a.id, a.user_id, a.activo,
                   c.cantidad_presion, c.cantidad_flujo
            FROM arduinos a
            LEFT JOIN configuracion_sensores c ON a.id = c.arduino_id
            WHERE a.codigo_arduino = ?
        ");
        $stmt->execute([$data['codigo_arduino']]);
        $arduino = $stmt->fetch();
        
        if (!$arduino) {
            echo json_encode([
                'success' => false,
                'error' => 'Arduino no encontrado',
                'code' => 404
            ]);
            exit;
        }
        
        if (!$arduino['activo']) {
            echo json_encode([
                'success' => false,
                'error' => 'Arduino inactivo',
                'code' => 403
            ]);
            exit;
        }
        
        $lecturas_insertadas = 0;
        
        // Procesar sensores de presión
        for ($i = 1; $i <= $arduino['cantidad_presion']; $i++) {
            $key = "presion" . $i;
            if (isset($data[$key])) {
                $valor = floatval($data[$key]);
                
                // Validar rango razonable (0 a 1000 PSI)
                if ($valor >= 0 && $valor <= 1000) {
                    $stmt = $db->prepare("
                        INSERT INTO lecturas (arduino_id, tipo_sensor, numero_sensor, valor, unidad)
                        VALUES (?, 'presion', ?, ?, 'PSI')
                    ");
                    $stmt->execute([$arduino['id'], $i, $valor]);
                    $lecturas_insertadas++;
                }
            }
        }
        
        // Procesar sensores de flujo
        for ($i = 1; $i <= $arduino['cantidad_flujo']; $i++) {
            $key = "flujo" . $i;
            if (isset($data[$key])) {
                $valor = floatval($data[$key]);
                
                // Validar rango razonable (0 a 500 L/min)
                if ($valor >= 0 && $valor <= 500) {
                    $stmt = $db->prepare("
                        INSERT INTO lecturas (arduino_id, tipo_sensor, numero_sensor, valor, unidad)
                        VALUES (?, 'flujo', ?, ?, 'L/min')
                    ");
                    $stmt->execute([$arduino['id'], $i, $valor]);
                    $lecturas_insertadas++;
                }
            }
        }
        
        if ($lecturas_insertadas > 0) {
            // Actualizar timestamp de última lectura del Arduino
            $stmt = $db->prepare("
                UPDATE arduinos 
                SET ultima_lectura = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$arduino['id']]);
            
            // Log de actividad
            logActivity($arduino['user_id'], $arduino['id'], 'LECTURA_RECIBIDA', 
                "{$lecturas_insertadas} lecturas registradas");
            
            echo json_encode([
                'success' => true,
                'message' => 'Lecturas registradas correctamente',
                'arduino_id' => $arduino['id'],
                'lecturas_insertadas' => $lecturas_insertadas,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'No se recibieron lecturas válidas',
                'code' => 400
            ]);
        }
        
    } catch(PDOException $e) {
        error_log("Error en POST lecturas: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Error al registrar lectura',
            'code' => 500
        ]);
    }
}
// GET - Obtener lecturas de un sensor específico
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Debe estar autenticado para GET
        require_once '../auth.php';
        Auth::requireLogin();
        $user = Auth::getCurrentUser();
        
        $arduinoId = $_GET['arduino_id'] ?? null;
        $tipoSensor = $_GET['tipo_sensor'] ?? null;
        $numeroSensor = $_GET['numero_sensor'] ?? null;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
        $timeRange = $_GET['time_range'] ?? '24h';
        
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
        
        // Calcular fecha de inicio según time_range
        $timeCondition = "";
        switch($timeRange) {
            case '1h':
                $timeCondition = "AND timestamp >= NOW() - INTERVAL 1 HOUR";
                break;
            case '24h':
                $timeCondition = "AND timestamp >= NOW() - INTERVAL 24 HOUR";
                break;
            case '7d':
                $timeCondition = "AND timestamp >= NOW() - INTERVAL 7 DAY";
                break;
            case '30d':
                $timeCondition = "AND timestamp >= NOW() - INTERVAL 30 DAY";
                break;
        }
        
        // Obtener lecturas del sensor específico
        $stmt = $db->prepare("
            SELECT id, valor, unidad, timestamp
            FROM lecturas
            WHERE arduino_id = ? 
              AND tipo_sensor = ? 
              AND numero_sensor = ?
              {$timeCondition}
            ORDER BY timestamp DESC
            LIMIT ?
        ");
        $stmt->execute([$arduinoId, $tipoSensor, $numeroSensor, $limit]);
        $lecturas = $stmt->fetchAll();
        
        // Calcular estadísticas
        $stats = [
            'min' => null,
            'max' => null,
            'avg' => null
        ];
        
        if (count($lecturas) > 0) {
            $stmt = $db->prepare("
                SELECT 
                    MIN(valor) as min_val,
                    MAX(valor) as max_val,
                    AVG(valor) as avg_val
                FROM lecturas
                WHERE arduino_id = ? 
                  AND tipo_sensor = ? 
                  AND numero_sensor = ?
                  {$timeCondition}
            ");
            $stmt->execute([$arduinoId, $tipoSensor, $numeroSensor]);
            $statsData = $stmt->fetch();
            
            $stats = [
                'min' => round($statsData['min_val'], 2),
                'max' => round($statsData['max_val'], 2),
                'avg' => round($statsData['avg_val'], 2)
            ];
        }
        
        echo json_encode([
            'success' => true,
            'lecturas' => $lecturas,
            'stats' => $stats,
            'total' => count($lecturas)
        ]);
        
    } catch(PDOException $e) {
        error_log("Error en GET lecturas: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener lecturas'
        ]);
    }
}
// GET - Obtener lecturas de un Arduino
elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Debe estar autenticado para GET
        require_once '../auth.php';
        Auth::requireLogin();
        $user = Auth::getCurrentUser();
        
        $arduinoId = $_GET['arduino_id'] ?? null;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
        $timeRange = $_GET['time_range'] ?? '24h';
        
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
        
        // Calcular fecha de inicio según time_range
        $timeCondition = "";
        switch($timeRange) {
            case '1h':
                $timeCondition = "AND timestamp >= NOW() - INTERVAL 1 HOUR";
                break;
            case '24h':
                $timeCondition = "AND timestamp >= NOW() - INTERVAL 24 HOUR";
                break;
            case '7d':
                $timeCondition = "AND timestamp >= NOW() - INTERVAL 7 DAY";
                break;
            case '30d':
                $timeCondition = "AND timestamp >= NOW() - INTERVAL 30 DAY";
                break;
        }
        
        // Obtener lecturas
        $stmt = $db->prepare("
            SELECT id, temperatura, humedad, corriente, timestamp
            FROM lecturas
            WHERE arduino_id = ? {$timeCondition}
            ORDER BY timestamp DESC
            LIMIT ?
        ");
        $stmt->execute([$arduinoId, $limit]);
        $lecturas = $stmt->fetchAll();
        
        // Calcular estadísticas
        $stats = [
            'temperatura' => ['min' => null, 'max' => null, 'avg' => null],
            'humedad' => ['min' => null, 'max' => null, 'avg' => null],
            'corriente' => ['min' => null, 'max' => null, 'avg' => null]
        ];
        
        if (count($lecturas) > 0) {
            $stmt = $db->prepare("
                SELECT 
                    MIN(temperatura) as temp_min, MAX(temperatura) as temp_max, AVG(temperatura) as temp_avg,
                    MIN(humedad) as hum_min, MAX(humedad) as hum_max, AVG(humedad) as hum_avg,
                    MIN(corriente) as curr_min, MAX(corriente) as curr_max, AVG(corriente) as curr_avg
                FROM lecturas
                WHERE arduino_id = ? {$timeCondition}
            ");
            $stmt->execute([$arduinoId]);
            $statsData = $stmt->fetch();
            
            $stats = [
                'temperatura' => [
                    'min' => round($statsData['temp_min'], 2),
                    'max' => round($statsData['temp_max'], 2),
                    'avg' => round($statsData['temp_avg'], 2)
                ],
                'humedad' => [
                    'min' => round($statsData['hum_min'], 2),
                    'max' => round($statsData['hum_max'], 2),
                    'avg' => round($statsData['hum_avg'], 2)
                ],
                'corriente' => [
                    'min' => round($statsData['curr_min'], 3),
                    'max' => round($statsData['curr_max'], 3),
                    'avg' => round($statsData['curr_avg'], 3)
                ]
            ];
        }
        
        echo json_encode([
            'success' => true,
            'lecturas' => $lecturas,
            'stats' => $stats,
            'total' => count($lecturas)
        ]);
        
    } catch(PDOException $e) {
        error_log("Error en GET lecturas: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener lecturas'
        ]);
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>