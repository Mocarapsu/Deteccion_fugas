<?php
// reset_password.php - Archivo para resetear contraseña del usuario admin
// IMPORTANTE: Eliminar este archivo después de usarlo

require_once 'config.php';

// Configuración del usuario
$email = "admin@test.com";
$password = "admin123";
$nombre = "Admin";

try {
    $db = Database::getInstance()->getConnection();
    
    // Generar hash con la configuración actual de PHP
    $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Verificar si el usuario existe
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Usuario existe - actualizar contraseña
        $stmt = $db->prepare("UPDATE usuarios SET password = ?, intentos_fallidos = 0, bloqueado_hasta = NULL WHERE email = ?");
        $stmt->execute([$hashed, $email]);
        $mensaje = "Contraseña actualizada";
        $accion = "actualizado";
    } else {
        // Usuario no existe - crear nuevo
        $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, activo) VALUES (?, ?, ?, 1)");
        $stmt->execute([$nombre, $email, $hashed]);
        $mensaje = "Usuario creado";
        $accion = "creado";
    }
    
    // Mostrar resultado
    ?>
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reset Password - Detección de Fugas</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                padding: 20px;
            }
            
            .container {
                background: white;
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                text-align: center;
                max-width: 500px;
                width: 100%;
                animation: slideDown 0.5s;
            }
            
            @keyframes slideDown {
                from { opacity: 0; transform: translateY(-30px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            h2 { 
                color: #27ae60; 
                margin-bottom: 10px;
                font-size: 2em;
            }
            
            .subtitle {
                color: #666;
                margin-bottom: 30px;
            }
            
            .info { 
                background: #f8f9fa; 
                padding: 20px; 
                border-radius: 10px; 
                margin: 20px 0;
                border-left: 4px solid #667eea;
            }
            
            .info p { 
                margin: 10px 0;
                font-size: 1.1em;
                text-align: left;
            }
            
            .info strong {
                color: #333;
            }
            
            .btn {
                display: inline-block;
                padding: 14px 30px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                text-decoration: none;
                border-radius: 8px;
                margin-top: 20px;
                transition: all 0.3s;
                font-weight: 600;
                font-size: 1em;
            }
            
            .btn:hover { 
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            }
            
            .warning {
                background: #fff3cd;
                color: #856404;
                padding: 15px;
                border-radius: 8px;
                margin-top: 20px;
                font-size: 0.9em;
                border-left: 4px solid #ffc107;
            }
            
            .success-icon {
                font-size: 4em;
                margin-bottom: 20px;
            }
            
            .hash-info {
                background: #e9ecef;
                padding: 10px;
                border-radius: 5px;
                font-family: monospace;
                font-size: 0.8em;
                word-break: break-all;
                margin-top: 15px;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='success-icon'>✅</div>
            <h2>Usuario <?php echo $accion; ?> exitosamente</h2>
            <p class='subtitle'>El cifrado se ha sincronizado con tu versión de PHP</p>
            
            <div class='info'>
                <p><strong>📧 Email:</strong> <?php echo $email; ?></p>
                <p><strong>🔑 Contraseña:</strong> <?php echo $password; ?></p>
                <p><strong>👤 Nombre:</strong> <?php echo $nombre; ?></p>
            </div>
            
            <a href='login.php' class='btn'>🚀 Ir al Login</a>
            
            <div class='warning'>
                <strong>⚠️ IMPORTANTE:</strong><br>
                Por razones de seguridad, elimina este archivo (<strong>reset_password.php</strong>) inmediatamente después de usarlo.
            </div>
            
            <div class='hash-info'>
                <strong>Hash generado:</strong><br>
                <?php echo substr($hashed, 0, 50) . '...'; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
    
} catch(PDOException $e) {
    ?>
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - Reset Password</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: #e74c3c;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                padding: 20px;
            }
            
            .error {
                background: white;
                padding: 40px;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                text-align: center;
                max-width: 500px;
                width: 100%;
            }
            
            .error-icon {
                font-size: 4em;
                margin-bottom: 20px;
            }
            
            h2 {
                color: #e74c3c;
                margin-bottom: 20px;
            }
            
            .error-message {
                background: #fee;
                color: #c33;
                padding: 15px;
                border-radius: 8px;
                border-left: 4px solid #c33;
                margin: 20px 0;
                text-align: left;
            }
            
            .help {
                color: #666;
                margin-top: 20px;
                font-size: 0.9em;
            }
        </style>
    </head>
    <body>
        <div class='error'>
            <div class='error-icon'>❌</div>
            <h2>Error de Base de Datos</h2>
            <div class='error-message'>
                <strong>Error:</strong><br>
                <?php echo $e->getMessage(); ?>
            </div>
            <div class='help'>
                <strong>Posibles soluciones:</strong><br>
                • Verifica que la base de datos 'deteccion_fugas' exista<br>
                • Verifica las credenciales en config.php<br>
                • Asegúrate de que MySQL esté corriendo<br>
                • Revisa que la tabla 'usuarios' exista
            </div>
        </div>
    </body>
    </html>
    <?php
}
?>
```

## 📝 Cómo usar:

1. **Guarda el archivo** como `reset_password.php` en la carpeta raíz de tu proyecto (donde está login.php)

2. **Abre en el navegador:**
```
   http://localhost/deteccion_fugas/reset_password.php