<?php

/**
 * Newsletter REST API Core Integration
 * 
 * Integriert die Newsletter API direkt ins Hauptplugin
 */

class NewsletterRestApi extends NewsletterModule {

    /**
     * @var NewsletterRestApi
     */
    static $instance;
    
    var $table_name;
    static $authenticated = false;
    var $key = '';

    static function instance() {
        return self::$instance;
    }

    function __construct() {
        global $wpdb;

        self::$instance = $this;
        $this->table_name = $wpdb->prefix . 'newsletter_api_keys';

        parent::__construct('api');
        
        // REST API initialisieren
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('init', array($this, 'init_api'));
    }

    function init() {
        global $wpdb;
        parent::init();

        // API-Tabelle erstellen
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        $sql = "CREATE TABLE " . $this->table_name . " (
            id int(11) NOT NULL AUTO_INCREMENT,
            api_key varchar(255) NOT NULL,
            api_secret varchar(255) NOT NULL,
            name varchar(255) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            last_used datetime NULL,
            permissions text,
            PRIMARY KEY (id),
            UNIQUE KEY api_key (api_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        dbDelta($sql);
    }

    function init_api() {
        // API Authentication laden
        require_once NEWSLETTER_DIR . '/includes/api/authentication.php';
        require_once NEWSLETTER_DIR . '/includes/api/rest-controller.php';
        
        // V2 Controllers laden
        require_once NEWSLETTER_DIR . '/includes/api/v2/subscribers-controller.php';
        require_once NEWSLETTER_DIR . '/includes/api/v2/lists-controller.php';
        require_once NEWSLETTER_DIR . '/includes/api/v2/newsletters-controller.php';
    }

    function register_routes() {
        // V2 API Routes registrieren
        $subscribers_controller = new Newsletter_REST_Subscribers_Controller();
        $subscribers_controller->register_routes();
        
        $lists_controller = new Newsletter_REST_Lists_Controller();
        $lists_controller->register_routes();
        
        $newsletters_controller = new Newsletter_REST_Newsletters_Controller();
        $newsletters_controller->register_routes();
    }

    function admin_menu() {
        $this->add_admin_page('index', __('REST API', 'newsletter'));
    }

    /**
     * API Key generieren
     */
    function generate_api_key() {
        return 'tnp_' . wp_generate_password(32, false);
    }

    /**
     * API Secret generieren
     */
    function generate_api_secret() {
        return wp_generate_password(64, false);
    }

    /**
     * API Key erstellen
     */
    function create_api_key($name, $permissions = array()) {
        global $wpdb;

        $api_key = $this->generate_api_key();
        $api_secret = $this->generate_api_secret();

        $result = $wpdb->insert(
            $this->table_name,
            array(
                'api_key' => $api_key,
                'api_secret' => $api_secret,
                'name' => $name,
                'permissions' => json_encode($permissions),
                'status' => 'active'
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        if ($result) {
            return array(
                'api_key' => $api_key,
                'api_secret' => $api_secret
            );
        }

        return false;
    }

    /**
     * API Key validieren
     */
    function validate_api_key($api_key, $api_secret) {
        global $wpdb;

        $key_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE api_key = %s AND api_secret = %s AND status = 'active'",
            $api_key,
            $api_secret
        ));

        if ($key_data) {
            // Last used aktualisieren
            $wpdb->update(
                $this->table_name,
                array('last_used' => current_time('mysql')),
                array('id' => $key_data->id),
                array('%s'),
                array('%d')
            );

            return $key_data;
        }

        return false;
    }

    /**
     * Alle API Keys abrufen
     */
    function get_api_keys() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created DESC");
    }

    /**
     * API Key löschen
     */
    function delete_api_key($id) {
        global $wpdb;
        return $wpdb->delete($this->table_name, array('id' => $id), array('%d'));
    }

    /**
     * API Key deaktivieren
     */
    function deactivate_api_key($id) {
        global $wpdb;
        return $wpdb->update(
            $this->table_name,
            array('status' => 'inactive'),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Standard API Permissions
     */
    function get_default_permissions() {
        return array(
            'subscribers' => array('read', 'write', 'delete'),
            'lists' => array('read', 'write', 'delete'),
            'newsletters' => array('read', 'write'),
            'subscriptions' => array('read', 'write'),
            'extrafields' => array('read', 'write', 'delete')
        );
    }
}

// API-Modul initialisieren
new NewsletterRestApi();
