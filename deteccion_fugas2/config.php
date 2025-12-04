    <?php
    /**
     * config.example.php - Archivo de configuración de ejemplo
     * 
     * INSTRUCCIONES:
     * 1. Copia este archivo y renómbralo a "config.php"
     * 2. Modifica los valores según tu entorno
     * 3. NO subas config.php a repositorios públicos
     * 4. Mantén config.example.php como referencia
     */

    // =====================================================
    // CONFIGURACIÓN DE BASE DE DATOS
    // =====================================================

    // Host del servidor MySQL (usualmente 'localhost')
    define('DB_HOST', 'localhost');

    // Nombre de la base de datos (debe existir previamente)
    define('DB_NAME', 'deteccion_fugas');

    // Usuario de MySQL
    define('DB_USER', 'root');

    // Contraseña de MySQL (vacío por defecto en XAMPP)
    define('DB_PASS', '');

    // Charset de la base de datos
    define('DB_CHARSET', 'utf8mb4');

    // =====================================================
    // CONFIGURACIÓN DE SESIONES
    // =====================================================

    // Tiempo de inactividad antes de cerrar sesión (en segundos)
    // 1800 = 30 minutos
    define('SESSION_TIMEOUT', 1800);

    // Número máximo de intentos de login fallidos antes de bloquear
    define('MAX_LOGIN_ATTEMPTS', 5);

    // Tiempo de bloqueo después de exceder intentos (en segundos)
    // 900 = 15 minutos
    define('LOCKOUT_TIME', 900);

    // =====================================================
    // CONFIGURACIÓN DE LA APLICACIÓN
    // =====================================================

    // URL base del sitio (sin barra diagonal al final)
    // Ejemplo: 'http://192.168.1.100/deteccion_fugas'
    define('SITE_URL', 'http://localhost/deteccion_fugas2');

    // Zona horaria del sistema
    // Lista completa: https://www.php.net/manual/es/timezones.php
    define('TIMEZONE', 'America/Mexico_City');

    // =====================================================
    // CONFIGURACIÓN DE ARDUINO
    // =====================================================

    // Tiempo sin datos para considerar un Arduino offline (en segundos)
    // 300 = 5 minutos
    define('ARDUINO_TIMEOUT', 300);

    // =====================================================
    // CONFIGURACIÓN DE EMAIL (OPCIONAL - PARA FUTURAS VERSIONES)
    // =====================================================

    // Servidor SMTP
    // define('SMTP_HOST', 'smtp.gmail.com');
    // define('SMTP_PORT', 587);
    // define('SMTP_USER', 'tu_email@gmail.com');
    // define('SMTP_PASS', 'tu_contraseña');
    // define('SMTP_FROM', 'noreply@deteccionfugas.com');

    // =====================================================
    // CONFIGURACIÓN DE DESARROLLO
    // =====================================================

    // Habilitar modo debug (mostrar errores)
    // IMPORTANTE: Cambiar a false en producción
    define('DEBUG_MODE', true);

    // Nivel de reporte de errores en modo debug
    if (DEBUG_MODE) {
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    } else {
        
        error_reporting(0);
        ini_set('display_errors', 0);
    }

    // =====================================================
    // NO MODIFICAR A PARTIR DE AQUÍ
    // (A menos que sepas lo que estás haciendo)
    // =====================================================

    // Establecer zona horaria
    date_default_timezone_set(TIMEZONE);

    // Clase de conexión a base de datos (Singleton Pattern)
    class Database {
        private static $instance = null;
        private $conn;
        
        private function __construct() {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch(PDOException $e) {
                if (DEBUG_MODE) {
                    die("Error de conexión: " . $e->getMessage());
                } else {
                    die("Error de conexión a la base de datos. Contacta al administrador.");
                }
            }
        }
        
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        public function getConnection() {
            return $this->conn;
        }
        
        private function __clone() {}
        
        public function __wakeup() {
            throw new Exception("Cannot unserialize singleton");
        }
    }

    // Funciones helper globales
    function sanitize($data) {
        if (is_array($data)) {
            return array_map('sanitize', $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    function logActivity($user_id, $arduino_id, $accion, $descripcion = '') {
        try {
            $db = Database::getInstance()->getConnection();
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            $stmt = $db->prepare("
                INSERT INTO logs_actividad (user_id, arduino_id, accion, descripcion, ip_address) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $arduino_id, $accion, $descripcion, $ip]);
        } catch(PDOException $e) {
            if (DEBUG_MODE) {
                error_log("Error logging activity: " . $e->getMessage());
            }
        }
    }

    // Iniciar sesión
    session_start();

    // Verificar timeout de sesión
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        header("Location: login.php?timeout=1");
        exit();
    }
    $_SESSION['last_activity'] = time();

    // Headers de seguridad
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");

    // =====================================================
    // NOTAS IMPORTANTES
    // =====================================================

    /*
    * PRODUCCIÓN:
    * - Cambiar DEBUG_MODE a false
    * - Usar contraseñas fuertes para DB
    * - Habilitar HTTPS
    * - Configurar backups automáticos
    * - Revisar logs regularmente
    * 
    * SEGURIDAD:
    * - No subir config.php a Git
    * - Cambiar credenciales por defecto
    * - Restringir acceso a archivos sensibles
    * - Mantener PHP y MySQL actualizados
    * 
    * RENDIMIENTO:
    * - Habilitar caché en producción
    * - Optimizar consultas SQL
    * - Usar CDN para archivos estáticos
    * - Comprimir respuestas HTTP
    */
    ?>