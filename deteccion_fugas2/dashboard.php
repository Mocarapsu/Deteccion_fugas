<?php
require_once 'config.php';
require_once 'auth.php';

Auth::requireLogin();
$user = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Premium - Arduino Monitor</title>
    <style>
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

        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 10px rgba(99, 102, 241, 0.3); }
            50% { box-shadow: 0 0 20px rgba(99, 102, 241, 0.6); }
        }

        @keyframes blink-alert {
            0%, 50%, 100% { opacity: 1; }
            25%, 75% { opacity: 0.7; }
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', sans-serif;
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
            font-size: 1.6rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff 0%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 25px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .btn-logout {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 10px 20px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2.5rem 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(99, 102, 241, 0.3);
            border-color: rgba(99, 102, 241, 0.4);
        }
        
        .stat-card h3 {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
            margin-bottom: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        .stat-card .value {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .header-section h2 {
            color: white;
            font-size: 1.8rem;
            font-weight: 800;
        }

        .header-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 700;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.6);
        }

        .btn-simulator {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-simulator:hover {
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.6);
        }
        
        .arduinos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 2rem;
        }
        
        .arduino-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .arduino-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 50px rgba(99, 102, 241, 0.4);
            border-color: rgba(99, 102, 241, 0.5);
        }
        
        .arduino-header {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.3) 0%, rgba(168, 85, 247, 0.3) 100%);
            color: white;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .arduino-header h3 {
            margin-bottom: 0.5rem;
            font-size: 1.4rem;
            font-weight: 700;
        }
        
        .arduino-code {
            font-size: 0.9rem;
            opacity: 0.85;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .arduino-body {
            padding: 1.8rem;
        }
        
        .sensors-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.2rem;
        }
        
        .sensor-box {
            text-align: center;
            padding: 1.2rem 0.8rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            border: 1.5px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s;
        }
        
        .sensor-box:hover {
            border-color: rgba(99, 102, 241, 0.5);
            background: rgba(99, 102, 241, 0.1);
            transform: scale(1.05);
        }

        .sensor-box.alert {
            background: rgba(239, 68, 68, 0.2) !important;
            border: 2px solid #ef4444 !important;
            animation: pulse-glow 2s infinite;
            position: relative;
        }

        .sensor-box.alert::before {
            content: '⚠️';
            position: absolute;
            top: 5px;
            right: 5px;
            font-size: 1.1em;
        }
        
        .sensor-icon {
            font-size: 2.2rem;
            margin-bottom: 0.6rem;
        }
        
        .sensor-value {
            font-size: 1.6rem;
            font-weight: 800;
            color: #e0e0e0;
        }
        
        .sensor-label {
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            margin-top: 0.4rem;
        }
        
        .timestamp {
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.85rem;
            padding-top: 1.2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .status-online {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #10b981;
            border-radius: 50%;
            margin-right: 6px;
            animation: pulse-glow 2s infinite;
            box-shadow: 0 0 10px #10b981;
        }
        
        .status-offline {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #ef4444;
            border-radius: 50%;
            margin-right: 6px;
        }
        
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .empty-state h2 {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }
        
        .empty-state p {
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(10px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: rgba(10, 10, 26, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 2.5rem;
            width: 90%;
            max-width: 550px;
            animation: slideDown 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-80px) scale(0.9); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-header h2 {
            color: white;
            font-size: 1.8rem;
            font-weight: 800;
        }
        
        .btn-close {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            font-size: 1.5rem;
            cursor: pointer;
            color: #fca5a5;
            padding: 8px 14px;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .btn-close:hover {
            background: rgba(239, 68, 68, 0.25);
            transform: rotate(90deg);
        }
        
        .form-group {
            margin-bottom: 1.8rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.8rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .form-group input,
        .form-select {
            width: 100%;
            padding: 14px 18px;
            border: 1.5px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            transition: all 0.3s;
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }
        
        .form-group input:focus,
        .form-select:focus {
            outline: none;
            border-color: rgba(99, 102, 241, 0.6);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        }
        
        .loading {
            text-align: center;
            padding: 3rem;
            color: rgba(255, 255, 255, 0.6);
            font-size: 1.1rem;
        }
        
        .btn-delete {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 12px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-delete:hover {
            background: rgba(239, 68, 68, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
        }
        
        @media (max-width: 768px) {
            .sensors-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .arduinos-grid {
                grid-template-columns: 1fr;
            }

            .header-section {
                flex-direction: column;
                align-items: flex-start;
            }

            .header-buttons {
                width: 100%;
            }

            .btn-primary {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🔌 Arduino Monitor Premium</h1>
        <div class="navbar-right">
            <div class="user-info">
                <span>👤 <?php echo htmlspecialchars($user['nombre']); ?></span>
            </div>
            <button class="btn-logout" onclick="logout()">Cerrar Sesión</button>
        </div>
    </nav>
    
    <div class="container">
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <h3>Arduinos Activos</h3>
                <div class="value" id="totalArduinos">0</div>
            </div>
            <div class="stat-card">
                <h3>Total de Lecturas</h3>
                <div class="value" id="totalLecturas">0</div>
            </div>
            <div class="stat-card">
                <h3>Última Sincronización</h3>
                <div class="value" id="ultimaSync" style="font-size: 1.4rem;">--:--</div>
            </div>
        </div>
        
        <div class="header-section">
            <h2>Mis Dispositivos Arduino</h2>
            <div class="header-buttons">
                <a href="simulacion.html" class="btn-primary btn-simulator">
                    🎮 Abrir Simulador
                </a>
                <button class="btn-primary" onclick="openAddArduinoModal()">+ Agregar Arduino</button>
            </div>
        </div>
        
        <div class="arduinos-grid" id="arduinosGrid">
            <div class="loading">Cargando dispositivos...</div>
        </div>
    </div>
    
    <div class="modal" id="addArduinoModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Agregar Nuevo Arduino</h2>
                <button class="btn-close" onclick="closeAddArduinoModal()">&times;</button>
            </div>
            <form id="addArduinoForm" onsubmit="addArduino(event)">
                <div class="form-group">
                    <label>Código Único del Arduino *</label>
                    <input type="text" name="codigo_arduino" required placeholder="Ej: ARD001" pattern="[A-Z0-9]+" title="Solo letras mayúsculas y números">
                </div>
                
                <div class="form-group">
                    <label>Nombre Descriptivo *</label>
                    <input type="text" name="nombre" required placeholder="Ej: Tubería Principal Norte">
                </div>
                
                <div class="form-group">
                    <label>Ubicación (opcional)</label>
                    <input type="text" name="ubicacion" placeholder="Ej: Sector A - Planta Baja">
                </div>
                
                <div class="form-group">
                    <label>Cantidad de Sensores de Presión:</label>
                    <select name="cantidad_presion" id="cantidad_presion" class="form-select">
                        <option value="0">0 - Sin sensores de presión</option>
                        <option value="1" selected>1 sensor</option>
                        <option value="2">2 sensores</option>
                        <option value="3">3 sensores</option>
                        <option value="4">4 sensores</option>
                        <option value="5">5 sensores</option>
                        <option value="6">6 sensores</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Cantidad de Sensores de Flujo:</label>
                    <select name="cantidad_flujo" id="cantidad_flujo" class="form-select">
                        <option value="0">0 - Sin sensores de flujo</option>
                        <option value="1" selected>1 sensor</option>
                        <option value="2">2 sensores</option>
                        <option value="3">3 sensores</option>
                        <option value="4">4 sensores</option>
                        <option value="5">5 sensores</option>
                        <option value="6">6 sensores</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-primary">Agregar Arduino</button>
            </form>
        </div>
    </div>
    
    <script>
        let autoRefreshInterval;
        
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboard();
            startAutoRefresh();
        });
        
        function startAutoRefresh() {
            autoRefreshInterval = setInterval(loadDashboard, 2000);
        }
        
        function loadDashboard() {
            loadStats();
            loadArduinos();
        }
        
        function loadStats() {
            fetch('api/stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('totalArduinos').textContent = data.stats.total_arduinos;
                        document.getElementById('totalLecturas').textContent = data.stats.total_lecturas.toLocaleString();
                        document.getElementById('ultimaSync').textContent = new Date().toLocaleTimeString();
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        function loadArduinos() {
            fetch('api/arduinos.php')
                .then(response => response.json())
                .then(data => {
                    const grid = document.getElementById('arduinosGrid');
                    
                    if (data.success && data.arduinos.length > 0) {
                        grid.innerHTML = data.arduinos.map(arduino => createArduinoCard(arduino)).join('');
                    } else {
                        grid.innerHTML = `
                            <div class="empty-state">
                                <h2>No tienes Arduinos registrados</h2>
                                <p>Comienza agregando tu primer dispositivo Arduino</p>
                                <button class="btn-primary" onclick="openAddArduinoModal()">+ Agregar Arduino</button>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('arduinosGrid').innerHTML = '<div class="loading">Error al cargar dispositivos</div>';
                });
        }
        
        function createArduinoCard(arduino) {
            const tiempoTranscurrido = arduino.ultima_lectura ? 
                formatearTiempo(new Date() - new Date(arduino.ultima_lectura)) : 
                'Sin lecturas';
            
            const statusDot = arduino.ultima_lectura ? 
                '<span class="status-online"></span>' : 
                '<span class="status-offline"></span>';
            
            const alertIcon = arduino.tiene_alertas ? '⚠️ ' : '';
            
            return `
                <div class="arduino-card" onclick="window.location.href='arduino_detail.php?id=${arduino.id}'">
                    <div class="arduino-header">
                        <h3>${alertIcon}${escapeHtml(arduino.nombre)}</h3>
                        <div class="arduino-code">
                            ${statusDot}
                            <span>${escapeHtml(arduino.codigo_arduino)}</span>
                        </div>
                    </div>
                    <div class="arduino-body">
                        <div class="sensors-grid">
                            ${renderSensorBoxes(arduino)}
                        </div>
                        <div class="timestamp">
                            <strong>Última lectura:</strong> ${tiempoTranscurrido}
                        </div>
                        <button class="btn-delete" onclick="event.stopPropagation(); deleteArduino(${arduino.id}, '${escapeHtml(arduino.nombre)}')" 
                                style="width: 100%; margin-top: 1rem; padding: 12px; background: rgba(239, 68, 68, 0.15); 
                                       color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 10px; 
                                       cursor: pointer; font-weight: 600; transition: all 0.3s;">
                            🗑️ Eliminar Arduino
                        </button>
                    </div>
                </div>
            `;
        }
        
        function formatTimestamp(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);
            
            if (diff < 60) return 'Hace ' + diff + ' segundos';
            if (diff < 3600) return 'Hace ' + Math.floor(diff / 60) + ' minutos';
            if (diff < 86400) return 'Hace ' + Math.floor(diff / 3600) + ' horas';
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        }
        
        function openAddArduinoModal() {
            document.getElementById('addArduinoModal').classList.add('active');
        }
        
        function closeAddArduinoModal() {
            document.getElementById('addArduinoModal').classList.remove('active');
            document.getElementById('addArduinoForm').reset();
        }
        
        function addArduino(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const data = {
                codigo_arduino: formData.get('codigo_arduino'),
                nombre: formData.get('nombre'),
                ubicacion: formData.get('ubicacion') || null,
                cantidad_presion: parseInt(formData.get('cantidad_presion')),
                cantidad_flujo: parseInt(formData.get('cantidad_flujo'))
            };
            
            fetch('api/arduinos.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Arduino agregado exitosamente con ' + data.cantidad_presion + ' sensores de presión y ' + data.cantidad_flujo + ' sensores de flujo');
                    closeAddArduinoModal();
                    loadDashboard();
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al agregar Arduino');
            });
        }
        
        function viewArduino(arduinoId) {
            window.location.href = 'arduino_detail.php?id=' + arduinoId;
        }
        
        function logout() {
            if (confirm('¿Seguro que deseas cerrar sesión?')) {
                fetch('api/logout.php', { method: 'POST' })
                    .then(() => window.location.href = 'login.php');
            }
        }
        
        function deleteArduino(id, nombre) {
            if (!confirm(`¿Estás seguro de que deseas eliminar el Arduino "${nombre}"?\n\nEsta acción no se puede deshacer y se eliminarán todos sus datos históricos.`)) {
                return;
            }
            
            fetch('api/delete_arduino.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ arduino_id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Arduino eliminado correctamente');
                    loadDashboard();
                } else {
                    alert('Error al eliminar: ' + (data.error || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al eliminar el Arduino');
            });
        }
        
        function formatearTiempo(ms) {
            const seconds = Math.floor(ms / 1000);
            const minutes = Math.floor(seconds / 60);
            const hours = Math.floor(minutes / 60);
            const days = Math.floor(hours / 24);
            
            if (seconds < 60) return `Hace ${seconds} segundos`;
            if (minutes < 60) return `Hace ${minutes} minutos`;
            if (hours < 24) return `Hace ${hours} horas`;
            return `${days} días atrás`;
        }
        
        function escapeHtml(text) {
            return text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
        
        function renderSensorBoxes(arduino) {
            let sensoresHTML = '';
            
            if (arduino.ultima_lectura && arduino.ultima_lectura.sensores) {
                const sensores = arduino.ultima_lectura.sensores;
                
                sensores.forEach(sensor => {
                    const alertClass = sensor.en_alerta ? 'alert' : '';
                    const icon = sensor.tipo === 'presion' ? '📊' : '💧';
                    
                    sensoresHTML += `
                        <div class="sensor-box ${alertClass}" onclick="event.stopPropagation(); viewSensor(${arduino.id}, '${sensor.tipo}', ${sensor.numero})">
                            <div class="sensor-icon">${icon}</div>
                            <div class="sensor-value">${sensor.valor !== null ? sensor.valor + ' ' + sensor.unidad : '--'}</div>
                            <div class="sensor-label">${sensor.tipo === 'presion' ? 'Presión' : 'Flujo'} ${sensor.numero}</div>
                        </div>
                    `;
                });
            } else {
                sensoresHTML = '<div class="sensor-box"><div class="sensor-label">Sin lecturas</div></div>';
            }
            
            return sensoresHTML;
        }
        
        function viewSensor(arduinoId, sensorType, sensorNumber) {
            window.location.href = `sensor_detail.php?id=${arduinoId}&type=${sensorType}&number=${sensorNumber}`;
        }
        
        window.addEventListener('beforeunload', function() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
        });
    </script>
</body>
</html>
