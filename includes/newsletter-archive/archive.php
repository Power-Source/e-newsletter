<?php

require_once NEWSLETTER_INCLUDES_DIR . '/module-admin.php';

class NewsletterArchive extends NewsletterModuleAdmin {

    /**
     * @var NewsletterArchive
     */
    static $instance;

    function __construct($version) {
        parent::__construct('archive');
        self::$instance = $this;
    }

    function get_default_options($sub = '') {
        if (empty($sub)) {
            return [
                'show' => '',
                'date' => 1
            ];
        }
        return [];
    }

    function __get($name) {
        if ($name === 'options') {
            return $this->get_options();
        }
    }    function init() {
        // Shortcode im Frontend und Admin registrieren
        add_shortcode('newsletter_archive', array($this, 'shortcode_archive'));

        add_action('newsletter_action', array($this, 'hook_newsletter_action'));
    }

    function hook_newsletter_action($action) {
        global $wpdb;
        if ($action === 'archive') {
            $email_id = (int) $_GET['email_id'] ?? 0;
            if (empty($email_id)) {
                die('Wronf email ID');
            }

            $email = $wpdb->get_row($wpdb->prepare("select id, subject, message from " . NEWSLETTER_EMAILS_TABLE . " where private=0 and id=%d and type<>'followup' and status in ('sent', 'sending') limit 1", $email_id));

            if (empty($email)) {
                die('Email not found');
            }

            // Force the UTF-8 charset
            header('Content-Type: text/html;charset=UTF-8');
            $message = do_shortcode($this->replace($email->message), true);

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $message;
            die();
        }
    }

    function hook_newsletter_menu_newsletters($entries) {
        $entries[] = array('label' => '<i class="fa fa-archive"></i> Archive', 'url' => '?page=newsletter_archive_index', 'description' => 'Publish your sent newsletters');
        return $entries;
    }    function shortcode_archive($attrs, $content) {
        global $wpdb;
        static $email_shown = false;

        if ($email_shown)
            return '';

        if (!is_array($attrs))
            $attrs = [];
        $attrs = array_merge(['type' => 'message', 'url' => get_permalink(), 'max' => 10000, 'separator' => '-', 'title' => '', 'list'=>0], $attrs);        $type = $attrs['type'];
        $max = (int) $attrs['max'];

        // Datum-Anzeige prüfen
        $show_date = isset($this->options['date']) && $this->options['date'];
        if (isset($attrs['show_date'])) {
            $show_date = ($attrs['show_date'] === 'true' || $attrs['show_date'] === true);
        }

        $buffer = '';

        if (isset($_GET['email_id'])) {
            $email_shown = true;
            $email = $wpdb->get_row($wpdb->prepare("select id, subject, message, send_on from " . NEWSLETTER_EMAILS_TABLE . " where id=%d and private=0 and type=%s and status='sent' limit 1", (int) $_GET['email_id'], $type));
            if (!$email) {
                return 'Invalid email identifier';
            }
            $buffer .= '<h2>' . esc_html($this->replace($email->subject, $email)) . '</h2>';
            $buffer .= '<iframe class="tnp-archive-iframe" style="width: 100%; height: 800px; border:1px solid #ddd" framborder="0" ';
            $buffer .= 'src="' . home_url() . '?na=archive&email_id=' . $email->id . '"></iframe>';        } else {
            $buffer .= '<div class="tnp-archive">';
            if (!empty($attrs['title'])) {
                $buffer .= '<h2>' . $attrs['title'] . '</h2>';
            }            $emails = $wpdb->get_results($wpdb->prepare("select id, subject, send_on, options from " . NEWSLETTER_EMAILS_TABLE . " where private=0 and type=%s and status='sent' order by send_on desc limit %d", $type, $max));

            $buffer .= $content;

            if (empty($emails)) {
                $buffer .= '<p>Noch keine Newsletter versendet oder verfügbar.</p>';
            } else {
                $buffer .= '<ul>';

            $date_format = get_option('date_format');
            $time_format = get_option('time_format');
            $gmt_offset = get_option('gmt_offset') * 3600;
            if (empty($this->options['show'])) {
                $base_url = $attrs['url'];
            } else {
                $base_url = home_url();
            }

            foreach ($emails as $email) {

                // Filter by list
                if ($attrs['list']) {
                    $options = maybe_unserialize($email->options);

                    // Lists were not set, skip (actually, it means the email was sent to everyone...)
                    if (!isset($options['lists'])) {
                        continue;
                    }

                    // Not sent to the requested list
                    if (!in_array($attrs['list'], $options['lists'])) {
                        continue;
                    }
                }


                // TODO: Other replacements
                $subject = $this->replace($email->subject);

                $buffer .= '<li>';
                if ($show_date) {
                    $d = date_i18n($date_format, $email->send_on + $gmt_offset);
                    $buffer .= ' <span class="tnp-archive-date">' . esc_html($d) . '</span> ';
                    $buffer .= ' <span class="tnp-archive-separator">' . wp_strip_all_tags($attrs['separator']) . '</span> ';
                }
                if (empty($this->options['show'])) {
                    $url = NewsletterModule::add_qs($base_url, 'email_id=' . $email->id);
                    $buffer .= '<a href="' . $url . '">' . esc_html($this->replace($subject, $email)) . '</a>';
                } else {
                    $target = $this->options['show'] === 'self' ? '_self' : '_blank';
                    $url = NewsletterModule::add_qs($base_url, 'na=view&id=' . $email->id);
                    $buffer .= '<a href="' . $url . '" target="' . $target . '">' . esc_html($this->replace($subject, $email)) . '</a>';
                }                $buffer .= '</li>';
            }

            $buffer .= '</ul>';
            }
            $buffer .= '</div>';
        }
        return $buffer;
    }

    function replace($text, $email = null) {
        $text = str_replace('{name}', '', $text);
        $text = str_replace('{surname}', '', $text);
        $text = str_replace('{email_url}', '#', $text);
        $text = str_replace('{profile_url}', '#', $text);
        $text = str_replace('%7dprofile_url%7d', '#', $text);
        $text = str_replace('{unsubscription_url}', '#', $text);
        $text = str_replace('{unsubscription_confirm_url}', '#', $text);
        $text = str_replace('%7bemail_url%7d', '#', $text);
        if ($email && $email->send_on) {
                $text = Newsletter::instance()->replace_date($text, $email->send_on);
        }
        return $text;
    }
}

// Instanz der Klasse erzeugen
$newsletter_archive = new NewsletterArchive('1.0');

// init() beim WordPress-Init-Hook aufrufen
add_action('init', array($newsletter_archive, 'init'));
