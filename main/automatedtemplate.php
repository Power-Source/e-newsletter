<?php
/* @var $this NewsletterAutomated */

defined('ABSPATH') || exit;

if (!isset($controls) || !$controls) {
    include_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
    $controls = new NewsletterControls();
}

// Channel-ID aus GET holen
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$channels = get_option('tnp_automated_channels', []);
if ($id && isset($channels[$id])) {
    $channel = (object) $channels[$id];
} else {
    $channel = new stdClass();
    $channel->id = 0;
    $channel->data = [
        'name' => '',
        'track' => 1,
        'frequency' => 'weekly',
        'day_1' => 1,
    ];
}

// Template laden
$template_option = get_option('tnp_automated_template_' . $channel->id);

// Dummy-E-Mail-Objekt für Composer
$email = new stdClass();
$email->id = $channel->id;
$email->subject = $channel->subject ?? '';
$email->status = '';
$email->send_on = 0;
$email->options = [];
$email->updated = 0;

// Wenn gespeichert wird, Controls-Daten übernehmen und als Option speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Composer-Daten aus dem Formular übernehmen
    $controls->data = array_merge($controls->data, $_POST);

    // Felder ins Template übernehmen
    $template_data = [
        'subject' => $controls->data['subject'] ?? '',
        'message' => $controls->data['message'] ?? '',
        'blocks'  => $controls->data['blocks'] ?? [],
        'options' => $controls->data['options'] ?? [],
        'updated' => time(),
    ];

    update_option('tnp_automated_template_' . $channel->id, $template_data);
    echo '<div class="notice notice-success"><p>' . esc_html__('Vorlage gespeichert.', 'newsletter') . '</p></div>';
    $template_option = $template_data;
    // Werte ins Dummy-Objekt übernehmen, damit sie im Composer angezeigt werden
    $email->subject = $template_data['subject'];
    $email->message = $template_data['message'];
    $email->options = $template_data['options'];
    $email->updated = $template_data['updated'];
} else {
    // Beim Laden: Werte aus Option ins Dummy-Objekt übernehmen
    if (is_array($template_option)) {
        $email->subject = $template_option['subject'] ?? '';
        $email->message = $template_option['message'] ?? '';
        $email->options = $template_option['options'] ?? [];
        $email->updated = $template_option['updated'] ?? 0;
    }
}

TNP_Composer::prepare_controls($controls, $email);
?>
<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER ?>
    <div id="tnp-heading">
        <?php include __DIR__ . '/automatednav.php' ?>
    </div>
    <div id="tnp-body" class="tnp-automated-edit">
        <?php $controls->show(); ?>
        <div class="tnp-automated-edit">
            <form method="post" id="tnpc-form" action="" onsubmit="tnpc_save(this); return true;">
                <?php $controls->init(); ?>
                <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 20px;">
                    <?php $controls->button_back('?page=newsletter_main_automatedindex', '') ?>
                    <?php $controls->button('save', __('Speichern', 'newsletter'), 'tnpc_save(this.form); this.form.submit();'); ?>
                </div>
                <?php $controls->composer_fields_v2() ?>
            </form>
            <?php $controls->composer_load_v2(true, false, 'automated') ?>
        </div>
    </div>
</div>
