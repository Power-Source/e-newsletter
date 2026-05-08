<?php
    $arg['orderby'] = 'create_date';
    $arg['order'] = 'desc';

    // Input validation for order parameter
    $allowed_order = array('asc', 'desc');
    if(isset( $_REQUEST['order'] ) && in_array($_REQUEST['order'], $allowed_order)) {
        $order = ($_REQUEST['order'] == 'asc') ? "desc" : "asc";
    } else {
        $order = "asc";
    }
    $args = array('order' => $order, 'orderby' => false);

    $url_orginal = add_query_arg( $args );

    // Whitelist validation for orderby parameter
    $allowed_orderby = array('newsletter_id', 'create_date', 'subject');
    if ( isset( $_REQUEST['orderby'] ) && in_array($_REQUEST['orderby'], $allowed_orderby) )
        $arg['orderby'] = $_REQUEST['orderby'];

    if ( isset( $_REQUEST['order'] ) && in_array($_REQUEST['order'], $allowed_order) )
        $arg['order'] = $_REQUEST['order'];

    $newsletters = $this->get_newsletters($arg);

    $total_newsletters = is_array( $newsletters ) ? count( $newsletters ) : 0;
    $total_sent = 0;
    $total_opened = 0;
    $total_bounced = 0;
    $top_opened_count = -1;
    $top_opened_subject = '';

    if ( is_array( $newsletters ) ) {
        foreach ( $newsletters as $newsletter_stat ) {
            $sent = intval( isset( $newsletter_stat['count_sent'] ) ? $newsletter_stat['count_sent'] : 0 );
            $opened = intval( isset( $newsletter_stat['count_opened'] ) ? $newsletter_stat['count_opened'] : 0 );
            $bounced = intval( isset( $newsletter_stat['count_bounced'] ) ? $newsletter_stat['count_bounced'] : 0 );

            $total_sent += $sent;
            $total_opened += $opened;
            $total_bounced += $bounced;

            if ( $opened > $top_opened_count ) {
                $top_opened_count = $opened;
                $top_opened_subject = isset( $newsletter_stat['subject'] ) ? trim( $newsletter_stat['subject'] ) : '';
            }
        }
    }

    $open_rate = $total_sent > 0 ? round( ( $total_opened / $total_sent ) * 100, 1 ) : 0;
    if ( '' === $top_opened_subject ) {
        $top_opened_subject = __( '(Ohne Betreff)', 'email-newsletter' );
    }

    //Display status message
    if ( isset( $_GET['updated'] ) ) {
        ?><div id="message" class="updated fade"><p><?php echo esc_html( urldecode( $_GET['message'] ) ); ?></p></div><?php
    }

?>
    <div class="wrap">
        <h2>
        	<?php _e( 'Newsletter', 'email-newsletter' ) ?>
            <?php if(current_user_can('create_newsletter')) { ?>
	        <a href="<?php echo admin_url( 'admin.php?page=newsletters-builder-v2&create=1' ); ?>" class="add-new-h2"><?php _e('Erstelle Newsletter','email-newsletter'); ?></a>
            <?php } ?>
        </h2>
        <p><?php _e( 'Diese Seite enthält die Liste aller Newsletter.', 'email-newsletter' ) ?></p>
        <section class="enews-hero" aria-label="<?php esc_attr_e( 'Newsletter Gesamtübersicht', 'email-newsletter' ); ?>">
            <div class="enews-hero-head">
                <h3><?php _e( 'Gesamtübersicht', 'email-newsletter' ); ?></h3>
                <p class="description"><?php _e( 'Deine wichtigsten Newsletter-Kennzahlen auf einen Blick.', 'email-newsletter' ); ?></p>
            </div>
            <div class="enews-hero-grid">
                <article class="enews-hero-card">
                    <span class="enews-hero-label"><?php _e( 'Newsletter gesamt', 'email-newsletter' ); ?></span>
                    <strong class="enews-hero-value"><?php echo intval( $total_newsletters ); ?></strong>
                </article>
                <article class="enews-hero-card">
                    <span class="enews-hero-label"><?php _e( 'Gesendet', 'email-newsletter' ); ?></span>
                    <strong class="enews-hero-value"><?php echo intval( $total_sent ); ?></strong>
                </article>
                <article class="enews-hero-card">
                    <span class="enews-hero-label"><?php _e( 'Geöffnet', 'email-newsletter' ); ?></span>
                    <strong class="enews-hero-value"><?php echo intval( $total_opened ); ?></strong>
                </article>
                <article class="enews-hero-card">
                    <span class="enews-hero-label"><?php _e( 'Öffnungsrate', 'email-newsletter' ); ?></span>
                    <strong class="enews-hero-value"><?php echo esc_html( number_format_i18n( $open_rate, 1 ) ); ?>%</strong>
                </article>
                <article class="enews-hero-card">
                    <span class="enews-hero-label"><?php _e( 'Unzustellbar', 'email-newsletter' ); ?></span>
                    <strong class="enews-hero-value"><?php echo intval( $total_bounced ); ?></strong>
                </article>
                <article class="enews-hero-card enews-hero-card-wide">
                    <span class="enews-hero-label"><?php _e( 'Meistgelesener Newsletter', 'email-newsletter' ); ?></span>
                    <strong class="enews-hero-value enews-hero-value-title"><?php echo esc_html( $top_opened_subject ); ?></strong>
                    <span class="enews-hero-meta"><?php echo max( 0, intval( $top_opened_count ) ); ?> <?php _e( 'Öffnungen', 'email-newsletter' ); ?></span>
                </article>
            </div>
        </section>

        <?php
        $i = 0;
        $template_query = array();
        ?>
        <table id="newsletter_list" class="widefat post">
            <thead>
                <tr>
                    <th <?php echo (isset($arg['orderby']) && "newsletter_id" == $arg['orderby']) ? 'class="newsletter-id sorted '. $arg['order'].'"' : 'class="newsletter-id sortable desc"';?>>
                        <?php $url = add_query_arg( array('orderby' => 'newsletter_id'), $url_orginal ); ?>
                        <a href="<?php echo esc_url( $url ); ?>">
                            <span><?php _e( 'ID', 'email-newsletter' ) ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th <?php echo (isset($arg['orderby']) && "create_date" == $arg['orderby']) ? 'class="sorted '. $arg['order'].'"' : 'class="sortable desc"';?>>
                        <?php $url = add_query_arg( array('orderby' => 'create_date'), $url_orginal ); ?>
                        <a href="<?php echo esc_url( $url ); ?>">
                            <span><?php _e( 'Erstellungsdatum', 'email-newsletter' ) ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th <?php echo (isset($arg['orderby']) && "subject" == $arg['orderby']) ? 'class="newsletter-subject sorted '. $arg['order'].'"' : 'class="newsletter-subject sortable desc"';?>>
                        <?php $url = add_query_arg( array('orderby' => 'subject'), $url_orginal ); ?>
                        <a href="<?php echo esc_url( $url ); ?>">
                            <span><?php _e( 'Email Betreff', 'email-newsletter' ) ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th>
                        <span><?php _e( 'Gesendet an', 'email-newsletter' ) ?></span>
                    </th>
                    <th>
                        <span><?php _e( 'Geöffnet', 'email-newsletter' ) ?></span>
                    </th>
                    <th>
                        <span><?php _e( 'Unzustellbar', 'email-newsletter' ) ?></span>
                    </th>
                    <th class="newsletters_actions">
                        <span><?php _e( 'Aktionen', 'email-newsletter' ) ?></span>
                    </th>
                </tr>
            </thead>
            <?php
            if($newsletters)
                foreach( $newsletters as $key => $newsletter ) {
                	$template_id = $this->get_newsletter_meta($newsletter['newsletter_id'],'plugin_template_id');

    				if($template_id != false) {
    					$template_query[$template_id] = $newsletter;
    					unset($newsletters[$key]);
    					continue;
    				}

                    if ( $i % 2 == 0 )
                        echo "<tr class='alternate'>";
                    else
                        echo "<tr class='' >";

                    $i++;
                ?>
                    <td>
                        <?php echo $newsletter['newsletter_id']; ?>
                    </td>
                    <td>
                        <?php echo get_date_from_gmt(date('d.m.Y H:i:s', $newsletter['create_date'])); ?>
                    </td>
                    <td>
                        <?php echo $newsletter['subject']; ?>
                    </td>
                    <td>
                        <?php echo $newsletter['count_sent']; ?> <?php _e( 'Abonnenten', 'email-newsletter' ) ?>
                    </td>
                    <td>
                        <?php echo $newsletter['count_opened']; ?> <?php _e( 'Abonnenten', 'email-newsletter' ) ?>
                    </td>
                    <td>
                        <?php echo $newsletter['count_bounced']; ?> <?php _e( 'Abonnenten', 'email-newsletter' ) ?>
                    </td>
                    <td>
                        <?php if(current_user_can('delete_newsletter')) { 
                            $delete_url = wp_nonce_url(
                                '?page=newsletters&newsletter_action=delete_newsletter&newsletter_id=' . $newsletter['newsletter_id'],
                                'enewsletter_admin_action'
                            );
                        ?>
                        <a class="deleteNewsletter button button-secondary" href="<?php echo esc_url($delete_url); ?>">
                            <?php _e( 'Löschen', 'email-newsletter' ) ?>
                        </a>
                        <?php } ?>
                        <?php if(current_user_can('create_newsletter')) { 
                            $clone_url = wp_nonce_url(
                                '?page=newsletters&newsletter_action=clone_newsletter&newsletter_id=' . $newsletter['newsletter_id'],
                                'enewsletter_admin_action'
                            );
                        ?>
                        <a class="cloneNewsletter button button-secondary" href="<?php echo esc_url($clone_url); ?>">
                            <?php _e( 'Klonen', 'email-newsletter' ) ?>
                        </a>
                        <?php } ?>
                        <?php if(current_user_can('save_newsletter')) { ?>
                        <a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=newsletters-builder-v2&newsletter_id=' . intval( $newsletter['newsletter_id'] ) ) ); ?>">
                            <?php _e( 'Bearbeiten', 'email-newsletter' ) ?>
                        </a>
                        <a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=newsletters-versions&newsletter_id=' . intval( $newsletter['newsletter_id'] ) ) ); ?>">
                            <?php _e( 'Versionen', 'email-newsletter' ) ?>
                        </a>
                        <?php } ?>
                        <?php if(current_user_can('send_newsletter')) { 
                            $send_url = wp_nonce_url(
                                '?page=newsletters&newsletter_action=send_newsletter&newsletter_id=' . $newsletter['newsletter_id'],
                                'enewsletter_admin_action'
                            );
                        ?>
                        <a class="button button-primary"  href="<?php echo esc_url($send_url); ?>">
                            <?php _e( 'Senden', 'email-newsletter' ) ?>
                        </a>
                        <?php } ?>
                    </td>
                </tr>
        <?php
                }
            else
                echo '<tr><td colspan="8">'.__( 'Keine Newsletter gefunden.', 'email-newsletter' ).'</td></tr>';
        ?>
        </table>

    </div><!--/wrap-->