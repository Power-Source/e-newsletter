<?php
	global $email_builder;

    $arg['limit'] = 'LIMIT 0,5';
    $arg['orderby'] = 'newsletter_id';
    $arg['order'] = 'desc';
    $newsletters = $this->get_newsletters($arg);

    $arg = array();
    $arg['limit'] = 'LIMIT 0,5';
    $arg['orderby'] = 'join_date';
    $arg['order'] = 'desc';
    $members = $this->get_members( $arg );


    //Display status message
    if ( isset( $_GET['updated'] ) ) {
        ?><div id="message" class="updated fade"><p><?php echo esc_html( urldecode( $_GET['message'] ) ); ?></p></div><?php
    }
?>

    <div class="wrap">
        <h2><?php _e( 'Newsletters Dashboard', 'email-newsletter' ) ?></h2>
		<p><?php _e( 'Take a look at newsletter reports.', 'email-newsletter' ) ?></p>

		<h3><?php _e( 'Statistiken für aktuelle Mitglieder:', 'email-newsletter' ) ?></h3>
        <table class="widefat post table_slim table_centered">
            <thead>
                <tr>
                    <th>
                        <?php _e( 'Newsletter', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Mitglieder', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Gruppen', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Gesendet', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Geöffnet', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Unzustellbar', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Wartend', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'CRON', 'email-newsletter' ) ?>
                        (<?php echo wp_next_scheduled( $this->cron_send_name ) ? __( 'enabled', 'email-newsletter') : __( 'disabled', 'email-newsletter'); ?>)

                    </th>
                </tr>
            </thead>

            <tr class="alternate">
                <?php $stats = $this->get_count_stats(); ?>
                <td>
                    <?php echo $this->get_newsletters("", 1); ?>
                </td>
                <td>
                    <?php echo $this->get_count_members(); ?>
                </td>
                <td>
                    <?php echo $this->get_count_groups(); ?>
                </td>
                <td>
                    <?php echo $stats['sent']; ?>
                </td>
                <td>
                    <?php echo $stats['opened']; ?>
                </td>
                <td>
                    <?php echo $stats['bounced']; ?>
                </td>
                <td>
                    <?php echo $this->get_count_send_members( '', 'waiting_send' ); ?>
                </td>
                <td>
                    <?php echo $this->get_count_send_members( '', 'by_cron' ); ?>
                </td>
            </tr>
        </table>

        <?php
        if ( $newsletters ) {
        ?>
        <h3><?php _e( '5 Neuesten Newsletter:', 'email-newsletter' ) ?></h3>
        <table class="widefat post newsletter_table_center">
            <thead>
                <tr>
                    <th>
                        <?php _e( 'Erstellungsdatum', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( '-Mail-Betreff', 'email-newsletter' ) ?>
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
                    <th class="newsletters_actions">
                        <?php _e( 'Aktionen', 'email-newsletter' ) ?>
                    </th>
                </tr>
            </thead>
        <?php
            $i = 0;

            foreach( $newsletters as $newsletter ) {
                if ( $i % 2 == 0 )
                    echo "<tr class='alternate'>";
                else
                    echo "<tr class='' >";

                $i++;
        ?>
                <td style="text-align: left;">
                    <?php echo get_date_from_gmt(date('Y-m-d H:i:s', $newsletter['create_date'])); ?>
                </td>        
                <td style="text-align: left;">
                    <?php echo $newsletter['subject']; ?>
                </td>

                <td>
                     <?php echo $newsletter['count_sent']; ?> <?php _e( 'Mitglieder', 'email-newsletter' ) ?>
                </td>
                <td>
                     <?php echo $newsletter['count_opened']; ?> <?php _e( 'Mitglieder', 'email-newsletter' ) ?>
                </td>
                <td>
                     <?php echo $newsletter['count_bounced']; ?> <?php _e( 'Mitglieder', 'email-newsletter' ) ?>
                </td>
                <td style="width: 180px;">
                    <a href="?page=newsletters-dashboard&amp;newsletter_action=delete_newsletter&amp;newsletter_id=<?php echo $newsletter['newsletter_id'];?>">
                        <input class="button button-secondary" type="button" value="<?php _e( 'Löschen', 'email-newsletter' ) ?>" />
                    </a>
                    <a href="<?php echo $email_builder->generate_builder_link($newsletter['newsletter_id']); ?>">
                        <input class="button button-secondary" type="button" value="<?php _e( 'Bearbeiten', 'email-newsletter' ) ?>" />
                    </a>
                    <a href="?page=newsletters-dashboard&amp;newsletter_action=send_newsletter&amp;newsletter_id=<?php echo $newsletter['newsletter_id'];?>">
                        <input class="button button-primary" type="button" value="<?php _e( 'Senden', 'email-newsletter' ) ?>" />
                    </a>
                </td>
            </tr>
        <?php
            }
        ?>

        </table>
        <?php
        }

        if(current_user_can('create_newsletter')) { ?>
		<p class="submit">
            <a class="button button-primary" href="<?php echo admin_url( 'admin.php?newsletter_builder_action=create_newsletter' ); ?>"><?php _e( 'Neuen Newsletter erstellen', 'email-newsletter' ) ?></a>
        </p>
        <?php } ?>

        <h3><?php _e( '5 Neueste Mitglieder:', 'email-newsletter' ) ?></h3>
           <table id="members_table" class="widefat post">
                <thead>
                    <tr>
                        <th class="members-wp manage-column column-name">
                            <?php _e( 'WP ID', 'email-newsletter' ) ?>
                        </th>
                        <th class="members-email manage-column column-name">

                                <span><?php _e( 'E-Mail Adresse', 'email-newsletter' ) ?>   </span>

                        </th>
                        <th class="members-name manage-column column-name">

                                <span><?php _e( 'Name', 'email-newsletter' ) ?>   </span>

                        </th>
                        <th class="members-join manage-column column-name">

                                <span><?php _e( 'Beitrittsdatum', 'email-newsletter' ) ?>   </span>

                        </th>
                        <th class="members-count manage-column column-name">

                                <span><?php _e( 'Gesendet', 'email-newsletter' ) ?>   </span>

                        </th>
                        <th class="members-count manage-column column-name">

                                <span><?php _e( 'Geöffnet', 'email-newsletter' ) ?>   </span>

                        </th>
                        <th class="members-count manage-column column-name">

                                <span><?php _e( 'Unzustellbar', 'email-newsletter' ) ?></span>

                        </th>
                        <th class="members-groups manage-column column-name">
                            <?php _e( 'Gruppen', 'email-newsletter' ) ?>
                        </th>
                    </tr>
                </thead>
            <?php
            $i = 0;
            if ( $members )
                foreach( $members as $member ) {
                    if ( $i % 2 == 0 )
                        echo "<tr class='alternate'>";
                    else
                        echo "<tr class='' >";

                    $i++;

                    $member['member_nicename'] = $member['member_fname'];
                    $member['member_nicename'] .= $member['member_lname'] ? ' ' . $member['member_lname'] : '';

            ?>
                    <td style="vertical-align: middle;">
                        <?php
                        if(current_user_can('edit_users') && $member['wp_user_id'])
                            echo '<a href="'.admin_url( 'user-edit.php?user_id='.$member['wp_user_id'] ).'">'.$member['wp_user_id'].'</a>';
                        else
                            echo $member['wp_user_id']
                        ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <span id="member_email_block_<?php echo $member['member_id'];?>">
                            <?php echo $member['member_email']; ?>
                        </span>
                    </td>
                    <td style="vertical-align: middle;">
                        <span id="member_nicename_block_<?php echo $member['member_id'];?>">
                            <?php echo $member['member_nicename']; ?>
                        </span>
                    </td>
                    <td style="vertical-align: middle;">
                        <?php echo get_date_from_gmt(date('Y-m-d H:i:s', $member['join_date'])); ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <?php echo $member['sent']; ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <?php echo $member['opened']; ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <?php echo $member['bounced']; ?>
                    </td>
                    <td style="vertical-align: middle;">
                    <?php
                        if ( "" != $member['unsubscribe_code'] ) {
                            $groups_id = $this->get_memeber_groups( $member['member_id'] );
                            if ( $groups_id ) {
                                $memeber_groups = "";
                                foreach ( $groups_id as $group_id) {
                                    $group  = $this->get_group_by_id( $group_id );
                                    if ( isset( $_REQUEST['group_id'] ) && $group_id == $_REQUEST['group_id'] )
                                        $memeber_groups .= '<span style="color: green;" >' . $group['group_name'] . '</span>, ';
                                    else {
                                        $memeber_groups .= $group['group_name'];
                                    }
                                }
                                echo substr( $memeber_groups, 0, strlen( $memeber_groups )-2 );
                            }
                        } else {
                            echo __( 'Deabonniert', 'email-newsletter' );
                        }
                    ?>
                    </td>
                </tr>
            <?php
                }
            ?>
            </table>

    </div><!--/wrap-->