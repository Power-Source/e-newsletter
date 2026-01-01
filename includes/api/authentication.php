<?php

/**
 * Newsletter REST API Authentication
 */

class Newsletter_REST_Authentication {

    public function __construct() {
        add_filter('rest_authentication_errors', array($this, 'authenticate'), 10, 1);
    }

    /**
     * Authentifizierung für Newsletter API Endpoints
     */
    public function authenticate($result) {
        // Nur für Newsletter API Endpoints
        if (!$this->is_newsletter_api_request()) {
            return $result;
        }

        // API Key Authentication
        $api_key = $this->get_api_key();
        $api_secret = $this->get_api_secret();

        if (!$api_key || !$api_secret) {
            return new WP_Error(
                'newsletter_api_missing_credentials',
                __('API-Schlüssel und Secret erforderlich', 'newsletter'),
                array('status' => 401)
            );
        }

        $api = NewsletterRestApi::instance();
        $key_data = $api->validate_api_key($api_key, $api_secret);

        if (!$key_data) {
            return new WP_Error(
                'newsletter_api_invalid_credentials',
                __('Ungültige API-Zugangsdaten', 'newsletter'),
                array('status' => 401)
            );
        }

        // Erfolgreiche Authentifizierung
        NewsletterRestApi::$authenticated = true;
        return true;
    }

    /**
     * Prüft ob es sich um einen Newsletter API Request handelt
     */
    private function is_newsletter_api_request() {
        $rest_route = $GLOBALS['wp']->query_vars['rest_route'] ?? '';
        return strpos($rest_route, 'newsletter/') === 1; // Entfernt führenden Slash
    }

    /**
     * API Key aus Request holen
     */
    private function get_api_key() {
        // Header prüfen
        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            return sanitize_text_field($_SERVER['HTTP_X_API_KEY']);
        }

        // Query Parameter prüfen
        if (isset($_GET['api_key'])) {
            return sanitize_text_field($_GET['api_key']);
        }

        // POST Parameter prüfen
        if (isset($_POST['api_key'])) {
            return sanitize_text_field($_POST['api_key']);
        }

        return null;
    }

    /**
     * API Secret aus Request holen
     */
    private function get_api_secret() {
        // Header prüfen
        if (isset($_SERVER['HTTP_X_API_SECRET'])) {
            return sanitize_text_field($_SERVER['HTTP_X_API_SECRET']);
        }

        // Query Parameter prüfen
        if (isset($_GET['api_secret'])) {
            return sanitize_text_field($_GET['api_secret']);
        }

        // POST Parameter prüfen
        if (isset($_POST['api_secret'])) {
            return sanitize_text_field($_POST['api_secret']);
        }

        return null;
    }
}

// Authentication initialisieren
new Newsletter_REST_Authentication();
