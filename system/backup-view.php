<?php
/* @var $this NewsletterSystemAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

$options = get_option('newsletter_backup_' . $_GET['id']);
ksort($options);
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

            <div class="psource-tabs" id="backup-tabs">
                <div class="psource-tabs-nav">
                    <button class="psource-tab active" data-tab="tabs-logs"><?php _e('Options', 'newsletter') ?></button>
                </div>
                <div class="psource-tabs-content">
                    <div class="psource-tab-panel active" id="tabs-logs">
                        <table class="widefat">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Key', 'newsletter'); ?></th>
                                    <th><?php esc_html_e('Value', 'newsletter'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($options as $k => $v) { ?>
                                <?php if (is_array($v)) { ?>
                                    <?php ksort($v); ?>
                                    <?php foreach ($v as $k2 => $v2) { ?>
                                        <tr>
                                            <th><?php echo esc_html($k), '.', esc_html($k2) ?></th>
                                            <td><?php echo esc_html(is_scalar($v2) ? $v2 : 'object|array'); ?></td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr>
                                        <th><?php echo esc_html($k) ?></th>
                                        <td><?php echo esc_html(is_scalar($v) ? $v : 'object|array') ?></td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>

</div>
