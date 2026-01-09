<?php
/* @var $this NewsletterEmails */
/* @var $controls NewsletterControls */
defined('ABSPATH') || exit;

global $wpdb;

function tnp_prepare_controls($email, $controls) {
    $controls->data = $email;

    foreach ($email['options'] as $name => $value) {
        $controls->data['options_' . $name] = $value;
    }
}

// Always required
$email = $this->get_email($_GET['id'], ARRAY_A);

if (empty($email)) {
    echo 'Newsletter not found';
    return;
}

$email_id = $email['id'];

/* Satus changes which require a reload */
if ($controls->is_action('pause')) {
    $this->logger->info('Newsletter ' . $email_id . ' paused');
    $wpdb->update(NEWSLETTER_EMAILS_TABLE, array('status' => 'paused'), array('id' => $email_id));
    $email = $this->get_email($_GET['id'], ARRAY_A);
    tnp_prepare_controls($email, $controls);
}

if ($controls->is_action('continue')) {
    $this->logger->info('Newsletter ' . $email_id . ' restarted');
    $wpdb->update(NEWSLETTER_EMAILS_TABLE, array('status' => 'sending'), array('id' => $email_id));
    $email = $this->get_email($_GET['id'], ARRAY_A);
    tnp_prepare_controls($email, $controls);
}

if ($controls->is_action('abort')) {
    if (!current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions', 'newsletter'));
    }
    $this->logger->info('Newsletter ' . $email_id . ' aborted');
    $wpdb->query($wpdb->prepare(
        "UPDATE " . NEWSLETTER_EMAILS_TABLE . " SET last_id=0, sent=0, status='new' WHERE id=%d",
        intval($_GET['id'])
    ));
    $email = $this->get_email($_GET['id'], ARRAY_A);
    tnp_prepare_controls($email, $controls);
    $controls->messages = __('Lieferung endgültig abgesagt', 'newsletter');
}

if ($controls->is_action('change-private')) {
    $data = [];
    $data['private'] = $controls->data['private'];
    $data['id'] = $email['id'];
    $email = $this->save_email($data, ARRAY_A);
    $controls->add_toast_saved();

    tnp_prepare_controls($email, $controls);
}


$editor_type = $this->get_editor_type($email);

// Backward compatibility: preferences conversion
if (!$controls->is_action()) {
    if (!isset($email['options']['lists'])) {

        $options_profile = get_option('newsletter_profile');

        if (empty($controls->data['preferences_status_operator'])) {
            $email['options']['lists_operator'] = 'or';
        } else {
            $email['options']['lists_operator'] = 'and';
        }
        $controls->data['options_lists'] = [];
        $controls->data['options_lists_exclude'] = [];

        if (!empty($email['preferences'])) {
            $preferences = explode(',', $email['preferences']);
            $value = empty($email['options']['preferences_status']) ? 'on' : 'off';

            foreach ($preferences as $x) {
                if ($value == 'on') {
                    $controls->data['options_lists'][] = $x;
                } else {
                    $controls->data['options_lists_exclude'][] = $x;
                }
            }
        }
    }
}
// End backward compatibility

if (!$controls->is_action()) {
    tnp_prepare_controls($email, $controls);
}

if ($controls->is_action('html')) {

    $this->logger->info('Newsletter ' . $email_id . ' converted to HTML');

    $data = [];
    $data['editor'] = NewsletterEmails::EDITOR_HTML;
    $data['id'] = $email_id;

    // Backward compatibility: clean up the composer flag
    $data['options'] = $email['options'];
    unset($data['options']['composer']);
    // End backward compatibility

    $data['message'] = preg_replace('/data-json=".*?"/is', '', $email['message']);
    $data['message'] = str_replace('</table>', "</table>\n", $data['message']);
    $data['message'] = str_replace('</td></tr>', "</td>\n</tr>", $data['message']);
    $data['message'] = str_replace('</td></tr>', "</td>\n</tr>", $data['message']);
    $data['message'] = str_replace('</tr></tbody>', "</tr>\n</tbody>", $data['message']);
    $data['message'] = str_replace('</tbody></table>', "</tbody>\n</table>", $data['message']);
    $data['message'] = str_replace('<tbody><tr>', "<tbody>\n<tr>", $data['message']);
    $data['message'] = str_replace('<tr><td ', "<tr>\n<td ", $data['message']);

    $email = $this->save_email($data, ARRAY_A);
    $controls->messages = __('Du kannst den Newsletter nun als reines HTML bearbeiten.', 'newsletter');

    tnp_prepare_controls($email, $controls);

    $editor_type = NewsletterEmails::EDITOR_HTML;
}



if ($controls->is_action('test') || $controls->is_action('save') || $controls->is_action('send') || $controls->is_action('schedule')) {

    $controls->data = wp_kses_post_deep($controls->data);

    if ($email['updated'] != $controls->data['updated']) {
        $controls->errors = __('Dieser Newsletter wurde von jemand anderem geändert. Speichern nicht möglich.', 'newsletter');
    } else {
        $email['updated'] = time();
        if ($controls->is_action('save')) {
            $this->logger->info('Speichere Newsletter: ' . $email_id);
        } else if ($controls->is_action('send')) {
            $this->logger->info('Sende Newsletter: ' . $email_id);
        } else if ($controls->is_action('schedule')) {
            $this->logger->info('Plane Newsletter: ' . $email_id);
        }

        //$email['subject'] = $controls->data['subject'];
        $email['track'] = $controls->data['track'];
        $email['editor'] = $editor_type;
        $email['private'] = $controls->data['private'];
        $email['message_text'] = $controls->data['message_text'];
        if ($controls->is_action('send') || $controls->is_action('save')) {
            $email['send_on'] = time();
        } else {
            // Patch, empty on continuation
            if (!empty($controls->data['send_on'])) {
                $email['send_on'] = $controls->data['send_on'];
            }
        }

        // Reset and refill the options
        // Try without the reset and let's see where the problems are
        //$email['options'] = array();
        // Reset only specific keys
        unset($email['options']['lists']);
        unset($email['options']['lists_operator']);
        unset($email['options']['lists_exclude']);
        unset($email['options']['sex']);
        unset($email['options']['countries']);
        unset($email['options']['regions']);
        unset($email['options']['cities']);
        for ($i = 1; $i <= NEWSLETTER_PROFILE_MAX; $i++) {
            unset($email['options']["profile_$i"]);
        }

        foreach ($controls->data as $name => $value) {
            if (strpos($name, 'options_') === 0) {
                $email['options'][substr($name, 8)] = $value;
            }
        }

        // Before send, we build the query to extract subscriber, so the delivery engine does not
        // have to worry about the email parameters
        if ($email['options']['status'] == 'S') {
            $query = "select * from " . NEWSLETTER_USERS_TABLE . " where status='S'";
        } else {
            $query = "select * from " . NEWSLETTER_USERS_TABLE . " where status='C'";
        }

        if ($email['options']['wp_users'] == '1') {
            $query .= " and wp_user_id<>0";
        }

        if (!empty($email['options']['language'])) {
            $query .= " and language='" . esc_sql((string) $email['options']['language']) . "'";
        }


        $list_where = [];
        $lists = $email['options']['lists'] ?? [];
        foreach ($lists as $list) {
            $list_where[] = 'list_' . ((int) $list) . '=1';
        }

        if ($list_where) {
            $operator = $email['options']['lists_operator'] ?? '';
            if ($operator === 'and') {
                $query .= ' and (' . implode(' and ', $list_where) . ')';
            } else {
                $query .= ' and (' . implode(' or ', $list_where) . ')';
            }
        }

        // Excluded lists
        $list_where = [];
        $lists = $email['options']['lists_exclude'] ?? [];
        foreach ($lists as $list) {
            $list_where[] = 'list_' . ((int) $list) . '=0';
        }

        if ($list_where) {
            // Must not be in one of the excluded lists
            $query .= ' and (' . implode(' and ', $list_where) . ')';
        }

        // Gender
        if (!empty($email['options']['sex'])) {
            $query .= " and sex in ('" . implode("','", esc_sql($email['options']['sex'])) . "') ";
        }

        // Profile fields filter
        $profile_clause = [];
        for ($i = 1; $i <= NEWSLETTER_PROFILE_MAX; $i++) {
            $values = $email["options"]["profile_$i"] ?? [];
            if ($values) {
                $profile_clause[] = 'profile_' . $i . " IN ('" . implode("','", esc_sql($values)) . "') ";
            }
        }

        if (!empty($profile_clause)) {
            $query .= ' and (' . implode(' and ', $profile_clause) . ')';
        }

        // Temporary save to have an object and call the query filter
        //$e = Newsletter::instance()->save_email($email);
        //$query = apply_filters('newsletter_emails_email_query', $query, $e);

        if (!empty($email["options"]['countries'])) {
            $query .= " and country in ('" . implode("','", esc_sql($email["options"]['countries'])) . "')";
        }

        if (!empty($email["options"]['regions'])) {
            $query .= " and region in ('" . implode("','", esc_sql($email["options"]['regions'])) . "')";
        }

        if (!empty($email["options"]['cities'])) {
            $query .= " and city in ('" . implode("','", esc_sql($email["options"]['cities'])) . "')";
        }

        if (!empty($email["options"]['date_year']) && !empty($email["options"]['date_month']) && !empty($email["options"]['date_day'])) {

            $year = (int) $email["options"]['date_year'];
            $month = (int) $email["options"]['date_month'];
            $day = (int) $email["options"]['date_day'];

            $query .= " and created>'{$year}-{$month}-{$day}'";
        }


        $email['query'] = $query;
        if ($email['status'] == 'sent') {
            $email['total'] = $email['sent'];
        } else {
            $email['total'] = $wpdb->get_var(str_replace('*', 'count(*)', $query));
        }

        if ($controls->is_action('send') && $controls->data['send_on'] < time()) {
            $controls->data['send_on'] = time();
        }

        $email = Newsletter::instance()->save_email($email, ARRAY_A);

        if ($email === false) {
            $controls->errors = __('Speichern nicht möglich. Versuche das Plugin zu deaktivieren und erneut zu aktivieren, möglicherweise ist die Datenbank nicht synchron.', 'newsletter');
        }

        tnp_prepare_controls($email, $controls);

        $controls->add_toast_saved();
    }
}

if (empty($controls->errors) && ($controls->is_action('send') || $controls->is_action('schedule'))) {

    if (empty($email['subject'])) {
        $controls->errors = __('Ein Betreff ist erforderlich, um zu senden', 'newsletter');
    } else {
        NewsletterStatistics::instance()->reset_stats($email);
        $wpdb->update(NEWSLETTER_EMAILS_TABLE, array('status' => TNP_Email::STATUS_SENDING), array('id' => $email_id));
        $email['status'] = TNP_Email::STATUS_SENDING;
        if ($controls->is_action('send')) {
            $controls->messages = __('Wird gesendet.', 'newsletter');
            $controls->messages .= '<br>' . __('Die erste E-Mail-Gruppe wird in 5 Minuten zugestellt.', 'newsletter');
        } else {
            $controls->messages = __('Geplant.', 'newsletter');
        }

        // Immadiate first batch sending since people has no patience

        if ($controls->is_action('send') && $email['total'] < 20 && !Newsletter::instance()->skip_this_run()) {
            // Avoid the first batch if there are other newsletters delivering otherwise we can get over the per hour quota
            $sending_count = $wpdb->get_results("select count(*) from " . NEWSLETTER_EMAILS_TABLE . " where status='sending' and send_on<" . time());
            if ($sending_count <= 1) { // This newsletter is counted as well
                Newsletter::instance()->hook_newsletter();
            }
        }

        NewsletterMainAdmin::instance()->set_completed_step('first-newsletter');
    }
}

if (isset($email['options']['status']) && $email['options']['status'] === 'S') {
    $controls->warnings[] = __('This newsletter will be sent to not confirmed subscribers.', 'newsletter');
}

//if (strpos($email['message'], '{profile_url}') === false && strpos($email['message'], '{unsubscription_url}') === false && strpos($email['message'], '{unsubscription_confirm_url}') === false) {
//    $controls->warnings[] = __('The message is missing the subscriber profile or cancellation link.', 'newsletter');
//}

if (TNP_Email::STATUS_ERROR === $email['status'] && isset($email['options']['error_message'])) {
    $controls->errors .= sprintf(__('Aufgrund eines schwerwiegenden Fehlers gestoppt: %s', 'newsletter'), esc_html($email['options']['error_message']));
}


if ($email['status'] != 'sent') {
    $subscriber_count = $wpdb->get_var(str_replace('*', 'count(*)', $email['query']));
} else {
    $subscriber_count = $email['sent'];
}
?>
<style>
<?php readfile(__DIR__ . '/assets/edit.css') ?>
</style>

<div class="wrap tnp-emails tnp-emails-edit" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php /*$controls->title_help('/newsletter-targeting'); */ ?>

        <h2><?php echo esc_html($email['subject']); ?></h2>

    </div>

    <div id="tnp-body">
        <?php $controls->show() ?>

        <form method="post" action="" id="newsletter-form">
            <?php $controls->init(['cookie_name' => 'newsletter_emails_edit_tab']); ?>
            <?php $controls->hidden('updated'); ?>

            <div class="tnp-emails-header">
                <div class="tnp-submit">

                    <?php if ($email['status'] == 'sending' || $email['status'] == 'sent') { ?>
                        <?php if ($email['status'] == 'message') { ?>
                            <?php $controls->button_back('?page=newsletter_emails_index') ?>
                        <?php } ?>
                    <?php } else { ?>

                        <?php $controls->btn_link($this->get_editor_url($email_id, $editor_type), __('Bearbeiten', 'newsletter'), ['icon' => 'fa-edit', 'secondary' => true]); ?>

                    <?php } ?>

                    <?php if ($email['status'] != 'sending' && $email['status'] != 'sent') $controls->button_save(); ?>
                    <?php if ($email['status'] == 'new') $controls->button_confirm('send', __('Jetzt senden', 'newsletter'), __('Echte Zustellung starten?', 'newsletter')); ?>
                    <?php if ($email['status'] == 'sending') $controls->button_confirm('pause', __('Pause', 'newsletter'), __('Lieferung unterbrechen?', 'newsletter')); ?>
                    <?php if ($email['status'] == 'paused' || $email['status'] == 'error') $controls->button_confirm('continue', __('Weitermachen', 'newsletter'), __('Lieferung fortsetzen?', 'newsletter')); ?>
                    <?php if ($email['status'] == 'paused') $controls->button_confirm('abort', __('Stop', 'newsletter'), __('Das stoppt die Lieferung komplett, okay?', 'newsletter')); ?>
                    <?php if ($email['status'] == 'new' || ( $email['status'] == 'paused' && $email['send_on'] > time() )) { ?>
                        <a id="tnp-schedule-button" class="button-secondary" href="javascript:tnp_toggle_schedule()"><i class="far fa-clock"></i> <?php _e("Zeitplan", "newsletter") ?></a>
                        <span id="tnp-schedule" style="display: none;">
                            <?php $controls->datetime('send_on') ?>
                            <?php $controls->button_confirm('schedule', __('Zeitplan', 'newsletter'), __('Zeitplan der Lieferung?', 'newsletter')); ?>
                            <a class="button-secondary tnp-button-cancel" href="javascript:tnp_toggle_schedule()"><?php _e("Abbrechen", "newsletter") ?></a>
                        </span>
                    <?php } ?>

                    <?php $controls->button_icon_view(home_url('/') . '?na=view&id=' . $email_id) ?>
                </div>

                <div class="tnp-emails-status">

                    <div style="display: flex; justify-content: space-between">
                        <div style="flex-grow: 1">
                            <?php $this->show_email_status_label($email) ?>
                        </div>

                        <div style="flex-grow: 1">
                            <?php
                            if ($email['status'] == 'sending' && $email['send_on'] > time() || $email['status'] == 'sent' || $email['status'] == 'error') {
                                echo $this->format_date($email['send_on']);
                            } else {
                                $this->show_email_progress_bar($email);
                            }
                            ?>

                        </div>

                        <div style="flex-grow: 1; text-align: right">
                            <?php if ($email['status'] == 'new') { ?>
                                <i class="fas fa-users"></i> <?php echo $subscriber_count ?>
                            <?php } else { ?>
                                <i class="fas fa-users"></i> <?php $this->show_email_progress_numbers($email) ?>
                            <?php } ?>
                        </div>

                    </div>

                </div>
            </div>


            <div class="psource-tabs" id="tabs">
                <div class="psource-tabs-nav">
                    <button class="psource-tab active" data-tab="tabs-options"><?php esc_html_e('Zielsetzung', 'newsletter') ?></button>
                    <button class="psource-tab" data-tab="tabs-ga">Google Analytics</button>
                    <button class="psource-tab" data-tab="tabs-geo">Geolocation</button>
                    <button class="psource-tab" data-tab="tabs-advanced"><?php esc_html_e('Fortschrittlich', 'newsletter') ?></button>
                </div>
                <div class="psource-tabs-content">
                    <div class="psource-tab-panel active tnp-list-conditions" id="tabs-options">

                    <p>
                        <?php esc_html_e('Wenn man alle Mehrfachauswahloptionen nicht auswählt, entspricht das der Auswahl aller Optionen.', 'newsletter'); ?>
                    </p>
                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('Lists', 'newsletter') ?></th>
                            <td>
                                <?php
                                $lists = $controls->get_list_options();
                                ?>
                                <?php $controls->select('options_lists_operator', array('or' => __('Mindestens eine auswählen', 'newsletter'), 'and' => __('Alle auswählen', 'newsletter'))); ?>

                                <?php $controls->select2('options_lists', $lists, null, true, null, __('Alle', 'newsletter')); ?>

                                <br>
                                <?php esc_html_e('darf nicht in einer der folgenden sein', 'newsletter') ?>

                                <?php $controls->select2('options_lists_exclude', $lists, null, true, null, __('Keine', 'newsletter')); ?>
                            </td>
                        </tr>

                        <tr>
                            <th><?php esc_html_e('Sprache', 'newsletter') ?></th>
                            <td>
                                <?php $controls->language('options_language'); ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Geschlecht', 'newsletter') ?></th>
                            <td>
                                <?php $controls->checkboxes_group('options_sex', array('f' => 'Frauen', 'm' => 'Männer', 'n' => 'Nicht angegeben')); ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Status', 'newsletter') ?></th>
                            <td>
                                <?php $controls->select('options_status', array('C' => __('Bestätigt', 'newsletter'), 'S' => __('Nicht bestätigt', 'newsletter'))); ?>

                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Nur an Abonnenten, die mit WP-Benutzern verknüpft sind', 'newsletter') ?></th>
                            <td>
                                <?php $controls->yesno('options_wp_users'); ?>
                            </td>
                        </tr>
                        <?php
                        $fields = $this->get_customfields();
                        ?>
                        <?php if (!empty($fields)) { ?>
                            <tr>
                                <th><?php esc_html_e('Profilfelder', 'newsletter') ?></th>
                                <td>
                                    <?php foreach ($fields as $profile) { ?>
                                        <?php if ($profile->type !== TNP_Profile::TYPE_SELECT) continue; ?>
                                        <?php echo esc_html($profile->name), ' ', __('ist eines von:', 'newsletter') ?>
                                        <?php $controls->select2("options_profile_$profile->id", $profile->options, null, true, null, __('Nicht nach diesem Feld filtern', 'newsletter')); ?>
                                        <br>
                                    <?php } ?>
                                    <p class="description">

                                    </p>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>

                    <?php //do_action('newsletter_emails_edit_target', $this->get_email($email_id), $controls)  ?>

                    <table class="form-table">
                        <tr valign="top">
                            <th>Subscribed after</th>
                            <td>
                                <?php $controls->date2('options_date'); ?>
                            </td>
                        </tr>
                    </table>

                </div>

                <div class="psource-tab-panel" id="tabs-ga">
                    <?php if (!class_exists('NewsletterAnalytics')) { ?>
                        <p class="tnp-tab-notice">
                            Optionen, die mit dem Newsletter - Google Analytics Addon wirksam sind.
                        </p>
                    <?php } ?>

                    <?php if (empty($email['track'])) { ?>
                        <p class="tnp-tab-warning">Tracking muss aktiviert sein, um Google Analytics zu verwenden.</p>
                    <?php } ?>


                    <table class="form-table">
                        <tr valign="top">
                            <th>UTM Quelle</th>
                            <td>
                                <?php $controls->text('options_utm_source', 50); ?>
                                <p class="description">
                                    Sollte als "newsletter-{email_id}" gesetzt werden und ist für Google obligatorisch. "{email_id}" wird durch die
                                    eindeutige Newsletter-ID ersetzt. Automatisierte Newsletter, Autoresponder und andere nicht standardmäßige Newsletter verwenden eine andere
                                    Quelle wie automated-{channel numer}-{email id}.
                                </p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th>UTM Kampagne</th>
                            <td>
                                <?php $controls->text('options_utm_campaign', 50); ?>
                                <p class="description">
                                    Dies ist der Kampagnenname
                                </p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th>UTM Medium</th>
                            <td>
                                <?php $controls->text('options_utm_medium', 50); ?>
                                <p class="description">
                                    Sollte auf "email" gesetzt werden, da dies das einzige verwendete Medium ist.
                                </p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th>UTM Begriff</th>
                            <td>
                                <?php $controls->text('options_utm_term', 50); ?>
                                <p class="description">
                                    Normalerweise leer, kann aber in bestimmten Newslettern verwendet werden. Es bezieht sich eher auf keyword-basierte Werbung.
                                </p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th>UTM Inhalt</th>
                            <td>
                                <?php $controls->text('options_utm_content', 50); ?>
                                <p class="description">
                                    Normalerweise leer, kann aber in bestimmten Newslettern verwendet werden.
                                </p>
                            </td>
                        </tr>

                    </table>
                </div>


                <div class="psource-tab-panel" id="tabs-geo">
                    <?php if (!class_exists('NewsletterGeo')) { ?>
                        <p class="tnp-tab-notice">
                            Optionen wirksam mit dem Newsletter - Geo Addon.
                        </p>
                    <?php } ?>

                    <?php
                    $subscriber_status = 'C';
                    if (!empty($email->options['status'])) {
                        $subscriber_status = $email->options['status'];
                    }

                    $list = $wpdb->get_results($wpdb->prepare("select country, count(*) as total from " . NEWSLETTER_USERS_TABLE . " where status=%s and country<>'' group by country order by country", $subscriber_status));

                    $countries = array('' => 'All');
                    foreach ($list as $item) {
                        if (empty($item->country))
                            continue;
                        if (empty($controls->countries[$item->country]))
                            $countries[$item->country] = $item->country . ' (' . $item->total . ')';
                        else
                            $countries[$item->country] = $controls->countries[$item->country] . ' (' . $item->total . ')';
                    }

                    $list = $wpdb->get_results($wpdb->prepare("select region, count(*) as total from " . NEWSLETTER_USERS_TABLE . " where status=%s and region<>'' group by region order by region", $subscriber_status));

                    $regions = array();
                    foreach ($list as $item) {
                        if (empty($item->region))
                            continue;
                        $regions[$item->region] = $item->region . ' (' . $item->total . ')';
                    }

                    $list = $wpdb->get_results($wpdb->prepare("select city as city, count(*) as total from " . NEWSLETTER_USERS_TABLE . " where status=%s and city<>'' group by lower(city) order by lower(city)", $subscriber_status));

                    $cities = array();
                    foreach ($list as $item) {
                        if (empty($item->city))
                            continue;
                        $cities[strtolower($item->city)] = $item->city . ' (' . $item->total . ')';
                    }
                    ?>

                    <table class="form-table">
                        <tr valign="top">
                            <th><?php esc_html_e('Country', 'newsletter'); ?></th>
                            <td>
                                <?php $controls->select2('options_countries', $countries, null, true); ?>
                                <p class="description">
                                    <?php esc_html_e('Some country codes could have no meaning. Not all subscribers are resolved.', 'newsletter'); ?><br>
                                    <?php esc_html_e("If you're targeting not confirmed subscribers, save to get the correct country list.", 'newsletter'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th><?php esc_html_e('Regions', 'newsletter'); ?></th>
                            <td>
                                <?php $controls->select2('options_regions', $regions, null, true); ?>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th><?php esc_html_e('Cities', 'newsletter'); ?></th>
                            <td>
                                <?php $controls->select2('options_cities', $cities, null, true); ?>
                            </td>
                        </tr>
                    </table>

                </div>

                <div class="psource-tab-panel" id="tabs-advanced">

                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('Keep private', 'newsletter') ?></th>
                            <td>
                                <?php $controls->yesno('private'); ?>
                                <?php if ($email['status'] == 'sent') { ?>
                                    <?php $controls->button('change-private', __('Save')) ?>
                                <?php } ?>
                                <span class="description">
                                    <?php esc_html_e('Hide/show from public sent newsletter list.', 'newsletter') ?>
                                    <?php esc_html_e('Used by', 'newsletter') ?>: <a href="" target="_blank">Archive Addon</a>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Track clicks and message opening', 'newsletter') ?></th>
                            <td>
                                <?php $controls->yesno('track'); ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Sender email address', 'newsletter') ?></th>
                            <td>
                                <?php $controls->text_email('options_sender_email', 40); ?>
                                <span class="description">
                                    <?php echo esc_html(Newsletter::instance()->get_sender_email()) ?>
                                </span>
                                <p class="description">
                                    If you use a delivery service, be sure to use a validated email address.
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <?php esc_html_e('Sender name', 'newsletter') ?>
                            </th>
                            <td>
                                <?php $controls->text('options_sender_name', 40); ?>
                                <span class="description">
                                    <?php echo esc_html(Newsletter::instance()->get_sender_name()) ?>
                                </span>
                            </td>
                        </tr>
                    </table>

                    <?php do_action('newsletter_emails_edit_other', $this->get_email($email_id), $controls) ?>

                    <table class="form-table">

                        <tr>
                            <th style="vertical-align: top">
                                This is the textual version of your newsletter.
                                If you empty it, only an HTML version will be sent but is an anti-spam best practice to include a text only version.
                            </th>
                            <td>
                                <?php if ($editor_type == NewsletterEmails::EDITOR_COMPOSER) { ?>
                                    <?php $controls->select('options_text_message_mode', ['' => __('Autogenerieren', 'newsletter'), '1' => __('Hand edited', 'newsletter')]) ?>
                                    <p class="description"></p>
                                <?php } ?>

                                <?php $controls->textarea_fixed('message_text', '100%', '500'); ?>
                                <!--
                                <p class="tnp-tab-warning">
                                    See <a href="https://wordpress.org/plugins/plaintext-newsletter/" target="_blank">this plugin</a> for automatic plaintext generation.
                                </p>
                                -->
                            </td>
                        </tr>
                        <tr>
                            <th>Query (tech)</th>
                            <td><?php echo esc_html($email['query']); ?></td>
                        </tr>
                        <tr>
                            <th>Token (tech)</th>
                            <td><?php echo esc_html($email['token']); ?></td>
                        </tr>

                        <?php if ($editor_type != NewsletterEmails::EDITOR_HTML && $email['status'] != 'sending' && $email['status'] != 'sent') { ?>
                            <tr>
                                <th>Convert to HTML</th>
                                <td>
                                    <?php $controls->button_confirm('html', __('Konvertieren', 'newsletter'), 'No way back!'); ?>
                                </td>
                            </tr>
                        <?php } ?>

                    </table>
                </div>

            </div>

        </form>
    </div>

</div>
