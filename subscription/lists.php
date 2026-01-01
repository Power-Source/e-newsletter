<?php
/* @var $this NewsletterSubscriptionAdmin */
/* @var $controls NewsletterControls */
/* @var $logger NewsletterLogger */

defined('ABSPATH') || exit;

if (!$controls->is_action()) {
    $controls->data = $this->get_options('lists', $language);
} else {
    if ($controls->is_action('save')) {

        // Processing lists for specific language
        if ($language) {
            for ($i = 0; $i <= NEWSLETTER_LIST_MAX; $i++) {
                if (empty($controls->data['list_' . $i])) {
                    unset($controls->data['list_' . $i]);
                }
            }
        }
        $controls->data = wp_kses_post_deep($controls->data);
        $this->save_options($controls->data, 'lists', $language);
        $controls->add_toast_saved();
    }

    if ($controls->is_action('unlink')) {
        $this->query("update " . NEWSLETTER_USERS_TABLE . " set list_" . ((int) $controls->button_data) . "=0");
        $controls->add_toast_done();
    }

    if ($controls->is_action('link')) {
        $this->query("update " . NEWSLETTER_USERS_TABLE . " set list_" . ((int) $controls->button_data) . "=1");
        $controls->add_toast_done();
    }

    if ($controls->is_action('unconfirm')) {
        $this->query("update " . NEWSLETTER_USERS_TABLE . " set status='S' where list_" . ((int) $controls->button_data) . "=1");
        $controls->add_toast_done();
    }

    if ($controls->is_action('confirm')) {
        $this->query("update " . NEWSLETTER_USERS_TABLE . " set status='C' where list_" . ((int) $controls->button_data) . "=1");
        $controls->add_toast_done();
    }
}

// Conditions for the count query
$conditions = [];
for ($i = 1; $i <= NEWSLETTER_LIST_MAX; $i++) {
    $conditions[] = "count(case list_$i when 1 then 1 else null end) list_$i";
}

$main_options = $this->get_options('lists', '');

$status = [0 => __('Private', 'newsletter'), 1 => __('Public', 'newsletter')];

$count = $this->get_row("select " . implode(',', $conditions) . ' from ' . NEWSLETTER_USERS_TABLE);

$panels = (int) (NEWSLETTER_LIST_MAX / 10) + (NEWSLETTER_LIST_MAX % 10 > 0 ? 1 : 0);
?>
<div class="wrap tnp-lists" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php $controls->title_help('/newsletter-lists/') ?>
        <h2><?php esc_html_e('Lists', 'newsletter') ?></h2>
    </div>

    <div id="tnp-body">

        <?php $controls->show(); ?>

        <p>
            <?php printf(
                /* translators: %1$s: Link zum Anmeldeformular, %2$s: Link zur Profilseite */
                esc_html__('Configure the lists visibility on the %1$s and %2$s.', 'newsletter'),
                '<a href="?page=newsletter_subscription_form" target="_blank">' . esc_html__('Subscription form', 'newsletter') . '</a>',
                '<a href="?page=newsletter_profile_index" target="_blank">' . esc_html__('Profile page', 'newsletter') . '</a>'
            ); ?>
        </p>
        <p>
            <?php printf(
                /* translators: %s: Link zur Abonnenten-Wartung */
                esc_html__('List wide operations on subscribers (delete, move, add, ...) can be performed on the %s.', 'newsletter'),
                '<a href="?page=newsletter_subscribers_maintenance" target="_blank">' . esc_html__('Subscribers Maintenance page', 'newsletter') . '</a>'
            ); ?>
        </p>
        <p>
            <?php printf(
                esc_html__('Need more lists? %s', 'newsletter'),
                '<a href="https://cp-psource.github.io/e-newsletter/newsletter-lists/#Mehr als 40 Listen hinzufügen" target="_blank">' . esc_html__('Read here', 'newsletter') . '</a>'
            ); ?>
        </p>

        <form method="post" action="">
            <?php $controls->init(); ?>

            <div class="psource-tabs" id="tabs">
                <div class="psource-tabs-nav">
                    <?php for ($i = 0; $i < $panels; $i++) { ?>
                        <button class="psource-tab<?php if ($i === 0) echo ' active'; ?>" data-tab="tabs-general-<?php echo $i ?>">
                            <?php esc_html_e('Lists', 'newsletter') ?> <?php echo $i * 10 + 1, '-', $i * 10 + 10 ?>
                        </button>
                    <?php } ?>
                    <?php if (NEWSLETTER_DEBUG) { ?>
                        <button class="psource-tab" data-tab="tabs-debug">Debug</button>
                    <?php } ?>
                </div>
                <div class="psource-tabs-content">
                    <?php for ($j = 0; $j < $panels; $j++) { ?>
                        <div class="psource-tab-panel<?php if ($j === 0) echo ' active'; ?>" id="tabs-general-<?php echo $j ?>">
                            <?php $this->language_notice() ?>
                            <table class="widefat" style="width: auto; max-width: 800px" scope="presentation">
                                <thead>
                                    <tr>
                                        <th style="vertical-align: top">#</th>
                                        <th style="vertical-align: top"><?php esc_html_e('Name', 'newsletter') ?></th>
                                        <?php if (!$language) { ?>
                                            <th style="vertical-align: top"><?php esc_html_e('Type', 'newsletter') ?></th>
                                            <th style="vertical-align: top; white-space: nowrap">
                                                <?php esc_html_e('Enforced', 'newsletter') ?>
                                                <span class="psource-tooltip" tabindex="0">
                                                    <i class="fas fa-info-circle"></i>
                                                    <span class="psource-tooltip-text">
                                                        <?php esc_html_e('If you check this box, all your new subscribers will be automatically added to this list', 'newsletter') ?>
                                                    </span>
                                                </span>
                                            </th>
                                            <?php if ($is_multilanguage) { ?>
                                                <th style="vertical-align: top; white-space: nowrap">
                                                    <?php esc_html_e('Enforced', 'newsletter') ?>
                                                    <span class="psource-tooltip" tabindex="0">
                                                        <i class="fas fa-info-circle"></i>
                                                        <span class="psource-tooltip-text"><?php esc_html_e('If you check this box, all your new subscribers will be automatically added to this list', 'newsletter') ?></span>
                                                    </span>
                                                </th>
                                            <?php } ?>
                                        <?php } elseif ($is_multilanguage) { ?>
                                            <th style="vertical-align: top; white-space: nowrap">
                                                <?php esc_html_e('Enforced', 'newsletter') ?><br>
                                                <span style="color: var(--tnp-gray-light); font-size: .9em">Switch to "all languages"</span>
                                            </th>
                                        <?php } ?>
                                        <th style="vertical-align: top"><?php esc_html_e('Subscribers', 'newsletter') ?></th>
                                        <th style="vertical-align: top; white-space: nowrap">
                                            <?php esc_html_e('Actions', 'newsletter') ?>
                                            <?php if ($language) { ?>
                                                <br><span style="color: var(--tnp-gray-light); font-size: .9em">Switch to "all languages"</span>
                                            <?php } ?>
                                        </th>
                                    </tr>
                                </thead>
                                <?php for ($i = $j * 10 + 1; $i <= min(($j + 1) * 10, NEWSLETTER_LIST_MAX); $i++) { ?>
                                    <?php
                                    if ($language && empty($main_options['list_' . $i])) {
                                        continue;
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo $i; ?></td>
                                        <td>
                                            <?php $placeholder = !$language ? '' : $main_options['list_' . $i] ?>
                                            <?php $controls->text('list_' . $i, 40, $placeholder); ?>
                                        </td>
                                        <?php if (!$language) { ?>
                                            <td><?php $controls->select('list_' . $i . '_status', $status); ?></td>
                                            <td style="text-align: center">
                                                <?php $controls->checkbox('list_' . $i . '_forced', ''); ?>
                                            </td>
                                            <?php if ($is_multilanguage) { ?>
                                                <td><?php $controls->languages('list_' . $i . '_languages'); ?></td>
                                            <?php } ?>
                                        <?php } elseif ($is_multilanguage) { ?>
                                            <td>&nbsp;</td>
                                        <?php } ?>
                                        <td>
                                            <?php
                                            $field = 'list_' . $i;
                                            echo $count->$field;
                                            ?>
                                        </td>
                                        <td style="white-space: nowrap">
                                            <?php if (!$language) { ?>
                                                <?php $controls->button_confirm_secondary('unlink', __('Unlink everyone', 'newsletter'), true, $i); ?>
                                                <?php $controls->button_confirm_secondary('link', __('Add everyone', 'newsletter'), true, $i); ?>
                                                <?php $controls->button_confirm_secondary('unconfirm', __('Unconfirm all', 'newsletter'), true, $i); ?>
                                                <?php $controls->button_confirm_secondary('confirm', __('Confirm all', 'newsletter'), true, $i); ?>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td colspan="7">
                                            <?php $notes = apply_filters('newsletter_lists_notes', array(), $i); ?>
                                            <?php
                                            $text = '';
                                            foreach ($notes as $note) {
                                                $text .= esc_html($note) . '<br>';
                                            }
                                            if (!empty($text)) {
                                                echo $text;
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </table>
                        </div>
                    <?php } ?>
                    <?php if (NEWSLETTER_DEBUG) { ?>
                        <div class="psource-tab-panel" id="tabs-debug">
                            <pre><?php echo esc_html(wp_json_encode($this->get_db_options('lists', $language), JSON_PRETTY_PRINT)) ?></pre>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <p>
                <?php $controls->button_save(); ?>
            </p>
        </form>
    </div>

</div>