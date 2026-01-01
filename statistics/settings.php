<?php
/* @var $this NewsletterStatisticsAdmin */
/* @var $controls NewsletterControls */
/* @var $logger NewsletterControls */

defined('ABSPATH') || exit;

if ($controls->is_action()) {
    if ($controls->is_action('save')) {

        $controls->add_toast_saved();
    }
} else {
    $controls->data = $this->get_main_options();
}
?>

<div class="wrap tnp-statistics tnp-statistics-settings" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php //$controls->title_help('/profile-page')  ?>
        <h2><?php esc_html_e('Statistics', 'newsletter') ?></h2>
    </div>

    <div id="tnp-body">

        <?php $controls->show() ?>


        <form id="channel" method="post" action="">
            <?php $controls->init(); ?>

            <div class="psource-tabs" id="tabs">
                <div class="psource-tabs-nav">
                    <button class="psource-tab active" data-tab="tabs-general"><?php esc_html_e('General', 'newsletter') ?></button>
                    <?php if (NEWSLETTER_DEBUG) { ?>
                        <button class="psource-tab" data-tab="tabs-debug">Debug</button>
                    <?php } ?>
                </div>
                <div class="psource-tabs-content">
                    <div class="psource-tab-panel active" id="tabs-general">
                        <table class="form-table">
                            <tr>
                                <th>Key</th>
                                <td>
                                    <?php $controls->value('key'); ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <?php if (NEWSLETTER_DEBUG) { ?>
                        <div class="psource-tab-panel" id="tabs-debug">
                            <pre><?php echo esc_html(wp_json_encode($this->get_db_options(''), JSON_PRETTY_PRINT)) ?></pre>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <p>
                <?php //$controls->button_save()  ?>
            </p>
        </form>

    </div>

</div>
