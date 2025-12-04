<?php
/**
 * api/simular_lectura.php
 * Endpoint para generar el PAYLOAD de lecturas simuladas.
 * * Uso: GET /api/simular_lectura.php?arduino_id=1[&fuga_tramo1=1&mag_tramo1=0.75]
 * NOTA: Este endpoint no inserta datos, solo genera el JSON para ser enviado
 * por el simulador 3D al endpoint de inserción real (lecturas.php).
 */
header("Access-Control-Allow-Origin: *");
require_once '../config.php';
require_once '../auth.php'; 

// Se requiere login para generar la simulación
Auth::requireLogin();

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

// ----------------------------------------------------
// 1. Función Central de Generación de Lecturas (Basada en Lógica de Fuga)
// ----------------------------------------------------

/**
 * Genera el payload de datos para ser enviado a api/lecturas.php.
 * Incorpora la lógica de fugas y los datos simulados de T/H/C.
 */
function generar_payload_simulacion(PDO $db, $arduinoId, $leaks) {
    // Obtener configuración de sensores y código del Arduino
    $stmt = $db->prepare("
        SELECT a.codigo_arduino, c.cantidad_presion, c.cantidad_flujo
        FROM arduinos a
        LEFT JOIN configuracion_sensores c ON a.id = c.arduino_id
        WHERE a.id = ? AND a.activo = 1
    ");
    // Se usa user_id del GET ya que este endpoint es llamado desde el front-end autenticado
    $stmt->execute([$arduinoId]); 
    $config = $stmt->fetch();

    if (!$config) {
        return ['error' => 'Arduino no encontrado o inactivo'];
    }

    $payload = [
        'codigo_arduino' => $config['codigo_arduino'],
    ];

    // Valores Base (Alineados a los de simulacion.html para consistencia)
    $baseValues = [
        'p1' => 5.0, // bar (usaremos PSI/14.5038 = bar en el simulador JS)
        'p2' => 4.5,
        'p3' => 4.0,
        'f1' => 100.0, // L/min
        'f2' => 100.0,
        'f3' => 100.0
    ];
    
    // --- Lógica de Fuga (Adaptada de simulacion.html y generalizada) ---
    // Usaremos los datos P1 y F1 del simulador para el sensor número 1,
    // que es el único que su endpoint maneja dinámicamente.
    
    $p1 = $baseValues['p1'];
    $f1 = $baseValues['f1'];

    // Simular el efecto combinado de las fugas en los sensores de entrada (P1/F1)
    if ($leaks['tramo1']['active']) {
        $mag = $leaks['tramo1']['magnitude'];
        $p1 *= (1 - (0.3 * $mag)); // Caída de Presión
        $f1 *= (1 - (0.4 * $mag)); // Caída de Flujo (Menor flujo neto en la tubería)
    }
    if ($leaks['tramo2']['active'] || $leaks['tramo3']['active']) {
        // Asumiendo que fugas más adelante también bajan P1/F1 ligeramente por demanda.
        $total_mag = ($leaks['tramo2']['active'] ? $leaks['tramo2']['magnitude'] : 0) + 
                     ($leaks['tramo3']['active'] ? $leaks['tramo3']['magnitude'] : 0);
        $p1 *= (1 - (0.1 * $total_mag));
        $f1 *= (1 - (0.15 * $total_mag));
    }
    
    $p1 = max(0, $p1);
    $f1 = max(0, $f1);
    
    // ----------------------------------------------------------------------
    // Generación de Datos
    // ----------------------------------------------------------------------

    // 1. Presión y Flujo (Sensores dinámicos)
    // Asignamos el valor P1/F1 simulado a todos los sensores si hay varios,
    // ya que su lógica de backend (lecturas.php) solo maneja Pn/Fn.
    
    // Su endpoint `lecturas.php` espera PSI, pero la simulación usa bar.
    // Factor de conversión: 1 bar = 14.5038 PSI.
    $psi_p1 = $p1 * 14.5038; 

    for ($i = 1; $i <= $config['cantidad_presion']; $i++) {
        $key = "presion" . $i;
        // Añadir ruido aleatorio de +/- 0.5 PSI al valor base
        $final_psi = round($psi_p1 + mt_rand(-50, 50) / 100, 2); 
        $payload[$key] = max(0, $final_psi);
    }

    for ($i = 1; $i <= $config['cantidad_flujo']; $i++) {
        $key = "flujo" . $i;
        // Añadir ruido aleatorio de +/- 2 L/min al valor base
        $final_lmin = round($f1 + mt_rand(-200, 200) / 100, 2); 
        $payload[$key] = max(0, $final_lmin);
    }
    
    // 2. Sensores T/H/C (Sensores fijos)
    // El endpoint actual NO maneja T/H/C, pero los añadimos al payload 
    // en caso de que se modifique `lecturas.php` más tarde.
    
    $temp_base = 25.0; 
    $curr_base = 0.5; 
    
    if ($leaks['tramo1']['active'] || $leaks['tramo2']['active'] || $leaks['tramo3']['active']) {
        // Simulación: Bomba trabajando más = Mayor T y Corriente
        $temp = round(mt_rand($temp_base * 1.05 * 10, $temp_base * 1.15 * 10) / 10, 1);
        $curr = round(mt_rand($curr_base * 1.3 * 1000, $curr_base * 2.0 * 1000) / 1000, 3);
    } else {
        // Normal
        $temp = round(mt_rand($temp_base * 0.95 * 10, $temp_base * 1.05 * 10) / 10, 1);
        $curr = round(mt_rand($curr_base * 0.9 * 1000, $curr_base * 1.1 * 1000) / 1000, 3);
    }
    
    // Asignamos a los campos fijos, aunque actualmente no se insertarán en DB
    $payload['temperatura1'] = $temp;
    $payload['corriente1'] = $curr;
    
    return $payload;
}

// ----------------------------------------------------
// 2. Ejecución de la API
// ----------------------------------------------------

$arduinoId = $_GET['arduino_id'] ?? null;

if (!$arduinoId || !is_numeric($arduinoId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de Arduino requerido']);
    exit();
}

// Recibir estado de fugas desde la URL
$leaks = [
    'tramo1' => [
        'active' => ($_GET['fuga_tramo1'] ?? 0) == 1, 
        'magnitude' => floatval($_GET['mag_tramo1'] ?? 0.0)
    ],
    'tramo2' => [
        'active' => ($_GET['fuga_tramo2'] ?? 0) == 1, 
        'magnitude' => floatval($_GET['mag_tramo2'] ?? 0.0)
    ],
    'tramo3' => [
        'active' => ($_GET['fuga_tramo3'] ?? 0) == 1, 
        'magnitude' => floatval($_GET['mag_tramo3'] ?? 0.0)
    ]
];

$payload = generar_payload_simulacion($db, $arduinoId, $leaks);

if (isset($payload['error'])) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => $payload['error']]);
    exit();
}

$user = Auth::getCurrentUser();
logActivity($user['id'], $arduinoId, 'SIMULACION_GENERADA', "Payload simulado. Fugando: " . ($leaks['tramo1']['active'] || $leaks['tramo2']['active'] || $leaks['tramo3']['active'] ? 'Sí' : 'No'));

echo json_encode([
    'success' => true,
    'message' => "Payload generado. Envíalo a /api/lecturas.php",
    'payload' => $payload
]);
?>