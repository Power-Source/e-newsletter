<?php
/* @var $this NewsletterSystemAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

if ($controls->is_action('delete_logs')) {
    $files = glob(WP_CONTENT_DIR . '/logs/newsletter/*.txt');
    foreach ($files as $file) {
        if (is_file($file))
            unlink($file);
    }
    $secret = NewsletterModule::get_token(8);
    update_option('newsletter_logger_secret', $secret);
    $controls->messages = 'Logs deleted';
}
?>

<style>
<?php include __DIR__ . '/css/system.css' ?>
</style>

<div class="wrap tnp-system tnp-system-logs" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">

<!--        <h2><?php _e('System', 'newsletter') ?></h2>-->
        <?php include __DIR__ . '/nav.php' ?>

    </div>

    <div id="tnp-body">
        <?php $controls->show(); ?>

        <form method="post" action="">
            <?php $controls->init(); ?>

            <div class="psource-tabs" id="tabs">
                <div class="psource-tabs-nav">
                    <button class="psource-tab active" data-tab="tabs-logs"><?php _e('Logs', 'newsletter') ?></button>
                    <button class="psource-tab" data-tab="tabs-cron"><?php _e('Scheduler', 'newsletter') ?></button>
                </div>
                <div class="psource-tabs-content">
                    <div class="psource-tab-panel active" id="tabs-logs">
                        <ul class="tnp-log-files">
                            <?php
                            $files = glob(WP_CONTENT_DIR . '/logs/newsletter/*.txt'); // get all file names
                            foreach ($files as $file) { // iterate files
                                echo '<li><a href="' . WP_CONTENT_URL . '/logs/newsletter/' . basename($file) . '" target="_blank">' . basename($file) . '</a>';
                                echo ' <span class="tnp-log-size">(' . size_format(filesize($file)) . ')</span>';
                                echo '</li>';
                            }
                            ?>
                        </ul>
                    </div>
                    <div class="psource-tab-panel" id="tabs-cron">
                        <?php $controls->logs('cron'); ?>
                    </div>
                </div>
            </div>

            <div class="tnp-buttons">
                <?php $controls->button('delete_logs', 'Delete all'); ?>
            </div>

        </form>
    </div>

</div>
