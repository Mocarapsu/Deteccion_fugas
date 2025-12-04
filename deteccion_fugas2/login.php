<?php
require_once 'config.php';
require_once 'auth.php';

if (Auth::isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new Auth();
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'login') {
            $email = sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            
            $result = $auth->login($email, $password);
            
            if ($result['success']) {
                header("Location: dashboard.php");
                exit();
            } else {
                $error = $result['message'];
            }
        }
        
        if ($_POST['action'] === 'register') {
            $nombre = sanitize($_POST['nombre'] ?? '');
            $email = sanitize($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $password_confirm = $_POST['password_confirm'] ?? '';
            
            if ($password !== $password_confirm) {
                $error = 'Las contraseñas no coinciden';
            } else {
                $result = $auth->register($nombre, $email, $password);
                
                if ($result['success']) {
                    $success = 'Registro exitoso. Por favor inicia sesión';
                } else {
                    $error = $result['message'];
                }
            }
        }
    }
}

if (isset($_GET['timeout'])) {
    $error = 'Tu sesión ha expirado. Por favor inicia sesión nuevamente';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Premium IoT</title>
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

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }

        @keyframes glow-pulse {
            0%, 100% { box-shadow: 0 0 40px rgba(139, 92, 246, 0.4), 0 0 80px rgba(168, 85, 247, 0.2), inset 0 0 20px rgba(139, 92, 246, 0.1); }
            50% { box-shadow: 0 0 60px rgba(139, 92, 246, 0.6), 0 0 120px rgba(168, 85, 247, 0.3), inset 0 0 30px rgba(139, 92, 246, 0.2); }
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(-45deg, #0a0118, #16081f, #1a0b2e, #0f051a);
            background-size: 400% 400%;
            animation: gradient-shift 20s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(139, 92, 246, 0.25) 0%, transparent 70%);
            border-radius: 50%;
            top: -200px;
            right: -100px;
            animation: float 15s ease-in-out infinite;
            filter: blur(60px);
        }

        body::after {
            content: '';
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.2) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -300px;
            left: -200px;
            animation: float 18s ease-in-out infinite reverse;
            filter: blur(80px);
        }
        
        .container {
            background: rgba(15, 5, 26, 0.85);
            backdrop-filter: blur(40px) saturate(150%);
            -webkit-backdrop-filter: blur(40px) saturate(150%);
            border: 1px solid rgba(139, 92, 246, 0.2);
            border-radius: 32px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.6), 
                        inset 0 1px 2px rgba(255,255,255,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 1200px;
            display: flex;
            min-height: 700px;
            position: relative;
            z-index: 1;
            animation: glow-pulse 4s ease-in-out infinite;
        }
        
        .left-panel {
            flex: 1;
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.15) 0%, rgba(168, 85, 247, 0.12) 100%);
            color: white;
            padding: 80px 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
            border-right: 1px solid rgba(139, 92, 246, 0.2);
        }

        .left-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(168, 85, 247, 0.1) 0%, transparent 50%);
            opacity: 0.6;
        }

        .left-panel::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                linear-gradient(45deg, transparent 30%, rgba(139, 92, 246, 0.03) 30%, rgba(139, 92, 246, 0.03) 70%, transparent 70%),
                linear-gradient(-45deg, transparent 30%, rgba(168, 85, 247, 0.03) 30%, rgba(168, 85, 247, 0.03) 70%, transparent 70%);
            background-size: 40px 40px;
        }
        
        .left-panel h1 {
            font-size: 3.5em;
            margin-bottom: 30px;
            font-weight: 900;
            letter-spacing: -2px;
            position: relative;
            z-index: 1;
            background: linear-gradient(135deg, #ffffff 0%, #c4b5fd 50%, #a78bfa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 80px rgba(168, 85, 247, 0.5);
        }
        
        .left-panel p {
            font-size: 1.2em;
            line-height: 1.8;
            opacity: 0.9;
            position: relative;
            z-index: 1;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 300;
        }

        .feature-list {
            list-style: none;
            margin-top: 40px;
            position: relative;
            z-index: 1;
        }

        .feature-list li {
            padding: 16px 0;
            font-size: 1.15em;
            display: flex;
            align-items: center;
            gap: 18px;
            opacity: 0.9;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 400;
        }

        .feature-list li::before {
            content: '●';
            font-size: 1.5em;
            color: #a78bfa;
            text-shadow: 0 0 10px rgba(167, 139, 250, 0.8);
        }
        
        .right-panel {
            flex: 1;
            padding: 80px 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: rgba(0, 0, 0, 0.4);
            position: relative;
        }
        
        .form-container {
            display: none;
        }
        
        .form-container.active {
            display: block;
            animation: fadeIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .form-title {
            font-size: 3em;
            margin-bottom: 15px;
            color: white;
            font-weight: 900;
            letter-spacing: -1.5px;
        }
        
        .form-subtitle {
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 50px;
            font-size: 1.1em;
            font-weight: 300;
        }
        
        .form-group {
            margin-bottom: 32px;
        }
        
        label {
            display: block;
            margin-bottom: 12px;
            color: rgba(255, 255, 255, 0.95);
            font-weight: 600;
            font-size: 0.95em;
            letter-spacing: 0.5px;
        }
        
        input {
            width: 100%;
            padding: 18px 22px;
            border: 2px solid rgba(139, 92, 246, 0.2);
            border-radius: 14px;
            font-size: 1em;
            background: rgba(139, 92, 246, 0.05);
            color: white;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            font-weight: 400;
        }

        input::placeholder {
            color: rgba(255, 255, 255, 0.25);
        }
        
        input:focus {
            outline: none;
            border-color: rgba(139, 92, 246, 0.8);
            background: rgba(139, 92, 246, 0.1);
            box-shadow: 0 0 0 5px rgba(139, 92, 246, 0.15), 
                        0 10px 25px rgba(139, 92, 246, 0.3),
                        inset 0 1px 1px rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        
        .btn {
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1.1em;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 15px 35px rgba(139, 92, 246, 0.5);
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-top: 15px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 45px rgba(139, 92, 246, 0.7);
        }
        
        .btn:active {
            transform: translateY(-2px);
        }
        
        .switch-form {
            text-align: center;
            margin-top: 35px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 0.95em;
            font-weight: 400;
        }
        
        .switch-form a {
            color: #c4b5fd;
            text-decoration: none;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
        }
        
        .switch-form a:hover {
            color: #ddd6fe;
            border-bottom-color: #ddd6fe;
            text-shadow: 0 0 10px rgba(221, 214, 254, 0.5);
        }
        
        .alert {
            padding: 18px 24px;
            border-radius: 14px;
            margin-bottom: 30px;
            animation: slideDown 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.1);
            font-weight: 500;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.25);
            color: #fca5a5;
            border-left: 5px solid #ef4444;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.25);
            color: #86efac;
            border-left: 5px solid #22c55e;
        }
        
        @media (max-width: 1000px) {
            .container {
                flex-direction: column;
                max-width: 550px;
            }
            
            .left-panel {
                padding: 50px 40px;
                border-right: none;
                border-bottom: 1px solid rgba(139, 92, 246, 0.2);
            }

            .left-panel h1 {
                font-size: 2.5em;
            }

            .feature-list {
                margin-top: 25px;
            }
            
            .right-panel {
                padding: 50px 40px;
            }

            .form-title {
                font-size: 2.2em;
            }
        }

        @media (max-width: 500px) {
            body {
                padding: 10px;
            }

            .left-panel, .right-panel {
                padding: 40px 30px;
            }

            .left-panel h1 {
                font-size: 2em;
            }

            .left-panel p, .feature-list li {
                font-size: 1em;
            }

            .form-title {
                font-size: 1.9em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <h1>💧 IoT Premium</h1>
            <p>Plataforma avanzada de monitoreo en tiempo real con inteligencia artificial para sistemas Arduino con sensores de temperatura, humedad, presión y flujo.</p>
            <ul class="feature-list">
                <li>Dashboard en tiempo real 24/7</li>
                <li>Analítica predictiva avanzada</li>
                <li>Gráficos interactivos HD</li>
                <li>Multi-dispositivo escalable</li>
                <li>Alertas inteligentes automáticas</li>
            </ul>
        </div>
        
        <div class="right-panel">
            <div id="loginForm" class="form-container active">
                <h2 class="form-title">Bienvenido</h2>
                <p class="form-subtitle">Accede a tu panel de control</p>
                
                <?php if ($error && (!isset($_POST['action']) || $_POST['action'] === 'login')): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required placeholder="correo@ejemplo.com" autocomplete="email">
                    </div>
                    
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" name="password" required placeholder="••••••••" autocomplete="current-password">
                    </div>
                    
                    <button type="submit" class="btn">Iniciar Sesión</button>
                </form>
                
                <div class="switch-form">
                    ¿No tienes cuenta? <a onclick="switchForm('register')">Crear cuenta</a>
                </div>
            </div>
            
            <div id="registerForm" class="form-container">
                <h2 class="form-title">Crear Cuenta</h2>
                <p class="form-subtitle">Únete a la plataforma IoT</p>
                
                <?php if ($error && isset($_POST['action']) && $_POST['action'] === 'register'): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="form-group">
                        <label>Nombre completo</label>
                        <input type="text" name="nombre" required placeholder="Juan Pérez" autocomplete="name">
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required placeholder="correo@ejemplo.com" autocomplete="email">
                    </div>
                    
                    <div class="form-group">
                        <label>Contraseña</label>
                        <input type="password" name="password" required placeholder="••••••••" minlength="6" autocomplete="new-password">
                    </div>
                    
                    <div class="form-group">
                        <label>Confirmar contraseña</label>
                        <input type="password" name="password_confirm" required placeholder="••••••••" minlength="6" autocomplete="new-password">
                    </div>
                    
                    <button type="submit" class="btn">Registrarse</button>
                </form>
                
                <div class="switch-form">
                    ¿Ya tienes cuenta? <a onclick="switchForm('login')">Inicia sesión</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function switchForm(formType) {
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            
            if (formType === 'register') {
                loginForm.classList.remove('active');
                registerForm.classList.add('active');
            } else {
                registerForm.classList.remove('active');
                loginForm.classList.add('active');
            }
        }
    </script>
</body>
</html>
