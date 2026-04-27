<?php
if ( ! current_user_can( 'save_newsletter' ) ) {
    wp_die( __( 'Dazu hast Du keine Berechtigung.', 'email-newsletter' ) );
}

$campaign_id = isset( $_REQUEST['campaign_id'] ) ? intval( $_REQUEST['campaign_id'] ) : 0;
$campaign = $campaign_id ? $this->get_campaign( $campaign_id ) : false;
$entity_type = $campaign ? $campaign['entity_type'] : ( isset( $_REQUEST['entity_type'] ) ? sanitize_key( wp_unslash( $_REQUEST['entity_type'] ) ) : 'campaign' );
$entity_type = in_array( $entity_type, array( 'campaign', 'automation' ), true ) ? $entity_type : 'campaign';
$settings = $campaign ? $this->decode_campaign_json( $campaign['settings'] ) : array();
$targets = $campaign ? $this->decode_campaign_json( $campaign['targets'] ) : array();
$selected_groups = isset( $targets['group_ids'] ) && is_array( $targets['group_ids'] ) ? array_map( 'intval', $targets['group_ids'] ) : array();
$newsletters = $this->get_newsletters( array( 'orderby' => 'create_date', 'order' => 'desc' ), 0, 0 );
$groups = $this->get_groups();
$title = $campaign ? $campaign['title'] : '';
$status = $campaign ? $campaign['status'] : 'draft';
$newsletter_id = $campaign ? intval( $campaign['newsletter_id'] ) : 0;
$message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';
?>
<div class="wrap">
    <h1><?php echo esc_html( 'campaign' === $entity_type ? __( 'Kampagne bearbeiten', 'email-newsletter' ) : __( 'Automation bearbeiten', 'email-newsletter' ) ); ?></h1>

    <?php if ( ! empty( $message ) ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
        <?php wp_nonce_field( 'enewsletter_admin_action' ); ?>
        <input type="hidden" name="page" value="newsletters-campaign-edit" />
        <input type="hidden" name="newsletter_action" value="save_campaign" />
        <input type="hidden" name="campaign_id" value="<?php echo intval( $campaign_id ); ?>" />
        <input type="hidden" name="entity_type" value="<?php echo esc_attr( $entity_type ); ?>" />

        <table class="form-table" role="presentation">
            <tr>
                <th><label for="campaign_title"><?php _e( 'Titel', 'email-newsletter' ); ?></label></th>
                <td><input type="text" class="regular-text" id="campaign_title" name="title" value="<?php echo esc_attr( $title ); ?>" required /></td>
            </tr>
            <tr>
                <th><label for="campaign_status"><?php _e( 'Status', 'email-newsletter' ); ?></label></th>
                <td>
                    <select id="campaign_status" name="status">
                        <option value="draft" <?php selected( $status, 'draft' ); ?>><?php _e( 'Entwurf', 'email-newsletter' ); ?></option>
                        <option value="active" <?php selected( $status, 'active' ); ?>><?php _e( 'Aktiv', 'email-newsletter' ); ?></option>
                        <option value="paused" <?php selected( $status, 'paused' ); ?>><?php _e( 'Pausiert', 'email-newsletter' ); ?></option>
                        <option value="stopped" <?php selected( $status, 'stopped' ); ?>><?php _e( 'Gestoppt', 'email-newsletter' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="campaign_newsletter_id"><?php _e( 'Newsletter Vorlage', 'email-newsletter' ); ?></label></th>
                <td>
                    <select id="campaign_newsletter_id" name="newsletter_id" required>
                        <option value="0"><?php _e( 'Bitte wählen ...', 'email-newsletter' ); ?></option>
                        <?php foreach ( (array) $newsletters as $newsletter ) : ?>
                            <option value="<?php echo intval( $newsletter['newsletter_id'] ); ?>" <?php selected( $newsletter_id, intval( $newsletter['newsletter_id'] ) ); ?>>
                                #<?php echo intval( $newsletter['newsletter_id'] ); ?> - <?php echo esc_html( $newsletter['subject'] ? $newsletter['subject'] : __( '(Ohne Betreff)', 'email-newsletter' ) ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="campaign_group_ids"><?php _e( 'Zielgruppen (Newsletter-Gruppen)', 'email-newsletter' ); ?></label></th>
                <td>
                    <select id="campaign_group_ids" name="campaign[group_ids][]" multiple size="6" style="min-width:280px;">
                        <?php foreach ( (array) $groups as $group ) : ?>
                            <?php $group_id = intval( $group['group_id'] ); ?>
                            <option value="<?php echo $group_id; ?>" <?php selected( in_array( $group_id, $selected_groups, true ), true ); ?>>
                                <?php echo esc_html( $group['group_name'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php _e( 'Leer lassen = alle aktiven Abonnenten.', 'email-newsletter' ); ?></p>
                </td>
            </tr>

            <?php if ( 'campaign' === $entity_type ) : ?>
                <tr>
                    <th><?php _e( 'Intervall', 'email-newsletter' ); ?></th>
                    <td>
                        <input type="number" min="1" max="365" name="campaign[interval_value]" value="<?php echo esc_attr( isset( $settings['interval_value'] ) ? intval( $settings['interval_value'] ) : 7 ); ?>" style="width:90px;" />
                        <select name="campaign[interval_unit]">
                            <option value="hours" <?php selected( isset( $settings['interval_unit'] ) ? $settings['interval_unit'] : '', 'hours' ); ?>><?php _e( 'Stunden', 'email-newsletter' ); ?></option>
                            <option value="days" <?php selected( isset( $settings['interval_unit'] ) ? $settings['interval_unit'] : 'days', 'days' ); ?>><?php _e( 'Tage', 'email-newsletter' ); ?></option>
                            <option value="weeks" <?php selected( isset( $settings['interval_unit'] ) ? $settings['interval_unit'] : '', 'weeks' ); ?>><?php _e( 'Wochen', 'email-newsletter' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e( 'Startzeitpunkt (optional)', 'email-newsletter' ); ?></th>
                    <td>
                        <input type="number" min="0" step="1" name="campaign[start_at]" value="<?php echo esc_attr( isset( $settings['start_at'] ) ? intval( $settings['start_at'] ) : 0 ); ?>" style="width:180px;" />
                        <p class="description"><?php _e( 'Unix-Timestamp. 0 = sofort nach Aktivierung im Intervall.', 'email-newsletter' ); ?></p>
                    </td>
                </tr>
            <?php else : ?>
                <tr>
                    <th><?php _e( 'Trigger', 'email-newsletter' ); ?></th>
                    <td>
                        <select name="campaign[trigger_type]" id="automation_trigger_type">
                            <option value="new_post" <?php selected( isset( $settings['trigger_type'] ) ? $settings['trigger_type'] : 'new_post', 'new_post' ); ?>><?php _e( 'Bei neuem Beitrag', 'email-newsletter' ); ?></option>
                            <option value="new_product" <?php selected( isset( $settings['trigger_type'] ) ? $settings['trigger_type'] : '', 'new_product' ); ?>><?php _e( 'Bei neuem Produkt', 'email-newsletter' ); ?></option>
                            <option value="digest" <?php selected( isset( $settings['trigger_type'] ) ? $settings['trigger_type'] : '', 'digest' ); ?>><?php _e( 'Geplanter Digest', 'email-newsletter' ); ?></option>
                        </select>
                        <p class="description"><?php _e( 'Tipp: Stelle in den Builder-Modulen Beitraege/Produkte die Inhaltsquelle auf "Aus Automation-Trigger", damit Single/List/Grid dynamisch aus dem Trigger befuellt wird.', 'email-newsletter' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e( 'Digest Rhythmus', 'email-newsletter' ); ?></th>
                    <td>
                        <select name="campaign[digest_period]">
                            <option value="weekly" <?php selected( isset( $settings['digest_period'] ) ? $settings['digest_period'] : 'weekly', 'weekly' ); ?>><?php _e( 'Wöchentlich', 'email-newsletter' ); ?></option>
                            <option value="monthly" <?php selected( isset( $settings['digest_period'] ) ? $settings['digest_period'] : '', 'monthly' ); ?>><?php _e( 'Monatlich', 'email-newsletter' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e( 'Wochentag', 'email-newsletter' ); ?></th>
                    <td><input type="number" min="1" max="7" name="campaign[weekday]" value="<?php echo esc_attr( isset( $settings['weekday'] ) ? intval( $settings['weekday'] ) : 1 ); ?>" style="width:90px;" /> <span class="description">1=Montag ... 7=Sonntag</span></td>
                </tr>
                <tr>
                    <th><?php _e( 'Monatstag', 'email-newsletter' ); ?></th>
                    <td><input type="number" min="1" max="31" name="campaign[month_day]" value="<?php echo esc_attr( isset( $settings['month_day'] ) ? intval( $settings['month_day'] ) : 1 ); ?>" style="width:90px;" /></td>
                </tr>
                <tr>
                    <th><?php _e( 'Uhrzeit', 'email-newsletter' ); ?></th>
                    <td>
                        <input type="number" min="0" max="23" name="campaign[send_hour]" value="<?php echo esc_attr( isset( $settings['send_hour'] ) ? intval( $settings['send_hour'] ) : 9 ); ?>" style="width:90px;" /> :
                        <input type="number" min="0" max="59" name="campaign[send_minute]" value="<?php echo esc_attr( isset( $settings['send_minute'] ) ? intval( $settings['send_minute'] ) : 0 ); ?>" style="width:90px;" />
                    </td>
                </tr>
            <?php endif; ?>
        </table>

        <p class="submit">
            <button type="submit" class="button button-primary"><?php _e( 'Speichern', 'email-newsletter' ); ?></button>
            <a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'newsletters-campaigns' ), admin_url( 'admin.php' ) ) ); ?>"><?php _e( 'Zurück', 'email-newsletter' ); ?></a>
        </p>
    </form>
</div>
