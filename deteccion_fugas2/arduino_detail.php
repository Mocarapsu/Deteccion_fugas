    <?php
    require_once 'config.php';
    require_once 'auth.php';

    Auth::requireLogin();
    $user = Auth::getCurrentUser();

    $arduinoId = $_GET['id'] ?? null;

    if (!$arduinoId) {
        header("Location: dashboard.php");
        exit();
    }

    // Verificar que el Arduino pertenece al usuario
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT a.*, c.cantidad_presion, c.cantidad_flujo
        FROM arduinos a
        LEFT JOIN configuracion_sensores c ON a.id = c.arduino_id
        WHERE a.id = ? AND a.user_id = ? AND a.activo = 1
    ");
    $stmt->execute([$arduinoId, $user['id']]);
    $arduino = $stmt->fetch();

    if (!$arduino) {
        header("Location: dashboard.php");
        exit();
    }

    // Obtener rangos de alerta
    $stmt = $db->prepare("
        SELECT tipo_sensor, numero_sensor, valor_minimo, valor_maximo
        FROM rangos_alertas
        WHERE arduino_id = ?
        ORDER BY tipo_sensor, numero_sensor
    ");
    $stmt->execute([$arduinoId]);
    $rangos = $stmt->fetchAll();
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($arduino['nombre']); ?> - Arduino Detail Premium</title>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            @keyframes gradient-shift {
                0% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }

            @keyframes pulse-border {
                0%, 100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.8); }
                50% { box-shadow: 0 0 0 20px rgba(239, 68, 68, 0); }
            }

            @keyframes blink {
                0%, 50%, 100% { opacity: 1; }
                25%, 75% { opacity: 0.4; }
            }
            
            body {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
                background: linear-gradient(-45deg, #0a0a1a, #1a1a2e, #16213e, #0f3460);
                background-size: 400% 400%;
                animation: gradient-shift 20s ease infinite;
                color: #e0e0e0;
                min-height: 100vh;
            }
            
            .navbar {
                background: rgba(10, 10, 26, 0.95);
                backdrop-filter: blur(20px);
                color: white;
                padding: 1.2rem 2rem;
                display: flex;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 4px 20px rgba(0,0,0,0.5);
                border-bottom: 1px solid rgba(99, 102, 241, 0.2);
                position: sticky;
                top: 0;
                z-index: 100;
            }
            
            .navbar h1 {
                font-size: 1.5rem;
                font-weight: 800;
                background: linear-gradient(135deg, #ffffff 0%, #a78bfa 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            
            .btn-back {
                background: rgba(99, 102, 241, 0.2);
                color: white;
                border: 1px solid rgba(99, 102, 241, 0.4);
                padding: 10px 20px;
                border-radius: 10px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                transition: all 0.3s;
                font-weight: 600;
            }
            
            .btn-back:hover {
                background: rgba(99, 102, 241, 0.3);
                transform: translateY(-2px);
            }
            
            .container {
                max-width: 1400px;
                margin: 0 auto;
                padding: 2.5rem 2rem;
            }
            
            .arduino-header-card {
                background: rgba(255, 255, 255, 0.05);
                backdrop-filter: blur(20px);
                border-radius: 20px;
                padding: 2rem;
                box-shadow: 0 8px 32px rgba(0,0,0,0.3);
                margin-bottom: 2.5rem;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .arduino-header-card h2 {
                color: white;
                margin-bottom: 1.5rem;
                font-size: 2rem;
                font-weight: 800;
            }

            .simulator-buttons {
                display: flex;
                gap: 1rem;
                margin-bottom: 1.5rem;
                flex-wrap: wrap;
            }

            .btn-simulator {
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 12px;
                cursor: pointer;
                font-size: 0.95rem;
                font-weight: 700;
                transition: all 0.3s;
                box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
                text-decoration: none;
                display: inline-block;
            }

            .btn-simulator:hover {
                transform: translateY(-3px);
                box-shadow: 0 12px 30px rgba(16, 185, 129, 0.6);
            }

            .btn-simulator.secondary {
                background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
                box-shadow: 0 8px 20px rgba(107, 114, 128, 0.4);
            }

            .btn-simulator.secondary:hover {
                box-shadow: 0 12px 30px rgba(107, 114, 128, 0.6);
            }
            
            .arduino-info {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1.2rem;
            }
            
            .info-item {
                padding: 1.2rem;
                background: rgba(255, 255, 255, 0.03);
                border-radius: 12px;
                border: 1px solid rgba(255, 255, 255, 0.1);
                transition: all 0.3s;
            }

            .info-item:hover {
                background: rgba(255, 255, 255, 0.05);
                border-color: rgba(99, 102, 241, 0.3);
            }
            
            .info-label {
                font-size: 0.85rem;
                color: rgba(255, 255, 255, 0.6);
                margin-bottom: 0.5rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-weight: 600;
            }
            
            .info-value {
                font-size: 1.2rem;
                font-weight: 700;
                color: white;
            }
            
            .sensors-container {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(480px, 1fr));
                gap: 2rem;
            }
            
            .sensor-card {
                background: rgba(255, 255, 255, 0.05);
                backdrop-filter: blur(20px);
                border-radius: 20px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.3);
                overflow: hidden;
                transition: all 0.4s;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .sensor-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 12px 40px rgba(99, 102, 241, 0.3);
            }
            
            .sensor-card.alert {
                border: 2px solid #ef4444;
                animation: pulse-border 2s infinite;
            }
            
            .sensor-header {
                padding: 1.5rem;
                background: linear-gradient(135deg, rgba(99, 102, 241, 0.3) 0%, rgba(168, 85, 247, 0.3) 100%);
                color: white;
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .sensor-header.presion {
                background: linear-gradient(135deg, rgba(99, 102, 241, 0.3) 0%, rgba(168, 85, 247, 0.3) 100%);
            }
            
            .sensor-header.flujo {
                background: linear-gradient(135deg, rgba(16, 185, 129, 0.3) 0%, rgba(5, 150, 105, 0.3) 100%);
            }
            
            .sensor-header.alert {
                background: linear-gradient(135deg, rgba(239, 68, 68, 0.4) 0%, rgba(220, 38, 38, 0.4) 100%);
            }
            
            .sensor-title {
                display: flex;
                align-items: center;
                gap: 0.8rem;
                font-size: 1.3rem;
                font-weight: 700;
            }
            
            .alert-badge {
                background: rgba(239, 68, 68, 0.9);
                color: white;
                padding: 6px 14px;
                border-radius: 20px;
                font-size: 0.85rem;
                font-weight: 700;
                animation: blink 1.5s infinite;
            }
            
            .sensor-body {
                padding: 1.8rem;
            }
            
            .current-value {
                text-align: center;
                padding: 2.5rem 1.5rem;
                background: rgba(255, 255, 255, 0.03);
                border-radius: 16px;
                margin-bottom: 1.5rem;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .current-value.alert {
                background: rgba(239, 68, 68, 0.15);
                border: 2px solid #ef4444;
            }
            
            .value-display {
                font-size: 3.5rem;
                font-weight: 900;
                background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            
            .value-display.alert {
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
            
            .value-unit {
                font-size: 1.3rem;
                color: rgba(255, 255, 255, 0.6);
                margin-left: 0.5rem;
            }
            
            .value-label {
                margin-top: 0.8rem;
                color: rgba(255, 255, 255, 0.7);
                font-size: 0.95rem;
                font-weight: 500;
            }
            
            .stats-row {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 1rem;
                margin-bottom: 1.5rem;
            }
            
            .stat-box {
                text-align: center;
                padding: 1.2rem;
                background: rgba(255, 255, 255, 0.03);
                border-radius: 12px;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .stat-label {
                font-size: 0.85rem;
                color: rgba(255, 255, 255, 0.6);
                margin-bottom: 0.5rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .stat-value {
                font-size: 1.4rem;
                font-weight: 800;
                color: #e0e0e0;
            }
            
            .chart-container {
                position: relative;
                height: 280px;
                margin-bottom: 1.5rem;
                background: rgba(0, 0, 0, 0.2);
                border-radius: 12px;
                padding: 1rem;
            }
            
            .controls {
                display: flex;
                gap: 0.8rem;
                justify-content: center;
                margin-bottom: 1.5rem;
                flex-wrap: wrap;
            }
            
            .time-btn {
                padding: 10px 20px;
                border: 1.5px solid rgba(255, 255, 255, 0.2);
                background: rgba(255, 255, 255, 0.05);
                color: rgba(255, 255, 255, 0.8);
                border-radius: 10px;
                cursor: pointer;
                transition: all 0.3s;
                font-weight: 600;
            }
            
            .time-btn.active {
                background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
                color: white;
                border-color: transparent;
            }
            
            .time-btn:hover {
                border-color: rgba(99, 102, 241, 0.6);
                background: rgba(99, 102, 241, 0.2);
            }
            
            .range-config {
                background: rgba(251, 191, 36, 0.15);
                padding: 1.5rem;
                border-radius: 12px;
                margin-top: 1rem;
                border-left: 4px solid #fbbf24;
            }
            
            .range-config h4 {
                margin-bottom: 1rem;
                color: #fbbf24;
                font-weight: 700;
            }
            
            .range-inputs {
                display: flex;
                gap: 1rem;
                align-items: center;
                flex-wrap: wrap;
            }

            .range-inputs label {
                color: rgba(255, 255, 255, 0.9);
                font-weight: 600;
            }
            
            .range-inputs input {
                width: 110px;
                padding: 10px 14px;
                border: 1.5px solid rgba(255, 255, 255, 0.2);
                background: rgba(255, 255, 255, 0.05);
                color: white;
                border-radius: 10px;
                font-size: 0.95rem;
                transition: all 0.3s;
            }

            .range-inputs input:focus {
                outline: none;
                border-color: rgba(99, 102, 241, 0.6);
                background: rgba(255, 255, 255, 0.08);
            }
            
            .btn-save {
                padding: 10px 24px;
                background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
                color: white;
                border: none;
                border-radius: 10px;
                cursor: pointer;
                font-weight: 700;
                transition: all 0.3s;
                box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
            }
            
            .btn-save:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(99, 102, 241, 0.6);
            }
            
            .loading {
                text-align: center;
                padding: 3rem;
                color: rgba(255, 255, 255, 0.6);
                font-size: 1.1rem;
            }
            
            @media (max-width: 900px) {
                .sensors-container {
                    grid-template-columns: 1fr;
                }
                
                .stats-row {
                    grid-template-columns: 1fr;
                }
                
                .chart-container {
                    height: 220px;
                }
            }
        </style>
    </head>
    <body>
        <nav class="navbar">
            <h1>💧 <?php echo htmlspecialchars($arduino['nombre']); ?></h1>
            <a href="dashboard.php" class="btn-back">← Volver al Dashboard</a>
        </nav>
        
        <div class="container">
            <div class="arduino-header-card">
                <h2><?php echo htmlspecialchars($arduino['nombre']); ?></h2>
                
                <div class="simulator-buttons">
                    <a href="simulacion.html?arduino_id=<?php echo $arduinoId; ?>" class="btn-simulator">
                        🎮 Abrir Simulador con este Arduino
                    </a>
                    <a href="simulacion.html" class="btn-simulator secondary">
                        🎮 Simulador Genérico
                    </a>
                </div>

                <div class="arduino-info">
                    <div class="info-item">
                        <div class="info-label">Código</div>
                        <div class="info-value"><?php echo htmlspecialchars($arduino['codigo_arduino']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Ubicación</div>
                        <div class="info-value"><?php echo htmlspecialchars($arduino['ubicacion'] ?? 'No especificada'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Sensores de Presión</div>
                        <div class="info-value"><?php echo $arduino['cantidad_presion']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Sensores de Flujo</div>
                        <div class="info-value"><?php echo $arduino['cantidad_flujo']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Última Lectura</div>
                        <div class="info-value" id="ultimaLectura">--:--</div>
                    </div>
                </div>
            </div>
            
            <div class="sensors-container" id="sensorsContainer">
                <div class="loading">Cargando sensores...</div>
            </div>
        </div>
        
        <script>
            const arduinoId = <?php echo $arduinoId; ?>;
            const cantidadPresion = <?php echo $arduino['cantidad_presion']; ?>;
            const cantidadFlujo = <?php echo $arduino['cantidad_flujo']; ?>;
            let charts = {};
            let autoRefreshInterval;
            let rangosAlertas = <?php echo json_encode($rangos); ?>;
            let sensorsCreated = false;
            
            // Cargar datos al iniciar
            document.addEventListener('DOMContentLoaded', function() {
                loadAllSensors();
                startAutoRefresh();
            });
            
            function startAutoRefresh() {
                autoRefreshInterval = setInterval(loadAllSensors, 2000);
            }
            
            function loadAllSensors() {
                if (!sensorsCreated) {
                    const container = document.getElementById('sensorsContainer');
                    container.innerHTML = '';
                    
                    // Cargar sensores de presión
                    for (let i = 1; i <= cantidadPresion; i++) {
                        createSensorCard('presion', i, container);
                    }
                    
                    // Cargar sensores de flujo
                    for (let i = 1; i <= cantidadFlujo; i++) {
                        createSensorCard('flujo', i, container);
                    }

                    if (cantidadPresion === 0 && cantidadFlujo === 0) {
                        container.innerHTML = '<div class="loading">No hay sensores configurados para este Arduino.</div>';
                    }
                    
                    sensorsCreated = true;
                }
                
                for (let i = 1; i <= cantidadPresion; i++) {
                    updateSensorData('presion', i);
                }
                
                for (let i = 1; i <= cantidadFlujo; i++) {
                    updateSensorData('flujo', i);
                }
            }
            
            function updateSensorData(tipo, numero) {
                fetch(`api/lecturas.php?arduino_id=${arduinoId}&tipo_sensor=${tipo}&numero_sensor=${numero}&time_range=1h&limit=1`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const cardId = `sensor-${tipo}-${numero}`;
                            const rango = rangosAlertas.find(r => r.tipo_sensor === tipo && r.numero_sensor == numero);
                            
                            // Actualizar valor actual (tomar la primera lectura)
                            let valorActual = null;
                            if (data.lecturas && data.lecturas.length > 0) {
                                valorActual = parseFloat(data.lecturas[0].valor);
                            }
                            
                            document.getElementById(`${cardId}-value`).textContent = valorActual !== null ? valorActual.toFixed(2) : '--';
                            
                            // Verificar alerta
                            const enAlerta = rango && valorActual !== null && 
                                            (valorActual < rango.valor_minimo || valorActual > rango.valor_maximo);
                            
                            // Actualizar clases de alerta
                            const card = document.getElementById(cardId);
                            const header = card.querySelector('.sensor-header');
                            const currentValue = document.getElementById(`${cardId}-current`);
                            const alertBadge = document.getElementById(`${cardId}-alert`);
                            const valueDisplay = document.getElementById(`${cardId}-value`);
                            
                            if (enAlerta) {
                                card.classList.add('alert');
                                header.classList.add('alert');
                                currentValue.classList.add('alert');
                                valueDisplay.classList.add('alert');
                                alertBadge.style.display = 'block';
                            } else {
                                card.classList.remove('alert');
                                header.classList.remove('alert');
                                currentValue.classList.remove('alert');
                                valueDisplay.classList.remove('alert');
                                alertBadge.style.display = 'none';
                            }
                            
                            // Actualizar estadísticas
                            document.getElementById(`${cardId}-min`).textContent = data.stats.min !== null ? data.stats.min : '--';
                            document.getElementById(`${cardId}-avg`).textContent = data.stats.avg !== null ? data.stats.avg : '--';
                            document.getElementById(`${cardId}-max`).textContent = data.stats.max !== null ? data.stats.max : '--';
                            
                            // Actualizar última lectura
                            document.getElementById('ultimaLectura').textContent = new Date().toLocaleTimeString();
                            
                            // NO actualizar la gráfica aquí - solo cuando se hace clic en los botones de tiempo
                        }
                    })
                    .catch(error => console.error('Error cargando sensor:', error));
            }
            
            function createSensorCard(tipo, numero, container) {
                const cardId = `sensor-${tipo}-${numero}`;
                const icon = tipo === 'presion' ? '📊' : '💧';
                const unidad = tipo === 'presion' ? 'PSI' : 'L/min';
                const tipoLabel = tipo === 'presion' ? 'Presión' : 'Flujo';
                
                // Obtener rangos de alerta
                const rango = rangosAlertas.find(r => r.tipo_sensor === tipo && r.numero_sensor == numero);
                
                const cardHTML = `
                    <div class="sensor-card" id="${cardId}">
                        <div class="sensor-header ${tipo}">
                            <div class="sensor-title">
                                <span>${icon}</span>
                                <span>${tipoLabel} ${numero}</span>
                            </div>
                            <span class="alert-badge" id="${cardId}-alert" style="display: none;">⚠️ ALERTA</span>
                        </div>
                        <div class="sensor-body">
                            <div class="current-value" id="${cardId}-current">
                                <div class="value-display" id="${cardId}-value">--</div>
                                <div class="value-unit">${unidad}</div>
                                <div class="value-label">Valor Actual</div>
                            </div>
                            
                            <div class="stats-row">
                                <div class="stat-box">
                                    <div class="stat-label">Mínimo</div>
                                    <div class="stat-value" id="${cardId}-min">--</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-label">Promedio</div>
                                    <div class="stat-value" id="${cardId}-avg">--</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-label">Máximo</div>
                                    <div class="stat-value" id="${cardId}-max">--</div>
                                </div>
                            </div>
                            
                            <div class="controls">
                                <button class="time-btn active" onclick="loadSensorData('${tipo}', ${numero}, '1h', this)">1h</button>
                                <button class="time-btn" onclick="loadSensorData('${tipo}', ${numero}, '24h', this)">24h</button>
                                <button class="time-btn" onclick="loadSensorData('${tipo}', ${numero}, '7d', this)">7d</button>
                                <button class="time-btn" onclick="loadSensorData('${tipo}', ${numero}, '30d', this)">30d</button>
                            </div>
                            
                            <div class="chart-container">
                                <canvas id="${cardId}-chart"></canvas>
                            </div>
                            
                            <div class="range-config">
                                <h4>🔔 Configurar Alertas</h4>
                                <div class="range-inputs">
                                    <label>Mínimo:
                                        <input type="number" step="0.1" id="${cardId}-min-input" 
                                            value="${rango ? parseFloat(rango.valor_minimo) : 0}" />
                                    </label>
                                    <label>Máximo:
                                        <input type="number" step="0.1" id="${cardId}-max-input" 
                                            value="${rango ? parseFloat(rango.valor_maximo) : 100}" />
                                    </label>
                                    <button class="btn-save" onclick="saveRange('${tipo}', ${numero})">Guardar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', cardHTML);
                
                // Cargar datos del sensor
                loadSensorData(tipo, numero, '1h');
            }
            
            function loadSensorData(tipo, numero, timeRange, button) {
                const cardId = `sensor-${tipo}-${numero}`;
                const unidad = tipo === 'presion' ? 'PSI' : 'L/min';
                
                console.log(`🔄 Loading sensor data: ${tipo} ${numero}, timeRange: ${timeRange}`);
                
                // Actualizar botones activos
                if (button) {
                    const card = document.getElementById(cardId);
                    card.querySelectorAll('.time-btn').forEach(btn => btn.classList.remove('active'));
                    button.classList.add('active');
                }
                
                fetch(`api/lecturas.php?arduino_id=${arduinoId}&tipo_sensor=${tipo}&numero_sensor=${numero}&time_range=${timeRange}&limit=500`)
                    .then(response => response.json())
                    .then(data => {
                        console.log(`✅ API Response for ${cardId}:`, data);
                        if (data.success) {
                            console.log(`   - Lecturas recibidas: ${data.lecturas?.length || 0}`);
                            updateSensorDisplay(cardId, data, unidad, tipo, numero);
                            updateChart(cardId, data.lecturas, tipo, unidad);
                        } else {
                            console.error('❌ API Error:', data.message);
                            // Display error to user, e.g., on the card
                            const card = document.getElementById(cardId);
                            card.querySelector('.sensor-body').innerHTML = `<div style="color: red; text-align: center;">Error al cargar datos: ${data.message}</div>`;
                        }
                    })
                    .catch(error => {
                        console.error('💥 Fetch Error:', error);
                        const card = document.getElementById(cardId);
                        card.querySelector('.sensor-body').innerHTML = `<div style="color: red; text-align: center;">Error de red o servidor.</div>`;
                    });
            }
            
            function updateSensorDisplay(cardId, data, unidad, tipo, numero) {
                const stats = data.stats;
                const lecturas = data.lecturas;
                
                // Obtener rango de alerta
                const rango = rangosAlertas.find(r => r.tipo_sensor === tipo && r.numero_sensor == numero);
                
                // Valor actual
                if (lecturas.length > 0) {
                    const valorActual = parseFloat(lecturas[0].valor); // Ensure it's a number
                    document.getElementById(`${cardId}-value`).textContent = valorActual.toFixed(2); // Format to 2 decimal places
                    
                    // Verificar si está en alerta
                    let enAlerta = false;
                    if (rango) {
                        const minVal = parseFloat(rango.valor_minimo);
                        const maxVal = parseFloat(rango.valor_maximo);
                        if (valorActual < minVal || valorActual > maxVal) {
                            enAlerta = true;
                        }
                    }
                    
                    // Aplicar estilos de alerta
                    const card = document.getElementById(cardId);
                    const header = card.querySelector('.sensor-header');
                    const currentValue = document.getElementById(`${cardId}-current`);
                    const valueDisplay = document.getElementById(`${cardId}-value`);
                    const alertBadge = document.getElementById(`${cardId}-alert`);
                    
                    if (enAlerta) {
                        card.classList.add('alert');
                        header.classList.add('alert');
                        currentValue.classList.add('alert');
                        valueDisplay.classList.add('alert');
                        alertBadge.style.display = 'block';
                    } else {
                        card.classList.remove('alert');
                        header.classList.remove('alert');
                        currentValue.classList.remove('alert');
                        valueDisplay.classList.remove('alert');
                        alertBadge.style.display = 'none';
                    }
                    
                    document.getElementById('ultimaLectura').textContent = formatTimestamp(lecturas[0].timestamp);
                } else {
                    // Handle case where there are no readings
                    document.getElementById(`${cardId}-value`).textContent = '--';
                    document.getElementById('ultimaLectura').textContent = 'Sin lecturas';
                    const card = document.getElementById(cardId);
                    card.classList.remove('alert');
                    card.querySelector('.sensor-header').classList.remove('alert');
                    card.querySelector('.current-value').classList.remove('alert');
                    card.querySelector('.value-display').classList.remove('alert');
                    document.getElementById(`${cardId}-alert`).style.display = 'none';
                }
                
                // Estadísticas
                document.getElementById(`${cardId}-min`).textContent = stats.min !== null ? parseFloat(stats.min).toFixed(2) + ' ' + unidad : '--';
                document.getElementById(`${cardId}-avg`).textContent = stats.avg !== null ? parseFloat(stats.avg).toFixed(2) + ' ' + unidad : '--';
                document.getElementById(`${cardId}-max`).textContent = stats.max !== null ? parseFloat(stats.max).toFixed(2) + ' ' + unidad : '--';
            }
            
            function updateChart(cardId, lecturas, tipo, unidad) {
                console.log('📊 updateChart called:', { cardId, lecturasCount: lecturas?.length, tipo, unidad });
                
                if (!lecturas || lecturas.length === 0) {
                    console.warn('⚠️ No hay lecturas para la gráfica:', cardId);
                    return;
                }
                
                const ctx = document.getElementById(`${cardId}-chart`).getContext('2d');
                
                // Destruir gráfico existente
                if (charts[cardId]) {
                    charts[cardId].destroy();
                }
                
                // Preparar datos (usar slice para no mutar el array original)
                const dataPoints = lecturas.slice().reverse().map(l => ({
                    x: new Date(l.timestamp),
                    y: parseFloat(l.valor)
                }));
                
                console.log('📈 Data points:', dataPoints.slice(0, 3)); // Mostrar primeros 3 puntos
                
                const color = tipo === 'presion' ? 'rgb(99, 102, 241)' : 'rgb(16, 185, 129)';
                
                
                // Crear nuevo gráfico
                charts[cardId] = new Chart(ctx, {
                    type: 'line',
                    data: {
                        datasets: [{
                            label: `${tipo === 'presion' ? 'Presión' : 'Flujo'} (${unidad})`,
                            data: dataPoints,
                            borderColor: color,
                            backgroundColor: color.replace(')', ', 0.1)').replace('rgb', 'rgba'),
                            tension: 0.4,
                            fill: true,
                            pointRadius: 2,
                            pointHoverRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                type: 'time',
                                time: {
                                    unit: 'hour',
                                    tooltipFormat: 'PPpp', // Format for tooltip (e.g., 'Sep 26, 2023, 10:30 AM')
                                    displayFormats: {
                                        hour: 'HH:mm',
                                        minute: 'HH:mm'
                                    }
                                },
                                ticks: { 
                                    color: 'rgba(255,255,255,0.7)',
                                    maxTicksLimit: 10 // Limit the number of x-axis labels
                                },
                                grid: { 
                                    color: 'rgba(255,255,255,0.1)' 
                                }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { 
                                    color: 'rgba(255,255,255,0.7)',
                                    callback: function(value, index, values) {
                                        // Format y-axis values if they are numbers
                                        if (typeof value === 'number') {
                                            return value.toFixed(2);
                                        }
                                        return value;
                                    }
                                },
                                grid: { 
                                    color: 'rgba(255,255,255,0.1)' 
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false,
                                callbacks: {
                                    label: function(context) {
                                        let label = context.dataset.label || '';
                                        if (label) {
                                            label += ': ';
                                        }
                                        if (context.parsed.y !== null) {
                                            label += context.parsed.y.toFixed(2);
                                        }
                                        return label;
                                    }
                                }
                            }
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                });
            }
            
            function saveRange(tipo, numero) {
                const cardId = `sensor-${tipo}-${numero}`;
                const minValueInput = document.getElementById(`${cardId}-min-input`);
                const maxValueInput = document.getElementById(`${cardId}-max-input`);
                const minValue = minValueInput.value;
                const maxValue = maxValueInput.value;
                
                if (parseFloat(minValue) >= parseFloat(maxValue)) {
                    alert('Error: El valor mínimo debe ser menor que el valor máximo.');
                    return;
                }

                fetch('api/rangos.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        arduino_id: arduinoId,
                        tipo_sensor: tipo,
                        numero_sensor: numero,
                        valor_minimo: parseFloat(minValue),
                        valor_maximo: parseFloat(maxValue)
                    })
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert('✓ Rango de alerta guardado exitosamente');
                        // Actualizar rangos locales para que la UI refleje los cambios inmediatamente
                        const index = rangosAlertas.findIndex(r => r.tipo_sensor === tipo && r.numero_sensor == numero);
                        if (index >= 0) {
                            rangosAlertas[index].valor_minimo = minValue;
                            rangosAlertas[index].valor_maximo = maxValue;
                        } else {
                            rangosAlertas.push({
                                tipo_sensor: tipo,
                                numero_sensor: numero,
                                valor_minimo: minValue,
                                valor_maximo: maxValue
                            });
                        }
                        // Re-fetch data to apply new range logic to current display
                        loadSensorData(tipo, numero, '1h'); 
                    } else {
                        alert('Error: ' + result.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al guardar rango');
                });
            }
            
            function formatTimestamp(timestamp) {
                const date = new Date(timestamp);
                // Using 'es-MX' for Mexican Spanish locale
                return date.toLocaleString('es-MX', { 
                    day: '2-digit', 
                    month: '2-digit', 
                    year: 'numeric',
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
            }
            
            // Limpiar intervalo al salir de la página
            window.addEventListener('beforeunload', function() {
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                }
            });
        </script>
    </body>
    </html>
