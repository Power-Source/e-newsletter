<?php
    $siteurl = get_option( 'siteurl' );
    $check_key = wp_create_nonce('newsletter_send');

    $newsletter_data = $this->get_newsletter_data( $_REQUEST['newsletter_id'] );

    $groups = $this->get_groups();

    //Display status message
    if ( isset( $_GET['updated'] ) ) {
        ?><div id="message" class="updated fade"><p><?php echo urldecode( $_GET['message'] ); ?></p></div><?php
    }

?>
    <div class="wrap">
        <h2><?php _e( 'Newsletter senden:', 'email-newsletter' ) ?> "<?php echo htmlspecialchars( $newsletter_data['subject'] );?>" <a href="?page=newsletters&amp;newsletter_builder_action=edit_newsletter&amp;newsletter_id=<?php echo $newsletter_data['newsletter_id'];?>&amp;template=<?php echo $newsletter_data['template'];?>&amp;return=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="add-new-h2"><?php _e( 'Edit', 'email-newsletter' ) ?></a></h2>

        <p><?php _e( 'Auf dieser Seite kannst Du den Newsletter an die ausgewählten Gruppen senden.', 'email-newsletter' ) ?></p>

        <?php

        if ( isset( $_REQUEST['send_id'] ) && 0 < $_REQUEST['send_id'] ) {
            $send_id        = $_REQUEST['send_id'];
            $newsletter_id  = $_REQUEST['newsletter_id'];

            $count_send_members = $this->get_count_send_members( $send_id );
        ?>
            <center>
                <p>Der Newsletter wurde an <span id="count_sent">0</span> von <?php echo $count_send_members; ?> Mitgliedern gesendet</p>
                <div class="enewsletter_progressbar">
                    <div id="progressbar" style="position: relative; width: 100%; max-width: 400px; margin: 20px auto;">
                        <progress id="progressbar_value" value="0" max="100" style="width: 100%; height: 30px; border: 1px solid #ccc; border-radius: 4px;"></progress>
                        <span id="progressbar_text" style="position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); font-weight: bold; color: #333; pointer-events: none;">
                            <?php echo _e( "Senden", 'email-newsletter' ) ?>
                        </span>
                    </div>
                </div>
                <form method="post" action="" id="sending_form" >
                    <input type="hidden" name="newsletter_id" value="<?php echo $newsletter_id; ?>">
                    <input type="hidden" name="send_id" value="<?php echo $send_id; ?>">
                    <input type="hidden" name="action" value="send_newsletter">
                    <input type="hidden" name="cron" value="add_to_cron" />
					<p class="submit">
                    <input class="button button-secondary" type="button" id="send_pause" value="<?php echo _e( 'Pause', 'email-newsletter' ) ?>" />
                    <input class="button button-secondary" type="button" id="send_cron" value="<?php echo _e( 'Pause, und sende per WP-CRON', 'email-newsletter' ) ?>" />
                    <input class="button button-secondary" type="button" id="send_cancel" value="<?php echo _e( 'Zurück', 'email-newsletter' ) ?>" />
					</p>
                </form>
            </center>

            <script type="text/javascript">
                jQuery( document ).ready( function() {
                    var pause = 0;

                    jQuery( function() {
                        // Using native HTML5 progress element instead of jQuery UI progressbar
                        jQuery( "#progressbar_value" ).attr( 'value', 0 );
                    });

                    jQuery( '#send_cron' ).on('click', function () {
                        pause = 1;
                        jQuery( '#sending_form' ).submit();
                    });

                    jQuery( '#send_cancel' ).on('click', function () {
                        pause = 1;
                        window.location.href = "?page=<?php echo $_REQUEST['page']; ?>&newsletter_action=send_newsletter&newsletter_id=<?php echo $newsletter_id; ?>";
                    });

                    jQuery( '#send_pause' ).on('click', function () {
                        if ( 1 == pause ) {
                            pause = 0;
                            jQuery( "#progressbar_text" ).html( '<?php echo _e( 'Senden', 'email-newsletter' ) ?>' );
                            jQuery( this ).val( '<?php echo _e( "Pause", 'email-newsletter' ) ?>' );
                            jQuery( this ).send_email();
                        } else {
                            pause = 1;
                            jQuery( "#progressbar_text" ).html( '<?php echo _e( 'Pause', 'email-newsletter' ) ?>' );
                            jQuery( this ).val( '<?php echo _e( "Fortsetzen", 'email-newsletter' ) ?>' );
                        }

                    });

                    var count_email = <?php echo $count_send_members ; ?>;
//                    var step = Math.round( 100 / count_email + 0.4 )
                    var step = 100 / count_email
                    var send = 1;

                    jQuery.fn.send_email = function ( ) {
                        jQuery.ajax({
                            type: 'POST',
                            url: ajaxurl,
                            data: 'action=send_email_to_member&send_id=<?php echo $send_id ; ?>&check_key=<?php echo $_REQUEST["check_key"] ; ?>',
                            success: function( html ){
                                if ( 'ok' == html.trim() ) {

                                    jQuery( '#count_sent' ).html( send );

                                    value = step * send;

                                    // Update native HTML5 progress element
                                    jQuery( "#progressbar_value" ).attr( 'value', value );

                                    send++;
                                    if ( 1 != pause )
                                        jQuery( this ).send_email();


                                } else if ( 'end' == html.trim()) {
                                     jQuery( "#send_pause" ).hide();
                                     jQuery( "#send_cron" ).hide();
                                     jQuery( "#progressbar_text" ).html( '<?php echo _e( 'Fertig', 'email-newsletter' ) ?>' );
									 jQuery( "#send_cancel" ).val('finish');
                                     jQuery( "#progressbar_value" ).fadeOut();
                                } else {
                                    alert( html );
                                }
                            }
                        });
                    };

                    jQuery( this ).send_email();

                });

            </script>

        <?php
        } else {
        ?>

        <form action="" method="post" id="send_form">
            <input type="hidden" name="newsletter_id" value="<?php echo $newsletter_data["newsletter_id"];?>">
            <input type="hidden" name="cron" id="cron" value="">
            <input type="hidden" name="cron_time" id="cron_time" value="" />
            <input type="hidden" name="check_key" id="check_key" value="<?php echo $check_key; ?>">
            <input type="hidden" name="action" value="send">
            <table cellpadding="10" cellspacing="10" class="widefat post table_slim">
				<thead>
					<tr>
						<th>
							<?php _e( 'Wähle die Gruppen aus, an die Du die Nachricht senden möchtest:', 'email-newsletter' ) ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>
    						<p>
    							<label><input type="checkbox" name="all_members" value="1" /> <strong><?php _e( 'Alle aktiven Mitglieder', 'email-newsletter' ) ?></strong> (<?php echo $this->get_count_members();?>)</label>
    						</p>
                            <p>
    							<?php
    								$this->the_targets();
                                ?>
                            </p>
                            <p class="description">
                                <?php _e( 'Bitte beachte, dass E-Mails nicht an abgemeldete Benutzer gesendet werden.', 'email-newsletter' ) ?>
                            </p>
                            <p>
                                <label>
                                    <input type="checkbox" name="dont_send_duplicate" value="1" checked="checked" />
                                    <?php _e( "Sende nicht erneut an Personen, die diesen Newsletter bereits erhalten haben.", 'email-newsletter' ); ?>
                                </label>

                                <label>
                                    <input type="checkbox" name="send_to_bounced" value="1" />
                                    <?php _e( "Sende an zurückgeprallte Mitglieder.", 'email-newsletter' ); ?>
                                </label>
                            </p>
						</td>
					</tr>
					<tr>
						<td>
                            <p>
                                <input class="button button-primary" type="submit" name="send" value="<?php echo _e( 'Newsletter jetzt senden', 'email-newsletter' ) ?>" />
                                <input class="button button-secondary" type="button" name="send" id="add_cron" value="<?php echo _e( 'Im Hintergrund senden (per CRON)', 'email-newsletter' ) ?>" />
                                <span id="timestamp">
                                    <?php _e( "Senden:", 'email-newsletter' ); ?> <b><?php _e( "So schnell wie möglich.", 'email-newsletter' ); ?></b>
                                </span>
                                <a href="#edit_timestamp" class="edit-timestamp" style="display: inline;">Bearbeiten</a>
                            </p>
                                <div id="timestampdiv">
                                    <div class="timestamp-wrap">
                                        <?php
                                        global $wp_locale;

                                        $time_adj = current_time('timestamp');
                                        $cur_jj = gmdate( 'd', $time_adj );
                                        $cur_mm = gmdate( 'm', $time_adj );
                                        $cur_aa = gmdate( 'Y', $time_adj );
                                        $cur_hh = gmdate( 'H', $time_adj );
                                        $cur_mn = gmdate( 'i', $time_adj );

                                        $month = "";
                                        for ( $i = 1; $i < 13; $i = $i +1 ) {
                                            $monthnum = zeroise($i, 2);
                                            $month .= "\t\t\t" . '<option value="' . $monthnum . '"';
                                            if ( $i == $cur_mm )
                                                $month .= ' selected="selected"';
                                            /* translators: 1: month number (01, 02, etc.), 2: month abbreviation */
                                            $month .= '>' . sprintf( __( '%1$s-%2$s' ), $monthnum, $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) ) . "</option>\n";
                                        }
                                        ?>
                                        <select id="mm" name="mm">
                                            <?php echo $month; ?>
                                        </select>
                                        <input type="text" id="jj" name="jj" value="<?php echo $cur_jj; ?>" size="2" maxlength="2" autocomplete="off">,
                                        <input type="text" id="aa" name="aa" value="<?php echo $cur_aa; ?>" size="4" maxlength="4" autocomplete="off"> @
                                        <input type="text" id="hh" name="hh" value="<?php echo $cur_hh; ?>" size="2" maxlength="2" autocomplete="off"> :
                                        <input type="text" id="mn" name="mn" value="<?php echo $cur_mn; ?>" size="2" maxlength="2" autocomplete="off">
                                    </div>
                                    <p>
                                        <a href="#edit_timestamp" class="save-timestamp button"><?php _e( "Setzen", 'email-newsletter' ); ?></a>
                                        <a href="#edit_timestamp" class="cancel-timestamp"><?php _e( "Abbrechen/Zurücksetzen", 'email-newsletter' ); ?></a>
                                    </p>
                                </div>
                        </td>
                    </tr>
				</tbody>
            </table>

        </form>

        <?php
            $sends = $this->get_sends( $_REQUEST['newsletter_id'] );
            $total = array ( 'send' => 0, 'cron' => 0, 'sent' => 0, 'bounced' => 0, 'opened' => 0 );
        ?>

        <h3><?php _e( 'Vorheriges Senden:', 'email-newsletter' ) ?></h3>

        <table width="700px" class="widefat post" id="send_list" style="width:95%;">
            <thead>
                <tr>
                    <th>
                        <?php _e( 'Startdatum', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Warten auf Versand (manuell)', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Warten auf Versand (cron)', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Gesendet an', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Geöffnet', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Unzustellbar', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Aktionen', 'email-newsletter' ) ?>
                    </th>
                </tr>
            </thead>
        <?php
        $i = 0;
        if ( $sends ) {
		?>
			<tbody>
		<?php
            foreach( $sends as $send ) {
                if ( $i % 2 == 0 )
                    echo "<tr class='alternate'>";
                else
                    echo "<tr class='' >";

                $i++;
        ?>
                <td style="vertical-align: middle;">
                   <?php echo get_date_from_gmt(date('d.m.Y H:i:s', $send['start_time'])); ?>
                </td>
                <td style="vertical-align: middle;">
                    <?php
                        echo $send['count_send_members'];
                        $total['send'] += $send['count_send_members'];
                    ?>
                </td>
                <td style="vertical-align: middle;">
                    <?php
                        echo $send['count_send_cron'];
                        if(is_numeric($send['status']))
                            echo ' <small>('.get_date_from_gmt(date('d.m.Y H:i:s', $send['status'])).')</small>';
                        $total['cron'] += $send['count_send_cron'];
                    ?>
                </td>
                <td style="vertical-align: middle;">
                    <?php
                        echo $send['count_sent'];
                        $total['sent'] += $send['count_sent'];
                    ?>
                </td>
                <td style="vertical-align: middle;">
                    <?php
                        echo $send['count_opened'];
                        $total['opened'] += $send['count_opened'];
                    ?>
                </td>
                <td style="vertical-align: middle;">
                    <?php
                        echo $send['count_bounced'];
                        $total['bounced'] += $send['count_bounced'];
                    ?>
                </td>
                <td style="vertical-align: middle; width: 250px;">
                <?php
                    if ( 0 < $send['count_send_cron'] ) :
                ?>
                        <a href="?page=<?php echo $_REQUEST['page']; ?>&newsletter_action=send_newsletter&cron=remove_from_cron&newsletter_id=<?php echo $newsletter_data["newsletter_id"];?>&send_id=<?php echo $send['send_id'];?>">
                            <input class="button button-secondary" type="button" value="<?php echo _e( "Aus der CRON-Liste entfernen", 'email-newsletter' ) ?>" />
                        </a>
                <?php
                    endif;

                    if ( 0 < $send['count_send_members'] ) :
                ?>
                        <a href="?page=<?php echo $_REQUEST['page']; ?>&newsletter_action=send_newsletter&cron=add_to_cron&newsletter_id=<?php echo $newsletter_data["newsletter_id"];?>&send_id=<?php echo $send['send_id'];?>">
                            <input class="button button-secondary" type="button" value="<?php echo _e( "Zur CRON-Liste hinzufügen", 'email-newsletter' ) ?>" />
                        </a>
                        <a href="?page=<?php echo $_REQUEST['page']; ?>&newsletter_action=send_newsletter&newsletter_id=<?php echo $newsletter_data["newsletter_id"];?>&send_id=<?php echo $send['send_id'];?>&check_key=<?php echo $check_key; ?>">
                            <input class="button button-primary" type="button" value="<?php _e( 'Weiter mit dem Versand', 'email-newsletter' ) ?>" />
                        </a>
                <?php
                    endif;
                ?>
                </td>
            </tr>
        <?php
            }
		?>
			</tbody>
		<?php
		}
        ?>
            <thead>
                <tr>
                    <th>
                        <?php _e( 'Gesamt:', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php echo $total['send']; ?>
                    </th>
                    <th>
                       <?php echo $total['cron']; ?>
                    </th>
                    <th>
                        <?php echo $total['sent']; ?>
                    </th>
                    <th>
                        <?php echo $total['opened']; ?>
                    </th>
                    <th>
                        <?php echo $total['bounced']; ?>
                    </th>
                    <th>
                    </th>
                </tr>
            </thead>
        </table>

        <?php } ?>

    </div><!--/wrap-->