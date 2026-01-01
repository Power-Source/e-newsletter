<?php

defined('ABSPATH') || exit;

require_once NEWSLETTER_INCLUDES_DIR . '/module-admin.php';

class NewsletterSmtp extends NewsletterModuleAdmin {

    /**
     * @var NewsletterSmtp
     */
    static $instance;

    public function __construct($version) {
        parent::__construct('smtp');
        self::$instance = $this;
    }

    function get_default_options($sub = '') {
        if (empty($sub)) {
            return [
                'enabled' => 0,
                'host' => '',
                'port' => 587,
                'user' => '',
                'pass' => '',
                'secure' => 'tls',
                'from_email' => '',
                'from_name' => '',
                'debug' => 0
            ];
        }
        return [];
    }

    function __get($name) {
        if ($name === 'options') {
            return $this->get_options();
        }
    }

    function init() {
        // SMTP als integrierte Funktion verfügbar machen
        add_action('newsletter_register_mailer', array($this, 'register_mailer'));
    }

    function get_title() {
        return 'SMTP-Konfiguration';
    }

    public function register_mailer() {
        // Nur SMTP-Mailer registrieren, wenn SMTP aktiviert ist
        if (!empty($this->options['enabled'])) {
            require_once __DIR__ . '/classes/mailer.php';
            Newsletter::instance()->mailer = new NewsletterSmtpMailer($this->options);
        }
    }

    public function get_mailer() {
        // Diese Methode wird für direkte Aufrufe (z.B. Tests) benötigt
        require_once __DIR__ . '/classes/mailer.php';
        return new NewsletterSmtpMailer($this->options);
    }

    public function get_test_message($test_email = '') {
        if (empty($test_email)) {
            $test_email = Newsletter::instance()->get_option('sender_email');
        }
        
        $message = new TNP_Email();
        $message->to = $test_email;
        $message->subject = '[SMTP Test] Newsletter Plugin Test E-Mail';
        $message->body = '<h1>SMTP Test erfolgreich!</h1><p>Diese E-Mail wurde über SMTP versendet.</p>';
        $message->body_text = 'SMTP Test erfolgreich! Diese E-Mail wurde über SMTP versendet.';
        
        return $message;
    }

}

// Instanz der Klasse erzeugen
$newsletter_smtp = new NewsletterSmtp('1.0');

// init() beim WordPress-Init-Hook aufrufen
add_action('init', array($newsletter_smtp, 'init'));
