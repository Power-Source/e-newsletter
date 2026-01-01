<?php
/* @var $this NewsletterAutomated */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

if (!isset($controls) || !$controls) {
    include_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
    $controls = new NewsletterControls();
}

$feeds = [];
$channels = get_option('tnp_automated_channels', []);
foreach ($channels as $channel) {
    $feed = new stdClass();
    $feed->id = $channel['id'];
    $feed->data = $channel;
    $feed->name = $channel['name']; // <--- Hier hinzufügen!
    // Beispielwerte, ggf. anpassen:
    $feed->last_time = isset($channel['last_time']) ? $channel['last_time'] : 0;
    $feed->sent = isset($channel['sent']) ? $channel['sent'] : 0;
    $feed->email = isset($channel['email']) ? (object)$channel['email'] : null;
    $feeds[] = $feed;
}

// Channel löschen, wenn delete-Parameter gesetzt ist
if (isset($_GET['delete'])) {
    $delete_id = (string)$_GET['delete'];
    $channels = get_option('tnp_automated_channels', []);
    if (isset($channels[$delete_id])) {
        unset($channels[$delete_id]);
        update_option('tnp_automated_channels', $channels);
    }
    // Nach dem Löschen auf die gleiche Seite weiterleiten (ohne delete-Parameter)
    echo '<script>window.location.href="' . esc_url(admin_url('admin.php?page=newsletter_main_automatedindex')) . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url(admin_url('admin.php?page=newsletter_main_automatedindex')) . '"></noscript>';
    exit;
}

NewsletterMainAdmin::instance()->set_completed_step('automated');
?>

<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER ?>
    <div id="tnp-heading">
        <h2><?php esc_html_e('Automated Newsletters', 'newsletter'); ?></h2>
    </div>
    <div id="tnp-body">
        <?php $controls->show(); ?>
        <div class="tnp-description" style="margin-bottom: 1em;">
            <?php esc_html_e('Automated channels allow you to send newsletters automatically based on your own schedule and content sources. Create, configure and manage recurring campaigns for your subscribers with just a few clicks.', 'newsletter'); ?>
        </div>

        <form method="post" action="">
            <?php $controls->init(); ?>

            <div class="tnp-buttons">
                <?php $controls->button_link('?page=newsletter_main_automatededit', esc_html__('New channel', 'newsletter'), 'primary'); ?>
            </div>

            <table class="widefat" id="tnp-channels">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Id', 'newsletter'); ?></th>
                        <th><?php esc_html_e('Name', 'newsletter'); ?></th>
                        <th><!--Status--></th>
                        <th colspan="2"><?php esc_html_e('Last newsletter', 'newsletter'); ?></th>

                        <th><?php esc_html_e('Sent', 'newsletter'); ?></th>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($feeds as $feed) { ?>
                        <tr>
                            <td>
                                <?php echo $feed->id ?>

                            </td>
                            <td><?php echo esc_html($feed->data['name']) ?></td>
                            <td class="tnp-automated-status">
                                <span class="tnp-led-<?php echo!empty($feed->data['enabled']) ? 'green' : 'gray' ?>">&#x2B24;</span>
                            </td>

                            <td style="white-space: nowrap">
                                <?php echo date_i18n(get_option('date_format'), $feed->last_time); ?>


                            </td>

                            <td>
                                <?php if ($feed->email) { ?>

                                    <?php Newsletter::instance()->show_email_status_label($feed->email) ?>
                                <?php } ?>
                            </td>
                            <td class="tnp-sent"><?php echo $feed->sent ?></td>

                            <td style="white-space: nowrap" class="tnp-automated-actions">

                                <?php $controls->button_icon_configure('?page=newsletter_main_automatededit&id=' . $feed->id) ?>
                                <?php $controls->button_icon_newsletters('?page=newsletter_main_automatednewsletters') ?>
                                <?php $controls->button_icon_design('?page=newsletter_main_automatedtemplate') ?>
                            </td>

                            <td style="white-space: nowrap">
                                <?php $controls->button_icon_copy($feed->id); ?>
                                <?php $controls->button_icon_delete('?page=newsletter_main_automatedindex&delete=' . $feed->id); ?>
                            </td>

                        </tr>
                    <?php } ?>
                </tbody>
            </table>

        </form>
    </div>
</div>