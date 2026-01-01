<?php

class NewsletterLock {

    static $instance;

    function __construct($version) {
        self::$instance = $this;
        $this->setup_options();
    }

    private function setup_options() {
        // Hier ggf. Standardwerte für Optionen setzen, falls benötigt.
    }

    function init() {
        if (is_admin()) {
            add_filter('newsletter_menu_subscription', [$this, 'hook_newsletter_menu_subscription']);
        }

        add_action('newsletter_action', [$this, 'hook_newsletter_action'], 10, 2);
        add_shortcode('newsletter_lock', [$this, 'shortcode_newsletter_lock']);

        // Lock configured for tags or categories?
        if (!empty($this->options['ids'])) {
            add_filter('the_content', [$this, 'hook_the_content']);
        }
    }

    /**
     * Compatibility code.
     */
    function hook_newsletter_action($action, $user) {

        switch ($action) {

            case 'ul':

                if ($user == null || $user->status != 'C') {
                    echo 'Subscriber not found, sorry.';
                    die();
                }

//                if (isset($user->_trusted) && !$user->_trusted) {
//                    echo 'Subscriber not found, sorry.';
//                    die();
//                }
                //setcookie('newsletter', $user->id . '-' . $user->token, time() + 60 * 60 * 24 * 365, '/');
                if (empty($this->options['url'])) {
                    header('Location: ' . home_url());
                } else {
                    header('Location: ' . $this->options['url']);
                }

                die();
        }
    }

    function hook_newsletter_menu_subscription($entries) {
        $entries[] = array('label' => 'Locked Content', 'url' => '?page=newsletter_lock_index');
        return $entries;
    }

    function admin_menu() {
        add_submenu_page('newsletter_main_index', 'Locked Content', '<span class="tnp-side-menu">Locked Content</span>', 'exist', 'newsletter_lock_index',
            function () {
                require __DIR__ . '/admin/index.php';
            }
        );
    }

    function hook_the_content($content) {
        global $post, $cache_stop;

        if (current_user_can('publish_posts')) {
            return $content;
        }

        if (!$post || !isset($post->post_name)) {
            return $content;
        }

        $ids = explode(',', str_replace(' ', '', $this->options['ids']));
        $ids = array_filter($ids);

        if (has_tag($ids) || in_category($ids) || in_array($post->post_name, $ids)) {
            $cache_stop = true;
            $user = Newsletter::instance()->get_current_user();
            //if ($user == null || $user->status != 'C' || isset($user->_trusted) && !$user->_trusted) {
            if ($user == null || $user->status !== 'C') {
                $language = $this->get_current_language();
                $key = 'message' . (empty($language) ? '' : '_' . $language);
                $buffer = Newsletter::instance()->replace($this->options[$key]);
                return '<div class="tnp-lock newsletter-lock">' . do_shortcode($buffer) . '</div>';
            }
        }

        return $content;
    }

    function shortcode_newsletter_lock_dummy($attrs, $content = null) {
        return $content;
    }

    function shortcode_newsletter_lock($attrs, $content = null) {

        $this->found = true;

        if (current_user_can('publish_posts')) {
            return do_shortcode($content);
        }

        $user = Newsletter::instance()->get_current_user();

        if ($user != null && $user->status === 'C') {
            //if (!isset($user->_trusted) || $user->_trusted) {
            return do_shortcode($content);
            //}
        }

        $language = $this->get_current_language();
        $key = 'message' . (empty($language) ? '' : '_' . $language);

        $buffer = $this->options[$key];

        if (empty($buffer)) {
            $buffer = '[newsletter_form';
            if (isset($attrs['confirmation_url'])) {
                if ($attrs['confirmation_url'] === '#') {
                    $attrs['confirmation_url'] = sanitize_url(wp_unslash($_SERVER['REQUEST_URI']));
                }
                $buffer .= ' confirmation_url="' . $attrs['confirmation_url'] . '"';
            }
            $buffer .= ']';
        } else {
            // Compatibility
            $buffer = str_ireplace('<form', '<form method="post" action="' . esc_attr(home_url('/')) . '?na=s"', $buffer);
            $buffer = Newsletter::instance()->replace($buffer, null, null, 'lock');
        }

        $buffer = do_shortcode($buffer);

        return '<div class="tnp-lock newsletter-lock">' . $buffer . '</div>';
    }
}
