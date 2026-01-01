<?php

define('NEWSLETTER_ADMIN_HEADER', NEWSLETTER_DIR . '/header.php');

require_once __DIR__ . '/includes/module-admin.php';
require_once __DIR__ . '/main/main-admin.php';
require_once __DIR__ . '/subscription/subscription-admin.php';
require_once __DIR__ . '/unsubscription/unsubscription-admin.php';
require_once __DIR__ . '/users/users-admin.php';
require_once __DIR__ . '/emails/emails-admin.php';
require_once __DIR__ . '/system/system-admin.php';
require_once __DIR__ . '/statistics/statistics-admin.php';
require_once __DIR__ . '/profile/profile-admin.php';
require_once __DIR__ . '/composer/composer-admin.php';
require_once __DIR__ . '/main/autoresponder.php';

class NewsletterAdmin extends NewsletterModuleAdmin {

    static $instance = null;

    static $menu = ['settings' => [], 'subscription' => [], 'newsletters' => [], 'forms' => [], 'subscribers' => []];

    /**
     *
     * @return NewsletterAdmin
     */
    static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        parent::__construct('main');
        add_action('wp_loaded', [$this, 'hook_wp_loaded'], 1);
        add_action('admin_init', [$this, 'hook_admin_init']);
        add_action('admin_head', [$this, 'hook_admin_head']);
        add_action('in_admin_header', [$this, 'hook_in_admin_header'], 1000);
        add_action('admin_menu', [$this, 'hook_admin_menu']);

        // Protection against strange schedule removal on some installations
        if (!wp_next_scheduled('newsletter') && (!defined('WP_INSTALLING') || !WP_INSTALLING)) {
            wp_schedule_event(time() + 30, 'newsletter', 'newsletter');
        }

        //add_action('admin_menu', [$this, 'add_extensions_menu'], 90);

        add_action('admin_enqueue_scripts', [$this, 'hook_admin_enqueue_scripts'], 999);
    }

    function hook_wp_loaded() {

        self::$is_admin_page = isset($_GET['page']) && strpos($_GET['page'], 'newsletter_') === 0;

        NewsletterMainAdmin::instance()->wp_loaded();
        //NewsletterEmailsAdmin::instance()->wp_loaded();
        NewsletterComposerAdmin::instance()->wp_loaded();

        if (self::$is_admin_page) {

            // Specpal header for HTML coded forms
            if (isset($_GET['page']) && $_GET['page'] === 'newsletter_subscription_forms') {
                header('X-XSS-Protection: 0');
            }
        }

        add_action('admin_bar_menu', [$this, 'hook_admin_bar_menu'], 500);
    }

    /**
     *
     * @global wpdb $wpdb
     * @param WP_Admin_Bar $admin_bar
     * @return type
     *
     * https://developer.wordpress.org/reference/hooks/admin_bar_menu/
     */
    function hook_admin_bar_menu(WP_Admin_Bar $admin_bar) {
        global $wpdb;

        if (!current_user_can('manage_options')) {
            return;
        }

        $data = get_transient('newsletter_topbar');
        if (!$data || !is_array($data)) {
            $data = $wpdb->get_row($wpdb->prepare("select count(*) as count, sum(total) as total, sum(sent) as sent from " . NEWSLETTER_EMAILS_TABLE . " where status='sending' and send_on<%d", time()), ARRAY_A);
            if (!$data) {
                $data = ['count' => 0];
            }
            //$data['updated_at'] = time();
            set_transient('newsletter_topbar', $data, 600);
        }

        if (!$data['count']) {
            return;
        }

        $admin_bar->add_menu(array(
            'id' => 'newsletter',
            'parent' => null,
            'group' => null,
            'title' => '<span class="ab-icon"></span>' . ((int) $data['count']) . ' sending (' . ((int) $data['sent']) . '/' . ((int) $data['total']) . ')',
            'href' => admin_url('admin.php?page=newsletter_system_delivery#sending'),
            'meta' => [
                'title' => __('Newsletters sending (updated every 10 minutes)', 'newsletter'),
            ]
        ));
    }

    function hook_in_admin_header() {
        if (self::$is_admin_page) {
            remove_all_actions('admin_notices');
            remove_all_actions('all_admin_notices');
        }
        add_action('admin_notices', [$this, 'hook_admin_notices']);
    }

    function hook_admin_notices() {
        NewsletterMainAdmin::instance()->admin_notices();
    }

    function hook_admin_init() {
        // Verificare il contesto
        if (isset($_GET['page']) && $_GET['page'] === 'newsletter_main_welcome') {
            return;
        }
        if (get_option('newsletter_show_welcome')) {
            delete_option('newsletter_show_welcome');
            wp_redirect(admin_url('admin.php?page=newsletter_main_welcome'));
        }

        if (self::$is_admin_page) {
            // Remove the emoji replacer to save to database the original emoji characters (see even woocommerce for the same problem)
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
        }
    }

    function hook_admin_head() {
    }

    function hook_admin_enqueue_scripts() {

        $url = plugins_url('e-newsletter');

        // Styles for the left side menu
        wp_enqueue_style('tnp-admin-global', $url . '/admin/css/global.css', [], NEWSLETTER_VERSION);

        // Styles and scripts only for our admin pages
        if (self::is_admin_page()) {
            // Lade jQuery UI direkt aus dem Plugin-Verzeichnis (unabhängig von WordPress/ClassicPress)
            wp_enqueue_script('newsletter-jquery-ui-draggable', $url . '/vendor/jquery-ui/jquery-ui-draggable.min.js', ['jquery'], NEWSLETTER_VERSION, true);
            wp_enqueue_script('newsletter-jquery-ui-sortable', $url . '/vendor/jquery-ui/jquery-ui-sortable.min.js', ['jquery'], NEWSLETTER_VERSION, true);
            
            // jQuery UI Initialisierung und Verfügbarkeits-Check
            wp_enqueue_script('newsletter-jquery-ui-init', $url . '/assets/js/newsletter-jquery-ui-init.js', ['jquery', 'newsletter-jquery-ui-draggable', 'newsletter-jquery-ui-sortable'], NEWSLETTER_VERSION, true);
            
            wp_enqueue_media();

            //wp_enqueue_style('psource-ui-draggable', $url . '/assets/psource-ui/draggable/psource-draggable.css', [], NEWSLETTER_VERSION);
            //wp_enqueue_script('psource-ui-draggable', $url . '/assets/psource-ui/draggable/psource-draggable.js', [], NEWSLETTER_VERSION, true);

            wp_enqueue_script('psource-ui-tabs', $url . '/assets/psource-ui/tabs/psource-tabs.js', ['jquery'], NEWSLETTER_VERSION, true);
            wp_enqueue_style('psource-ui-tabs', $url . '/assets/psource-ui/tabs/psource-tabs.css', [], NEWSLETTER_VERSION);

            wp_enqueue_script('psource-ui-accordion', $url . '/assets/psource-ui/accordion/psource-accordion.js', ['jquery'], NEWSLETTER_VERSION, true);
            wp_enqueue_style('psource-ui-accordion', $url . '/assets/psource-ui/accordion/psource-accordion.css', [], NEWSLETTER_VERSION);

            wp_enqueue_style('psource-ui-tooltip', $url . '/assets/psource-ui/tooltip/psource-tooltip.css', [], NEWSLETTER_VERSION);
            wp_enqueue_script('psource-ui-tooltip', $url . '/assets/psource-ui/tooltip/psource-tooltip.js', [], NEWSLETTER_VERSION, true);
            
            // PSource UI Modal System
            wp_enqueue_style('psource-ui-modal', $url . '/assets/psource-ui/modal/psource-modal.css', [], NEWSLETTER_VERSION);
            wp_enqueue_script('psource-ui-modal', $url . '/assets/psource-ui/modal/psource-modal.js', [], NEWSLETTER_VERSION, true);
            
            wp_enqueue_style('tnp-admin-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap');

            wp_enqueue_script('tnp-admin', $url . '/admin/js/all.js', ['jquery'], NEWSLETTER_VERSION);

            $data = [];
            $data['site_url'] = site_url('/');
            $data['newsletter_url'] = plugins_url('e-newsletter');
            wp_localize_script('tnp-admin', 'NewsletterControls', $data);

            wp_enqueue_style('tnp-select2', $url . '/vendor/select2/css/select2.min.css', [], NEWSLETTER_VERSION);
            wp_enqueue_script('tnp-select2', $url . '/vendor/select2/js/select2.min.js', ['jquery'], NEWSLETTER_VERSION);

            wp_enqueue_style('tnp-admin-fontawesome', $url . '/vendor/fa/css/all.min.css', [], NEWSLETTER_VERSION);
            wp_enqueue_style('tnp-admin-jquery-ui', $url . '/vendor/jquery-ui/jquery-ui.min.css', [], NEWSLETTER_VERSION);

            // Entfernt: jQuery Modal - wir verwenden PSource UI Modal
            // wp_enqueue_script('tnp-jquery-modal', $url . '/vendor/jquery-modal/jquery.modal.min.js', ['jquery', 'newsletter-jquery-ui-init'], NEWSLETTER_VERSION);
            // wp_enqueue_style('tnp-jquery-modal', $url . '/vendor/jquery-modal/jquery.modal.min.css', [], NEWSLETTER_VERSION);
            
            // Entfernt: Newsletter Modal Init - nicht mehr benötigt
            // wp_enqueue_script('newsletter-modal-init', $url . '/assets/js/newsletter-modal-init.js', ['jquery', 'tnp-jquery-modal'], NEWSLETTER_VERSION, true);

            wp_enqueue_style('tnp-admin', $url . '/admin/css/all.css', [], NEWSLETTER_VERSION);

            $translations_array = array(
                'save_to_update_counter' => __('Save the newsletter to update the counter!', 'newsletter')
            );
            wp_localize_script('tnp-admin', 'tnp_translations', $translations_array);

            wp_enqueue_script('tnp-jquery-vmap', $url . '/vendor/jqvmap/jquery.vmap.min.js', ['jquery'], NEWSLETTER_VERSION);
            wp_enqueue_script('tnp-jquery-vmap-world', $url . '/vendor/jqvmap/jquery.vmap.world.js', ['tnp-jquery-vmap'], NEWSLETTER_VERSION);
            wp_enqueue_style('tnp-jquery-vmap', $url . '/vendor/jqvmap/jqvmap.min.css', [], NEWSLETTER_VERSION);

            wp_register_script('tnp-chart', $url . '/vendor/chartjs/Chart.min.js', ['jquery'], NEWSLETTER_VERSION);

            wp_enqueue_script('tnp-color-picker', $url . '/vendor/spectrum-prefixed/spectrum.js', ['jquery'], NEWSLETTER_VERSION);
            wp_enqueue_style('tnp-color-picker', $url . '/vendor/spectrum-prefixed/spectrum.css', [], NEWSLETTER_VERSION);

            wp_enqueue_script('tnp-toastify', $url . '/vendor/toastify/toastify.js', [], NEWSLETTER_VERSION);
            wp_enqueue_style('tnp-toastify', $url . '/vendor/toastify/toastify.css', [], NEWSLETTER_VERSION);
        }
    }

    public function hook_admin_menu() {

        if (!Newsletter::instance()->is_allowed()) {
            return;
        }

        add_menu_page('Newsletter', 'Newsletter', 'manage_options', 'newsletter_main_index', '', plugins_url('e-newsletter') . '/admin/images/menu-icon.png', '40');

        NewsletterMainAdmin::instance()->admin_menu();
        NewsletterSubscriptionAdmin::instance()->admin_menu();
        NewsletterUsersAdmin::instance()->admin_menu();
        NewsletterEmailsAdmin::instance()->admin_menu();
        NewsletterSystemAdmin::instance()->admin_menu();
        NewsletterStatisticsAdmin::instance()->admin_menu();
        NewsletterProfileAdmin::instance()->admin_menu();
        NewsletterUnsubscriptionAdmin::instance()->admin_menu();

        // Main pages (to get them in the right order
        NewsletterMainAdmin::instance()->add_menu_page('index', __('Dashboard', 'newsletter'));
        NewsletterSubscriptionAdmin::instance()->add_menu_page('options', __('Subscription', 'newsletter'));
        NewsletterSubscriptionAdmin::instance()->add_menu_page('sources', __('Forms', 'newsletter'));
        NewsletterEmailsAdmin::instance()->add_menu_page('index', __('Newsletters', 'newsletter'));
        NewsletterUsersAdmin::instance()->add_menu_page('index', __('Subscribers', 'newsletter'));
        NewsletterSubscriptionAdmin::instance()->add_menu_page('lists', __('Lists', 'newsletter'));
        if (current_user_can('administrator')) {
            NewsletterMainAdmin::instance()->add_menu_page('main', __('Settings', 'newsletter'));
        }

        global $newsletterLock;
        add_submenu_page(
            'newsletter_main_index',
            'Locked Content',
            '<span class="tnp-side-menu">Locked Content</span>',
            'manage_options',
            'newsletter_lock_index',
            function () use ($newsletterLock) {
                $lock = $newsletterLock;
                require NEWSLETTER_DIR . '/includes/newsletter-lock/admin/index.php';
            }
        );

        add_submenu_page(
            'newsletter_main_index',
            __('Autoresponder', 'newsletter'),
            '<span class="tnp-side-menu">Autoresponder</span>',
            'manage_options',
            'newsletter_main_autoresponderindex',
            function () {
                require_once NEWSLETTER_DIR . '/main/autoresponderindex.php';
            }
        );

        global $newsletterInstasend;
        add_submenu_page(
            'newsletter_main_index',
            'Instasend',
            '<span class="tnp-side-menu">Instasend</span>',
            'manage_options',
            'newsletter_instasend_index',
            function () use ($newsletterInstasend) {
                $instasend = $newsletterInstasend;
                require NEWSLETTER_DIR . '/includes/newsletter-instasend/index.php';
            }
        );
        
        add_submenu_page(
            'newsletter_main_index',
            'WP Users Integration',
            'WP Users Integration',
            'manage_options',
            'newsletter_wpusers_index',
            function () {
                require_once NEWSLETTER_DIR . '/includes/newsletter-wpusers/index.php';
            }
        );

        add_submenu_page(
            'newsletter_main_index',
            'Newsletter-Archiv',
            'Newsletter-Archiv',
            'manage_options',
            'newsletter_archive_index',
            function () {
                require_once NEWSLETTER_DIR . '/includes/newsletter-archive/admin/index.php';
            }
        );

        add_submenu_page(
            'newsletter_main_index',
            'SMTP',
            'SMTP',
            'manage_options',
            'newsletter_smtp_index',
            function () {
                require_once NEWSLETTER_DIR . '/includes/newsletter-smtp/admin/index.php';
            }
        );

        global $newsletterForms;

        // Index-Seite
        add_submenu_page(
            'newsletter_main_index',
            'Forms',
            '<span class="tnp-side-menu">Forms</span>',
            'manage_options',
            'newsletter_forms_index',
            function () use ($newsletterForms) {
                $forms = $newsletterForms;
                require NEWSLETTER_DIR . '/includes/newsletter-forms/admin/index.php';
            }
        );

        // Edit-Seite (versteckt)
        add_submenu_page(
            '',
            'Edit Form',
            '', // leer, damit nicht im Menü sichtbar
            'manage_options',
            'newsletter_forms_edit',
            function () use ($newsletterForms) {
                $forms = $newsletterForms;
                require NEWSLETTER_DIR . '/includes/newsletter-forms/admin/edit.php';
            }
        );

        // REST API Menu
        add_submenu_page(
            'newsletter_main_index',
            __('REST API', 'newsletter'),
            '<span class="tnp-side-menu">REST API</span>',
            'manage_options',
            'newsletter_api_index',
            function () {
                $api = NewsletterRestApi::instance();
                require NEWSLETTER_DIR . '/includes/api/admin/index.php';
            }
        );

    }
}

NewsletterAdmin::instance();
NewsletterEmailsAdmin::instance(); // Required to activate the newsletter_action hook
NewsletterUsersAdmin::instance();
