<?php
if ( ! current_user_can( 'save_newsletter' ) ) {
    wp_die( __( 'Dazu hast Du keine Berechtigung.', 'email-newsletter' ) );
}

$campaign_id = isset( $_REQUEST['campaign_id'] ) ? intval( $_REQUEST['campaign_id'] ) : 0;
$run_id = isset( $_REQUEST['run_id'] ) ? intval( $_REQUEST['run_id'] ) : 0;
$campaigns = $this->get_campaigns();
$current_campaign = $campaign_id ? $this->get_campaign( $campaign_id ) : false;
$summary = $this->get_campaign_run_summary( $campaign_id );
$runs = $this->get_campaign_runs( $campaign_id, 200 );
$top_links = $this->get_campaign_top_links( $campaign_id, $run_id, 30 );
$kpi_series = $this->get_campaign_kpi_series( $campaign_id, 24 );
$date_format = isset( $this->settings['date_format'] ) ? $this->settings['date_format'] : 'Y-m-d';
$selected_target_hash = isset( $_REQUEST['target_hash'] ) ? preg_replace( '/[^a-f0-9]/', '', strtolower( sanitize_text_field( wp_unslash( $_REQUEST['target_hash'] ) ) ) ) : '';
$clickers = '' !== $selected_target_hash ? $this->get_campaign_clickers( $campaign_id, $run_id, $selected_target_hash, 500 ) : array();
$groups = $this->get_groups( 0 );
$status_message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';
$status_updated = isset( $_GET['updated'] ) && 'true' === $_GET['updated'];

$summary_sent = intval( isset( $summary['sent'] ) ? $summary['sent'] : 0 );
$summary_opened = intval( isset( $summary['opened'] ) ? $summary['opened'] : 0 );
$summary_clicked = intval( isset( $summary['clicked'] ) ? $summary['clicked'] : 0 );

$open_rate = $summary_sent > 0 ? round( ( $summary_opened / $summary_sent ) * 100, 2 ) : 0;
$click_rate = $summary_sent > 0 ? round( ( $summary_clicked / $summary_sent ) * 100, 2 ) : 0;
$reactivity = $summary_opened > 0 ? round( ( $summary_clicked / $summary_opened ) * 100, 2 ) : 0;

$campaign_titles = array();
foreach ( (array) $campaigns as $campaign ) {
    $campaign_titles[ intval( $campaign['campaign_id'] ) ] = $campaign['title'];
}

function enews_stats_date( $ts, $format ) {
    $ts = intval( $ts );
    return $ts > 0 ? date_i18n( $format . ' H:i', $ts ) : '&ndash;';
}

function enews_stats_line_points( $series, $metric_key, $width = 620, $height = 200, $padding = 24 ) {
    if ( empty( $series ) ) {
        return '';
    }

    $count = count( $series );
    $usable_width = max( 1, $width - ( $padding * 2 ) );
    $usable_height = max( 1, $height - ( $padding * 2 ) );
    $step = $count > 1 ? $usable_width / ( $count - 1 ) : 0;
    $points = array();

    for ( $i = 0; $i < $count; $i++ ) {
        $value = isset( $series[ $i ][ $metric_key ] ) ? floatval( $series[ $i ][ $metric_key ] ) : 0;
        $value = max( 0, min( 100, $value ) );
        $x = $padding + ( $step * $i );
        $y = $padding + ( ( 100 - $value ) / 100 ) * $usable_height;
        $points[] = round( $x, 2 ) . ',' . round( $y, 2 );
    }

    return implode( ' ', $points );
}
?>
<div class="wrap enews-campaign-stats-page">
    <h1><?php _e( 'Kampagnen-Metriken', 'email-newsletter' ); ?></h1>

    <?php if ( ! empty( $status_message ) ) : ?>
        <div class="notice <?php echo $status_updated ? 'notice-success' : 'notice-warning'; ?> is-dismissible"><p><?php echo esc_html( urldecode( $status_message ) ); ?></p></div>
    <?php endif; ?>

    <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin:12px 0 18px;">
        <input type="hidden" name="page" value="newsletters-campaign-stats" />
        <label for="campaign_id"><strong><?php _e( 'Eintrag wählen:', 'email-newsletter' ); ?></strong></label>
        <select name="campaign_id" id="campaign_id">
            <option value="0"><?php _e( 'Alle', 'email-newsletter' ); ?></option>
            <?php foreach ( (array) $campaigns as $campaign ) : ?>
                <option value="<?php echo intval( $campaign['campaign_id'] ); ?>" <?php selected( intval( $campaign['campaign_id'] ), $campaign_id ); ?>>
                    <?php echo esc_html( $campaign['title'] ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <label for="run_id" style="margin-left:10px;"><strong><?php _e( 'Run:', 'email-newsletter' ); ?></strong></label>
        <select name="run_id" id="run_id">
            <option value="0"><?php _e( 'Alle Runs', 'email-newsletter' ); ?></option>
            <?php foreach ( (array) $runs as $run ) : ?>
                <option value="<?php echo intval( $run['run_id'] ); ?>" <?php selected( intval( $run['run_id'] ), $run_id ); ?>>
                    #<?php echo intval( $run['run_id'] ); ?> - <?php echo esc_html( enews_stats_date( $run['started_at'], $date_format ) ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="button button-secondary"><?php _e( 'Filtern', 'email-newsletter' ); ?></button>
        <a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'newsletters-campaigns' ), admin_url( 'admin.php' ) ) ); ?>"><?php _e( 'Zur Übersicht', 'email-newsletter' ); ?></a>
    </form>

    <?php if ( $current_campaign ) : ?>
        <p>
            <strong><?php _e( 'Typ:', 'email-newsletter' ); ?></strong>
            <?php echo esc_html( 'campaign' === $current_campaign['entity_type'] ? __( 'Kampagne', 'email-newsletter' ) : __( 'Automation', 'email-newsletter' ) ); ?>,
            <strong><?php _e( 'Status:', 'email-newsletter' ); ?></strong>
            <?php echo esc_html( $current_campaign['status'] ); ?>
        </p>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:12px;max-width:980px;margin:8px 0 18px;">
        <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px 14px;">
            <div style="font-size:12px;color:#646970;"><?php _e( 'Open Rate', 'email-newsletter' ); ?></div>
            <strong style="font-size:22px;line-height:1.2;"><?php echo esc_html( number_format_i18n( $open_rate, 2 ) ); ?>%</strong>
        </div>
        <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px 14px;">
            <div style="font-size:12px;color:#646970;"><?php _e( 'Click Rate', 'email-newsletter' ); ?></div>
            <strong style="font-size:22px;line-height:1.2;"><?php echo esc_html( number_format_i18n( $click_rate, 2 ) ); ?>%</strong>
        </div>
        <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px 14px;">
            <div style="font-size:12px;color:#646970;"><?php _e( 'Reaktivität (Clicks/Opens)', 'email-newsletter' ); ?></div>
            <strong style="font-size:22px;line-height:1.2;"><?php echo esc_html( number_format_i18n( $reactivity, 2 ) ); ?>%</strong>
        </div>
    </div>

    <h2><?php _e( 'KPI-Verlauf', 'email-newsletter' ); ?></h2>
    <?php if ( empty( $kpi_series ) ) : ?>
        <p><?php _e( 'Noch keine KPI-Verlaufsdaten vorhanden.', 'email-newsletter' ); ?></p>
    <?php else : ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;max-width:1280px;margin-bottom:16px;">
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px;">
                <div style="font-weight:600;margin-bottom:8px;"><?php _e( 'Open Rate Verlauf', 'email-newsletter' ); ?></div>
                <svg viewBox="0 0 620 200" width="100%" height="220" role="img" aria-label="Open Rate Verlauf">
                    <line x1="24" y1="176" x2="596" y2="176" stroke="#ccd0d4" stroke-width="1" />
                    <line x1="24" y1="24" x2="24" y2="176" stroke="#ccd0d4" stroke-width="1" />
                    <polyline fill="none" stroke="#1d4ed8" stroke-width="3" points="<?php echo esc_attr( enews_stats_line_points( $kpi_series, 'open_rate' ) ); ?>" />
                </svg>
            </div>
            <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px;">
                <div style="font-weight:600;margin-bottom:8px;"><?php _e( 'Click Rate Verlauf', 'email-newsletter' ); ?></div>
                <svg viewBox="0 0 620 200" width="100%" height="220" role="img" aria-label="Click Rate Verlauf">
                    <line x1="24" y1="176" x2="596" y2="176" stroke="#ccd0d4" stroke-width="1" />
                    <line x1="24" y1="24" x2="24" y2="176" stroke="#ccd0d4" stroke-width="1" />
                    <polyline fill="none" stroke="#0f766e" stroke-width="3" points="<?php echo esc_attr( enews_stats_line_points( $kpi_series, 'click_rate' ) ); ?>" />
                </svg>
            </div>
        </div>
    <?php endif; ?>

    <table class="widefat" style="max-width:980px;margin-bottom:20px;">
        <thead>
            <tr>
                <th><?php _e( 'Runs', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Queued', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Sent', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Opened', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Clicked', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Bounced', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Failed', 'email-newsletter' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?php echo intval( $summary['runs'] ); ?></td>
                <td><?php echo intval( $summary['queued'] ); ?></td>
                <td><?php echo intval( $summary['sent'] ); ?></td>
                <td><?php echo intval( $summary['opened'] ); ?></td>
                <td><?php echo intval( $summary['clicked'] ); ?></td>
                <td><?php echo intval( $summary['bounced'] ); ?></td>
                <td><?php echo intval( $summary['failed'] ); ?></td>
            </tr>
        </tbody>
    </table>

    <table class="widefat striped">
        <thead>
            <tr>
                <th><?php _e( 'Run ID', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Kampagne', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Status', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Scheduled', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Started', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Finished', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Queued', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Sent', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Opened', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Clicked', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Bounced', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Failed', 'email-newsletter' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $runs ) ) : ?>
                <tr><td colspan="12"><?php _e( 'Keine Runs gefunden.', 'email-newsletter' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $runs as $run ) : ?>
                    <tr>
                        <td><?php echo intval( $run['run_id'] ); ?></td>
                        <td><?php echo isset( $campaign_titles[ intval( $run['campaign_id'] ) ] ) ? esc_html( $campaign_titles[ intval( $run['campaign_id'] ) ] ) : '#'.intval( $run['campaign_id'] ); ?></td>
                        <td><?php echo esc_html( $run['status'] ); ?></td>
                        <td><?php echo esc_html( enews_stats_date( $run['scheduled_at'], $date_format ) ); ?></td>
                        <td><?php echo esc_html( enews_stats_date( $run['started_at'], $date_format ) ); ?></td>
                        <td><?php echo esc_html( enews_stats_date( $run['finished_at'], $date_format ) ); ?></td>
                        <td><?php echo intval( $run['queued'] ); ?></td>
                        <td><?php echo intval( $run['sent'] ); ?></td>
                        <td><?php echo intval( $run['opened'] ); ?></td>
                        <td><?php echo intval( $run['clicked'] ); ?></td>
                        <td><?php echo intval( $run['bounced'] ); ?></td>
                        <td><?php echo intval( $run['failed'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <h2 style="margin-top:24px;"><?php _e( 'Top geklickte Links', 'email-newsletter' ); ?></h2>
    <table class="widefat striped" style="max-width:1200px;">
        <thead>
            <tr>
                <th><?php _e( 'URL', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Klicks', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Unique Klicker', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Zuletzt geklickt', 'email-newsletter' ); ?></th>
                <th><?php _e( 'Aktion', 'email-newsletter' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $top_links ) ) : ?>
                <tr><td colspan="5"><?php _e( 'Noch keine Klickdaten vorhanden.', 'email-newsletter' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $top_links as $link_row ) : ?>
                    <?php
                    $clickers_url = add_query_arg(
                        array(
                            'page' => 'newsletters-campaign-stats',
                            'campaign_id' => intval( $campaign_id ),
                            'run_id' => intval( $run_id ),
                            'target_hash' => isset( $link_row['target_hash'] ) ? $link_row['target_hash'] : '',
                        ),
                        admin_url( 'admin.php' )
                    );
                    ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url( $link_row['target_url'] ); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html( $link_row['target_url'] ); ?>
                            </a>
                        </td>
                        <td><?php echo intval( $link_row['clicks'] ); ?></td>
                        <td><?php echo intval( $link_row['unique_clickers'] ); ?></td>
                        <td><?php echo esc_html( enews_stats_date( $link_row['last_clicked'], $date_format ) ); ?></td>
                        <td><a class="button button-secondary" href="<?php echo esc_url( $clickers_url ); ?>"><?php _e( 'Klicker anzeigen', 'email-newsletter' ); ?></a></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ( '' !== $selected_target_hash ) : ?>
        <h2 style="margin-top:24px;"><?php _e( 'Wer hat geklickt?', 'email-newsletter' ); ?></h2>
        <?php if ( empty( $clickers ) ) : ?>
            <p><?php _e( 'Für diesen Link wurden keine auflösbaren Klicker gefunden.', 'email-newsletter' ); ?></p>
        <?php else : ?>
            <table class="widefat striped" style="max-width:1200px;">
                <thead>
                    <tr>
                        <th><?php _e( 'Name', 'email-newsletter' ); ?></th>
                        <th><?php _e( 'E-Mail', 'email-newsletter' ); ?></th>
                        <th><?php _e( 'Quelle', 'email-newsletter' ); ?></th>
                        <th><?php _e( 'Klicks', 'email-newsletter' ); ?></th>
                        <th><?php _e( 'Zuletzt geklickt', 'email-newsletter' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $clickers as $clicker ) : ?>
                        <?php
                        $member_id = intval( isset( $clicker['member_id'] ) ? $clicker['member_id'] : 0 );
                        $wp_only_user_id = intval( isset( $clicker['wp_only_user_id'] ) ? $clicker['wp_only_user_id'] : 0 );
                        $source_label = __( 'Newsletter-Mitglied', 'email-newsletter' );
                        $name = trim( isset( $clicker['member_name'] ) ? $clicker['member_name'] : '' );
                        $email = isset( $clicker['member_email'] ) ? $clicker['member_email'] : '';

                        if ( $member_id <= 0 && $wp_only_user_id > 0 ) {
                            $source_label = __( 'WordPress Benutzer', 'email-newsletter' );
                            $wp_user = get_userdata( $wp_only_user_id );
                            if ( $wp_user ) {
                                $name = $wp_user->display_name;
                                $email = $wp_user->user_email;
                            }
                        }

                        if ( '' === $name ) {
                            $name = '-';
                        }
                        if ( '' === $email ) {
                            $email = '-';
                        }
                        ?>
                        <tr>
                            <td><?php echo esc_html( $name ); ?></td>
                            <td><?php echo esc_html( $email ); ?></td>
                            <td><?php echo esc_html( $source_label ); ?></td>
                            <td><?php echo intval( $clicker['click_count'] ); ?></td>
                            <td><?php echo esc_html( enews_stats_date( $clicker['last_clicked'], $date_format ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ( ! empty( $groups ) ) : ?>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" style="margin-top:12px;max-width:720px;background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px;">
                    <?php wp_nonce_field( 'enewsletter_admin_action', 'newsletter_admin_action_nonce' ); ?>
                    <input type="hidden" name="page" value="newsletters-campaign-stats" />
                    <input type="hidden" name="newsletter_action" value="campaign_add_clickers_to_group" />
                    <input type="hidden" name="campaign_id" value="<?php echo intval( $campaign_id ); ?>" />
                    <input type="hidden" name="run_id" value="<?php echo intval( $run_id ); ?>" />
                    <input type="hidden" name="target_hash" value="<?php echo esc_attr( $selected_target_hash ); ?>" />

                    <label for="clicker_group_id"><strong><?php _e( 'Direktaktion', 'email-newsletter' ); ?>:</strong></label>
                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:6px;">
                        <select name="group_id" id="clicker_group_id" required>
                            <option value="0"><?php _e( 'Gruppe wählen', 'email-newsletter' ); ?></option>
                            <?php foreach ( (array) $groups as $group ) : ?>
                                <option value="<?php echo intval( $group['group_id'] ); ?>"><?php echo esc_html( isset( $group['group_name'] ) ? $group['group_name'] : ( '#' . intval( $group['group_id'] ) ) ); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="button button-primary"><?php _e( 'Klicker zur Gruppe hinzufügen', 'email-newsletter' ); ?></button>
                    </div>
                    <p class="description" style="margin:8px 0 0;">
                        <?php _e( 'Es werden Newsletter-Mitglieder aus dem Drilldown in die gewählte Gruppe aufgenommen.', 'email-newsletter' ); ?>
                    </p>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
