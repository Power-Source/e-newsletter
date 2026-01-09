<?php
/* @var $wpdb wpdb */

// phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged

global $wpdb;

defined('ABSPATH') || exit;

require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

if ($controls->is_action('import')) {

    @set_time_limit(0);

    $emails = NewsletterModule::to_array($controls->data['csv']);

    $updated = 0;
    $total = count($emails);
    $wrong = 0;

    foreach ($emails as &$email) {
        if (!is_email($email)) {
            $wrong++;
            continue;
        }
        $r = $wpdb->update(NEWSLETTER_USERS_TABLE, ['status' => TNP_User::STATUS_BOUNCED], ['email' => $email]);
        if ($r) {
            $updated++;
        }
    }

    $controls->messages = "$updated als unzustellbar markiert ($total angegeben). Fehlende oder bereits als unzustellbar markierte E-Mails werden nicht gezählt.";
    if ($wrong) {
        $controls->messages .= "<br>$wrong ungültige E-Mail(s).";
    }
}
?>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER ?>

    <div id="tnp-heading">
        <?php //$controls->title_help('/addons/extended-features/advanced-import/') ?>
        <h2>Import</h2>
        <?php include __DIR__ . '/nav.php' ?>
    </div>

    <div id="tnp-body" class="tnp-users tnp-users-import">

        <?php $controls->show(); ?>

        <form method="post">

            <?php $controls->init(); ?>

            <table class="form-table">

                <tr>
                    <th>
                        <?php esc_html_e('Unzustellbare Adressen', 'newsletter-import') ?>
                    </th>
                    <td>
                        <textarea name="options[csv]" wrap="off" style="width: 100%; height: 200px; font-size: 11px; font-family: monospace"><?php echo esc_html($controls->get_value('csv')); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Eine pro Zeile', 'newsletter-import') ?>
                        </p>
                    </td>
                </tr>
            </table>
            <div class="tnp-buttons">
                <?php $controls->button('import', __('Importieren', 'newsletter-import')); ?>
            </div>


        </form>

    </div>


</div>
