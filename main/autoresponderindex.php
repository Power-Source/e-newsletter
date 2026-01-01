<?php

defined('ABSPATH') || exit;

require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

global $wpdb;

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $wpdb->delete($wpdb->prefix . 'tnp_autoresponders', ['id' => intval($_GET['id'])]);
    echo '<script>window.location = "' . admin_url('admin.php?page=newsletter_main_autoresponderindex') . '";</script>';
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'copy' && isset($_GET['id'])) {
    $ar = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tnp_autoresponders WHERE id = %d", intval($_GET['id'])), ARRAY_A);
    if ($ar) {
        unset($ar['id']);
        $ar['name'] .= ' (Copy)';
        $wpdb->insert($wpdb->prefix . 'tnp_autoresponders', $ar);
    }
    echo '<script>window.location = "' . admin_url('admin.php?page=newsletter_main_autoresponderindex') . '";</script>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $wpdb->insert(
        $wpdb->prefix . 'tnp_autoresponders',
        [
            'name' => 'Neue Serie',
            'description' => '',
            'status' => 1,
            'created_at' => current_time('mysql')
        ]
    );
    // Nach dem Anlegen weiterleiten, damit kein doppeltes Anlegen bei Reload
    wp_redirect(admin_url('admin.php?page=newsletter_main_autoresponderindex'));
    exit;
}

$autoresponders = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tnp_autoresponders ORDER BY id DESC");

?>

<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER; ?>
    <div id="tnp-heading">
        <?php $controls->title_help('/addons/extended-features/autoresponder-extension/') ?>
        <h2>Email series</h2>
    </div>

    <div id="tnp-body">
        <?php $controls->show(); ?>

        <p>
            <?php esc_html_e('Mit Autoresponder-Serien kannst du automatisierte E-Mail-Strecken an neue Abonnenten oder bestimmte Zielgruppen senden. Jede Serie besteht aus mehreren E-Mails, die zeitlich gesteuert und individuell gestaltet werden können.', 'newsletter'); ?>
        </p>

        <form method="post" action="">
            <?php $controls->init(); ?>

            <div class="tnp-buttons">
                <button type="submit" name="add" class="button button-primary">Add new series</button>
            </div>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Name</th>
                        <th>List</th>
                        <th>Status</th>
                        <th>Steps</th>
                        <th>Subscribers</th>
                        <th>&nbsp;</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($autoresponders)) : ?>
                        <tr>
                            <td colspan="8">Keine Autoresponder-Serien gefunden.</td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($autoresponders as $ar) : ?>
                            <tr>
                                <td><?php echo esc_html($ar->id); ?></td>
                                <td><?php echo esc_html($ar->name); ?></td>
                                <td><?php echo isset($ar->list_id) ? esc_html($ar->list_id) : '-'; ?></td>
                                <td>
                                    <span class="tnp-led-<?php echo !empty($ar->status) ? 'green' : 'gray'; ?>">&#x2B24;</span>
                                </td>
                                <td>-</td>
                                <td>-</td>
                                <td style="white-space: nowrap">
                                    <?php $controls->button_icon_configure('?page=newsletter_main_autoresponderedit&id=' . $ar->id) ?>
                                    <?php $controls->button_icon_statistics('?page=newsletter_main_autoresponderstatistics&id=' . $ar->id) ?>
                                    <?php $controls->button_icon_subscribers('?page=newsletter_main_autoresponderusers&id=' . $ar->id) ?>
                                </td>
                                <td style="white-space: nowrap">
                                    <?php $controls->button_icon_copy($ar->id); ?>
                                    <?php $controls->button_icon_delete($ar->id); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </tbody>
            </table>

        </form>

    </div>
</div>
