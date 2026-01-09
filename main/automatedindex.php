<?php
/* @var $this NewsletterAutomated */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

if (!isset($controls) || !$controls) {
    include_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
    $controls = new NewsletterControls();
}

$feeds = [];
require_once NEWSLETTER_DIR . '/main/automated_channels.php';
$channels = AutomatedChannels::all();
foreach ($channels as $channel) {
    // Normalisiere jeden Channel als Array mit Standardwerten
    $feed = $channel;
    $feed['id'] = $channel['id'] ?? '';
    $feed['name'] = $channel['name'] ?? '';
    $feed['last_time'] = isset($channel['last_time']) ? (int)$channel['last_time'] : 0;
    $feed['sent'] = isset($channel['sent']) ? (int)$channel['sent'] : 0;
    $feed['email'] = $channel['email'] ?? null; // kann Array sein
    $feeds[] = $feed;
}

// Channel löschen, wenn delete-Parameter gesetzt ist
if (isset($_GET['delete'])) {
    $delete_id = (string)$_GET['delete'];
    $channels_all = AutomatedChannels::all();
    if (isset($channels_all[$delete_id])) {
        unset($channels_all[$delete_id]);
        AutomatedChannels::save($channels_all);
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
                                <?php echo esc_html($feed['id']); ?>

                            </td>
                            <td><?php echo esc_html($feed['name'] ?? '') ?></td>
                            <td class="tnp-automated-status">
                                <span class="tnp-led-<?php echo !empty($feed['enabled']) ? 'green' : 'gray'; ?>">&#x2B24;</span>
                            </td>

                            <td style="white-space: nowrap">
                                <?php echo $feed['last_time'] ? date_i18n(get_option('date_format'), $feed['last_time']) : '-'; ?>


                            </td>

                            <td>
                                <?php 
                                if (!empty($feed['email'])) {
                                    $email_obj = is_array($feed['email']) ? (object)$feed['email'] : $feed['email'];
                                    Newsletter::instance()->show_email_status_label($email_obj);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td class="tnp-sent"><?php echo (int)$feed['sent']; ?></td>

                            <td style="white-space: nowrap" class="tnp-automated-actions">

                                <?php $controls->button_icon_configure('?page=newsletter_main_automatededit&id=' . urlencode((string)$feed['id'])) ?>
                                <?php $controls->button_icon_newsletters('?page=newsletter_main_automatednewsletters&id=' . urlencode((string)$feed['id'])) ?>
                                <?php $controls->button_icon_design('?page=newsletter_main_automatedtemplate&id=' . urlencode((string)$feed['id'])) ?>
                            </td>

                            <td style="white-space: nowrap">
                                <?php $controls->button_icon_copy($feed['id']); ?>
                                <?php $controls->button_icon_delete('?page=newsletter_main_automatedindex&delete=' . urlencode((string)$feed['id'])); ?>
                            </td>

                        </tr>
                    <?php } ?>
                </tbody>
            </table>

        </form>
    </div>
</div>