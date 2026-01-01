<?php
defined('ABSPATH') || exit;

/* @var $this NewsletterWpUsers */

include_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

if (!$controls->is_action()) {
    $controls->data = $this->options;
} else {
    if ($controls->is_action('save')) {
        unset($controls->data['align_wp_users_status']);
        $this->save_options($controls->data);
        $controls->add_toast_saved();
    }

    if ($controls->is_action('align_wp_users')) {
        ignore_user_abort(true);
        set_time_limit(0);

        $newsletter = Newsletter::instance();

        // TODO: check if the user is already there
        $wp_user_ids = $wpdb->get_results("select id from $wpdb->users where user_email<>''");
        $count = 0;
        foreach ($wp_user_ids as $wp_user_id) {
            $wp_user = new WP_User($wp_user_id->id);

            // A subscriber is already there with the same wp_user_id? Do Nothing.
            $nl_user = Newsletter::instance()->get_user_by_wp_user_id($wp_user->ID);
            if (!empty($nl_user)) {
                $data = ['id' => $nl_user->id];
                $data['name'] = $newsletter->sanitize_name(strval($wp_user->first_name));
                $data['surname'] = $newsletter->sanitize_name(strval($wp_user->last_name));
                Newsletter::instance()->save_user($data);
                continue;
            }

            $email = Newsletter::instance()->normalize_email($wp_user->user_email);

            if (!$email) {
                continue;
            }

            // A subscriber has the same email? Align them if not already associated to another WP user
            $nl_user = Newsletter::instance()->get_user($email);
            if (!empty($nl_user)) {
                Newsletter::instance()->set_user_wp_user_id($nl_user->id, $wp_user->ID);
                continue;
            }

            // Create a new subscriber
            $nl_user = [];
            $nl_user['email'] = $email;
            $nl_user['name'] = strval($wp_user->first_name);
            if (empty($nl_user['name'])) {
                $nl_user['name'] = $wp_user->user_login;
            }
            $nl_user['name'] = $newsletter->normalize_name($nl_user['name']);

            $nl_user['surname'] = $newsletter->normalize_name(strval($wp_user->last_name));
            $nl_user['status'] = $controls->data['align_wp_users_status'];
            $nl_user['wp_user_id'] = $wp_user->ID;
            $nl_user['referrer'] = 'wordpress';

            // Adds the force subscription preferences
            $lists = Newsletter::instance()->get_lists();
            foreach ($lists as $list) {
                if ($list->forced) {
                    $nl_user['list_' . $list->id] = 1;
                }
            }

            // Adds the selected lists for new registered users
            if (!empty($controls->data['lists'])) {
                foreach ($controls->data['lists'] as $p) {
                    $nl_user['list_' . $p] = 1;
                }
            }

            Newsletter::instance()->save_user($nl_user);
            $count++;
        }
        $controls->messages = count($wp_user_ids) . ' ' . __('WordPress users processed', 'newsletter') . '. ';
        $controls->messages .= $count . ' ' . __('subscriptions added', 'newsletter') . '.';
    }

    if ($controls->is_action('link')) {
        global $wpdb; // <-- Diese Zeile hinzufügen
        /* @var $wpdb wpdb */
        $res = $wpdb->query("update " . NEWSLETTER_USERS_TABLE . " n join " . $wpdb->users . " u on u.user_email=n.email set n.wp_user_id=u.id");
        if ($res === false) {
            $controls->errors = 'Database error: ' . $wpdb->last_error;
        } else {
            $controls->messages = $res . ' ' . __('subscribers linked', 'newsletter') . '.';
        }
    }
}
?>
<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php $controls->title_help('https://www.thenewsletterplugin.com/documentation/addons/extended-features/wpusers-extension/') ?>
        <h2>WP Users Integration</h2>
    </div>

    <div id="tnp-body">
        <?php $controls->show(); ?>

        <form method="post" action="">

            <?php $controls->init(); ?>

            <table class="form-table">
                <tr valign="top">
                    <th>Subscription on registration</th>
                    <td>
                        <?php $controls->select('subscribe', array(0 => 'No', 1 => 'Yes, force subscription', 2 => 'Yes, show the option', 3 => 'Yes, show the option already checked')); ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th><?php esc_html_e('Checkbox label', 'newsletter') ?></th>
                    <td>
                        <?php $controls->text('subscribe_label', 30); ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th><?php esc_html_e('Subscribe as', 'newsletter') ?></th>
                    <td>
                        <?php $controls->select('status', array('S' => 'Confirmation required', 'C' => 'Confirmed')); ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th>Send the confirmation email</th>
                    <td>
                        <?php $controls->yesno('confirmation'); ?>
                        <p class="description">Only if the subscription requires confirmation</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th>Confirm on login</th>
                    <td>
                        <?php $controls->yesno('login'); ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th><?php esc_html_e('Send welcome email', 'newsletter') ?></th>
                    <td>
                        <?php $controls->yesno('welcome'); ?>
                    </td>
                </tr>


                <tr valign="top">
                    <th>Lists</th>
                    <td>
                        <?php $controls->preferences_group('lists'); ?>
                        <p class="description">
                            Forcibly add the subscriber to those lists.
                        </p>
                    </td>
                </tr>

                <tr valign="top">
                    <th><?php esc_html_e('Subscription delete', 'newsletter') ?></th>
                    <td>
                        <?php $controls->yesno('delete'); ?>
                        <p class="description">Delete the subscription connected to a WordPress user when that user is deleted</p>
                    </td>
                </tr>
            </table>

            <p>
                <?php $controls->button_save(); ?>
            </p>

            <h3><?php esc_html_e('Import already registered users', 'newsletter') ?></h3>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Import with status', 'newsletter') ?></th>
                    <td>
                        <?php $controls->select('align_wp_users_status', array('C' => __('Confirmed', 'newsletter'), 'S' => __('Not confirmed', 'newsletter'))); ?>
                        <?php $controls->button_confirm('align_wp_users', __('Import', 'newsletter'), __('Proceed?', 'newsletter')); ?>
                        <p class="description">
                            <a href="http://www.thenewsletterplugin.com/plugins/newsletter/subscribers-module#import-wp-users" target="_blank">
                                <?php esc_html_e('Please, carefully read the documentation before taking this action!', 'newsletter') ?>
                            </a>
                        </p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e('Maintenance', 'newsletter') ?></h3>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Link subscribers with users by email', 'newsletter') ?></th>
                    <td>
                        <?php $controls->button_confirm('link', __('Link', 'newsletter'), __('Proceed?', 'newsletter')); ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php esc_html_e('Log level', 'newsletter') ?>
                    </th>
                    <td>
                        <?php $controls->log_level('log_level'); ?>
                    </td>
                </tr>
            </table>

        </form>
    </div>

</div>
