<?php
	global $email_builder;
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
    $allowed_orderby = array('newsletter_id', 'create_date', 'subject', 'template');
    if ( isset( $_REQUEST['orderby'] ) && in_array($_REQUEST['orderby'], $allowed_orderby) )
        $arg['orderby'] = $_REQUEST['orderby'];

    if ( isset( $_REQUEST['order'] ) && in_array($_REQUEST['order'], $allowed_order) )
        $arg['order'] = $_REQUEST['order'];

    $newsletters = $this->get_newsletters($arg);

    //Display status message
    if ( isset( $_GET['updated'] ) ) {
        ?><div id="message" class="updated fade"><p><?php echo esc_html( urldecode( $_GET['message'] ) ); ?></p></div><?php
    }

?>
    <div class="wrap">
        <h2>
        	<?php _e( 'Newsletters', 'email-newsletter' ) ?>
            <?php if(current_user_can('create_newsletter')) { ?>
        	<a href="<?php echo admin_url( 'admin.php?newsletter_builder_action=create_newsletter' ); ?>" class="add-new-h2"><?php _e('Create New','email-newsletter'); ?></a>
            <?php } ?>
        </h2>
        <p><?php _e( 'This page contains the list of all Newsletters.', 'email-newsletter' ) ?></p>
        <p class="description"><?php _e( 'Note: please store your custom themes in enewsletter-custom-themes folder located in wp-content/uploads(+/siteID/ if activated on a single blog of a multi-site install).', 'email-newsletter' ) ?></p>

        <?php
        global $email_builder;
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
                            <span><?php _e( 'Create Date', 'email-newsletter' ) ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th <?php echo (isset($arg['orderby']) && "subject" == $arg['orderby']) ? 'class="newsletter-subject sorted '. $arg['order'].'"' : 'class="newsletter-subject sortable desc"';?>>
                        <?php $url = add_query_arg( array('orderby' => 'subject'), $url_orginal ); ?>
                        <a href="<?php echo esc_url( $url ); ?>">
                            <span><?php _e( 'Email Subject', 'email-newsletter' ) ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th <?php echo (isset($arg['orderby']) && "template" == $arg['orderby']) ? 'class="newsletter-template sorted '. $arg['order'].'"' : 'class="newsletter-template sortable desc"';?>>
                        <?php $url = add_query_arg( array('orderby' => 'template'), $url_orginal ); ?>
                        <a href="<?php echo esc_url( $url ); ?>">
                            <span><?php _e( 'Template', 'email-newsletter' ) ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th>
                        <span><?php _e( 'Sent To', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <?php _e( 'Opened', 'email-newsletter' ) ?>
                    </th>
                    <th>
                        <span><?php _e( 'Bounced', 'email-newsletter' ) ?>
                    </th>
                    <th class="newsletters_actions">
                        <?php _e( 'Actions', 'email-newsletter' ) ?>
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
                        <?php echo get_date_from_gmt(date('Y-m-d H:i:s', $newsletter['create_date'])); ?>
                    </td>
                    <td>
                        <?php echo $newsletter['subject']; ?>
                    </td>
                    <td>
                        <?php echo $newsletter['template']; ?>
                    </td>
                    <td>
                        <?php echo $newsletter['count_sent']; ?> <?php _e( 'members', 'email-newsletter' ) ?>
                    </td>
                    <td>
                        <?php echo $newsletter['count_opened']; ?> <?php _e( 'members', 'email-newsletter' ) ?>
                    </td>
                    <td>
                        <?php echo $newsletter['count_bounced']; ?> <?php _e( 'members', 'email-newsletter' ) ?>
                    </td>
                    <td>
                        <?php if(current_user_can('delete_newsletter')) { 
                            $delete_url = wp_nonce_url(
                                '?page=newsletters&newsletter_action=delete_newsletter&newsletter_id=' . $newsletter['newsletter_id'],
                                'enewsletter_admin_action'
                            );
                        ?>
                        <a class="deleteNewsletter button button-secondary" href="<?php echo esc_url($delete_url); ?>">
                            <?php _e( 'Delete', 'email-newsletter' ) ?>
                        </a>
                        <?php } ?>
                        <?php if(current_user_can('create_newsletter')) { 
                            $clone_url = wp_nonce_url(
                                '?page=newsletters&newsletter_action=clone_newsletter&newsletter_id=' . $newsletter['newsletter_id'],
                                'enewsletter_admin_action'
                            );
                        ?>
                        <a class="cloneNewsletter button button-secondary" href="<?php echo esc_url($clone_url); ?>">
                            <?php _e( 'Clone', 'email-newsletter' ) ?>
                        </a>
                        <?php } ?>
                        <?php if(current_user_can('save_newsletter')) { ?>
                        <a class="button button-secondary" href="?page=newsletters&amp;newsletter_builder_action=edit_newsletter&amp;newsletter_id=<?php echo $newsletter['newsletter_id'];?>&amp;template=<?php echo esc_attr($newsletter['template']);?>">
                            <?php _e( 'Edit', 'email-newsletter' ) ?>
                        </a>
                        <?php } ?>
                        <?php if(current_user_can('send_newsletter')) { 
                            $send_url = wp_nonce_url(
                                '?page=newsletters&newsletter_action=send_newsletter&newsletter_id=' . $newsletter['newsletter_id'],
                                'enewsletter_admin_action'
                            );
                        ?>
                        <a class="button button-primary"  href="<?php echo esc_url($send_url); ?>">
                            <?php _e( 'Send', 'email-newsletter' ) ?>
                        </a>
                        <?php } ?>
                    </td>
                </tr>
        <?php
                }
            else
                echo '<tr><td colspan="8">'.__( 'No newsletters found.', 'email-newsletter' ).'</td><td>';
        ?>
        </table>

    </div><!--/wrap-->