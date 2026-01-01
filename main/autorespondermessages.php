<?php
defined('ABSPATH') || exit;

require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

global $wpdb;
error_log('Aktuelles Tabellenpräfix: ' . $wpdb->prefix);
// Autoresponder-ID aus GET holen
$ar_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_email']) && $ar_id) {
    $result = $wpdb->insert(
        $wpdb->prefix . 'tnp_autoresponder_emails',
        [
            'autoresponder_id' => $ar_id,
            'subject' => __('Neue E-Mail', 'newsletter'),
            'step' => $wpdb->get_var($wpdb->prepare(
                "SELECT IFNULL(MAX(step),0)+1 FROM {$wpdb->prefix}tnp_autoresponder_emails WHERE autoresponder_id = %d", $ar_id
            )),
            'delay' => 0
        ]
    );
    $new_email_id = $wpdb->insert_id;

    // Fehlerausgabe ergänzen:
    if (!$result || !$new_email_id) {
        echo '<div class="notice notice-error"><b>Fehler beim Anlegen der E-Mail:</b> ' . esc_html($wpdb->last_error) . '</div>';
        error_log('Autoresponder E-Mail Insert-Fehler: ' . $wpdb->last_error);
        return;
    }

    echo '<script>window.location = "' . admin_url('admin.php?page=newsletter_main_autorespondercomposer&id=' . $new_email_id) . '";</script>';
    exit;
}

// Autoresponder laden
$autoresponder = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tnp_autoresponders WHERE id = %d", $ar_id
));

// E-Mails der Serie laden
$emails = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tnp_autoresponder_emails WHERE autoresponder_id = %d ORDER BY step ASC", $ar_id
));

// Für jede E-Mail: Wartende Abonnenten zählen (Beispiel)
foreach ($emails as $email) {
    $email->waiting = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tnp_autoresponder_queue WHERE email_id = %d AND status = 'waiting'", $email->id
    ));
    // Optional: Delay berechnen/anzeigen
    $email->delay = $email->delay ?? '';
}

$debug = false;
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

            <table class="widefat" style="width: 100%">
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <?php if ($debug) { ?>
                            <th><code>Email ID</code></th>
                        <?php } ?>
                        <th><?php esc_html_e('Subject', 'newsletter') ?></th>
                        <th>Delay</th>
                        <th><?php esc_html_e('Subscribers waiting', 'newsletter') ?></th>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($emails as $i => $email) { ?>
                        <tr>
                            <td><?php echo $i + 1 ?></td>
                            <?php if ($debug) { ?>
                                <td><?php echo esc_html($email->id) ?></td>
                            <?php } ?>
                            <td><?php echo esc_html($email->subject) ?></td>
                            <td><?php echo esc_html($email->delay) ?></td>
                            <td><?php echo esc_html($email->waiting); ?></td>
                            <td>
                                <?php
                                if ($i > 0) {
                                    $controls->button_confirm('up', '↑', '', $i);
                                } else {
                                    echo '<span style="margin-left: 34px"></span>';
                                }
                                if ($i < (count($emails) - 1)) {
                                    $controls->button_confirm('down', '↓', '', $i);
                                }
                                ?>
                            </td>
                            <td style="white-space: nowrap">
                                <?php $controls->button_icon_edit('?page=newsletter_main_autorespondercomposer&id=' . $email->id) ?>
                                <?php $controls->button_icon_statistics('?page=newsletter_main_autorespondermessages&id=' . $email->id) ?>
                            </td>
                            <td style="white-space: nowrap">
                                <?php $controls->button_icon_copy($email->id); ?>
                                <?php $controls->button_icon_delete($email->id); ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            <div class="tnp-buttons">
                <button type="submit" name="add_email" class="button button-primary"><?php esc_html_e('New email', 'newsletter'); ?></button>
            </div>
        </form>
    </div>
</div>