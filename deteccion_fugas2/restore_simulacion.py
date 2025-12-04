import os

file_path = r'c:\xampp\htdocs\deteccion_fugas\deteccion_fugas\simulacion.html'

with open(file_path, 'r', encoding='utf-8') as f:
    lines = f.readlines()

# Keep lines 0 to 1223 (index 0 to 1222)
# Line 1223 is the last valid line inside the animate loop.
valid_lines = lines[:1223]

# New content to append
new_content = """                });

                controls.update();
                renderer.render(scene, camera);
            }

            // Iniciar
            updateVisuals(systemState);
            animate();

            window.addEventListener('resize', () => {
                const w = controlPanel.classList.contains('open') ? window.innerWidth - 320 : window.innerWidth;
                camera.aspect = w / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(w, window.innerHeight);
            });

            // =============================================
            // CONEXIÓN CON TU BACKEND - DATOS EN TIEMPO REAL
            // =============================================

            class PipeLiveData {
                constructor() {
                    this.interval = null;
                    this.currentArduinoId = 1; // Default
                }

                init(arduinoId) {
                    console.log("Inicializando PipeLiveData para Arduino:", arduinoId);
                    this.currentArduinoId = arduinoId;
                    if (mode === 'live') {
                        this.fetchLiveData();
                        if (this.interval) clearInterval(this.interval);
                        this.interval = setInterval(() => this.fetchLiveData(), 2000);
                    }
                }

                stop() {
                    if (this.interval) clearInterval(this.interval);
                }

                async fetchLiveData() {
                    try {
                        const response = await fetch(`/api/arduinos.php`);
                        const data = await response.json();

                        if (data.success && data.arduinos) {
                            // Encontrar el Arduino actual
                            const arduino = data.arduinos.find(a => a.id == this.currentArduinoId);

                            if (arduino && arduino.ultima_lectura) {
                                this.updateDashboard(arduino.ultima_lectura.sensores);
                                this.updateSystemStatus(arduino);
                            }
                        }
                    } catch (error) {
                        console.error('Error fetching live data:', error);
                    }
                }

                updateDashboard(sensores) {
                    sensores.forEach(sensor => {
                        let elementId;

                        // Mapear sensores a elementos HTML
                        if (sensor.tipo === 'presion') {
                            if (sensor.numero === 1) elementId = 'pressure1';
                            if (sensor.numero === 2) elementId = 'pressure2';
                            if (sensor.numero === 3) elementId = 'pressure3';
                        } else if (sensor.tipo === 'flujo') {
                            if (sensor.numero === 1) elementId = 'flow1';
                            if (sensor.numero === 2) elementId = 'flow2';
                            if (sensor.numero === 3) elementId = 'flow3';
                        }

                        const element = document.getElementById(elementId);
                        if (!element) return;

                        const valueElement = element.querySelector('.sensor-value');
                        if (!valueElement) return;

                        if (sensor.valor !== null) {
                            if (sensor.tipo === 'presion') {
                                // Mostrar en PSI y BAR
                                const psi = sensor.valor; // Ya viene en PSI de tu API
                                const bar = (psi / 14.5038).toFixed(2); // Convertir a BAR
                                valueElement.innerHTML = `${psi.toFixed(2)} <span class="sensor-unit">PSI</span> / ${bar} <span class="sensor-unit">BAR</span>`;
                            } else {
                                // Flujo en L/min
                                valueElement.innerHTML = `${sensor.valor.toFixed(2)}<span class="sensor-unit">L/min</span>`;
                            }

                            // Cambiar estilo si está en alerta
                            if (sensor.en_alerta) {
                                element.classList.add('alert');
                                valueElement.classList.add('blinking');
                            } else {
                                element.classList.remove('alert');
                                valueElement.classList.remove('blinking');
                            }
                        } else {
                            valueElement.innerHTML = sensor.tipo === 'presion' ?
                                '--<span class="sensor-unit">PSI</span> / --<span class="sensor-unit">BAR</span>' :
                                '--<span class="sensor-unit">L/min</span>';
                        }
                    });
                }

                updateSystemStatus(arduino) {
                    const statusPanel = document.getElementById('status-panel');
                    const statusText = document.getElementById('status-text');

                    if (!statusPanel || !statusText) return;

                    if (!arduino.is_online) {
                        statusText.textContent = '🔴 ARDUINO OFFLINE';
                        statusPanel.className = 'status-panel status-error';
                        statusText.className = 'status-text alert';
                    } else {
                        // Verificar si hay sensores en alerta
                        const hasAlerts = arduino.ultima_lectura.sensores.some(sensor => sensor.en_alerta);

                        if (hasAlerts) {
                            statusText.textContent = '⚠️ SISTEMA EN ALERTA - Fugas detectadas';
                            statusPanel.className = 'status-panel alert';
                            statusText.className = 'status-text alert';
                        } else {
                            statusText.textContent = '✅ SISTEMA OPERANDO NORMALMENTE';
                            statusPanel.className = 'status-panel normal';
                            statusText.className = 'status-text normal';
                        }
                    }
                }
            }

            // Crear instancia global
            const pipeLiveData = new PipeLiveData();

            // Función para inicializar el dashboard en vivo
            function initLiveDashboard(arduinoId) {
                console.log('🚀 Iniciando dashboard en vivo con Arduino ID:', arduinoId);
                pipeLiveData.init(arduinoId);
            }

            // Agregar estilos para las alertas en vivo
            const liveDataStyles = `
<style>
    .sensor-item.alert-live {
        border-left-color: #ef4444 !important;
        background: #fef2f2 !important;
    }
    
    .blinking {
        animation: blink-alert 0.5s ease-in-out infinite;
    }
    
    @keyframes blink-alert {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.6; }
    }
    
    .status-panel.status-error {
        background: #fef2f2 !important;
        border-left: 4px solid #8b8b8b !important;
    }
</style>
`;

            // Inyectar estilos
            document.head.insertAdjacentHTML('beforeend', liveDataStyles);

            // =============================================
            // MODIFICAR TU CÓDIGO EXISTENTE PARA INTEGRAR
            // =============================================

            // En tu función fetchSensorDataFromAPI, reemplázala con esta versión mejorada:
            async function fetchSensorDataFromAPI() {
                if (mode !== 'live') return;

                try {
                    // Usar el sistema de datos en vivo
                    await pipeLiveData.fetchLiveData();
                } catch (error) {
                    console.error('Error en modo live:', error);
                }
            }
        </script>
</body>
</html>
"""

with open(file_path, 'w', encoding='utf-8') as f:
    f.writelines(valid_lines)
    f.write(new_content)

print("File restored successfully.")
