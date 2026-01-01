<?php

defined('ABSPATH') || exit;

require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

global $wpdb;

// ID aus URL holen
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Beim Speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    error_log('ID: ' . $id);
    error_log('POST: ' . print_r($_POST, true));
    $wpdb->update(
        $wpdb->prefix . 'tnp_autoresponders',
        [
            'name'         => sanitize_text_field($_POST['options']['name'] ?? ''),
            'status'       => isset($_POST['options']['status']) ? 1 : 0,
            'list_id'      => isset($_POST['options']['list']) ? intval($_POST['options']['list']) : 0,
            'keep_active'  => isset($_POST['options']['keep_active']) ? 1 : 0,
            'language'     => sanitize_text_field($_POST['options']['language'] ?? ''),
            'restart'      => isset($_POST['options']['restart']) ? 1 : 0,
            'regenerate'   => isset($_POST['options']['regenerate']) ? 1 : 0,
            'utm_campaign' => sanitize_text_field($_POST['options']['utm_campaign'] ?? ''),
            'utm_source'   => sanitize_text_field($_POST['options']['utm_source'] ?? ''),
            'utm_medium'   => sanitize_text_field($_POST['options']['utm_medium'] ?? ''),
            'utm_term'     => sanitize_text_field($_POST['options']['utm_term'] ?? ''),
            'utm_content'  => sanitize_text_field($_POST['options']['utm_content'] ?? ''),
            'rules' => isset($_POST['options']['rules']) ? intval($_POST['options']['rules']) : 0,
            'list_id'      => isset($_POST['list']) ? intval($_POST['list']) : 0,
            'keep_active' => isset($_POST['options']['keep_active']) ? intval($_POST['options']['keep_active']) : 0,
            'language'     => sanitize_text_field($_POST['language'] ?? ''),
        ],
        ['id' => $id]
    );

    error_log('Rows affected: ' . $wpdb->rows_affected);
    error_log('DB-Fehler: ' . $wpdb->last_error);
    if ($wpdb->last_error) {
        echo '<div class="notice notice-error"><p>DB-Fehler: ' . esc_html($wpdb->last_error) . '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p>Gespeichert!</p></div>';
    }
}

// Daten laden
$autoresponder = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tnp_autoresponders WHERE id = %d", $id
));

if (!$autoresponder) {
    echo '<div class="notice notice-error">Serie nicht gefunden.</div>';
    return;
}

$controls->set_data($autoresponder);

?>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>
    <div id="tnp-heading">
        <?php include __DIR__ . '/autorespondernav.php' ?>
    </div>

    <div id="tnp-body">

        <?php $controls->show(); ?>

        <p>
            <?php esc_html_e('Here you can configure the autoresponder settings. Define activation rules, select the subscriber list, set language filters, and adjust advanced options for your automated email series.', 'newsletter'); ?>
        </p>

        <form method="post" action="">

        <?php $controls->init(); ?>

        <div class="psource-tabs" id="tabs">
            <div class="psource-tabs-nav">
                <button class="psource-tab active" data-tab="tabs-general"><?php esc_html_e('General', 'newsletter') ?></button>
                <button class="psource-tab" data-tab="tabs-advanced"><?php esc_html_e('Advanced', 'newsletter') ?></button>
            </div>
            <div class="psource-tabs-content">
                <div class="psource-tab-panel active" id="tabs-general">
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('Enabled', 'newsletter'); ?></th>
                            <td><?php $controls->yesno('status') ?></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Title', 'newsletter'); ?></th>
                            <td><?php $controls->text('name', 70) ?></td>
                        </tr>
                    </table>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('Activation rules', 'newsletter'); ?></th>
                            <td>
                                <?php $controls->enabled('rules', ['bind_to'=>'divrules']) ?>
                                <p class="description">
                                    <?php esc_html_e('When disabled the series can anyway be activated by addons, custom subscription forms, and so on.', 'newsletter'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    <table class="form-table" id="options-divrules">
                        <tr>
                            <th><?php esc_html_e('List', 'newsletter'); ?></th>
                            <td>
                                <?php $controls->lists_select_with_notes('list', esc_html__('All subscribers', 'newsletter')) ?>
                                <p class="description">
                                    <strong><?php esc_html_e('List set', 'newsletter'); ?></strong> - 
                                    <?php esc_html_e('the series is activated to subscribers in the specified list. Subscribers are automatically captured when they enter the list and automatically released when they exit the list (usually within 5 minutes).', 'newsletter'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Keep active', 'newsletter'); ?></th>
                            <td>
                                <?php $controls->yesno('keep_active') ?>
                                <p class="description">
                                    <?php esc_html_e('Keep the series active if the subscriber is removed from the list, otherwise stop it.', 'newsletter'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Language', 'newsletter'); ?></th>
                            <td>
                                <?php $controls->language() ?>
                                <p class="description">
                                    <?php esc_html_e('Only subscribers with a matching language will be linked.', 'newsletter'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="psource-tab-panel" id="tabs-advanced">
                    <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Restart on re-subscription', 'newsletter'); ?></th>
                        <td>
                            <?php $controls->yesno('restart') ?>
                            <p class="description">
                                <?php esc_html_e('If a subscriber re-subscribes and the series is already completed, restart it.', 'newsletter'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Update emails content', 'newsletter'); ?></th>
                        <td>
                            <?php $controls->yesno('regenerate') ?>
                            <p class="description">
                                <?php esc_html_e('If the content of the emails should be updated every day (it applies to post list, product list and so on)', 'newsletter'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Lists to add on completion', 'newsletter'); ?></th>
                        <td>
                            <?php $controls->lists('new_lists') ?>
                            <p class="description">
                                <?php esc_html_e("List to be set on a subscriber's profile when the series reaches its end.", 'newsletter'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                </div>
            </div>
            <div class="tnp-buttons">
                <?php $controls->button_save('Save'); ?>
            </div>
        </div>
    </form>

    </div>

</div>
