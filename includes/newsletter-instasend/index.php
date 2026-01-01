<?php
defined('ABSPATH') || exit;

/* @var $this NewsletterInstasend */

@include_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

//$logger = $instasend->get_admin_logger();

if (!$controls->is_action()) {
    $controls->data = $this->options;
} else {
    
    $logger->info($controls->action);
    
    if ($controls->is_action('save')) {
        $this->save_options($controls->data);
        $controls->add_message_saved();
    }
}
?>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_DIR . '/tnp-header.php'; ?>

    <div id="tnp-heading">

        <h2>Instasend</h2>

    </div>

    <div id="tnp-body">

        <form method="post" action="">
            <?php $controls->init(); ?>

            <p>
                <?php _e('Instasend adds a meta box to your posts, allowing you to instantly turn any published post into a newsletter.', 'newsletter'); ?>
            </p>
            <p>
                <?php _e('How it works:', 'newsletter'); ?>
            </p>
            <ul>
                <li><?php _e('Create or edit your post as usual.', 'newsletter'); ?></li>
                <li><?php _e('Save or publish the post.', 'newsletter'); ?></li>
                <li><?php _e('In the Instasend meta box, click "Create Newsletter".', 'newsletter'); ?></li>
                <li><?php _e('Optionally, choose whether to include the featured image.', 'newsletter'); ?></li>
                <li><?php _e('Select if you want to send the full post or just the excerpt.', 'newsletter'); ?></li>
                <li><?php _e('Click "Create".', 'newsletter'); ?></li>
                <li><?php _e('You can now review, edit, and send your new newsletter as usual.', 'newsletter'); ?></li>
            </ul>
            <p>
                <?php _e('Instasend makes it easy to share your latest content with your subscribers in just a few clicks!', 'newsletter'); ?>
            </p>
             <?php //$controls->button_save('save') ?>
            </p>
        </form>
    </div>

    <?php include NEWSLETTER_DIR . '/tnp-footer.php'; ?>

</div>