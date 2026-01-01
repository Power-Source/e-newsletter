<?php
defined('ABSPATH') || exit;

class NewsletterAutomated {
    static $instance;

    function __construct() {
        self::$instance = $this;
    }

    function init() {
        add_action('admin_menu', array($this, 'add_menu'));

        // Cronjob registrieren (nur einmal)
        if (!wp_next_scheduled('tnp_automated_cron')) {
            wp_schedule_event(time(), 'hourly', 'tnp_automated_cron');
        }

        // Cron-Handler registrieren
        add_action('tnp_automated_cron', array($this, 'tnp_automated_cron'));
    }

    function panel_newsletters() {
        include NEWSLETTER_DIR . '/main/automatednewsletters.php';
    }

    function panel_edit() {
        include NEWSLETTER_DIR . '/main/automatededit.php';
    }

    function panel_index() {
        include NEWSLETTER_DIR . '/main/automatedindex.php';
    }

    function panel() {
        $this->panel_index();
    }

    function add_menu() {
        add_submenu_page(
            'newsletter_main_index',
            __('Automated', 'newsletter'),
            '<span class="tnp-side-menu">Automated</span>',
            'manage_options',
            'newsletter_main_automatedindex',
            array($this, 'panel_index')
        );
        // Edit-Seite (versteckt)
        add_submenu_page(
            '',
            __('Edit Automated Channel', 'newsletter'),
            '',
            'manage_options',
            'newsletter_main_automatededit',
            array($this, 'panel_edit')
        );
        // Template-Seite (versteckt)
        add_submenu_page(
            '',
            __('Automated Template', 'newsletter'),
            '',
            'manage_options',
            'newsletter_main_automatedtemplate',
            array($this, 'panel_template')
        );
        // Newsletters-Seite (versteckt)
        add_submenu_page(
            '',
            __('Automated Newsletters', 'newsletter'),
            '',
            'manage_options',
            'newsletter_main_automatednewsletters',
            array($this, 'panel_newsletters')
        );
        // Index-Seite (versteckt, aber für Direktaufruf)
        add_submenu_page(
            '',
            __('Automated Index', 'newsletter'),
            '',
            'manage_options',
            'newsletter_main_automatedindex',
            array($this, 'panel_index')
        );
    }

    function panel_template() {
        include NEWSLETTER_DIR . '/main/automatedtemplate.php';
    }

    function tnp_automated_cron() {
        $channels = get_option('tnp_automated_channels', []);
        $now = current_time('timestamp');

        foreach ($channels as $channel) {
            // 1. Ist der Channel aktiviert?
            if (empty($channel['enabled'])) continue;

            // 2. Ist heute ein Versand-Tag?
            $weekday = (int)date('N', $now); // 1=Montag ... 7=Sonntag
            if (empty($channel['day_' . $weekday])) continue;

            // 3. Ist die Uhrzeit erreicht?
            $hour = (int)date('G', $now); // 0-23
            if ($hour < (int)$channel['hour']) continue;

            // 4. Wurde heute schon gesendet?
            $last_sent = isset($channel['last_sent']) ? (int)$channel['last_sent'] : 0;
            if (date('Y-m-d', $last_sent) === date('Y-m-d', $now)) continue;

            // 5. Gibt es neue Inhalte?
            $args = [
                'post_type'      => 'post',
                'posts_per_page' => 1,
                'post_status'    => 'publish',
                'date_query'     => [
                    ['after' => date('Y-m-d H:i:s', $last_sent)]
                ]
            ];
            $query = new WP_Query($args);
            $has_new_content = $query->have_posts();
            if (!$has_new_content) continue;

            // 6. Template laden
            $template_option = get_option('tnp_automated_template_' . $channel['id']);
            if (!$template_option) {
                error_log('Kein Template für Channel ' . $channel['id']);
                continue;
            }

            // 7. Doppelte Newsletter verhindern
            global $wpdb;
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . NEWSLETTER_EMAILS_TABLE . " WHERE type = %s AND DATE(FROM_UNIXTIME(send_on)) = %s",
                'automated_' . $channel['id'],
                date('Y-m-d', $now)
            ));
            if ($exists) {
                error_log('Newsletter für Channel ' . $channel['id'] . ' heute schon in Queue.');
                continue;
            }

            // 8. Newsletter-Inhalt generieren (Composer-Logik)
            if (class_exists('TNP_Composer')) {
                $composer = new TNP_Composer();
                $message = $composer->render($template_option, [
                    'channel' => $channel,
                    // weitere Platzhalter/Variablen je nach Bedarf
                ]);
            } else {
                $message = $template_option; // Fallback: rohes Template
            }

            // 9. Betreff/Empfängerliste Fallback
            $subject = !empty($channel['subject']) ? $channel['subject'] : __('Automatischer Newsletter', 'newsletter');
            $list = isset($channel['list']) && $channel['list'] !== '' ? $channel['list'] : 0;

            // 10. Newsletter-Eintrag erzeugen (wie delivery.php)
            $email = [
                'subject' => $subject,
                'message' => $message,
                'type' => 'automated_' . $channel['id'],
                'status' => 'new',
                'send_on' => $now,
                'track' => $channel['track'],
                'list' => $list,
                'created' => $now,
                'editor' => 'composer',
                // weitere Felder je nach Bedarf
            ];

            $result = $wpdb->insert(NEWSLETTER_EMAILS_TABLE, $email);
            if ($result === false) {
                error_log('Fehler beim Einfügen des Newsletters für Channel ' . $channel['id'] . ': ' . $wpdb->last_error);
                continue;
            }

            // 11. Letzten Versandzeitpunkt speichern
            $channel['last_sent'] = $now;
            $channels[$channel['id']] = $channel;
        }

        // Channels mit aktualisiertem last_sent speichern
        update_option('tnp_automated_channels', $channels);
    }
}