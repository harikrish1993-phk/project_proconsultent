// core/ErrorHandler.php
class ErrorHandler {
    public static function register() {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');
        
        set_error_handler([__CLASS__, 'handleError']);
        set_exception_handler([__CLASS__, 'handleException']);
        register_shutdown_function([__CLASS__, 'handleShutdown']);
    }
    
    public static function handleException(Throwable $e) {
        Logger::error("Uncaught exception: {$e->getMessage()}", [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
        
        // Show user-friendly error page
        if (APP_ENV === 'production') {
            include __DIR__ . '/../include/500.php';
        } else {
            // Show debug information in development
            echo "<h1>Application Error</h1>";
            echo "<p>{$e->getMessage()}</p>";
            echo "<pre>{$e->getTraceAsString()}</pre>";
        }
        exit(1);
    }
}