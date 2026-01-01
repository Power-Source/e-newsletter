<?php
/* @var $this NewsletterAutomated */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

global $wpdb;

require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

// Kanal-ID aus der URL holen
$id = isset($_POST['id']) ? (string)$_POST['id'] : (isset($_GET['id']) ? (string)$_GET['id'] : '');

// Kanäle laden
$channels = get_option('tnp_automated_channels', []);

// Formularverarbeitung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $options = $_POST['options'] ?? [];
    $data = [];
    $data['id'] = !empty($id) ? $id : (string)time();
    $data['name'] = $options['name'] ?? '';
    $data['track'] = isset($options['track']) ? intval($options['track']) : 0;
    $data['frequency'] = $options['frequency'] ?? 'weekly';
    $data['day_1'] = isset($options['day_1']) ? intval($options['day_1']) : 0;
    $data['day_2'] = isset($options['day_2']) ? intval($options['day_2']) : 0;
    $data['day_3'] = isset($options['day_3']) ? intval($options['day_3']) : 0;
    $data['day_4'] = isset($options['day_4']) ? intval($options['day_4']) : 0;
    $data['day_5'] = isset($options['day_5']) ? intval($options['day_5']) : 0;
    $data['day_6'] = isset($options['day_6']) ? intval($options['day_6']) : 0;
    $data['day_7'] = isset($options['day_7']) ? intval($options['day_7']) : 0;
    $data['hour'] = isset($options['hour']) ? intval($options['hour']) : 0;
    $data['hour2_enabled'] = isset($options['hour2_enabled']) ? intval($options['hour2_enabled']) : 0;
    $data['hour2'] = isset($options['hour2']) ? intval($options['hour2']) : 0;
    $data['enabled'] = isset($options['enabled']) ? intval($options['enabled']) : 0;
    $data['subject'] = $options['subject'] ?? '';
    $data['list'] = $options['list'] ?? '';
    $data['sender_name'] = $options['sender_name'] ?? '';
    $data['sender_email'] = $options['sender_email'] ?? '';

    $channels[$data['id']] = $data;
    update_option('tnp_automated_channels', $channels);

    echo '<script>window.location.href="' . admin_url('admin.php?page=newsletter_main_automatedindex') . '";</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' . admin_url('admin.php?page=newsletter_main_automatedindex') . '"></noscript>';
    exit;
}

// Kanal suchen oder neuen anlegen
if ($id && isset($channels[$id])) {
    $channel = $channels[$id];
} else {
    $channel = [
        'id' => '',
        'name' => '',
        'track' => 1,
        'frequency' => 'weekly',
        'day_1' => 1,
        // ... weitere Felder ...
    ];
}
$controls->data = $channel;

?>
<script src="<?php echo plugins_url('e-newsletter') ?>/vendor/driver/driver.js.iife.js"></script>
<link rel="stylesheet" href="<?php echo plugins_url('e-newsletter') ?>/vendor/driver/driver.css"/>

<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER ?>
    <div id="tnp-heading">

        <?php include __DIR__ . '/automatednav.php' ?>

    </div>

    <div id="tnp-body" class="tnp-automated-edit">

        <?php $controls->show(); ?>

        <form method="post" action="">
            <?php $controls->init(); ?>
            <input type="hidden" name="id" value="<?php echo esc_attr($channel['id']); ?>">

            <div class="psource-tabs" id="tabs">
                <div class="psource-tabs-nav">
                    <button class="psource-tab active" data-tab="tabs-configuration">Configuration</button>
                    <button class="psource-tab" data-tab="tabs-planning">Planning</button>
                </div>
                <div class="psource-tabs-content">
                    <div class="psource-tab-panel active" id="tabs-configuration">
                        <table class="form-table">
                            <tr valign="top">
                                <th>Enabled?</th>
                                <td>
                                    <?php $controls->yesno('enabled'); ?>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>Channel name</th>
                                <td>
                                    <?php $controls->text('name', 50); ?>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>Subject</th>
                                <td>
                                    <?php $controls->text('subject', 50); ?>
                                    <p class="description">
                                        If empty the first block suggested subject is used. Use <code>{dynamic_subject}</code> to reference the first block suggested
                                        subject. Use <code>{date}</code> tag for the current date
                                        (<a href="https://www.thenewsletterplugin.com/documentation/newsletter-tags" target="_blank">see more options</a>).
                                    </p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>Send to list</th>
                                <td>
                                    <?php $controls->lists_select('list', 'Everyone'); ?>
                                    <p class="description">
                                        The subscriber list this channel is sent to. Subscribers can stop to receive this channel disabling the
                                        list on their profile.
                                    </p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>Filter subscribers by language</th>
                                <td>
                                    <?php $controls->languages(); ?>
                                    <p class="description">
                                        If no language is selected, no filter is applied. This filter DOES NOT affect the newsletter content.
                                    </p>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>Track opens and clicks?</th>
                                <td>
                                    <?php $controls->yesno('track'); ?>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>Sender name</th>
                                <td>
                                    <?php $controls->text('sender_name', ['size' => 40]); ?>
                                    <span class="description">
                                        Default: <?php echo esc_html(Newsletter::instance()->get_sender_name()) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>Sender email name</th>
                                <td>
                                    <?php $controls->text_email('sender_email', ['size' => 40]); ?>
                                    <span class="description">
                                        Default: <?php echo esc_html(Newsletter::instance()->get_sender_email()) ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="psource-tab-panel" id="tabs-planning">
                        <table class="form-table">
                            <tr valign="top">
                                <th>Planning</th>
                                <td>
                                    <?php $controls->radio('frequency', 'weekly', 'Weekly on...'); ?><br>
                                    Monday&nbsp;<?php $controls->yesno('day_1'); ?>
                                    Tuesday&nbsp;<?php $controls->yesno('day_2'); ?>
                                    Wednesday&nbsp;<?php $controls->yesno('day_3'); ?>
                                    Thursday&nbsp;<?php $controls->yesno('day_4'); ?>
                                    Friday&nbsp;<?php $controls->yesno('day_5'); ?>
                                    Saturday&nbsp;<?php $controls->yesno('day_6'); ?>
                                    Sunday&nbsp;<?php $controls->yesno('day_7'); ?>
                                    <br><br>
                                    <?php $controls->radio('frequency', 'monthly', 'Monthly on...'); ?><br>
                                    <style>
                                        #tnp-monthly-plan {
                                            width: auto!important;
                                        }
                                        #tnp-monthly-plan th, #tnp-monthly-plan td {
                                            padding: 3px;
                                            text-align: center;
                                            width: 80px;
                                        }
                                        #tnp-monthly-plan th {
                                            font-weight: bold;
                                        }
                                    </style>
                                    <table id="tnp-monthly-plan">
                                        <tr>
                                            <th>Week</th>
                                            <th>Monday</th>
                                            <th>Tuesday</th>
                                            <th>Wednesday</th>
                                            <th>Thursday</th>
                                            <th>Friday</th>
                                            <th>Saturday</th>
                                            <th>Sunday</th>
                                        </tr>
                                        <?php for ($week = 1; $week <= 5; $week++) { ?>
                                            <tr>
                                                <td><?php echo $week; ?></td>
                                                <?php
                                                for ($i = 1; $i <= 7; $i++) {
                                                    echo '<td>';
                                                    $controls->checkbox_group('monthly_' . $week . '_days', $i);
                                                    echo '</td>';
                                                }
                                                ?>
                                            </tr>
                                        <?php } ?>
                                    </table>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>Delivery hour</th>
                                <td>
                                    <?php $controls->hours('hour'); ?>
                                    <span class="description">
                                        <a href="https://www.thenewsletterplugin.com/documentation/addons/extended-features/automated-extension/#hours">Read more about DST (Daylight Time Saving)</a>
                                    </span>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th>Second optional delivery hour</th>
                                <td>
                                    <?php $controls->enabled('hour2_enabled', ['bind_to' => 'hour2']); ?>
                                    <?php $controls->hours('hour2'); ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            <p>
                <?php $controls->button('save', __('Speichern', 'newsletter')); ?>
            </p>
        </form>
    </div>
</div>