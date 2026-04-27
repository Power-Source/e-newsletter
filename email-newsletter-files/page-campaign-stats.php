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
$date_format = isset( $this->settings['date_format'] ) ? $this->settings['date_format'] : 'Y-m-d';

$campaign_titles = array();
foreach ( (array) $campaigns as $campaign ) {
    $campaign_titles[ intval( $campaign['campaign_id'] ) ] = $campaign['title'];
}

function enews_stats_date( $ts, $format ) {
    $ts = intval( $ts );
    return $ts > 0 ? date_i18n( $format . ' H:i', $ts ) : '&ndash;';
}
?>
<div class="wrap enews-campaign-stats-page">
    <h1><?php _e( 'Kampagnen-Metriken', 'email-newsletter' ); ?></h1>

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
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $top_links ) ) : ?>
                <tr><td colspan="4"><?php _e( 'Noch keine Klickdaten vorhanden.', 'email-newsletter' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $top_links as $link_row ) : ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url( $link_row['target_url'] ); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html( $link_row['target_url'] ); ?>
                            </a>
                        </td>
                        <td><?php echo intval( $link_row['clicks'] ); ?></td>
                        <td><?php echo intval( $link_row['unique_clickers'] ); ?></td>
                        <td><?php echo esc_html( enews_stats_date( $link_row['last_clicked'], $date_format ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
