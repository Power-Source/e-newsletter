<?php

defined('ABSPATH') || exit;

require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

global $wpdb;

// E-Mail-ID aus GET holen
$email_id = isset($_GET['id']) ? intval($_GET['id']) : 0;


// E-Mail laden
$email = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tnp_autoresponder_emails WHERE id = %d", $email_id
));

// SPEICHERN DER EINGABEN
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Holt und speichert ALLE Composer-Daten (inkl. Layout, Blöcke, Optionen, HTML)
    TNP_Composer::update_email($email, $controls);

    // Delay ggf. separat speichern (falls nicht im Composer-Options-Array)
    $delay = isset($_POST['options_delay']) ? intval($_POST['options_delay']) : 0;
    $wpdb->update(
        "{$wpdb->prefix}tnp_autoresponder_emails",
        ['delay' => $delay],
        ['id' => $email_id]
    );

    echo '<div class="notice notice-success"><p>' . esc_html__('E-Mail gespeichert.', 'newsletter') . '</p></div>';
}

// Autoresponder laden (für Navigation etc.)
$autoresponder = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tnp_autoresponders WHERE id = %d", $email->autoresponder_id
));

// Fallback, falls keine E-Mail gefunden wurde
if (!$email) {
    echo '<div class="notice notice-error">E-Mail nicht gefunden.</div>';
    return;
}

// Optionen vorbereiten (z.B. Delay)
$email->options = ['delay' => $email->delay ?? 0];

TNP_Composer::prepare_controls($controls, $email);
?>
<div class="wrap" id="tnp-wrap">

    <?php $controls->show(); ?>

    <div id="tnp-body">

    <form id="tnpc-form" method="post" action="" onsubmit="tnpc_save(this); return true;">
        <?php $controls->init(); ?>
        <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 20px;">
            <?php $controls->button_back('?page=newsletter_main_autorespondermessages&id=' . intval($autoresponder->id), '') ?>
            <?php $controls->button('save', __('Speichern', 'newsletter'), 'tnpc_save(this.form); this.form.submit();'); ?>
            <?php $controls->button('preview', __('Weiter', 'newsletter') . ' &raquo;', 'tnpc_save(this.form); this.form.submit();'); ?>
        </div>

        <table class="form-table" style="width: auto; margin-bottom: 20px">
            <tr>
                <th>
                    <?php esc_html_e('Delay (hours)', 'newsletter'); ?>
                    <div style="font-weight: normal; font-size: 12px; color: #666;">
                        <?php esc_html_e('Wie viele Stunden nach Start oder nach der vorherigen E-Mail soll diese E-Mail versendet werden?', 'newsletter'); ?>
                    </div>
                </th>
                <td><?php $controls->text('options_delay') ?></td>
            </tr>
        </table>

        <?php $controls->composer_fields_v2() ?>
    </form>

        <?php $controls->composer_load_v2(true, false, 'autoresponder') ?>
    </div>

</div>
