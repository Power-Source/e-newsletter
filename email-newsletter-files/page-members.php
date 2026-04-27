<?php
    global $wpdb;
    $arg = NULL;
    $groups = $this->get_groups();

    // Input validation for order parameter
    $allowed_order = array('asc', 'desc');
    if(isset( $_REQUEST['order'] ) && in_array($_REQUEST['order'], $allowed_order)) {
        $order = ($_REQUEST['order'] == 'asc') ? "desc" : "asc";
    } else {
        $order = "asc";
    }
    $args = array('order' => $order, 'orderby' => false);
    if(isset($_REQUEST['search_members']))
        $args['search_members'] = sanitize_text_field($_REQUEST['search_members']);

    $url_orginal = esc_url(add_query_arg( $args ));

    //Pagination option
    if ( isset( $_REQUEST['per_page'] ) )
        $per_page = absint($_REQUEST['per_page']);
    else
        $per_page = 15;

    // Whitelist validation for orderby parameter
    $allowed_orderby = array('member_email', 'member_fname', 'join_date', 'count_sent', 'count_bounced', 'count_opened', 'sent', 'bounced', 'opened');
    if ( isset( $_REQUEST['orderby'] ) && in_array($_REQUEST['orderby'], $allowed_orderby) )
        $arg['orderby'] = $_REQUEST['orderby'];

    if ( isset( $_REQUEST['order'] ) && in_array($_REQUEST['order'], $allowed_order) )
        $arg['order'] = $_REQUEST['order'];

    if(isset( $_REQUEST['search_members'] )) {
        $sql_search = '%'.sanitize_text_field($_REQUEST['search_members']).'%';
        $arg['where'] = $wpdb->prepare('member_fname LIKE %s OR member_lname LIKE %s OR member_email LIKE %s', $sql_search, $sql_search, $sql_search);
    }

    // Whitelist validation for filter parameter
    $allowed_filters = array('group', 'ungrouped', 'unsubscribed', 'bounced');
    if ( isset( $_REQUEST['filter'] ) && in_array($_REQUEST['filter'], $allowed_filters) ) {
        if ( "group" == $_REQUEST['filter'] ) {
            if ( isset($_REQUEST['group_id']) && absint($_REQUEST['group_id']) > 0 ) {
                $arg['inner_join'] = $this->tb_prefix.'enewsletter_member_group C ON (A.member_id = C.member_id)';
                $arg['where'] = $wpdb->prepare('group_id = %d', absint($_REQUEST['group_id']));
            }
        }
        elseif ( "ungrouped" == $_REQUEST['filter'] ) {
            $arg['left_join'] = $this->tb_prefix.'enewsletter_member_group C ON (A.member_id = C.member_id)';
            $arg['where'] = 'C.group_id IS NULL';
        }
        elseif("unsubscribed" == $_REQUEST['filter']) {
            $arg['where'] = "unsubscribe_code = ''";
        }
        elseif("bounced" == $_REQUEST['filter']) {
            $arg['where'] = "A.bounced > 0";
        }
    }

    $count = $this->get_members( $arg, 1 );

    $members_pagination = $this->get_pagination_data( $count, $per_page );
    if ( isset( $members_pagination['limit'] ) )
        $arg['limit'] = $members_pagination['limit'];

    $members = $this->get_members( $arg );


    $siteurl = get_option( 'siteurl' );

    //Display status message
    if ( isset( $_GET['updated'] ) ) {
        ?><div id="message" class="updated fade"><p><?php echo esc_html( urldecode( $_GET['message'] ) ); ?></p></div><?php
    }

?>

    <div class="wrap">
        <h2><?php _e( 'Abonnenten', 'email-newsletter' ) ?></h2>
        <p><?php _e( 'Auf dieser Seite kannst du deine Abonnenten verwalten.', 'email-newsletter' ) ?></p>
        <p><?php _e( 'Hinweis: Änderungen an Abonnenten werden nicht mit ClassicPress-Benutzern synchronisiert, aber umgekehrt.', 'email-newsletter' ) ?></p>

        <p class="slide">
            <?php if(current_user_can('add_newsletter_member') || current_user_can('import_newsletter_members')) { ?>
                <input type="button" class="button-secondary action" id="show_add_form" value="<?php _e( 'Zeige die Formulare für neuen Abonnenten / Import', 'email-newsletter' ) ?>" />
            <?php } ?>
            <input type="button" class="button-secondary action" id="show_add_form2" value="<?php _e( 'Zeige das Formular zum Exportieren von Abonnenten', 'email-newsletter' ) ?>" />
        </p>

        <div id="panel" class="panel">
            <form action="" method="post" name="add_new_member" id="add_new_member" enctype="multipart/form-data">
                <?php wp_nonce_field('enewsletter_admin_action', '_wpnonce'); ?>
                <input type="hidden" name="newsletter_action" id="newsletter_action2" value="" />
                <input type="hidden" name="members_import" id="members_import" value="" />
                <table cellspacing="10">
                    <tr>
                        <?php if(current_user_can('add_newsletter_member')) { ?>
                        <td valign="top">
                            <table class="create_member">
                                <tr>
                                    <td colspan="2">
                                        <h3><?php _e( 'Erstelle den neuen Abonnenten:', 'email-newsletter' ) ?></h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php _e( 'Abonnenten-E-Mail:', 'email-newsletter' ) ?><span class="required">*</span>
                                    </td>
                                    <td>
                                        <input type="text" name="member[email]" id="member_email" />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php _e( 'Vorname:', 'email-newsletter' ) ?>
                                    </td>
                                    <td>
                                        <input type="text" name="member[fname]" id="member_fname" />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php _e( 'Nachname:', 'email-newsletter' ) ?>
                                    </td>
                                    <td>
                                        <input type="text" name="member[lname]" id="member_lname" />
                                    </td>
                                </tr>

                                <?php if ( $groups ):?>
                                    <tr>
                                        <td>
                                            <?php _e( 'Abonnentengruppen:', 'email-newsletter' ) ?>
                                        </td>
                                        <td>
                                            <?php foreach( $groups as $group ) : ?>
                                                <input type="checkbox" name="member[groups_id][]" value="<?php echo $group['group_id'];?>" />
                                                <label for="member[groups_id][]">
                                                    <?php echo ( $group['public'] ) ? $group['group_name'] .' (öffentlich)' : $group['group_name']; ?>
                                                </label>
                                                <br />
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endif;?>

                                <tr>
                                    <td colspan="2">
                                        <p class="submit">
                                            <input class="button button-secondary" type="button" name="add_member" id="add_member" value="<?php _e( 'Abonnenten hinzufügen', 'email-newsletter' ) ?>" />
                                        </p>
                                    </td>
                                </tr>
                            </table>
                       </td>
                       <?php } ?>
                       <?php if(current_user_can('import_newsletter_members')) { ?>
                       <td valign="top">
                            <table class="import_members">
                                <tr>
                                    <td colspan="2">
                                        <h3><?php _e( 'Abonnenten importieren:', 'email-newsletter' ) ?></h3>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2">
                                       <span class="description"><?php _e( 'Hinweis: Die Datei sollte die folgenden Spalten enthalten: E-Mail (erforderlich), Vorname (nicht erforderlich), Nachname (nicht erforderlich). Ohne Überschriften.', 'email-newsletter' ) ?> <a href="<?php echo $this->plugin_url.'/email-newsletter-files/sample.csv'; ?>"><?php _e( 'Beispieldatei', 'email-newsletter' ) ?></a></span>
                                    </td>
                                </tr>
                                <tr id="import_file_line">
                                    <td>
                                        <?php _e( 'Von .csv Datei:', 'email-newsletter' ) ?><span class="required">*</span>
                                    </td>
                                    <td>
                                        <input type="file" name="import_members_file" id="import_members_file" />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php _e( 'Getrennt durch:', 'email-newsletter' ) ?>
                                    </td>
                                    <td>
                                        <select name="separ_sign">
                                            <option value="2" <?php echo ( isset( $_GET['separ_sign'] ) && 2 == $_GET['separ_sign'] ) ? 'selected': ''; ?> >
                                                <?php _e( 'Komma', 'email-newsletter' ) ?> (,)&nbsp;
                                            </option>
                                            <option value="1" <?php echo ( isset( $_GET['separ_sign'] ) && 1 == $_GET['separ_sign'] ) ? 'selected': ''; ?> >
                                                <?php _e( 'Semikolon', 'email-newsletter' ) ?> (;)&nbsp;
                                            </option>
                                        </select>
                                    </td>
                                </tr>

                                <?php if ( $groups ):?>
                                    <tr>
                                        <td>
                                            <?php _e( 'Einer Gruppe zuweisen:', 'email-newsletter' ) ?>
                                        </td>
                                        <td>
                                            <?php foreach( $groups as $group ) : ?>
                                                <input type="checkbox" name="import_groups_id[]" value="<?php echo $group['group_id'];?>" />
                                                <label for="import_groups_id[]">
                                                    <?php echo ( $group['public'] ) ? $group['group_name'] .' (öffentlich)' : $group['group_name']; ?>
                                                </label>
                                                <br />
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endif;?>
                                <tr>
                                    <td colspan="2">
                                        <p class="submit">
                                            <input class="button button-primary" type="button" name="import_members" id="import_members" value="<?php _e( 'Mitglieder importieren', 'email-newsletter' ) ?>" />
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <?php } ?>
                    </tr>
                </table>
            </form>
        </div>

        <div id="panel2" class="panel">
            <form action="" method="post" name="export_members" id="export_members" enctype="multipart/form-data">
                <input type="hidden" name="newsletter_action" id="newsletter_action3" value="export_members" />
                <table cellspacing="10">
                    <tr>
                        <td valign="top">
                            <table class="export_members">
                                <tr>
                                    <td colspan="2">
                                        <h3><?php _e( 'Mitglieder in Datei exportieren', 'email-newsletter' ) ?></h3>
                                    </td>
                                </tr>

                                <?php if ( $groups ):?>
                                    <tr>
                                        <td>
                                            <?php _e( 'Abonnementgruppen:', 'email-newsletter' ) ?>
                                        </td>
                                        <td>
                                            <input type="checkbox" name="groups_ungrouped" value="1" checked/>
                                            <label for="groups_id[]">
                                                <?php _e( 'Nicht gruppiert', 'email-newsletter' ) ?>
                                            </label>
                                            <br />
                                            <?php foreach( $groups as $group ) : ?>
                                                <input type="checkbox" name="groups_id[]" value="<?php echo $group['group_id'];?>" checked/>
                                                <label for="groups_id[]">
                                                    <?php echo ( $group['public'] ) ? $group['group_name'] .' (öffentlich)' : $group['group_name']; ?>
                                                </label>
                                                <br />
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endif;?>

                                <tr>
                                    <td>
                                        <?php _e( 'Getrennt durch:', 'email-newsletter' ) ?>
                                    </td>
                                    <td>
                                        <select name="separ_sign">
                                            <option value="2">
                                                <?php _e( 'Komma', 'email-newsletter' ) ?> (,)&nbsp;
                                            </option>
                                            <option value="1">
                                                <?php _e( 'Semikolon', 'email-newsletter' ) ?> (;)&nbsp;
                                            </option>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <td colspan="2">
                                        <p class="submit">
                                            <input class="button button-primary" type="submit" name="add_member" id="add_member" value="<?php _e( 'Mitglieder exportieren', 'email-newsletter' ) ?>" />
                                        </p>
                                    </td>
                                </tr>
                            </table>
                       </td>
                    </tr>
                </table>
            </form>
        </div>

        <form method="post" action="" name="form_members" id="form_members" >
            <?php wp_nonce_field('enewsletter_admin_action', '_wpnonce'); ?>
            <p style="float:left;">
                <?php $url = add_query_arg( array('filter' => false, 'group_id' => false), $url_orginal ); ?>
                <a class="button button-second" href="<?php echo $url; ?>"><?php _e( 'Alle anzeigen', 'email-newsletter' ); ?></a>
                <?php $url = add_query_arg( array('filter' => 'ungrouped', 'group_id' => false), $url_orginal ); ?>
                <a class="button button-second" href="<?php echo $url; ?>"><?php _e( 'Nicht gruppiert anzeigen', 'email-newsletter' ); ?></a>
                <?php $url = add_query_arg( array('filter' => 'bounced', 'group_id' => false), $url_orginal ); ?>
                <a class="button button-second" href="<?php echo $url; ?>"><?php _e( 'Zurückgewiesene anzeigen', 'email-newsletter' ); ?></a>
                <?php $url = add_query_arg( array('filter' => 'unsubscribed', 'group_id' => false), $url_orginal ); ?>
                <a class="button button-second" href="<?php echo $url; ?>"><?php _e( 'Abgemeldete anzeigen', 'email-newsletter' ); ?></a>
            </p>
            <p style="float:right;">
                <label class="screen-reader-text" for="post-search-input">Search Pages:</label>
                <input type="search" id="post-search-input" name="search_members" value="<?php if(isset( $_REQUEST['search_members'] )) echo esc_attr(sanitize_text_field($_REQUEST['search_members'])); ?>">
                <input type="submit" name="" id="search-submit" class="button" value="<?php _e( 'Mitglieder suchen', 'email-newsletter' ) ?>">
            </p>

            <input type="hidden" name="member_id" id="member_id" value="" />
            <input type="hidden" name="newsletter_action" id="newsletter_action" value="" />
            <input type="hidden" name="per_page" id="members_per_page" value="<?php echo $per_page; ?>" />
            <table id="members_table" class="widefat post">
                <thead>
                    <tr>
                        <th class="manage-column column-cb check-column" id="cb" scope="col">
                            <input type="checkbox">
                        </th>
                        <th class="members-wp manage-column column-name">
                            <?php _e( 'WP ID', 'email-newsletter' ) ?>
                        </th>
                        <th class="members-email manage-column column-name <?php echo (isset($arg['orderby']) && "member_email" == $arg['orderby']) ? 'sorted ' . $arg['order'] : 'sortable desc';?>">
                            <?php $url = add_query_arg( array('orderby' => 'member_email'), $url_orginal ); ?>
                            <a href="<?php echo $url; ?>">
                                <span><?php _e( 'Email Addresse', 'email-newsletter' ) ?>   </span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th class="members-name manage-column column-name <?php echo (isset($arg['orderby']) && "member_fname" == $arg['orderby']) ? 'sorted ' . $arg['order'] : 'sortable desc';?>">
                            <?php $url = add_query_arg( array('orderby' => 'member_fname'), $url_orginal ); ?>
                            <a href="<?php echo $url; ?>">
                                <span><?php _e( 'Name', 'email-newsletter' ) ?>   </span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th class="members-join manage-column column-name <?php echo (isset($arg['orderby']) && "join_date" == $arg['orderby']) ? 'sorted ' . $arg['order'] : 'sortable desc';?>">
                            <?php $url = add_query_arg( array('orderby' => 'join_date'), $url_orginal ); ?>
                            <a href="<?php echo $url; ?>">
                                <span><?php _e( 'Beitrittsdatum', 'email-newsletter' ) ?>   </span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th class="members-count manage-column column-name <?php echo (isset($arg['orderby']) && "sent" == $arg['orderby']) ? 'sorted ' . $arg['order'] : 'sortable desc';?>">
                            <?php $url = add_query_arg( array('orderby' => 'sent'), $url_orginal ); ?>
                            <a href="<?php echo $url; ?>">
                                <span><?php _e( 'Gesendet', 'email-newsletter' ) ?>   </span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th class="members-count manage-column column-name <?php echo (isset($arg['orderby']) && "opened" == $arg['orderby']) ? 'sorted ' . $arg['order'] : 'sortable desc';?>">
                            <?php $url = add_query_arg( array('orderby' => 'opened'), $url_orginal ); ?>
                            <a href="<?php echo $url; ?>">
                                <span><?php _e( 'Geöffnet', 'email-newsletter' ) ?>   </span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th class="members-count manage-column column-name <?php echo (isset($arg['orderby']) && "bounced" == $arg['orderby']) ? 'sorted ' . $arg['order'] : 'sortable desc';?>">
                            <?php $url = add_query_arg( array('orderby' => 'bounced'), $url_orginal ); ?>
                            <a href="<?php echo $url; ?>">
                                <span><?php _e( 'Zurückgewiesen', 'email-newsletter' ) ?></span>
                                <span class="sorting-indicator"></span>
                            </a>
                        </th>
                        <th class="members-groups manage-column column-name">
                            <?php _e( 'Gruppen', 'email-newsletter' ) ?>
                        </th>
                        <th class="members-actions manage-column column-name">
                            <?php _e( 'Aktionen', 'email-newsletter' ) ?>
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
                    <th class="check-column" scope="row">
                        <input type="checkbox" value="<?php echo $member['member_id'];?>" class="administrator" id="user_<?php echo $member['member_id'];?>" name="members_id[]">
                    </th>
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
                        <?php echo get_date_from_gmt(date('d.m.Y H:i:s', $member['join_date'])); ?>
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
                        $memeber_groups = "";
                        if ( "" == $member['unsubscribe_code'] ) {
                            $url = add_query_arg( array('filter' => 'unsubscribed', 'group_id' => false ), $url_orginal );
                            $memeber_groups .= '<a href="'.$url.'"><span class="red" >' . __( 'Deabonniert', 'email-newsletter' ) . '</span></a>, ';
                        }
                        $groups_id = $this->get_memeber_groups( $member['member_id'] );
                        if ( $groups_id ) {
                            foreach ( $groups_id as $group_id) {
                                $group  = $this->get_group_by_id( $group_id );
                                if ( isset( $_REQUEST['group_id'] ) && $group_id == $_REQUEST['group_id'] )
                                    $memeber_groups .= '<span style="color: green;" >' . $group['group_name'] . '</span>, ';
                                else {
                                    $url = add_query_arg( array('filter' => 'group', 'group_id' => $group['group_id']), $url_orginal );
                                    $memeber_groups .= '<a href="'.$url.'" >' . $group['group_name'] . '</a>, ';
                                }
                            }
                        }
                        echo substr( $memeber_groups, 0, strlen( $memeber_groups )-2 );
                    ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <span id="close_block_<?php echo $member['member_id'];?>"></span>

                        <div id="change_group_block_<?php echo $member['member_id'];?>"></div>
                        <?php if(current_user_can('add_members_group')) { ?>
                            <input class="button button-secondary" type="button" id="change_button_<?php echo $member['member_id'];?>" value="<?php _e( 'Gruppenwechsel', 'email-newsletter' ) ?>" onclick="jQuery(this).changeGroups( <?php echo $member['member_id'];?> );" />
                        <?php } ?>

                        <?php if(current_user_can('edit_newsletter_member')) { ?>
                            <input class="button button-secondary" type="button" id="edit_button_<?php echo $member['member_id'];?>" value="<?php _e( 'Bearbeiten', 'email-newsletter' ) ?>" onclick="jQuery(this).editMember( <?php echo $member['member_id'];?> );" />
                        <?php } ?>
                        <span id="save_block_<?php echo $member['member_id'];?>"></span>

                        <?php if(current_user_can('delete_newsletter_member')) { ?>
                            <input class="button button-secondary" type="button" value="<?php _e( 'Löschen', 'email-newsletter' ) ?>" onclick="jQuery(this).deleteMember( <?php echo $member['member_id'];?> );" />
                        <?php } ?>
                    </td>
                </tr>
            <?php
                }
                else
                    echo '<tr><td colspan="9">'.__( 'Keine Mitglieder gefunden.', 'email-newsletter' ).'</td></tr>'
            ?>
            </table>
            <div class="tablenav bottom">
                <div class="alignleft actions">
                    <select name="some_action" id="some_action">
                        <option selected="selected" value="-1"><?php _e( 'Massenaktionen', 'email-newsletter' ) ?></option>

                        <?php if ( $groups ): ?>
                        <option value="add_members_group"><?php _e( 'Tag/Liste hinzufügen', 'email-newsletter' ) ?></option>
                        <option value="delete_members_group"><?php _e( 'Tag/Liste entfernen', 'email-newsletter' ) ?></option>
                        <option value="move_members_group"><?php _e( 'Liste ersetzen', 'email-newsletter' ) ?></option>
                        <?php endif; ?>

                        <option value="set_members_status"><?php _e( 'Status setzen', 'email-newsletter' ) ?></option>

                        <option value="delete_members"><?php _e( 'Löschen', 'email-newsletter' ) ?></option>
                    </select>

                    <?php if ( $groups ): ?>
                    <select name="list_group_id" id="list_group_id" style="display: none;">
                        <option selected="selected" value="-1"> <?php _e( 'Gruppenliste', 'email-newsletter' ) ?> </option>
                        <?php foreach( $groups as $group ) : ?>
                            <option value="<?php echo $group['group_id'];?>">
                            <?php echo ( $group['public'] ) ? $group['group_name'] .' ('.__( 'Öffentlich', 'email-newsletter' ).')' : $group['group_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>

                    <select name="members_status" id="members_status" style="display: none;">
                        <option selected="selected" value="-1"><?php _e( 'Status wählen', 'email-newsletter' ) ?></option>
                        <option value="subscribed"><?php _e( 'Abonniert', 'email-newsletter' ) ?></option>
                        <option value="unsubscribed"><?php _e( 'Abbestellt', 'email-newsletter' ) ?></option>
                    </select>

                    <input type="button" value="<?php _e( 'Anwenden', 'email-newsletter' ) ?>" id="apply" class="button-secondary action" name="">
                </div>

                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php echo ( isset( $members_pagination ) ) ? $members_pagination['count'] : $count; ?> <?php _e( 'Mitglied(er)', 'email-newsletter' ) ?>
                        <?php _e( 'by', 'email-newsletter' ) ?>
                        <select name="per_page" id="per_page">
                            <option value="15" <?php echo ( 15 == $per_page ) ? 'selected' : ''; ?> >15</option>
                            <option value="30" <?php echo ( 30 == $per_page ) ? 'selected' : ''; ?> >30</option>
                            <option value="50" <?php echo ( 50 == $per_page ) ? 'selected' : ''; ?> >50</option>
                            <option value="100" <?php echo ( 100 == $per_page ) ? 'selected' : ''; ?> >100</option>
                            <option value="all" <?php echo ( 'all' == $per_page ) ? 'selected' : ''; ?> ><?php _e( 'Alle', 'email-newsletter' ) ?></option>
                        </select>
                        <?php _e( 'pro Seite.', 'email-newsletter' ) ?>
                    </span>

                    <?php
                    if ( isset( $members_pagination ) && is_array( $members_pagination ) ):

                        //count page count before and after current
                        $pagedisprange = 3;

                        $pagescount = ceil( $members_pagination['count'] / $per_page );

                        //start page number
                        $stpage = $members_pagination['cpage'] - $pagedisprange;
                        if ( $stpage < 1 )
                            $stpage = 1;

                        // end page number
                        $endpage = $members_pagination['cpage'] + $pagedisprange;
                        if ( $endpage > $pagescount )
                            $endpage=$pagescount;
                        ?>

                        <span class="pagination-links">
                        <?php
                            if ( $members_pagination['cpage'] > 1 ) {
                                // first
                                $url = add_query_arg( array('cpage' => 1, 'per_page' => $per_page ), $url_orginal );
                                echo '<a href="'.$url.'" title="' . __( 'Zur ersten Seite gehen', 'email-newsletter' ) . '" class="first-page" ><<</a> ';
                                $url = add_query_arg( array('cpage' => ( $members_pagination['cpage'] - 1 ), 'per_page' => $per_page ), $url_orginal );
                                echo '<a href="'.$url.'" title="' . __( 'Zur vorherigen Seite gehen', 'email-newsletter' ) . '" class="prev-page" ><</a> ';
                            }

                            if ( $stpage > 1)
                                echo '<span>...</span> ';

                            for ( $i = $stpage; $i <= $endpage; $i++ ) {
                                if ( $i == $members_pagination['cpage'] ) {
                                    echo '<span class="current" style="margin: 0px 7px 0px 3px;"><strong>' . $i . '</strong></span>';
                                } else {
                                    $url = add_query_arg( array('cpage' => $i, 'per_page' => $per_page ), $url_orginal );
                                    echo '<a href="'.$url.'">' . $i . '</a> ';
                                }
                            }

                            if ( $endpage < $pagescount )
                                echo '<span>...</span> ';

                            if ( $members_pagination['cpage'] < $pagescount ) {
                                // next
                                $url = add_query_arg( array('cpage' => ( $members_pagination['cpage'] + 1 ), 'per_page' => $per_page ), $url_orginal );
                                echo '<a href="'.$url.'" title="' . __( 'Zur nächsten Seite gehen', 'email-newsletter' ) . '" class="next-page" >></a> ';
                                // last
                                $url = add_query_arg( array('cpage' => $pagescount, 'per_page' => $per_page ), $url_orginal );
                                echo '<a href="'.$url.'" title="' . __( 'Zur letzten Seite gehen', 'email-newsletter' ) . '" class="last-page" >>></a> ';
                            }
                        ?>
                        </span>
                    <?php endif;?>
                </div>
            </div>
        </form>

    </div><!--/wrap-->