<?php

defined('ABSPATH') || exit;

require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

global $wpdb;

// Autoresponder-ID aus GET holen
$ar_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Autoresponder laden
$autoresponder = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tnp_autoresponders WHERE id = %d", $ar_id
));

// E-Mails der Serie laden
$emails = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tnp_autoresponder_emails WHERE autoresponder_id = %d ORDER BY step ASC", $ar_id
));

// Wartende Abonnenten pro E-Mail zählen
$email_stats = [];
$total_waiting = 0;
foreach ($emails as $email) {
    $waiting = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tnp_autoresponder_queue WHERE email_id = %d AND status = 'waiting'", $email->id
    ));
    $email_stats[] = [
        'subject' => $email->subject,
        'waiting' => $waiting,
        'step' => $email->step,
    ];
    $total_waiting += $waiting;
}

// Status-Übersicht (Beispiel)
$status_stats = [
    'completed' => $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tnp_autoresponder_queue WHERE autoresponder_id = %d AND status = 'completed'", $ar_id
    )),
    'active' => $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tnp_autoresponder_queue WHERE autoresponder_id = %d AND status = 'waiting'", $ar_id
    )),
    'abandoned' => $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tnp_autoresponder_queue WHERE autoresponder_id = %d AND status = 'abandoned'", $ar_id
    )),
    'other' => $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tnp_autoresponder_queue WHERE autoresponder_id = %d AND status NOT IN ('completed','waiting','abandoned')", $ar_id
    )),
];

?>
<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER; ?>
    <div id="tnp-heading">
        <?php include __DIR__ . '/autorespondernav.php' ?>
    </div>
    <div id="tnp-body">

        <?php $controls->show(); ?>

        <form method="post" action="">
            <?php $controls->init(); ?>

            <div class="psource-tabs" id="autoresponder-tabs">
                <div class="psource-tabs-nav">
                    <button class="psource-tab active" data-tab="tabs-email"><?php esc_html_e('By email', 'newsletter'); ?></button>
                    <button class="psource-tab" data-tab="tabs-status"><?php esc_html_e('By status', 'newsletter'); ?></button>
                </div>
                <div class="psource-tabs-content">
                    <div class="psource-tab-panel active" id="tabs-email">
                        <p><?php esc_html_e('Counts are limited to active subscribers who have not abandoned the series (by list change, cancellation, ...).', 'newsletter'); ?></p>
                        <table class="widefat" style="width: auto">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Progress', 'newsletter'); ?></th>
                                    <th><?php esc_html_e('Subscribers', 'newsletter'); ?></th>
                                    <th><?php esc_html_e('Subject', 'newsletter'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($email_stats as $i => $stat): ?>
                                    <tr>
                                        <td><?php printf(esc_html__('Waiting to receive message %d', 'newsletter'), $i + 1); ?></td>
                                        <td><?php echo esc_html($stat['waiting']); ?></td>
                                        <td><?php echo esc_html($stat['subject']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td><strong><?php esc_html_e('Total queued', 'newsletter'); ?></strong></td>
                                    <td><strong><?php echo esc_html($total_waiting); ?></strong></td>
                                    <td>&nbsp;</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="psource-tab-panel" id="tabs-status">
                        <p><?php esc_html_e('Overview of subscriber on this message series.', 'newsletter'); ?></p>
                        <table class="widefat" style="width: auto">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Status', 'newsletter'); ?></th>
                                    <th><?php esc_html_e('Subscribers', 'newsletter'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php esc_html_e('Completed', 'newsletter'); ?></td>
                                    <td><?php echo esc_html($status_stats['completed']); ?></td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Active', 'newsletter'); ?></td>
                                    <td><?php echo esc_html($status_stats['active']); ?></td>
                                </tr>
                                <tr>
                                    <td><?php esc_html_e('Abandoned', 'newsletter'); ?></td>
                                    <td><?php echo esc_html($status_stats['abandoned']); ?></td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php esc_html_e('Other', 'newsletter'); ?><br>
                                        <small><?php esc_html_e('Missing user, errors', 'newsletter'); ?></small>
                                    </td>
                                    <td><?php echo esc_html($status_stats['other']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong><?php esc_html_e('Total', 'newsletter'); ?></strong></td>
                                    <td><strong><?php echo array_sum($status_stats); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
