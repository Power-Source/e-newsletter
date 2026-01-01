<?php

defined('ABSPATH') || exit;

if (!isset($controls) || !$controls) {
    include_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
    $controls = new NewsletterControls();
}

// Channel-ID aus der URL holen
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$channels = get_option('tnp_automated_channels', []);
if ($id && isset($channels[$id])) {
    $channel = (object) $channels[$id];
} else {
    // Fallback: leerer Kanal
    $channel = new stdClass();
    $channel->id = 0;
    $channel->data = [
        'name' => '',
        'track' => 1,
        'frequency' => 'weekly',
        'day_1' => 1,
    ];
}
global $wpdb;
$email_id = $email->id;

// Empfänger und Status laden (Tabellenname ggf. anpassen!)
$recipients = $wpdb->get_results($wpdb->prepare(
    "SELECT email, name, status, sent_on FROM {$wpdb->prefix}newsletter_stats WHERE email_id = %d",
    $email_id
));

$total = count($recipients);
$opened = 0;
$bounced = 0;
$unread = 0;

foreach ($recipients as $recipient) {
    if ($recipient->status === 'read') $opened++;
    elseif ($recipient->status === 'bounced') $bounced++;
    else $unread++;
}
?>
<script src="<?php echo plugins_url('e-newsletter') ?>/vendor/driver/driver.js.iife.js"></script>
<link rel="stylesheet" href="<?php echo plugins_url('e-newsletter') ?>/vendor/driver/driver.css"/>

<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER ?>
    <div id="tnp-heading">
        <h2><?php echo esc_html($channel->data['name']); ?> – Newsletter-Status</h2>
    </div>
    <div id="tnp-body" class="tnp-automated-edit">

        <div class="tnp-stats-box">
            <div>
                <h3 style="margin-bottom:0.2em;"><?php echo esc_html($email->subject); ?></h3>
                <div style="font-size:0.98em; color:#666;">
                    <strong>Versanddatum:</strong> <?php echo NewsletterControls::print_date($email->send_on); ?><br>
                    <strong>Status:</strong> <?php Newsletter::instance()->show_email_status_label($email); ?>
                </div>
            </div>
            <div class="tnp-stats-numbers">
                <div>
                    <strong><?php echo $total; ?></strong>
                    <div>Gesendet</div>
                </div>
                <div>
                    <strong style="color:green;"><?php echo $opened; ?></strong>
                    <div>Geöffnet</div>
                </div>
                <div>
                    <strong style="color:gray;"><?php echo $unread; ?></strong>
                    <div>Ungelesen</div>
                </div>
                <div>
                    <strong style="color:red;"><?php echo $bounced; ?></strong>
                    <div>Bounces</div>
                </div>
            </div>
        </div>

        <!-- Optional: Empfängerliste -->
        <table class="widefat">
            <thead>
                <tr>
                    <th>E-Mail</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Versandzeit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recipients as $recipient) { ?>
                    <tr>
                        <td><?php echo esc_html($recipient->email); ?></td>
                        <td><?php echo esc_html($recipient->name); ?></td>
                        <td><?php echo esc_html($recipient->status); // read/unread/bounced ?></td>
                        <td><?php echo NewsletterControls::print_date($recipient->sent_on); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
<style>
.tnp-stats-box {
    display: flex;
    gap: 40px;
    margin: 1.5em 0 2em 0;
    background: #f6f7f7;
    border-radius: 8px;
    padding: 18px 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    align-items: center;
    flex-wrap: wrap;
}
.tnp-stats-box > div:first-child {
    min-width: 220px;
    flex: 1 1 220px;
}
.tnp-stats-numbers {
    display: flex;
    gap: 32px;
    flex: 2 1 400px;
    justify-content: flex-start;
}
.tnp-stats-numbers > div {
    text-align: center;
    min-width: 80px;
}
.tnp-stats-numbers strong {
    font-size: 1.6em;
    display: block;
}
.tnp-stats-numbers div {
    font-size: 0.95em;
    color: #666;
}
</style>