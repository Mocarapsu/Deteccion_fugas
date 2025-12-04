// Extensión para conectar PipeLiveData a la visualización 3D
// Este archivo se ejecuta después de que simulacion.html carga

// Guardar la función original updateDashboard
const originalUpdateDashboard = PipeLiveData.prototype.updateDashboard;

//  Sobrescribir con la nueva versión que actualiza 3D
PipeLiveData.prototype.updateDashboard = function (sensores) {
    // Construir estado desde API
    const apiState = {
        p1: { value: 0, unit: 'PSI' },
        p2: { value: 0, unit: 'PSI' },
        p3: { value: 0, unit: 'PSI' },
        f1: { value: 0, unit: 'L/min' },
        f2: { value: 0, unit: 'L/min' },
        f3: { value: 0, unit: 'L/min' }
    };

    sensores.forEach(s => {
        const v = s.valor !== null ? parseFloat(s.valor) : 0;
        if (s.tipo === 'presion') {
            if (s.numero === 1) apiState.p1.value = v;
            if (s.numero === 2) apiState.p2.value = v;
            if (s.numero === 3) apiState.p3.value = v;
        } else if (s.tipo === 'flujo') {
            if (s.numero === 1) apiState.f1.value = v;
            if (s.numero === 2) apiState.f2.value = v;
            if (s.numero === 3) apiState.f3.value = v;
        }
    });

    // Actualizar visualización 3D
    if (typeof updateVisuals === 'function') {
        updateVisuals(apiState);
    }

    // Llamar a la función original para mantener el resto de la funcionalidad
    originalUpdateDashboard.call(this, sensores);
};

console.log('✅ Live data 3D connection enabled');
