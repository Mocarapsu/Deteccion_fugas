<?php
require_once '../config.php';

// Permitir acceso sin autenticación (para el Arduino)
header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

// Recibir parámetros GET
$arduino_id = $_GET['arduino_id'] ?? null;
$flujo = $_GET['flujo'] ?? null;
$presion = $_GET['presion'] ?? null;

if (!$arduino_id) {
    echo json_encode(['success' => false, 'error' => 'Falta arduino_id']);
    exit;
}

try {
    // Verificar si el Arduino existe
    $stmt = $db->prepare("SELECT id FROM arduinos WHERE id = ?");
    $stmt->execute([$arduino_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Arduino no encontrado']);
        exit;
    }

    // Insertar lectura de Flujo (Sensor 1)
    if ($flujo !== null) {
        $stmt = $db->prepare("INSERT INTO lecturas (arduino_id, tipo_sensor, numero_sensor, valor, unidad) VALUES (?, 'flujo', 1, ?, 'L/min')");
        $stmt->execute([$arduino_id, $flujo]);
    }

    // Insertar lectura de Presión (Sensor 1)
    if ($presion !== null) {
        $stmt = $db->prepare("INSERT INTO lecturas (arduino_id, tipo_sensor, numero_sensor, valor, unidad) VALUES (?, 'presion', 1, ?, 'PSI')");
        $stmt->execute([$arduino_id, $presion]);
    }

    // =======================================================
    // 💡 CÓDIGO CORREGIDO: Actualiza la CACHÉ de valores y tiempo 
    //                     en una sola consulta.
    // =======================================================
    $stmt = $db->prepare("
        UPDATE arduinos 
        SET 
            ultima_lectura = NOW(), 
            ultima_presion = ?,
            ultima_flujo = ? 
        WHERE id = ?
    ");
    $stmt->execute([$presion, $flujo, $arduino_id]);

    echo json_encode(['success' => true, 'message' => 'Datos recibidos']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>