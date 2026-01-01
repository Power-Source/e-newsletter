<?php
/* @var $this NewsletterSubscriptionAdmin */
/* @var $controls NewsletterControls */
/* @var $logger NewsletterLogger */

defined('ABSPATH') || exit;
?>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php $controls->title_help('/subscription') ?>
        <?php include __DIR__ . '/nav-forms.php' ?>
    </div>

    <div id="tnp-body">

        <?php $controls->show(); ?>

        <h3><?php esc_html_e('Shortcodes', 'newsletter'); ?></h3>
        <p>
            <?php esc_html_e('The shortcode', 'newsletter'); ?>
            <code>[newsletter_form]</code>
            <?php esc_html_e('can be used anywhere to display the subscription form. Use the "shortcode block" in your posts and pages and in your widgets.', 'newsletter'); ?>
        </p>
        <p>
            <a href="https://cp-psource.github.io/e-newsletter/subscription-form-shortcodes/" target="_blank">
                <?php esc_html_e('Read more', 'newsletter'); ?>
            </a>
            <?php esc_html_e('to know all the available features.', 'newsletter'); ?>
        </p>

        <h3><?php esc_html_e('Widgets', 'newsletter'); ?></h3>
        <p>
            <?php esc_html_e('Two widgets are provided (standard and minimal). You can use them on', 'newsletter'); ?>
            <?php if (function_exists('wp_is_block_theme') && wp_is_block_theme()) { ?>
                <?php echo ' '; ?>
                <a href="<?php echo esc_attr(admin_url('site-editor.php')); ?>" target="_blank">
                    <?php esc_html_e('the Site Editor', 'newsletter'); ?>
                </a>
            <?php } else { ?>
                <?php echo ' '; ?>
                <a href="<?php echo esc_attr(admin_url('widgets.php')); ?>" target="_blank">
                    <?php esc_html_e('the Widgets panel', 'newsletter'); ?>
                </a>
            <?php } ?>
        </p>

    </div>


</div>
