<?php
    $page_title =  __( 'PS-eNewsletter Einstellungen', 'email-newsletter' );

    if ( !$this->settings ) {
        $page_title =  __( 'PS-eNewsletter Plugin Installation', 'email-newsletter' );
        $mode = "install";
    }

    $default_tab = isset($mode) ? 'tabs-2' : 'tabs-1';

	global $email_newsletter;
	if (!class_exists('PSOURCE_HelpTooltips')) require_once $email_newsletter->plugin_dir . '/email-newsletter-files/class.wd_help_tooltips.php';
	$tips = new PSOURCE_HelpTooltips();
	$tips->set_icon_url($email_newsletter->plugin_url.'/email-newsletter-files/images/information.png');

    $cp_defender_status = $this->enews_cp_defender_get_status_snapshot();


    //Display status message
    if ( isset( $_GET['updated'] ) ) {
        ?><div id="message" class="updated fade"><p><?php echo esc_html( urldecode( isset( $_GET['message'] ) ? wp_unslash( $_GET['message'] ) : '' ) ); ?></p></div><?php
    }
?>


    <div class="wrap">
        <h2><?php echo $page_title; ?></h2>

        <form method="post" name="settings_form" id="settings_form" action="<?php echo admin_url( 'admin.php?page=newsletters-settings'); ?>">
            <?php wp_nonce_field('enewsletter_admin_action', '_wpnonce'); ?>
            <input type="hidden" name="newsletter_action" id="newsletter_action" value="" />
            <input type="hidden" name="newsletter_setting_page" id="newsletter_setting_page" value="#tabs-1" />
            <?php if(isset($mode)) echo '<input type="hidden" name="mode"  value="'.esc_attr($mode).'" />'; ?>

            <div class="newsletter-settings-tabs">

					<h3 id="newsletter-tabs" class="nav-tab-wrapper">
						<a href="#tabs-1" class="nav-tab nav-tab-active"><?php _e( 'Allgemeine Einstellungen', 'email-newsletter' ) ?></a>
						<a href="#tabs-2" class="nav-tab"><?php _e( 'Ausgehende E-Mail-Einstellungen', 'email-newsletter' ) ?></a>
						<a href="#tabs-3" class="nav-tab"><?php _e( 'Bounce-Einstellungen', 'email-newsletter' ) ?></a>
						<a href="#tabs-4" class="nav-tab"><?php _e( 'Benutzerberechtigungen', 'email-newsletter' ) ?></a>
						<a href="#tabs-5" class="nav-tab"><?php _e( 'Shortcodes', 'email-newsletter' ) ?></a>
                        <?php if ( ! isset( $mode ) || "install" != $mode ): ?>
                            <a class="nav-tab" href="#tabs-6"><?php _e( 'Uninstall', 'email-newsletter' ) ?></a>
						 <?php endif; ?>
					</h3>
                    <div id="tabs-1" class="tab">
						<h3><?php _e( 'Standard-Info-Einstellungen', 'email-newsletter' ) ?></h3>

						<table class="settings-form form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Absendername:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <input type="text" class="regular-text" name="settings[from_name]" value="<?php echo isset($this->settings['from_name']) ? esc_attr($this->settings['from_name']) : get_option( 'blogname' );?>" />
                                    <span class="description"><?php _e( 'Standard "Absender" Name beim Versenden von Newslettern.', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Branding:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <textarea name="settings[branding_html]" class="branding-html" ><?php echo isset($this->settings['branding_html']) ? esc_textarea($this->settings['branding_html']) : "";?></textarea>
                                    <br />
                                    <span class="description"><?php _e( 'Standard Branding HTML/Text wird oben in jede E-Mail eingefügt.', 'email-newsletter' ) ?> <?php _e( 'Es kann für jeden Newsletter einfach geändert werden', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Kontaktdatenrmation:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <textarea name="settings[contact_info]" class="contact-information" ><?php echo isset($this->settings['contact_info']) ? esc_textarea($this->settings['contact_info']) : "";?></textarea>
                                    <br />
                                    <span class="description"><?php _e( 'Standard Kontaktinformationen werden am Ende jeder E-Mail hinzugefügt.', 'email-newsletter' ) ?> <?php _e( 'Es kann für jeden Newsletter einfach geändert werden', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'E-Mail im Browser anzeigen:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <textarea name="settings[view_browser]" class="view-browser" ><?php echo isset($this->settings['view_browser']) ? esc_textarea($this->settings['view_browser']) : __( '<a href="{VIEW_LINK}" title="View e-mail in browser">E-Mail im Browser anzeigen</a>', 'email-newsletter' ); ?></textarea>
                                    <br />
                                    <span class="description"><?php _e( 'Diese HTML-Nachricht wird angezeigt, bevor der Newsletter beginnt, damit der Benutzer die E-Mail im Browser anzeigen kann. Verwenden Sie "{VIEW_LINK}" als Link. Leer lassen, um zu deaktivieren.', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Vorschau-E-Mail:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <input type="text" class="regular-text" name="settings[preview_email]" value="<?php echo isset($this->settings['preview_email']) ? esc_attr($this->settings['preview_email']) : $this->settings['from_email'];?>" />
                                    <span class="description"><?php _e( 'Standard-E-Mail-Adresse zum Senden von Vorschauen.', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>
                        </table>

                        <h3><?php _e( 'Standardbenutzer Abonnement-/Abmeldeeinstellungen', 'email-newsletter' ) ?></h3>

                        <table class="settings-form form-table">
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Double Opt In:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <label for="settings[double_opt_in]"><?php _e( 'Aktivieren:', 'email-newsletter' ) ?></label>
                                    <input type="checkbox" name="settings[double_opt_in]" value="1" <?php checked('1',$this->settings['double_opt_in']); ?> />
                                    <label for="settings[double_opt_in]"><?php _e( 'Betreff:', 'email-newsletter' ) ?></label>
                                    <input type="text" class="regular-text" name="settings[double_opt_in_subject]" value="<?php echo (isset($this->settings['double_opt_in_subject']) && !empty($this->settings['double_opt_in_subject'])) ? esc_attr($this->settings['double_opt_in_subject']) : __( 'Bitte bestätige Deine E-Mail-Adresse.', 'email-newsletter' ).' ('.get_bloginfo('name').')'; ?>" />
                                    <span class="description"><?php _e( 'Ist diese Option aktiviert, erhalten Mitglieder eine Bestätigungs-E-Mail mit dem konfigurierten Betreff, um Newsletter zu abonnieren (nur für nicht registrierte Benutzer).', 'email-newsletter' ) ?>. <?php _e( 'Bitte lasse das Betreff-Feld nicht leer.', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Standardgruppen:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <?php
                                    $groups = !isset($mode) ? $this->get_groups() : 0;

                                    if ( $groups ) {
                                        $this->settings['subscribe_groups'] = isset($this->settings['subscribe_groups']) ? explode(',', $this->settings['subscribe_groups']) : array();
                                    ?>
                                        <?php foreach( $groups as $group ) : ?>
                                            <label for="member[groups_id][]">
                                                <input type="checkbox" name="settings[subscribe_groups][<?php echo $group['group_id'];?>]" value="<?php echo $group['group_id'];?>" <?php if(in_array($group['group_id'], $this->settings['subscribe_groups'])) echo 'checked'; ?>/>
                                                <?php echo ( $group['public'] ) ? $group['group_name'] .' (public)' : $group['group_name']; ?>
                                            </label>
                                            <br />
                                        <?php endforeach; ?>
                                    <?php
                                    }
                                    else {
                                    ?>
                                        <p><?php _e( 'Du hast noch keine Mitgliedergruppen erstellt.', 'email-newsletter' ); ?></p>
                                    <?php
                                    }
                                    ?>
                                    <span class="description"><?php _e( 'Standardgruppen, denen Benutzer nach der Anmeldung hinzugefügt werden (auch wenn im Abonnement-Widget nichts ausgewählt ist).', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>

                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Willkommens-Newsletter:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <select name="settings[subscribe_newsletter]">
                                        <option value=""><?php _e( 'Disable', 'email-newsletter' ) ?></option>
                                        <?php
                                        $newsletters = (isset($mode) && $mode == 'install') ? 0 : $this->get_newsletters();

                                        if($newsletters)
                                            foreach( $newsletters as $key => $newsletter ) {
                                                if (strlen($newsletter['subject']) > 30)
                                                $newsletter['subject'] = substr($newsletter['subject'], 0, 27) . '...';
                                                echo '<option value="'.$newsletter['newsletter_id'].'" '.selected( $this->settings['subscribe_newsletter'], $newsletter['newsletter_id'], false).'>'.$newsletter['newsletter_id'].': '.$newsletter['subject'].'</option>';
                                            }
                                        ?>
                                    </select>
                                    <span class="description"><?php _e( 'Standard-Newsletter, der bei der Benutzeranmeldung versendet wird.', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>

                           <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'ClassicPress Benutzerregistrierung:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <?php
                                    if(!isset($this->settings['wp_user_register_subscribe']))
                                        $this->settings['wp_user_register_subscribe'] = 1;
                                    ?>
                                    <select name="settings[wp_user_register_subscribe]">
                                        <option value="1"<?php selected( $this->settings['wp_user_register_subscribe'], 1); ?>><?php _e( 'Subscribe', 'email-newsletter' ) ?></option>
                                        <option value="0"<?php selected( $this->settings['wp_user_register_subscribe'], 0); ?>><?php _e( 'Disable', 'email-newsletter' ) ?></option>
                                    </select>
                                    <span class="description"><?php _e( 'Wähle, ob Benutzer, die sich (mit ClassicPress) auf Deiner Webseite registrieren, automatisch für den Newsletter angemeldet werden.', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>

                           <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'CP Defender Delegation:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="settings[cp_defender_delegate_enabled]" value="1" <?php checked( '1', isset( $this->settings['cp_defender_delegate_enabled'] ) ? (string) $this->settings['cp_defender_delegate_enabled'] : '0' ); ?> />
                                        <?php _e( 'Anmeldeschutz an PS Security delegieren', 'email-newsletter' ) ?>
                                    </label>
                                    <p class="description">
                                        <?php _e( 'Nutzt, wenn verfügbar, IP-Lockout, Wegwerf-E-Mail-Prüfung, Pattern-Block und IP-Reputation aus PS Security.', 'email-newsletter' ) ?>
                                    </p>

                                    <div class="enews-cpdefender-status <?php echo $cp_defender_status['available'] ? 'is-ready' : 'is-missing'; ?>">
                                        <div class="enews-cpdefender-status-head">
                                            <strong><?php _e( 'Schutzstatus', 'email-newsletter' ) ?>:</strong>
                                            <?php if ( ! $cp_defender_status['available'] ) : ?>
                                                <span class="enews-pill enews-pill-danger"><?php _e( 'PS Security nicht aktiv/erreichbar', 'email-newsletter' ) ?></span>
                                            <?php elseif ( ! $cp_defender_status['enabled'] ) : ?>
                                                <span class="enews-pill enews-pill-muted"><?php _e( 'Delegation deaktiviert', 'email-newsletter' ) ?></span>
                                            <?php else : ?>
                                                <span class="enews-pill enews-pill-ok"><?php _e( 'Delegation aktiv', 'email-newsletter' ) ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="enews-cpdefender-grid">
                                            <div class="enews-cpdefender-card">
                                                <span class="enews-cpdefender-label"><?php _e( 'Prüfungen gesamt', 'email-newsletter' ) ?></span>
                                                <span class="enews-cpdefender-value"><?php echo number_format_i18n( $cp_defender_status['checks_total'] ); ?></span>
                                            </div>
                                            <div class="enews-cpdefender-card">
                                                <span class="enews-cpdefender-label"><?php _e( 'Geblockt', 'email-newsletter' ) ?></span>
                                                <span class="enews-cpdefender-value"><?php echo number_format_i18n( $cp_defender_status['checks_blocked'] ); ?></span>
                                            </div>
                                            <div class="enews-cpdefender-card">
                                                <span class="enews-cpdefender-label"><?php _e( 'Erfolgsquote Schutz', 'email-newsletter' ) ?></span>
                                                <span class="enews-cpdefender-value"><?php echo esc_html( $cp_defender_status['success_rate'] ); ?>%</span>
                                            </div>
                                            <div class="enews-cpdefender-card enews-cpdefender-card-wide">
                                                <span class="enews-cpdefender-label"><?php _e( 'Letzter Block', 'email-newsletter' ) ?></span>
                                                <span class="enews-cpdefender-value-small">
                                                    <?php
                                                    if ( ! empty( $cp_defender_status['last_blocked_at'] ) ) {
                                                        echo esc_html( wp_date( 'd.m.Y H:i', $cp_defender_status['last_blocked_at'] ) );
                                                        if ( ! empty( $cp_defender_status['last_block_reason'] ) ) {
                                                            echo ' · ' . esc_html( $cp_defender_status['last_block_reason'] );
                                                        }
                                                        if ( ! empty( $cp_defender_status['last_block_ip'] ) ) {
                                                            echo ' · IP ' . esc_html( $cp_defender_status['last_block_ip'] );
                                                        }
                                                    } else {
                                                        _e( 'Noch kein Block-Ereignis', 'email-newsletter' );
                                                    }
                                                    ?>
                                                </span>
                                            </div>
                                        </div>

                                        <div class="enews-cpdefender-timeline-wrap">
                                            <div class="enews-cpdefender-label" style="margin-bottom:6px;"><?php _e( 'Verlauf letzte 7 Tage (geblockt / erlaubt)', 'email-newsletter' ) ?></div>
                                            <div class="enews-cpdefender-timeline">
                                                <?php foreach ( (array) $cp_defender_status['history_last_7'] as $day_row ) : ?>
                                                    <?php
                                                    $allowed = isset( $day_row['allowed'] ) ? max( 0, intval( $day_row['allowed'] ) ) : 0;
                                                    $blocked = isset( $day_row['blocked'] ) ? max( 0, intval( $day_row['blocked'] ) ) : 0;
                                                    $total = $allowed + $blocked;
                                                    $blocked_pct = $total > 0 ? round( ( $blocked / $total ) * 100 ) : 0;
                                                    ?>
                                                    <div class="enews-cpdefender-day">
                                                        <span class="enews-cpdefender-day-label"><?php echo esc_html( isset( $day_row['label'] ) ? $day_row['label'] : '' ); ?></span>
                                                        <span class="enews-cpdefender-day-bar" role="img" aria-label="<?php echo esc_attr( sprintf( __( '%1$s: %2$d geblockt, %3$d erlaubt', 'email-newsletter' ), isset( $day_row['label'] ) ? $day_row['label'] : '', $blocked, $allowed ) ); ?>">
                                                            <span class="enews-cpdefender-day-bar-blocked" style="height:<?php echo esc_attr( $blocked_pct ); ?>%;"></span>
                                                        </span>
                                                        <span class="enews-cpdefender-day-meta"><?php echo esc_html( $blocked ); ?> / <?php echo esc_html( $allowed ); ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <p class="description enews-cpdefender-features">
                                            <?php _e( 'Verfügbare Schutzmodule:', 'email-newsletter' ) ?>
                                            <?php echo $cp_defender_status['features']['ip_lockout'] ? 'IP-Lockout' : ''; ?>
                                            <?php echo $cp_defender_status['features']['disposable_email'] ? ( $cp_defender_status['features']['ip_lockout'] ? ', ' : '' ) . 'Wegwerf-Mail' : ''; ?>
                                            <?php echo $cp_defender_status['features']['pattern_blocking'] ? ( ( $cp_defender_status['features']['ip_lockout'] || $cp_defender_status['features']['disposable_email'] ) ? ', ' : '' ) . 'Pattern-Block' : ''; ?>
                                            <?php echo $cp_defender_status['features']['ip_reputation'] ? ( ( $cp_defender_status['features']['ip_lockout'] || $cp_defender_status['features']['disposable_email'] || $cp_defender_status['features']['pattern_blocking'] ) ? ', ' : '' ) . 'IP-Reputation' : ''; ?>
                                        </p>

                                        <p style="margin-top:10px;">
                                            <a
                                                class="button button-secondary enews-danger"
                                                href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'newsletters-settings', 'newsletter_action' => 'reset_cp_defender_metrics' ), admin_url( 'admin.php' ) ), 'enewsletter_admin_action' ) ); ?>"
                                                onclick="return confirm('<?php echo esc_js( __( 'Möchtest Du die CP Defender Schutzmetriken wirklich zurücksetzen?', 'email-newsletter' ) ); ?>');"
                                            >
                                                <?php _e( 'Schutzmetriken zurücksetzen', 'email-newsletter' ) ?>
                                            </a>
                                        </p>
                                    </div>
                                </td>
                            </tr>

                           <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'ID der Abonnierseite:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <input class="small-text" type="number" name="settings[subscribe_page_id]" value="<?php echo isset($this->settings['subscribe_page_id']) ? esc_attr($this->settings['subscribe_page_id']) : '';?>" />
                                    <span class="description"><?php _e( 'Füge die ID der Seite hinzu, die Du nach der Anmeldung des Benutzers anzeigen möchtest. Du kannst den Shortcode [enewsletter_subscribe_message] verwenden, um den Anmeldestatus anzuzeigen. Leer lassen, um zu deaktivieren.', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>

                           <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'ID der Abmeldeseite:', 'email-newsletter' ) ?>
                                </th>
                                <td>
                                    <input class="small-text" type="number" name="settings[unsubscribe_page_id]" value="<?php echo isset($this->settings['unsubscribe_page_id']) ? esc_attr($this->settings['unsubscribe_page_id']) : '';?>" />
                                    <span class="description"><?php _e( 'Füge die ID der Seite hinzu, die Du nach der Abmeldung des Benutzers anzeigen möchtest. Du kannst den Shortcode [enewsletter_unsubscribe_message] verwenden, um den Abmeldestatus anzuzeigen. Leer lassen, um zu deaktivieren.', 'email-newsletter' ) ?></span>
                                </td>
                            </tr>
                        </table>

                    </div>

                    <div id="tabs-2" class="tab">
                        <h3><?php _e( 'Ausgehende SMTP-E-Mail-Einstellungen', 'email-newsletter' ) ?></h3>
                        <table class="settings-form form-table">
                            <tbody>
                                <tr valign="top">
                                    <th scope="row">
                                        <?php echo _e( '-Mail-Versandmethode:', 'email-newsletter' ); ?>
                                    </th>
                                    <td>
                                        <label id="tip_smtp">
                                            <input type="radio" name="settings[outbound_type]" id="smtp_method" value="smtp" class="email_out_type" <?php echo (!isset($this->settings['outbound_type']) || $this->settings['outbound_type'] == 'smtp') ? 'checked="checked"' : '';?> /><?php echo _e( 'SMTP (empfohlen)', 'email-newsletter' );?>
                                        </label>

										<?php $tips->bind_tip(__("Die SMTP-Methode ermöglicht es Dir, Deinen SMTP-Server (oder Gmail, Yahoo, Hotmail usw.) zum Versenden von Newslettern und E-Mails zu nutzen. Sie ist in der Regel die beste Wahl, insbesondere wenn Dein Hosting-Anbieter Einschränkungen für den E-Mail-Versand hat und Du so vermeiden möchtest, als Spam-Absender auf eine Blacklist zu geraten.",'email-newsletter'), '#tip_smtp'); ?>

                                        <label id="tip_php">
                                            <input type="radio" name="settings[outbound_type]" value="mail" class="email_out_type" <?php echo (isset($this->settings['outbound_type']) && $this->settings['outbound_type'] == 'mail') ? 'checked="checked"' : '';?> /><?php echo _e( 'PHP mail', 'email-newsletter' );?>
                                        </label>
										<?php $tips->bind_tip(__( "Diese Methode verwendet PHP-Funktionen zum Versenden von Newslettern und E-Mails. Sei vorsichtig, da einige Hosts Einschränkungen für die Verwendung dieser Methode festlegen können. Wenn Du die Einstellungen Deines Servers nicht bearbeiten kannst, empfehlen wir die Verwendung der SMTP-Methode für optimale Ergebnisse!", 'email-newsletter' ), '#tip_php'); ?>

                                        <label id="tip_wpmail">
                                            <input type="radio" name="settings[outbound_type]" value="wpmail" class="email_out_type" <?php echo (isset($this->settings['outbound_type']) && $this->settings['outbound_type'] == 'wpmail') ? 'checked="checked"' : '';?> /><?php echo _e( 'CMS Mail', 'email-newsletter' );?>
                                        </label>
                                        <?php $tips->bind_tip(__( "Diese Methode nutzt die Standardfunktionen von ClassicPress für den E-Mail-Versand von Newslettern und E-Mails. Sie ermöglicht die Verwendung anderer Plugins zum Versenden von E-Mails, kann aber die Funktionsfähigkeit der Bounce-Prüfung beeinträchtigen.", 'email-newsletter' ), '#tip_wpmail'); ?>
 
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">
                                        <?php _e( 'Absender-E-Mail-Adresse:', 'email-newsletter' ) ?>
                                    </th>
                                    <td>
                                        <input type="text" id="smtp_from" class="regular-text" name="settings[from_email]" value="<?php $default_domain = parse_url(home_url()); echo esc_attr( (isset($this->settings['from_email']) && !empty($this->settings['from_email'])) ? $this->settings['from_email'] : 'newsletter@'.$default_domain['host'] );?>" />
                                        <span class="description"><?php _e( 'Standardmäßige Absender-E-Mail-Adresse beim Versenden von Newslettern.', 'email-newsletter' ) ?></span><br/>
                                        <span class="red description"><?php _e( 'Hinweis: Für die SMTP-Methode - in "Absender-E-Mail" solltest Du nur E-Mails verwenden, die mit Deinem SMTP-Server verbunden sind!', 'email-newsletter' ) ?></span><br/>
                                        <span class="red description"><?php _e( 'Hinweis2: Für die PHP-Mail-Methode - in "Absender-E-Mail" solltest Du nur E-Mails mit einer Domain verwenden, die für Deinen Server konfiguriert ist!', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">
                                        <?php _e( 'Return-Path (optional):', 'email-newsletter' ) ?>
                                    </th>
                                    <td>
                                        <input type="email" id="return_path" class="regular-text" name="settings[return_path]" value="<?php echo isset($this->settings['return_path']) ? esc_attr($this->settings['return_path']) : ''; ?>" />
                                        <span class="description"><?php _e( 'Optionaler Envelope-Sender für Zustellung/Bounces. Leer lassen für Standardverhalten.', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">
                                        <?php _e( 'Reply-To (optional):', 'email-newsletter' ) ?>
                                    </th>
                                    <td>
                                        <input type="email" id="reply_to" class="regular-text" name="settings[reply_to]" value="<?php echo isset($this->settings['reply_to']) ? esc_attr($this->settings['reply_to']) : ''; ?>" />
                                        <span class="description"><?php _e( 'Antwortadresse für Empfänger-Antworten. Leer lassen, um die Absenderadresse zu nutzen.', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                            </tbody>

                            <tbody class="email_out email_out_smtp">
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'SMTP Outgoing Server', 'email-newsletter' ) ?>:</th>
                                    <td>
                                        <input type="text" id="smtp_host" class="regular-text" name="settings[smtp_host]" value="<?php echo isset($this->settings['smtp_host']) ? esc_attr($this->settings['smtp_host']) : '';?>" />
                                        <span class="description"><?php _e( 'Der Hostname für das SMTP-Konto, z.B.: mail.', 'email-newsletter' ) ?><?php echo $_SERVER['HTTP_HOST'];?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'SMTP Benutzername:', 'email-newsletter' ) ?></th>
                                    <td>
                                        <input type="text" id="smtp_username" class="regular-text" name="settings[smtp_user]" value="<?php echo isset($this->settings['smtp_user']) ? esc_attr($this->settings['smtp_user']) : '';?>" />
                                        <span class="description"><?php _e( '(Für keine Angabe leer lassen.)', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'SMTP Passwort:', 'email-newsletter' ) ?></th>
                                    <td>
                                        <input type="password" id="smtp_password" class="regular-text" name="settings[smtp_pass]" value="<?php echo ( isset( $this->settings['smtp_pass'] ) && '' != $this->settings['smtp_pass'] ) ? '********' : ''; ?>" />
                                        <span class="description"><?php _e( '(Für keine Angabe leer lassen.)', 'email-newsletter' ); if(isset( $this->settings['smtp_pass'] ) && '' != $this->settings['smtp_pass']) _e( ' (Aus Sicherheitsgründen stimmt die gespeicherte Passwortlänge nicht mit der Vorschau überein)', 'email-newsletter' ); ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'SMTP Port', 'email-newsletter' ) ?>:</th>
                                    <td>
                                        <input type="text" id="smtp_port" name="settings[smtp_port]" value="<?php echo isset($this->settings['smtp_port']) ? esc_attr($this->settings['smtp_port']) : '';?>" />
                                        <span class="description"><?php _e( 'Standardmäßig 25. Gmail verwendet 465 oder 587', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'Sicheres SMTP?', 'email-newsletter' ) ?>:</th>
                                    <td>
                                        <?php
                                        if(!isset($this->settings['smtp_secure_method']))
                                            $this->settings['smtp_secure_method'] = 0;
                                        ?>
                                        <select id="smtp_security" name="settings[smtp_secure_method]" >
                                            <option value="0" <?php selected('0',$this->settings['smtp_secure_method']); ?>><?php _e( 'Keine', 'email-newsletter' ) ?></option>
                                            <option value="ssl" <?php selected('ssl',$this->settings['smtp_secure_method']); ?>><?php _e( 'SSL', 'email-newsletter' ) ?></option>
                                            <option value="tls" <?php selected('tls',$this->settings['smtp_secure_method']); ?>><?php _e( 'TLS', 'email-newsletter' ) ?></option>
                                        </select>
                                        <span class="description"><?php _e( 'Wähle eine optionale Verbindungsart', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><div id="test_smtp_loading"></div></th>
                                    <td>
                                        <input class="button button-secondary" type="button" name="" id="test_smtp_conn" value="<?php _e( 'Verbindung testen', 'email-newsletter' ) ?>" />
                                        <span class="description"><?php _e( 'Wir senden eine Test-E-Mail an die konfigurierte Absenderadresse.', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <table class="settings-form form-table">
                            <h3><?php _e( 'CRON Email Sending Einstellungen', 'email-newsletter' ) ?></h3>
                            <tbody>
                                <tr valign="top">
                                    <th scope="row">
                                        <?php _e( 'CRON Email Sending:', 'email-newsletter' ) ?>
                                    </th>
                                    <td>
                                        <?php
                                        if(!isset($this->settings['cron_enable']))
                                            $this->settings['cron_enable'] = 1;
                                        ?>
                                        <select name="settings[cron_enable]" >
                                            <option value="1" <?php selected('1',esc_attr($this->settings['cron_enable'])); ?>><?php _e( 'Aktivieren', 'email-newsletter' ) ?></option>
                                            <option value="2" <?php selected('2',esc_attr($this->settings['cron_enable'])); ?>><?php _e( 'Deaktivieren', 'email-newsletter' ) ?></option>
                                        </select>
                                        <span class="description"><?php _e( "('Deaktivieren' - nicht CRON zum Senden von E-Mails verwenden)", 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">
                                        <?php _e( 'Begrenzungen:', 'email-newsletter' ) ?>
                                    </th>
                                    <td>
                                        <?php _e( 'Sende', 'email-newsletter' ) ?>
                                        <input class="small-text" type="number" name="settings[send_limit]" value="<?php echo isset($this->settings['send_limit']) ? esc_attr($this->settings['send_limit']) : '';?>" />
                                        <small class="description"><?php _e( '(0 oder leer für unbegrenzt)', 'email-newsletter' ) ?></small>
                                        <?php _e( 'E-Mails pro Rate-Limit-Zeitraum', 'email-newsletter' ) ?>
                                        <?php
                                        if(!isset($this->settings['cron_time']))
                                            $this->settings['cron_time'] = 1;
                                        ?>
                                        <select name="settings[cron_time]" >
                                            <option value="1" <?php echo ( 1 == $this->settings['cron_time'] ) ? 'selected="selected"' : ''; ?> ><?php _e( 'Stunde', 'email-newsletter' ) ?></option>
                                            <option value="2" <?php echo ( 2 == $this->settings['cron_time'] ) ? 'selected="selected"' : ''; ?> ><?php _e( 'Tag', 'email-newsletter' ) ?></option>
                                            <option value="3" <?php echo ( 3 == $this->settings['cron_time'] ) ? 'selected="selected"' : ''; ?> ><?php _e( 'Monat', 'email-newsletter' ) ?></option>
                                        </select>
                                        <small class="description"><?php _e( ' (Steuert die Versandbegrenzung, nicht die CRON-Ausführungsfrequenz)', 'email-newsletter' ) ?></small>
                                        <?php _e( 'und warte', 'email-newsletter' ) ?>
                                        <input class="small-text" type="number" name="settings[cron_wait]" value="<?php echo isset($this->settings['cron_wait']) ? esc_attr($this->settings['cron_wait']) : 1;?>" />
                                        <?php _e( 'Sekunde(n) zwischen jeder E-Mail', 'email-newsletter' ) ?>.
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'Versandfenster:', 'email-newsletter' ); ?></th>
                                    <td>
                                        <?php if ( ! isset( $this->settings['send_window_enabled'] ) ) { $this->settings['send_window_enabled'] = 0; } ?>
                                        <?php if ( ! isset( $this->settings['send_window_start'] ) ) { $this->settings['send_window_start'] = 0; } ?>
                                        <?php if ( ! isset( $this->settings['send_window_end'] ) ) { $this->settings['send_window_end'] = 0; } ?>

                                        <label>
                                            <input type="checkbox" name="settings[send_window_enabled]" value="1" <?php checked( '1', (string) $this->settings['send_window_enabled'] ); ?> />
                                            <?php _e( 'Nur in Zeitfenster senden', 'email-newsletter' ); ?>
                                        </label>
                                        <br />
                                        <span><?php _e( 'Von', 'email-newsletter' ); ?></span>
                                        <select name="settings[send_window_start]">
                                            <?php for ( $hour = 0; $hour <= 23; $hour++ ) : ?>
                                                <option value="<?php echo $hour; ?>" <?php selected( intval( $this->settings['send_window_start'] ), $hour ); ?>><?php echo sprintf( '%02d:00', $hour ); ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <span><?php _e( 'bis', 'email-newsletter' ); ?></span>
                                        <select name="settings[send_window_end]">
                                            <?php for ( $hour = 0; $hour <= 23; $hour++ ) : ?>
                                                <option value="<?php echo $hour; ?>" <?php selected( intval( $this->settings['send_window_end'] ), $hour ); ?>><?php echo sprintf( '%02d:00', $hour ); ?></option>
                                            <?php endfor; ?>
                                        </select>
                                        <span class="description"><?php _e( 'Wenn Start und Ende identisch sind, ist Versand jederzeit erlaubt. Auch Zeitfenster über Mitternacht werden unterstützt.', 'email-newsletter' ); ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'Debug-Logging', 'email-newsletter' ); ?></th>
                                    <td>
                                        <?php if ( ! isset( $this->settings['debug_enabled'] ) ) { $this->settings['debug_enabled'] = 0; } ?>
                                        <select name="settings[debug_enabled]">
                                            <option value="0" <?php selected( '0', esc_attr( $this->settings['debug_enabled'] ) ); ?>><?php _e( 'Deaktivieren', 'email-newsletter' ); ?></option>
                                            <option value="1" <?php selected( '1', esc_attr( $this->settings['debug_enabled'] ) ); ?>><?php _e( 'Aktivieren', 'email-newsletter' ); ?></option>
                                        </select>
                                        <span class="description"><?php _e( 'Schreibt strukturierte Ereignisse in die Log-Datei auf der Logs-Seite.', 'email-newsletter' ); ?></span>
                                    </td>
                                </tr>
							</tbody>
                        </table>
                    </div>

                    <div id="tabs-3" class="tab">
                        <h3><?php _e( 'Bounce Einstellungen', 'email-newsletter' ) ?></h3>
						<?php
						if(!function_exists('imap_open')) {
						?>

	                    <p><?php _e( 'Bitte aktiviere die "IMAP" PHP-Erweiterung, damit Bounce funktioniert.', 'email-newsletter' ) ?></p>

						<?php
						}
						else {
						?>
                        <p><?php _e( 'Dies steuert, wie Bounce-E-Mails vom System verarbeitet werden. Bitte erstelle ein neues separates POP3-E-Mail-Konto, um Bounce-E-Mails zu verarbeiten. Gib diese POP3-E-Mail-Details unten ein.', 'email-newsletter' ) ?></p>
                        <table class="settings-form form-table">
                            <tbody>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'Email Adresse:', 'email-newsletter' ) ?></td>
                                    <td>
                                        <input type="text" name="settings[bounce_email]" id="bounce_email" class="regular-text" value="<?php echo isset($this->settings['bounce_email']) ? esc_attr($this->settings['bounce_email']) : '';?>" />
                                        <span class="description"><?php _e( 'E-Mail-Adresse, an die standardmäßig Fehlermeldungen gesendet werden (kann vom Server überschrieben werden)', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'POP3 Host:', 'email-newsletter' ) ?></th>
                                    <td>
                                        <input type="text" name="settings[bounce_host]" id="bounce_host" class="regular-text" value="<?php echo isset($this->settings['bounce_host']) ? esc_attr($this->settings['bounce_host']) : '';?>" />
                                        <span class="description"><?php _e( 'Der Hostname für das POP3-Konto, z.B.: mail.', 'email-newsletter' ) ?><?php echo $_SERVER['HTTP_HOST'];?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'POP3 Port', 'email-newsletter' ) ?>:</th>
                                    <td>
                                        <input type="text" name="settings[bounce_port]" id="bounce_port" value="<?php echo isset($this->settings['bounce_port']) ? esc_attr($this->settings['bounce_port']) : '110';?>" size="2" />
                                        <span class="description"><?php _e( 'Standardmäßig 110 oder 995 bei aktivierter SSL', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'POP3 Benutzername:', 'email-newsletter' ) ?></th>
                                    <td>
                                        <input type="text" name="settings[bounce_username]" id="bounce_username" class="regular-text" value="<?php echo isset($this->settings['bounce_username']) ? esc_attr($this->settings['bounce_username']) : '';?>" />
                                        <span class="description"><?php _e( 'Benutzername für dieses Bounce-E-Mail-Konto (normalerweise derselbe wie die oben angegebene E-Mail-Adresse) ', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'POP3 Passwort:', 'email-newsletter' ) ?></th>
                                    <td>
                                        <input type="password" name="settings[bounce_password]" id="bounce_password" class="regular-text" value="<?php echo ( isset( $this->settings['bounce_password'] ) && '' != $this->settings['bounce_password'] ) ? '********' : ''; ?>" />
                                        <span class="description"><?php _e( 'Passwort zum Zugriff auf dieses Bounce-E-Mail-Konto', 'email-newsletter' ); if(isset( $this->settings['bounce_password'] ) && '' != $this->settings['bounce_password']) _e( ' (Aus Sicherheitsgründen stimmt die gespeicherte Passwortlänge nicht mit der Vorschau überein)', 'email-newsletter' ); ?></span>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row">
                                        <?php _e( 'Sicheres POP3?:', 'email-newsletter' );?>
                                    </th>
                                    <td>
                                        <?php
                                        if(!isset($this->settings['bounce_security']))
                                            $this->settings['bounce_security'] = '';
                                        ?>
                                        <select name="settings[bounce_security]" id="bounce_security" >
                                            <option value="" <?php echo ( '' == $this->settings['bounce_security'] ) ? 'selected="selected"' : ''; ?> ><?php _e( 'None', 'email-newsletter' ) ?></option>
                                            <option value="/ssl" <?php echo ( '/ssl' == $this->settings['bounce_security'] ) ? 'selected="selected"' : ''; ?> ><?php _e( 'SSL', 'email-newsletter' ) ?></option>
                                        </select>
                                    </td>
                                </tr>
                                <tr valign="top">
                                    <th scope="row"><div id="test_bounce_loading"></div></th>
                                    <td>
                                        <input class="button button-secondary" type="button" name="" id="test_bounce_conn" value="<?php _e( 'Verbindung testen', 'email-newsletter' ) ?>" />
                                        <span class="description"><?php _e( 'Wir senden eine Test-E-Mail an die Bounce-Adresse und versuchen, diese E-Mail zu lesen und anschließend zu löschen (dieser Teil ist möglicherweise nicht möglich)', 'email-newsletter' ) ?></span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
						<?php
						}
						?>
                    </div>
					<div id="tabs-4" class="tab">
						<?php global $wp_roles; ?>
						<h3><?php _e('Benutzerberechtigungen','email-newsletter'); ?></h3>
						<p><?php _e('Hier kannst du die gewünschten Berechtigungen für jede Benutzerrolle auf deiner Webseite festlegen','email-newsletter'); ?></p>
						<div class="metabox-holder" id="newsletter_user_permissions">
							<?php foreach($wp_roles->get_names() as $name => $label) : ?>
								<?php if($name == 'administrator') continue; ?>
								<?php $role_obj = get_role($name); ?>
								<div class="postbox">
									<h3 class="hndle"><span><?php echo $label; ?></span></h3>
									<div class="inside">
										<table class="widefat permissionTable">
											<thead>
												<tr valign="top">
													<th style="" class="manage-column column-cb check-column" scope="col"><input type="checkbox"></th>
													<th><?php _e('Berechtigung','email-newsletter'); ?></th>
												</tr>
											</thead>
											<tbody>
												<?php foreach($this->capabilities as $key => $label) : ?>
													<tr valign="top">
														<th class="check-column" scope="row">
															<input id="<?php echo $name.'_'.$key; ?>" type="checkbox" value="1" name="settings[email_caps][<?php echo $key; ?>][<?php echo $name; ?>]" <?php checked(isset($wp_roles->roles[$name]['capabilities'][$key]) ? $wp_roles->roles[$name]['capabilities'][$key] : '',true); ?> />
														</th>
														<th style="" class="manage-column column-<?php echo $key; ?>" id="<?php echo $key; ?>" scope="col">
															<label for="<?php echo $name.'_'.$key; ?>"><?php echo $label; ?></label>
														</th>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
                        <h3><?php _e( 'Gruppenberechtigungen', 'email-newsletter' ) ?></h3>
                        <table class="settings-form form-table">
                            <tbody>
                                <tr valign="top">
                                    <th scope="row"><?php _e( 'Öffentlicher Gruppen-Zugang:', 'email-newsletter' ) ?></td>
                                    <td>
                                        <?php
                                        if(!isset($this->settings['non_public_group_access']))
                                            $this->settings['non_public_group_access'] = 'registered';
                                        ?>
                                        <select id="non_public_group_access" name="settings[non_public_group_access]" >
                                            <option value="registered" <?php selected('registered',$this->settings['non_public_group_access']); ?>><?php _e( 'Registrierte Benutzer', 'email-newsletter' ) ?></option>
                                            <option value="nobody" <?php selected('nobody',$this->settings['non_public_group_access']); ?>><?php _e( 'Niemand', 'email-newsletter' ) ?></option>
                                        </select>
                                        <span class="description"><?php _e( 'Wähle aus, welche Benutzertypen nicht-öffentliche Gruppen abonnieren können. <small>Beachte, dass Benutzer weiterhin auf der Mitglieder-Administrationsseite des E-Newsletters zu allen Gruppentypen hinzugefügt werden können.</small>', 'email-newsletter' ) ?></span>
                                   </td>
                                </tr>
                            </tbody>
                        </table>
					</div>
                    <div id="tabs-5" class="tab">
                    <h3><?php _e( 'Shortcode Verwendung', 'email-newsletter' ) ?></h3>
                    <p><?php _e('Hier erfährst du, wie du e-Newsletter-Shortcodes zu deinen Beiträgen, Seiten und Theme-Vorlagen hinzufügen kannst.','email-newsletter'); ?></p>
                    <div class="shortcode-help">
                        <p><?php _e('Du kannst den folgenden Shortcode verwenden, um das Abonnementformular überall dort einzufügen, wo du es benötigst.'); ?></p>
                        <p><code>[enewsletter_subscribe]</code></p>
                        <p><?php _e('Der Shortcode hat 3 Parameter, die du anpassen kannst.'); ?></p>
                        <ul>
                            <li><strong>show_name</strong> <?php _e('aktiviert/deaktiviert das "Name"-Feld im Formular für Seitenbesucher.'); ?></li>
                            <li><strong>show_groups</strong> <?php _e('aktiviert/deaktiviert die Gruppenauswahl für Seitenbesucher.'); ?></li>
                            <li><strong>subscribe_to_groups</strong> <?php _e('meldet Benutzer automatisch bei den Gruppen mit den angegebenen IDs an.'); ?></li>
                        </ul>
                        <p><?php _e('Zum Beispiel würde der folgende Shortcode die Gruppenauswahl ausblenden, den Benutzer automatisch bei den Gruppen mit den angegebenen IDs anmelden und nach dem Namen des Besuchers fragen.'); ?>
                            <p><code>[enewsletter_subscribe show_name="1" show_groups="0" subscribe_to_groups="1,5"]</code></p>
                        <p><?php _e('Verwende den Shortcode, um das Abonnementformular zu jedem Beitrag oder Seiteninhalt hinzuzufügen oder sogar in benutzerdefinierten Seitenvorlagen mit der'); ?> <a href="https://developer.wordpress.org/reference/functions/do_shortcode/" target="_blank">do_shortcode-Funktion</a>.</p>
                        <p><?php _e('Verwende den folgenden Shortcode, um die <em>abonniert</em> Bestätigungsnachricht auf der Seite anzuzeigen, die in <strong>Allgemeine Einstellungen -> Abonnierte Seiten-ID</strong> definiert ist.'); ?></p>
                        <p><code>[enewsletter_subscribe_message]</code></p>
                        <p><?php _e('Verwende den folgenden Shortcode, um die <em>abgemeldet</em> Bestätigungsnachricht auf der Seite anzuzeigen, die in <strong>Allgemeine Einstellungen -> Abmelde-Seiten-ID</strong> definiert ist.'); ?></p>
                        <p><code>[enewsletter_unsubscribe_message]</code></p>
                    </div>
                    </div>
                    <?php if ( ! isset( $mode ) || "install" != $mode ): ?>
                    <div id="tabs-6" class="tab">
                        <h3><?php _e( 'Deinstallieren', 'email-newsletter' ) ?></h3>
                        <p><?php _e( 'Hier kannst du alle mit dem Plugin verbundenen Daten aus der Datenbank löschen.', 'email-newsletter' ) ?></p>
                        <p>
                            <input class="button button-secondary" type="button" name="uninstall" id="uninstall" value="<?php _e( 'Daten löschen', 'email-newsletter' ) ?>" />
                            <span class="description" style="color: red;"><?php _e( "Lösche alle Plugin-Daten aus der Datenbank.", 'email-newsletter' ) ?></span>
                            <div id="uninstall_confirm" style="display: none;">
								<p>
									<span class="description"><?php _e( 'Bist du sicher?', 'email-newsletter' ) ?></span>
									<br />
									<input class="button button-secondary" type="button" name="uninstall" id="uninstall_no" value="<?php _e( 'Nein', 'email-newsletter' ) ?>" />
									<input class="button button-secondary" type="button" name="uninstall" id="uninstall_yes" value="<?php _e( 'Ja', 'email-newsletter' ) ?>" />
								</p>
                            </div>
                        </p>
                    </div>
                    <?php endif; ?>

            </div><!--/.newsletter-tabs-settings-->

            <p class="submit">
            <?php if ( isset( $mode ) && "install" == $mode ) { ?>
                <input class="button button-primary" type="button" name="install" id="install" value="<?php _e( 'Installieren', 'email-newsletter' ) ?>" />
            <?php } else { ?>
                <input class="button button-primary" type="button" name="save" value="<?php _e( 'Alle Einstellungen speichern', 'email-newsletter' ) ?>" />
            <?php } ?>
			</p>

        </form>

    </div><!--/wrap-->