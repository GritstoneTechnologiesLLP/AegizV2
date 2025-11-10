<?php
/**
 * AEGIZ Application Configuration
 * 
 * This file contains all configuration settings for the application.
 * Update the API_BASE_URL here to change it across all pages.
 */

// API Configuration
$envApiUrl = getenv('API_BASE_URL');
$defaultApiUrl = 'http://127.0.0.1:8000';
define('API_BASE_URL', rtrim($envApiUrl ?: $defaultApiUrl, '/'));

// Application Configuration
define('APP_NAME', 'AEGIZ');
define('APP_VERSION', '1.0.0');

// Timezone
date_default_timezone_set('UTC');

/**
 * Get API Base URL
 * @return string
 */
function getApiBaseUrl() {
    return API_BASE_URL;
}

/**
 * Output JavaScript configuration
 * Call this function in your PHP files to output JS config
 */
function outputJsConfig() {
    $apiUrl = defined('API_BASE_URL') ? API_BASE_URL : 'http://127.0.0.1:8000';
    ?>
    <script>
        // API Configuration
        window.API_CONFIG = {
            baseUrl: '<?php echo htmlspecialchars($apiUrl, ENT_QUOTES, 'UTF-8'); ?>',
            appName: '<?php echo htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8'); ?>',
            appVersion: '<?php echo htmlspecialchars(APP_VERSION, ENT_QUOTES, 'UTF-8'); ?>'
        };
        
        // Alias for backward compatibility - use var for global scope
        var API_BASE_URL = window.API_CONFIG.baseUrl;
        
        console.log('API Configuration loaded:', window.API_CONFIG);
        console.log('API_BASE_URL set to:', API_BASE_URL);
    </script>
    <?php
}
?>

