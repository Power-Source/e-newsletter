<?php
if ( ! current_user_can( 'save_newsletter' ) ) {
    wp_die( __( 'Dazu hast Du keine Berechtigung.', 'email-newsletter' ) );
}

$newsletter_id = isset( $_REQUEST['newsletter_id'] ) ? intval( $_REQUEST['newsletter_id'] ) : 0;
$newsletter = $newsletter_id > 0 ? $this->get_newsletter_data( $newsletter_id ) : false;
$versions = array();
if ( $newsletter && isset( $this->builder_v2 ) && $this->builder_v2 ) {
    $versions = $this->builder_v2->get_newsletter_versions( $newsletter_id );
}

$message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';
$updated = isset( $_GET['updated'] ) && 'true' === $_GET['updated'];
$date_format = isset( $this->settings['date_format'] ) ? $this->settings['date_format'] : 'Y-m-d';

$reason_labels = array(
    'save' => __( 'Speichern', 'email-newsletter' ),
    'restore' => __( 'Wiederherstellung', 'email-newsletter' ),
);

$preview_version_id = isset( $_REQUEST['preview_version_id'] ) ? sanitize_key( wp_unslash( $_REQUEST['preview_version_id'] ) ) : '';
$preview_entry = false;
$preview_current_html = '';
$preview_selected_html = '';
$preview_diff = array(
    'subject_changed' => false,
    'sections_current' => 0,
    'sections_selected' => 0,
    'blocks_current' => 0,
    'blocks_selected' => 0,
);

if ( $newsletter && '' !== $preview_version_id && isset( $this->builder_v2 ) && $this->builder_v2 ) {
    $preview_entry = $this->builder_v2->get_newsletter_version( $newsletter_id, $preview_version_id );
    if ( $preview_entry ) {
        $current_state = $this->builder_v2->get_state( $newsletter_id );
        $preview_current_html = $this->builder_v2->render_full_email_document( $current_state, 'preview', $newsletter_id );
        $preview_selected_html = $this->builder_v2->render_full_email_document( $preview_entry['state'], 'preview', $newsletter_id );

        $count_blocks = function( $state ) {
            $count = 0;
            if ( isset( $state['sections'] ) && is_array( $state['sections'] ) ) {
                foreach ( $state['sections'] as $section ) {
                    if ( empty( $section['rows'] ) || ! is_array( $section['rows'] ) ) {
                        continue;
                    }
                    foreach ( $section['rows'] as $row ) {
                        if ( empty( $row['columns'] ) || ! is_array( $row['columns'] ) ) {
                            continue;
                        }
                        foreach ( $row['columns'] as $col ) {
                            if ( ! empty( $col['blocks'] ) && is_array( $col['blocks'] ) ) {
                                $count += count( $col['blocks'] );
                            }
                        }
                    }
                }
            } elseif ( isset( $state['modules'] ) && is_array( $state['modules'] ) ) {
                $count = count( $state['modules'] );
            }

            return intval( $count );
        };

        $current_subject = isset( $current_state['global']['subject'] ) ? (string) $current_state['global']['subject'] : '';
        $selected_subject = isset( $preview_entry['state']['global']['subject'] ) ? (string) $preview_entry['state']['global']['subject'] : '';
        $preview_diff = array(
            'subject_changed' => $current_subject !== $selected_subject,
            'sections_current' => isset( $current_state['sections'] ) && is_array( $current_state['sections'] ) ? count( $current_state['sections'] ) : 0,
            'sections_selected' => isset( $preview_entry['state']['sections'] ) && is_array( $preview_entry['state']['sections'] ) ? count( $preview_entry['state']['sections'] ) : 0,
            'blocks_current' => $count_blocks( $current_state ),
            'blocks_selected' => $count_blocks( $preview_entry['state'] ),
        );
    }
}
?>
<div class="wrap">
    <h1><?php _e( 'Newsletter-Versionen', 'email-newsletter' ); ?></h1>

    <?php if ( ! empty( $message ) ) : ?>
        <div class="notice <?php echo $updated ? 'notice-success' : 'notice-warning'; ?> is-dismissible"><p><?php echo esc_html( urldecode( $message ) ); ?></p></div>
    <?php endif; ?>

    <?php if ( ! $newsletter ) : ?>
        <div class="notice notice-error"><p><?php _e( 'Newsletter nicht gefunden.', 'email-newsletter' ); ?></p></div>
        <p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=newsletters' ) ); ?>"><?php _e( 'Zur Newsletter-Liste', 'email-newsletter' ); ?></a></p>
    <?php else : ?>
        <p>
            <strong><?php _e( 'Newsletter:', 'email-newsletter' ); ?></strong>
            <?php echo esc_html( $newsletter['subject'] ); ?>
        </p>
        <p class="description"><?php _e( 'Es werden bis zu 30 letzte Builder-Versionen gespeichert. Beim Wiederherstellen wird der aktuelle Stand automatisch als neue Version gesichert.', 'email-newsletter' ); ?></p>

        <p>
            <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=newsletters' ) ); ?>"><?php _e( 'Zur Newsletter-Liste', 'email-newsletter' ); ?></a>
            <a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=newsletters-builder-v2&newsletter_id=' . intval( $newsletter_id ) ) ); ?>"><?php _e( 'Im Builder öffnen', 'email-newsletter' ); ?></a>
        </p>

        <?php if ( $preview_entry ) : ?>
            <h2><?php _e( 'Versionsvorschau', 'email-newsletter' ); ?></h2>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px;max-width:900px;margin:8px 0 14px;">
                <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:10px 12px;">
                    <div style="font-size:12px;color:#646970;"><?php _e( 'Betreff geändert', 'email-newsletter' ); ?></div>
                    <strong><?php echo $preview_diff['subject_changed'] ? esc_html__( 'Ja', 'email-newsletter' ) : esc_html__( 'Nein', 'email-newsletter' ); ?></strong>
                </div>
                <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:10px 12px;">
                    <div style="font-size:12px;color:#646970;"><?php _e( 'Sections (Aktuell / Version)', 'email-newsletter' ); ?></div>
                    <strong><?php echo intval( $preview_diff['sections_current'] ); ?> / <?php echo intval( $preview_diff['sections_selected'] ); ?></strong>
                </div>
                <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:10px 12px;">
                    <div style="font-size:12px;color:#646970;"><?php _e( 'Blöcke (Aktuell / Version)', 'email-newsletter' ); ?></div>
                    <strong><?php echo intval( $preview_diff['blocks_current'] ); ?> / <?php echo intval( $preview_diff['blocks_selected'] ); ?></strong>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <h3 style="margin:0 0 8px;"><?php _e( 'Aktuell', 'email-newsletter' ); ?></h3>
                    <iframe title="current-version-preview" style="width:100%;min-height:540px;border:1px solid #dcdcde;border-radius:8px;background:#fff;" srcdoc="<?php echo esc_attr( $preview_current_html ); ?>"></iframe>
                </div>
                <div>
                    <h3 style="margin:0 0 8px;"><?php _e( 'Gewählte Version', 'email-newsletter' ); ?></h3>
                    <iframe title="selected-version-preview" style="width:100%;min-height:540px;border:1px solid #dcdcde;border-radius:8px;background:#fff;" srcdoc="<?php echo esc_attr( $preview_selected_html ); ?>"></iframe>
                </div>
            </div>
        <?php endif; ?>

        <table class="widefat striped" style="max-width:1200px;">
            <thead>
                <tr>
                    <th><?php _e( 'Zeitpunkt', 'email-newsletter' ); ?></th>
                    <th><?php _e( 'Benutzer', 'email-newsletter' ); ?></th>
                    <th><?php _e( 'Grund', 'email-newsletter' ); ?></th>
                    <th><?php _e( 'Betreff', 'email-newsletter' ); ?></th>
                    <th><?php _e( 'Aktionen', 'email-newsletter' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $versions ) ) : ?>
                    <tr><td colspan="5"><?php _e( 'Noch keine Versionen vorhanden.', 'email-newsletter' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $versions as $entry ) : ?>
                        <?php
                        $user_label = __( 'System', 'email-newsletter' );
                        $user_id = isset( $entry['user_id'] ) ? intval( $entry['user_id'] ) : 0;
                        if ( $user_id > 0 ) {
                            $user = get_userdata( $user_id );
                            if ( $user ) {
                                $user_label = $user->display_name . ' (#' . $user_id . ')';
                            } else {
                                $user_label = '#' . $user_id;
                            }
                        }

                        $reason = isset( $entry['reason'] ) ? $entry['reason'] : 'save';
                        $reason_label = isset( $reason_labels[ $reason ] ) ? $reason_labels[ $reason ] : ucfirst( $reason );

                        $restore_url = wp_nonce_url(
                            add_query_arg(
                                array(
                                    'page' => 'newsletters-versions',
                                    'newsletter_action' => 'restore_newsletter_version',
                                    'newsletter_id' => intval( $newsletter_id ),
                                    'version_id' => isset( $entry['id'] ) ? $entry['id'] : '',
                                ),
                                admin_url( 'admin.php' )
                            ),
                            'enewsletter_admin_action'
                        );

                        $preview_url = add_query_arg(
                            array(
                                'page' => 'newsletters-versions',
                                'newsletter_id' => intval( $newsletter_id ),
                                'preview_version_id' => isset( $entry['id'] ) ? $entry['id'] : '',
                            ),
                            admin_url( 'admin.php' )
                        );
                        ?>
                        <tr>
                            <td><?php echo ! empty( $entry['created_at'] ) ? esc_html( date_i18n( $date_format . ' H:i:s', intval( $entry['created_at'] ) ) ) : '&ndash;'; ?></td>
                            <td><?php echo esc_html( $user_label ); ?></td>
                            <td><?php echo esc_html( $reason_label ); ?></td>
                            <td><?php echo esc_html( isset( $entry['subject'] ) ? $entry['subject'] : '' ); ?></td>
                            <td>
                                <a class="button" href="<?php echo esc_url( $preview_url ); ?>"><?php _e( 'Vorschau', 'email-newsletter' ); ?></a>
                                <a class="button button-secondary" href="<?php echo esc_url( $restore_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Diese Version wirklich wiederherstellen?', 'email-newsletter' ) ); ?>');"><?php _e( 'Wiederherstellen', 'email-newsletter' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
