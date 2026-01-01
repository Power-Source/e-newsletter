<?php
/* @var $this NewsletterLock */

defined('ABSPATH') || exit;

include_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

if (!$controls->is_action()) {
    $controls->data = $this->options;
} else {
    if ($controls->is_action('save')) {
        $this->save_options($controls->data);
        $controls->add_toast_saved();
    }
}
?>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER ?>

    <div id="tnp-heading">
        <h2>Locked Content</h2>
    </div>

    <div id="tnp-body">
        <?php $controls->show(); ?>
        <p>
            <?php
            printf(
                esc_html__('Please, %sread more here how to use and configure%s, since it can incredibly increase your subscription rate.', 'newsletter'),
                '<a href="https://cp-psource.github.io/e-newsletter/locked-content" target="_blank">',
                '</a>'
            );
            ?>
        </p>
        <form method="post" action="">
            <?php $controls->init(); ?>
            <table class="form-table">

                <tr valign="top">
                    <th><?php _e('Tags or categories to block', 'newsletter') ?></th>
                    <td>
                        <?php $controls->text('ids', 70); ?> (<?php _e('comma separated', 'newsletter') ?>)
                        <p class="description">
                            <?php _e('Use tag or category slugs or id for which lock the posts content', 'newsletter') ?>
                        </p>
                    </td>
                </tr>


                <tr valign="top">
                    <th><?php _e('Denied content message', 'newsletter') ?></th>
                    <td>
                        <?php if ($this->is_multilanguage()) { ?>
                        <div class="psource-tabs" id="lock-tabs">
                            <div class="psource-tabs-nav">
                                <button class="psource-tab active" data-tab="tabs-xx"><?php _e('Default'); ?></button>
                                <?php foreach ($this->get_languages() as $key => $value) { ?>
                                    <button class="psource-tab" data-tab="tabs-<?php echo esc_attr($key) ?>"><?php echo esc_html($value) ?></button>
                                <?php } ?>
                            </div>
                            <div class="psource-tabs-content">
                                <div class="psource-tab-panel active" id="tabs-xx">
                                    <?php $controls->wp_editor('message'); ?>
                                </div>
                                <?php foreach ($this->get_languages() as $key => $value) { ?>
                                    <div class="psource-tab-panel" id="tabs-<?php echo esc_attr($key) ?>">
                                        <?php $controls->wp_editor('message_' . $key); ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <?php } else { ?>
                            <?php $controls->wp_editor('message'); ?>
                        <?php } ?>

                        <p class="description">
                            <?php esc_html_e('This message is shown in place of protected post or page content which is surrounded with [newsletter_lock] and [/newsletter_lock] shortcodes or in place of the full content if they are in categories or have tags as specified above.', 'newsletter'); ?>
                            <br>
                            <?php esc_html_e('You can use shortcodes like [newsletter_form] to display a subscription form or any other Newsletter shortcode.', 'newsletter'); ?>
                            <strong>
                                <?php esc_html_e('Remember to add the {unlock_url} on the welcome email so the user can unlock the content.', 'newsletter'); ?>
                            </strong>
                        </p>
                    </td>
                </tr>
                <tr valign="top">
                    <th><?php _e('Unlock destination URL', 'newsletter') ?></th>
                    <td>
                        <?php $controls->text('url', 70); ?>
                        <p class="description">
                            <?php esc_html_e('URL where redirect subscribers when they click on unlocking URL ({unlock_url}) inserted in newsletters and welcome message.', 'newsletter'); ?>
                            <br>
                            <?php esc_html_e('Newsletters with tracking active can link directly the pages with locked content since the URLs will unlock the content as well.', 'newsletter'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            <p>
                <?php $controls->button_save(); ?>
            </p>
        </form>
    </div>

</div>