<?php
if ( ! current_user_can( 'save_newsletter_settings' ) ) {
    wp_die( __( 'Dazu hast Du keine Berechtigung.', 'email-newsletter' ) );
}

$level = isset( $_GET['level'] ) ? sanitize_key( wp_unslash( $_GET['level'] ) ) : '';
$event = isset( $_GET['event'] ) ? sanitize_key( wp_unslash( $_GET['event'] ) ) : '';
$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$limit = isset( $_GET['limit'] ) ? intval( $_GET['limit'] ) : 200;
$limit = max( 20, min( 1000, $limit ) );

$entries = $this->get_debug_log_entries( $limit, $level, $event, $search );
$log_file = $this->get_debug_log_file_path();
$message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';
$updated = isset( $_GET['updated'] ) && 'true' === $_GET['updated'];

$levels = array( 'debug', 'info', 'warning', 'error', 'legacy' );
$event_options = array();
foreach ( (array) $entries as $entry ) {
    if ( ! empty( $entry['event'] ) ) {
        $event_options[ $entry['event'] ] = $entry['event'];
    }
}
ksort( $event_options );

$clear_url = wp_nonce_url(
    add_query_arg(
        array(
            'page' => 'newsletters-logs',
            'newsletter_action' => 'clear_logs',
        ),
        admin_url( 'admin.php' )
    ),
    'enewsletter_admin_action'
);

$download_url = wp_nonce_url(
    add_query_arg(
        array(
            'page' => 'newsletters-logs',
            'newsletter_action' => 'download_logs',
        ),
        admin_url( 'admin.php' )
    ),
    'enewsletter_admin_action'
);

$refresh_url = add_query_arg( array( 'page' => 'newsletters-logs' ), admin_url( 'admin.php' ) );
?>
<div class="wrap enews-logs-page">
    <h1><?php _e( 'Logs', 'email-newsletter' ); ?></h1>

    <?php if ( ! empty( $message ) ) : ?>
        <div class="notice <?php echo $updated ? 'notice-success' : 'notice-warning'; ?> is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
    <?php endif; ?>

    <p class="description"><?php echo esc_html( $log_file ); ?></p>

    <div class="enews-toolbar">
        <a class="button" href="<?php echo esc_url( $refresh_url ); ?>"><?php _e( 'Zurücksetzen', 'email-newsletter' ); ?></a>
        <a class="button" href="<?php echo esc_url( $download_url ); ?>"><?php _e( 'Logs herunterladen', 'email-newsletter' ); ?></a>
        <a class="button enews-danger" href="<?php echo esc_url( $clear_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Logs wirklich leeren?', 'email-newsletter' ) ); ?>');"><?php _e( 'Logs leeren', 'email-newsletter' ); ?></a>
    </div>

    <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="enews-toolbar" style="align-items:center;">
        <input type="hidden" name="page" value="newsletters-logs" />

        <label for="enews-log-level"><strong><?php _e( 'Level', 'email-newsletter' ); ?></strong></label>
        <select name="level" id="enews-log-level">
            <option value=""><?php _e( 'Alle', 'email-newsletter' ); ?></option>
            <?php foreach ( $levels as $level_option ) : ?>
                <option value="<?php echo esc_attr( $level_option ); ?>" <?php selected( $level, $level_option ); ?>><?php echo esc_html( $level_option ); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="enews-log-event"><strong><?php _e( 'Event', 'email-newsletter' ); ?></strong></label>
        <select name="event" id="enews-log-event">
            <option value=""><?php _e( 'Alle', 'email-newsletter' ); ?></option>
            <?php foreach ( $event_options as $event_option ) : ?>
                <option value="<?php echo esc_attr( $event_option ); ?>" <?php selected( $event, $event_option ); ?>><?php echo esc_html( $event_option ); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="enews-log-limit"><strong><?php _e( 'Limit', 'email-newsletter' ); ?></strong></label>
        <input type="number" id="enews-log-limit" name="limit" min="20" max="1000" value="<?php echo intval( $limit ); ?>" style="width:90px;" />

        <label for="enews-log-search"><strong><?php _e( 'Suche', 'email-newsletter' ); ?></strong></label>
        <input type="search" id="enews-log-search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="event / message / context" style="min-width:220px;" />

        <button type="submit" class="button button-primary"><?php _e( 'Filtern', 'email-newsletter' ); ?></button>
    </form>

    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php _e( 'Zeit', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Level', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Event', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Message', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Kontext', 'email-newsletter' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $entries ) ) : ?>
                <tr><td colspan="5"><?php _e( 'Keine Log-Einträge gefunden.', 'email-newsletter' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $entries as $entry ) : ?>
                    <tr>
                        <td><?php echo esc_html( $entry['time'] ? $entry['time'] : '-' ); ?></td>
                        <td><?php echo esc_html( $entry['level'] ? $entry['level'] : '-' ); ?></td>
                        <td><?php echo esc_html( $entry['event'] ? $entry['event'] : '-' ); ?></td>
                        <td><?php echo esc_html( $entry['message'] ? $entry['message'] : '-' ); ?></td>
                        <td>
                            <?php if ( ! empty( $entry['context'] ) && is_array( $entry['context'] ) ) : ?>
                                <code><?php echo esc_html( wp_json_encode( $entry['context'] ) ); ?></code>
                            <?php else : ?>
                                &ndash;
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
