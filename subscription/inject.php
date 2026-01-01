<?php
/* @var $this NewsletterSubscriptionAdmin */
/* @var $controls NewsletterControls */
/* @var $logger NewsletterLogger */

defined('ABSPATH') || exit;

if (!$controls->is_action()) {
    $controls->data = $this->get_options('inject', $language);
} else {
    if ($controls->is_action('save')) {
        $controls->data = wp_kses_post_deep($controls->data);
        $this->save_options($controls->data, 'inject', $language);
        $controls->add_toast_saved();
        NewsletterMainAdmin::instance()->set_completed_step('forms');
    }
}

$posts = get_posts(['posts_per_page' => 1]);
$last_post_url = $posts ? get_the_permalink($posts[0]) : null;

if (class_exists('NewsletterLeads')) {
    $controls->warnings[] = 'The Newsletter Leads Addon is active: disable this injection and configure the <a href="?page=newsletter_leads_inject">full-featured injection</a>';
}
?>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php $controls->title_help('/subscription') ?>
        <?php include __DIR__ . '/nav-forms.php' ?>
    </div>

    <div id="tnp-body">

        <?php $controls->show(); ?>

        <?php $controls->language_notice(); ?>

        <p>
            <?php _e('Injected after the content of each post.', 'newsletter'); ?>
        </p>

        <form action="" method="post">
            <?php $controls->init(); ?>
            <div class="psource-tabs" id="tabs">
                <div class="psource-tabs-nav">
                    <button class="psource-tab active" data-tab="tabs-settings"><?php esc_html_e('Settings', 'newsletter') ?></button>
                    <?php if (NEWSLETTER_DEBUG) { ?>
                        <button class="psource-tab" data-tab="tabs-debug">Debug</button>
                    <?php } ?>
                </div>
                <div class="psource-tabs-content">
                    <div class="psource-tab-panel active" id="tabs-settings">
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e('Enabled?', 'newsletter'); ?></th>
                                <td>
                                    <?php $controls->yesno('bottom_enabled'); ?>
                                    <?php if ($last_post_url) { ?>
                                        <a href="<?php echo esc_attr($last_post_url); ?>#tnp-subscription-posts" target="test">
                                            <?php esc_html_e('See on your last post', 'newsletter'); ?>
                                        </a>.
                                    <?php } else { ?>
                                        <?php esc_html_e('No public posts on your site?', 'newsletter'); ?>
                                    <?php } ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Shown before the form', 'newsletter'); ?></th>
                                <td>
                                    <?php $controls->wp_editor('bottom_text', ['editor_height' => 150], ['body_background' => '#ccc']); ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <?php if (NEWSLETTER_DEBUG) { ?>
                        <div class="psource-tab-panel" id="tabs-debug">
                            <?php //$controls->button_reset(); ?>
                            <pre><?php echo esc_html(json_encode($this->get_db_options('inject', $language), JSON_PRETTY_PRINT)) ?></pre>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <p>
                <?php $controls->button_save(); ?>
            </p>
        </form>

    </div>

</div>
