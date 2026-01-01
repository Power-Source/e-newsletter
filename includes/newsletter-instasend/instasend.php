<?php

class NewsletterInstasend {

    static $instance;
    private static $nonce_name = 'tnp_instasend_nonce';
    private static $post_meta_key = 'tnp_newsletter_id';
    public $version;

    function __construct($version) {
        self::$instance = $this;
        $this->version = $version;
        $this->setup_options();
    }

    private function setup_options() {
        // Hier ggf. Standardwerte für Optionen setzen, falls benötigt.
    }

    function upgrade($first_install = false) {

    }

    function init() {

        if (is_admin()) {

            add_action('admin_enqueue_scripts', array($this, 'hook_admin_enqueue_scripts'));
            add_action('add_meta_boxes', array($this, 'add_instasend_post_metabox'), 1);

            add_action('wp_ajax_instasend_create_newsletter', array($this, 'create_newsletter_ajax_hook'));
            add_action('wp_ajax_instasend_delete_newsletter', array($this, 'delete_newsletter_ajax_hook'));
        }
    }

    function hook_newsletter_menu_newsletter($entries) {
        $entries[] = array(
            'label' => '<i class="fas fa-bolt"></i> Instasend',
            'url' => '?page=newsletter_instasend_index',
            'description' => 'Quick newsletter from a post'
        );

        return $entries;
    }

    function hook_admin_menu() {
        add_submenu_page('newsletter_main_index', 'Instasend', '<span class="tnp-side-menu">Instasend</span>', 'manage_options', 'newsletter_instasend_index', array(
            $this,
            'menu_page_index'
        ));
    }

    function menu_page_index() {
        global $wpdb;
        require dirname(__FILE__) . '/index.php';
    }

	function hook_admin_enqueue_scripts() {
		$plugin_url = plugin_dir_url( __FILE__ );

		$screen = get_current_screen();
		if ( $screen->post_type === 'post' && $screen->base == 'post' ) {
			wp_enqueue_style( 'tnp-instasend', $plugin_url . 'style.css', [], $this->version );
			wp_enqueue_script( 'tnp-instasend', $plugin_url . 'script.js', [
				'jquery',
				'jquery-effects-slide'
			], filemtime( plugin_dir_path( __FILE__ ) . 'script.js' ), true );
		}

	}

    public function add_instasend_post_metabox() {
        error_log('Instasend: add_instasend_post_metabox called');

        add_meta_box(
                'tnp-instasend-metabox', 'Instasend', array($this, 'metabox_html'), 'post', 'side', 'high', null //All actions are performed with ajax requests
        );
    }

    public function metabox_html() {

        include_once NEWSLETTER_INCLUDES_DIR . '/controls.php';

        global $post;
        $controls = new NewsletterControls();
        $nonce = wp_create_nonce(self::$nonce_name);
        $have_newsletter = $this->already_have_a_newsletter($post->ID);
        ?>
        <input type="hidden" name="instasend_nonce" value="<?php echo $nonce ?>">
        <input type="hidden" name="instasend_post_id" value="<?php echo $post->ID ?>">
        <input type="hidden" name="instasend_already_have_newsletter" value="<?php echo $have_newsletter ? 1 : 0 ?>">
        <?php
        if ($this->already_have_a_newsletter($post->ID)) {

            $email = Newsletter::instance()->get_email(get_post_meta($post->ID, self::$post_meta_key, true));
            $this->print_created_newsletter_slide_html($email);
        } else {
            ?>
            <p>
                <?php _e('Create a newsletter from this post instantly. Use the options below to customize your newsletter draft.', 'newsletter'); ?>
            </p>

            <div class="tnp_metabox_section">
                <button type="button" class="button"
                        id="tnp-instasend-make-draft">
                            <?php echo __('Create Newsletter', 'newsletter') ?>
                </button>
            </div>
            <div class="tnp_metabox_section">
                <div class="title"><?php echo __('Post Image', 'newsletter') ?></div>
                <p><?php echo __('Show featured image', 'newsletter') ?></p>
                <?php $controls->yesno('instasend_show_featured_image'); ?>
            </div>
            <div class="tnp_metabox_section">
                <div class="title"><?php echo __('Post Content', 'newsletter') ?></div>
                <p><?php echo __('Newsletter content', 'newsletter') ?></p>
                <?php
                $controls->select('instasend_content_type_dropdown', [
                    'full' => __('Full post', 'newsletter'),
                    'excerpt' => __('Excerpt', 'newsletter'),
                ]);
                ?>
                <div id='tnp-excerpt-field-container' class="hidden">
                    <table>
                        <tr style="margin-top: 5px;">
                            <td><label><?php echo __('Words count', 'newsletter') ?></label></td>
                            <td><?php $controls->text('instasend_excerpt_words', 5); ?></td>
                        </tr>
                        <tr style="margin-top: 5px;">
                            <td><label><?php echo __('Show read more button', 'newsletter') ?></label></td>
                            <td><?php $controls->yesno('instasend_excerpt_read_more'); ?></td>
                        </tr>
                    </table>
                </div>
                <div id='tnp-full-content-field-container'>
                    <table>
                        <tr style="margin-top: 5px;">
                            <td><label><?php echo __('Keep post content images', 'newsletter') ?></label></td>
                            <td><?php $controls->yesno('instasend_keep_post_content_images'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
            <div class="tnp_metabox_section">
                <button id="tnp-instasend-confirm-button" type="button" class="button-primary"
                        disabled><?php echo __('Create', 'newsletter') ?>
                </button>
            </div>
            <div class="tnp-steps-controllers">
                <button type="button" class="button"
                        id="tnp-instasend-prev"><?php echo __('Previous', 'newsletter') ?></button>
                <button type="button" class="button"
                        id="tnp-instasend-next"><?php echo __('Next', 'newsletter') ?></button>
            </div>
            <div class="tnp_metabox_section_notice">
                <div class="tnp-notice"></div>
            </div>
            <?php
        }
    }

    private function print_created_newsletter_slide_html($email) {
        ?>
        <div class="tnp-buttons-section">
            <?php echo NewsletterEmails::instance()->get_edit_button($email) ?>
            <a class="button-primary" target="_blank" rel="noopener"
               href="<?php echo home_url('/') ?>?na=view&id=<?php echo $email->id; ?>"><?php _e('View', 'newsletter') ?></a>
        </div>
        <div class="tnp_metabox_section_notice">
            <div class="tnp-notice"><?php echo __('A newsletter draft has already been created.', 'newsletter') ?></div>
        </div>
        <?php
    }

    private function already_have_a_newsletter($post_id) {

        $email_id = get_post_meta($post_id, self::$post_meta_key, true);
        $email = Newsletter::instance()->get_email($email_id);
        if (!empty($email)) {
            return true;
        }

        return false;
    }

    public function create_newsletter_ajax_hook() {

        include_once NEWSLETTER_INCLUDES_DIR . '/helper.php';

        $data = [
            'nonce' => isset($_POST['nonce']) ? $_POST['nonce'] : '',
            'post_id' => isset($_POST['postID']) ? $_POST['postID'] : 0,
            'show_featured_image' => isset($_POST['showFeaturedImage']) ? (bool) $_POST['showFeaturedImage'] : false,
            'keep_post_content_images' => isset($_POST['keepPostContentImages']) ? (bool) $_POST['keepPostContentImages'] : false,
            'post_content_length' => isset($_POST['postContentLength']) ? $_POST['postContentLength'] : 'full',
            'excerpt_max_words' => isset($_POST['excerptMaxWords']) ? $_POST['excerptMaxWords'] : null,
            'show_read_more_button' => isset($_POST['showReadMoreButton']) ? (bool) $_POST['showReadMoreButton'] : false,
        ];

        try {

            $this->check_create_newsletter_ajax_field($data);
            $message = $this->get_message_from_preferences($data);

            $post = get_post($data['post_id']);

            /* @var TNP_Email */
            $email = [];
            $email['subject'] = $post->post_title;
            $email['message'] = $message;
            $email['message_text'] = tnp_post_excerpt($post);
            $email['send_on'] = 0;
            $email['status'] = 'new';
            $email['type'] = 'message';
            $email['editor'] = NewsletterEmails::EDITOR_COMPOSER;
            $email['track'] = true;

            $email = Newsletter::instance()->save_email($email);
            update_post_meta($post->ID, self::$post_meta_key, $email->id);

            ob_start();
            $this->print_created_newsletter_slide_html($email);
            $rendered_html = ob_get_contents();
            ob_end_clean();

            wp_send_json_success($rendered_html);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    private function get_message_from_preferences($data) {

        $blocks = $this->compose_blocks_from_preferences($data);

        ob_start();

        foreach ($blocks as $block) {
            NewsletterEmails::instance()->render_block($block['block'], true, $block['options']);
        }

        $message = ob_get_contents();
        ob_end_clean();

        return $message;
    }

    private function compose_blocks_from_preferences($data) {
        $post = get_post($data['post_id']);
        $permalink = get_permalink($post->ID);

        $blocks = [];

        // ADD SITE HEADER
        $blocks[] = [
            'block' => 'header',
            'options' => []
        ];

        // ADD FEATURED IMAGE
        if ($data['show_featured_image'] && ( $featured_image_id = get_post_thumbnail_id($post) )) {

            $blocks[] = [
                'block' => 'image',
                'options' => [
                    'image' => [
                        'id' => $featured_image_id
                    ],
                    'url' => $permalink
                ]
            ];
        }

        // ADD POST TITLE HEADING
        $blocks[] = [
            'block' => 'heading',
            'options' => [
                'text' => $post->post_title,
            ]
        ];

        // ADD POST CONTENT
        if ($data['post_content_length'] === 'full') {

            $content = tnp_delete_all_shordcodes_tags($post->post_content);

            if (!$data['keep_post_content_images']) {
                $content = preg_replace("/<img[^>]+\>/i", '', $content);
            }


            $content = wpautop($content);
            //$content = preg_replace('/<p(.+?)style="/', '<p $1 style="font-family: Arial; font-size: 16px;', $content);
            //$content = preg_replace('/<p>/', '<p style="font-family: Arial; font-size: 16px">', $content);

            $blocks[] = [
                'block' => 'text',
                'options' => [
                    'html' => $content,
                    'block_padding_left' => 30,
                    'block_padding_right' => 30,
                ]
            ];
        } else if ($data['post_content_length'] === 'excerpt') {

            $blocks[] = [
                'block' => 'text',
                'options' => [
                    'html' => tnp_post_excerpt($post, $data['excerpt_max_words']),
                    'block_padding_left' => 30,
                    'block_padding_right' => 30,
                ]
            ];

            if ($data['show_read_more_button']) {
                $blocks[] = [
                    'block' => 'cta',
                    'options' => [
                        'text' => __('Read more', 'newsletter'),
                        'background' => '#9bc091',
                        'url' => $permalink,
                    ]
                ];
            }
        }

        // ADD FOOTER
        $blocks[] = [
            'block' => 'footer',
            'options' => []
        ];

        // ADD FOOTER CANSPAM
        $blocks[] = [
            'block' => 'canspam',
            'options' => []
        ];

        return $blocks;
    }

    private function check_create_newsletter_ajax_field($field) {

        $this->verify_nonce($field['nonce']);
        $this->verify_post_id($field['post_id']);
    }

    private function verify_nonce($nonce) {
        if (!wp_verify_nonce($nonce, self::$nonce_name)) {
            throw new Exception('Nonce is not valid. Action is not authorized!');
        }
    }

    private function verify_post_id($post_id) {
        if (empty($post_id)) {
            throw new Exception('No value set for post id');
        }
    }

    public function delete_newsletter_ajax_hook() {

        $data = [
            'nonce' => isset($_POST['nonce']) ? $_POST['nonce'] : '',
            'post_id' => isset($_POST['postID']) ? $_POST['postID'] : 0,
        ];

        try {

            $this->verify_nonce($data['nonce']);
            $this->verify_post_id($data['post_id']);

            $newsletter_id = get_post_meta($data['post_id'], self::$post_meta_key, true);
            delete_post_meta($data['post_id'], self::$post_meta_key);
            Newsletter::instance()->delete_email((int) $newsletter_id);

            wp_send_json_success(__('Success', 'newsletter'));
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

}
