<?php
defined('ABSPATH') || exit;

require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

global $wpdb;

// Autoresponder-ID aus GET holen
$ar_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Filter/Suche
$search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$step_filter = isset($_GET['step']) ? intval($_GET['step']) : '';

// Paginierung
$per_page = 20;
$page = max(1, isset($_GET['paged']) ? intval($_GET['paged']) : 1);
$offset = ($page - 1) * $per_page;

// Autoresponder laden (für Navigation)
$autoresponder = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tnp_autoresponders WHERE id = %d", $ar_id
));

// Benutzer entfernen
if (isset($_GET['remove_user']) && $ar_id && current_user_can('manage_options')) {
    $remove_user_id = intval($_GET['remove_user']);
    $wpdb->delete(
        "{$wpdb->prefix}tnp_autoresponder_progress",
        ['autoresponder_id' => $ar_id, 'user_id' => $remove_user_id]
    );
    wp_redirect(add_query_arg(['id' => $ar_id], menu_page_url('newsletter_main_autoresponderusers', false)));
    exit;
}

// Query für Filter/Suche
$where = $wpdb->prepare("WHERE autoresponder_id = %d", $ar_id);
if ($search) {
    $user_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT ID FROM {$wpdb->users} WHERE user_login LIKE %s OR user_email LIKE %s OR display_name LIKE %s",
        "%$search%", "%$search%", "%$search%"
    ));
    if ($user_ids) {
        $where .= " AND user_id IN (" . implode(',', array_map('intval', $user_ids)) . ")";
    } else {
        $where .= " AND 1=0";
    }
}
if ($status_filter) {
    $where .= $wpdb->prepare(" AND status = %s", $status_filter);
}
if ($step_filter) {
    $where .= $wpdb->prepare(" AND current_step = %d", $step_filter);
}

// Gesamtzahl für Paginierung
$total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tnp_autoresponder_progress $where");

// Benutzer (Progress) laden
$progress = [];
if ($ar_id) {
    $progress = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}tnp_autoresponder_progress $where ORDER BY id DESC LIMIT $per_page OFFSET $offset"
    );
}

// Status-Optionen für Filter
$status_options = [
    '' => __('Alle', 'newsletter'),
    'active' => __('Aktiv', 'newsletter'),
    'paused' => __('Pausiert', 'newsletter'),
    'completed' => __('Abgeschlossen', 'newsletter'),
];

?>
<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER; ?>
    <div id="tnp-heading">
        <?php if ($autoresponder) include __DIR__ . '/autorespondernav.php'; ?>
    </div>
    <div id="tnp-body">
        <h1 class="wp-heading-inline"><?php esc_html_e('Autoresponder-Benutzer', 'newsletter'); ?></h1>
        <hr class="wp-header-end">

        <!-- Suchfeld & Filter -->
        <form method="get" style="margin-bottom: 1em;">
            <input type="hidden" name="page" value="newsletter_main_autoresponderusers">
            <input type="hidden" name="id" value="<?php echo esc_attr($ar_id); ?>">
            <input type="search" name="search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Benutzer suchen...', 'newsletter'); ?>">
            <select name="status">
                <?php foreach ($status_options as $val => $label): ?>
                    <option value="<?php echo esc_attr($val); ?>"<?php selected($status_filter, $val); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="step" value="<?php echo esc_attr($step_filter); ?>" min="0" placeholder="<?php esc_attr_e('Schritt', 'newsletter'); ?>" style="width: 80px;">
            <button class="button"><?php esc_html_e('Filtern', 'newsletter'); ?></button>
        </form>

        <?php if (!$ar_id): ?>
            <div class="notice notice-error"><p><?php esc_html_e('Autoresponder-ID fehlt.', 'newsletter'); ?></p></div>
        <?php elseif (empty($progress)): ?>
            <div class="notice notice-info inline"><p><?php esc_html_e('Noch keine Benutzer für diese Serie.', 'newsletter'); ?></p></div>
        <?php else: ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('User', 'newsletter'); ?></th>
                        <th><?php esc_html_e('Aktueller Schritt', 'newsletter'); ?></th>
                        <th><?php esc_html_e('Status', 'newsletter'); ?></th>
                        <th><?php esc_html_e('Letzte E-Mail', 'newsletter'); ?></th>
                        <th><?php esc_html_e('Gestartet am', 'newsletter'); ?></th>
                        <th><?php esc_html_e('Aktualisiert am', 'newsletter'); ?></th>
                        <th><?php esc_html_e('Aktionen', 'newsletter'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($progress as $row): ?>
                        <tr>
                            <td>
                                <?php
                                $user = get_userdata($row->user_id);
                                if ($user) {
                                    echo esc_html($user->display_name) . ' <span style="color:#888">(' . esc_html($user->user_email) . ')</span>';
                                } else {
                                    echo esc_html($row->user_id);
                                }
                                ?>
                            </td>
                            <td><?php echo intval($row->current_step); ?></td>
                            <td><?php echo esc_html($row->status); ?></td>
                            <td><?php echo esc_html($row->last_sent); ?></td>
                            <td><?php echo esc_html($row->started_at); ?></td>
                            <td><?php echo esc_html($row->updated_at); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(['page'=>'newsletter_main_autoresponderusers','id'=>$ar_id,'remove_user'=>$row->user_id])); ?>"
                                   class="button button-small" onclick="return confirm('<?php esc_attr_e('Benutzer wirklich entfernen?', 'newsletter'); ?>');">
                                    <?php esc_html_e('Entfernen', 'newsletter'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Paginierung -->
            <?php
            $total_pages = ceil($total / $per_page);
            if ($total_pages > 1): ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        $base_url = remove_query_arg('paged');
                        for ($p = 1; $p <= $total_pages; $p++):
                            $url = add_query_arg('paged', $p, $base_url);
                            ?>
                            <a class="page-numbers<?php if ($p == $page) echo ' current'; ?>" href="<?php echo esc_url($url); ?>"><?php echo $p; ?></a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>