<?php
if ( ! current_user_can( 'save_newsletter' ) ) {
    wp_die( __( 'Dazu hast Du keine Berechtigung.', 'email-newsletter' ) );
}

$campaigns = $this->get_campaigns();
$message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';
$updated = isset( $_GET['updated'] ) && 'true' === $_GET['updated'];
$date_format = isset( $this->settings['date_format'] ) ? $this->settings['date_format'] : 'Y-m-d';
?>
<div class="wrap">
    <h1><?php _e( 'Kampagnen & Automationen', 'email-newsletter' ); ?></h1>

    <?php if ( ! empty( $message ) ) : ?>
        <div class="notice <?php echo $updated ? 'notice-success' : 'notice-warning'; ?> is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
    <?php endif; ?>

    <p>
        <a class="button button-primary" href="<?php echo esc_url( add_query_arg( array( 'page' => 'newsletters-campaign-edit', 'entity_type' => 'campaign' ), admin_url( 'admin.php' ) ) ); ?>"><?php _e( 'Neue Kampagne', 'email-newsletter' ); ?></a>
        <a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'newsletters-campaign-edit', 'entity_type' => 'automation' ), admin_url( 'admin.php' ) ) ); ?>"><?php _e( 'Neue Automation', 'email-newsletter' ); ?></a>
    </p>

    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php _e( 'Titel', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Typ', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Status', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Letzter Lauf', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Nächster Lauf', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Aktionen', 'email-newsletter' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $campaigns ) ) : ?>
                <tr><td colspan="6"><?php _e( 'Noch keine Kampagnen oder Automationen vorhanden.', 'email-newsletter' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $campaigns as $campaign ) : ?>
                    <?php
                    $campaign_id = intval( $campaign['campaign_id'] );
                    $edit_url = add_query_arg(
                        array(
                            'page' => 'newsletters-campaign-edit',
                            'campaign_id' => $campaign_id,
                        ),
                        admin_url( 'admin.php' )
                    );
                    $stats_url = add_query_arg(
                        array(
                            'page' => 'newsletters-campaign-stats',
                            'campaign_id' => $campaign_id,
                        ),
                        admin_url( 'admin.php' )
                    );
                    $pause_url = wp_nonce_url(
                        add_query_arg(
                            array(
                                'page' => 'newsletters-campaigns',
                                'newsletter_action' => 'pause_campaign',
                                'campaign_id' => $campaign_id,
                            ),
                            admin_url( 'admin.php' )
                        ),
                        'enewsletter_admin_action'
                    );
                    $resume_url = wp_nonce_url(
                        add_query_arg(
                            array(
                                'page' => 'newsletters-campaigns',
                                'newsletter_action' => 'resume_campaign',
                                'campaign_id' => $campaign_id,
                            ),
                            admin_url( 'admin.php' )
                        ),
                        'enewsletter_admin_action'
                    );
                    $stop_url = wp_nonce_url(
                        add_query_arg(
                            array(
                                'page' => 'newsletters-campaigns',
                                'newsletter_action' => 'stop_campaign',
                                'campaign_id' => $campaign_id,
                            ),
                            admin_url( 'admin.php' )
                        ),
                        'enewsletter_admin_action'
                    );
                    $delete_url = wp_nonce_url(
                        add_query_arg(
                            array(
                                'page' => 'newsletters-campaigns',
                                'newsletter_action' => 'delete_campaign',
                                'campaign_id' => $campaign_id,
                            ),
                            admin_url( 'admin.php' )
                        ),
                        'enewsletter_admin_action'
                    );

                    $entity_label = 'campaign' === $campaign['entity_type'] ? __( 'Kampagne', 'email-newsletter' ) : __( 'Automation', 'email-newsletter' );
                    $last_run = intval( $campaign['last_run'] );
                    $next_run = intval( $campaign['next_run'] );
                    ?>
                    <tr>
                        <td><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $campaign['title'] ); ?></a></td>
                        <td><?php echo esc_html( $entity_label ); ?></td>
                        <td><?php echo esc_html( $campaign['status'] ); ?></td>
                        <td><?php echo $last_run ? esc_html( date_i18n( $date_format . ' H:i', $last_run ) ) : '&ndash;'; ?></td>
                        <td><?php echo $next_run ? esc_html( date_i18n( $date_format . ' H:i', $next_run ) ) : '&ndash;'; ?></td>
                        <td>
                            <a class="button button-small" href="<?php echo esc_url( $edit_url ); ?>"><?php _e( 'Bearbeiten', 'email-newsletter' ); ?></a>
                            <a class="button button-small" href="<?php echo esc_url( $stats_url ); ?>"><?php _e( 'Metriken', 'email-newsletter' ); ?></a>
                            <?php if ( 'active' === $campaign['status'] ) : ?>
                                <a class="button button-small" href="<?php echo esc_url( $pause_url ); ?>"><?php _e( 'Pausieren', 'email-newsletter' ); ?></a>
                            <?php else : ?>
                                <a class="button button-small" href="<?php echo esc_url( $resume_url ); ?>"><?php _e( 'Fortsetzen', 'email-newsletter' ); ?></a>
                            <?php endif; ?>
                            <a class="button button-small" href="<?php echo esc_url( $stop_url ); ?>"><?php _e( 'Stoppen', 'email-newsletter' ); ?></a>
                            <a class="button button-small" href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Wirklich löschen?', 'email-newsletter' ) ); ?>');"><?php _e( 'Löschen', 'email-newsletter' ); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
