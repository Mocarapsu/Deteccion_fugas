<?php
// auth.php - Funciones de autenticación

require_once 'config.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    // Registrar nuevo usuario
    public function register($nombre, $email, $password) {
        try {
            // Validar datos
            if (empty($nombre) || empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Todos los campos son requeridos'];
            }
            
            if (!isValidEmail($email)) {
                return ['success' => false, 'message' => 'Email inválido'];
            }
            
            if (strlen($password) < 6) {
                return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
            }
            
            // Verificar si el email ya existe
            $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'El email ya está registrado'];
            }
            
            // Hash de la contraseña
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            
            // Insertar usuario
            $stmt = $this->db->prepare("
                INSERT INTO usuarios (nombre, email, password) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$nombre, $email, $hashedPassword]);
            
            $userId = $this->db->lastInsertId();
            logActivity($userId, null, 'REGISTRO', 'Usuario registrado exitosamente');
            
            return ['success' => true, 'message' => 'Usuario registrado exitosamente', 'user_id' => $userId];
            
        } catch(PDOException $e) {
            error_log("Error en registro: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al registrar usuario'];
        }
    }
    
    // Login de usuario
    public function login($email, $password) {
        try {
            // Validar datos
            if (empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Email y contraseña son requeridos'];
            }
            
            // Obtener usuario
            $stmt = $this->db->prepare("
                SELECT id, nombre, email, password, activo, intentos_fallidos, bloqueado_hasta 
                FROM usuarios 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if (!$user) {
                return ['success' => false, 'message' => 'Credenciales incorrectas'];
            }
            
            // Verificar si está bloqueado
            if ($user['bloqueado_hasta'] && strtotime($user['bloqueado_hasta']) > time()) {
                $tiempo_restante = ceil((strtotime($user['bloqueado_hasta']) - time()) / 60);
                return ['success' => false, 'message' => "Cuenta bloqueada. Intenta en $tiempo_restante minutos"];
            }
            
            // Verificar si está activo
            if (!$user['activo']) {
                return ['success' => false, 'message' => 'Cuenta desactivada'];
            }
            
            // Verificar contraseña
            if (!password_verify($password, $user['password'])) {
                // Incrementar intentos fallidos
                $intentos = $user['intentos_fallidos'] + 1;
                $bloqueado_hasta = null;
                
                if ($intentos >= MAX_LOGIN_ATTEMPTS) {
                    $bloqueado_hasta = date('Y-m-d H:i:s', time() + LOCKOUT_TIME);
                    $mensaje = 'Demasiados intentos fallidos. Cuenta bloqueada temporalmente';
                } else {
                    $mensaje = 'Credenciales incorrectas';
                }
                
                $stmt = $this->db->prepare("
                    UPDATE usuarios 
                    SET intentos_fallidos = ?, bloqueado_hasta = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$intentos, $bloqueado_hasta, $user['id']]);
                
                logActivity($user['id'], null, 'LOGIN_FALLIDO', 'Intento de login fallido');
                return ['success' => false, 'message' => $mensaje];
            }
            
            // Login exitoso - resetear intentos
            $stmt = $this->db->prepare("
                UPDATE usuarios 
                SET intentos_fallidos = 0, bloqueado_hasta = NULL 
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
            
            // Establecer sesión
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nombre'] = $user['nombre'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['logged_in'] = true;
            
            logActivity($user['id'], null, 'LOGIN', 'Login exitoso');
            
            return ['success' => true, 'message' => 'Login exitoso', 'user' => [
                'id' => $user['id'],
                'nombre' => $user['nombre'],
                'email' => $user['email']
            ]];
            
        } catch(PDOException $e) {
            error_log("Error en login: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al iniciar sesión'];
        }
    }
    
    // Logout
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            logActivity($_SESSION['user_id'], null, 'LOGOUT', 'Usuario cerró sesión');
        }
        
        session_unset();
        session_destroy();
        return ['success' => true, 'message' => 'Sesión cerrada'];
    }
    
    // Verificar si está autenticado
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    // Obtener usuario actual
    public static function getCurrentUser() {
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'nombre' => $_SESSION['user_nombre'],
                'email' => $_SESSION['user_email']
            ];
        }
        return null;
    }
    
    // Requerir autenticación
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header("Location: login.php");
            exit();
        }
    }
}
?>