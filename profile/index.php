<?php
/* @var $this NewsletterProfileAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

if ($controls->is_action()) {
    if ($controls->is_action('save')) {
        foreach ($controls->data as $k => $v) {
            if (strpos($k, '_custom') > 0) {
                if (empty($v)) {
                    $controls->data[str_replace('_custom', '', $k)] = '';
                }
                unset($controls->data[$k]);
            }
        }
        $controls->data = wp_kses_post_deep($controls->data);
        $this->save_options($controls->data, '', $language);
        $controls->add_toast_saved();
    }
} else {
    $controls->data = $this->get_options('', $language);
}

foreach (['text'] as $key) {
    if (!empty($controls->data[$key])) {
        $controls->data[$key . '_custom'] = '1';
    }
}
?>

<div class="wrap tnp-profile tnp-profile-index" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php $controls->title_help('/profile-page') ?>
<!--        <h2><?php esc_html_e('Subscribers', 'newsletter') ?></h2>-->
        <?php include __DIR__ . '/../users/nav.php' ?>

    </div>

    <div id="tnp-body">

        <?php $controls->show() ?>
        <p>
            The online page where your subscribers manage their subscription. They reach this page
            clicking the link on the footer of your newsleters.
        </p>

        <form id="channel" method="post" action="">
            <?php $controls->init(); ?>
            <div class="psource-tabs" id="tabs">
                <div class="psource-tabs-nav">
                    <button class="psource-tab active" data-tab="tabs-general"><?php esc_html_e('General', 'newsletter'); ?></button>
                    <button class="psource-tab" data-tab="tabs-fields"><?php esc_html_e('Form', 'newsletter'); ?></button>
                    <button class="psource-tab" data-tab="tabs-labels"><?php esc_html_e('Labels', 'newsletter'); ?></button>
                    <?php if (NEWSLETTER_DEBUG) { ?>
                        <button class="psource-tab" data-tab="tabs-debug">Debug</button>
                    <?php } ?>
                </div>
                <div class="psource-tabs-content">
                    <div class="psource-tab-panel active" id="tabs-general">
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e('Page', 'newsletter') ?></th>
                                <td>
                                    <?php $controls->page_or_url('page'); ?>
                                    <!-- <p class="description">
                                        The specified page must contain the <code>[newsletter /]</code> shortcode which will be replaced with the
                                        content below.
                                    </p>-->
                                </td>
                            </tr>
                            <tr data-tnpshow="page_id=0">
                                <th>
                                    <?php esc_html_e('Page content', 'newsletter') ?>
                                </th>
                                <td>
                                    <?php $controls->checkbox2('text_custom', 'Customize', ['onchange' => 'tnp_refresh_binds()']); ?>
                                    <div data-bind="options-text_custom">
                                        <?php $controls->wp_editor('text', ['editor_height' => 150], ['default' => $this->get_default_text('text')]); ?>
                                    </div>
                                    <div data-bind="!options-text_custom" class="tnpc-default-text">
                                        <?php echo wp_kses_post($this->get_default_text('text')) ?>
                                    </div>
                                    <p class="description">
                                        <?php esc_html_e('Content of the Newsletter public page', 'newsletter'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <?php esc_html_e('Notes', 'newsletter'); ?>
                                </th>
                                <td>
                                    <ul>
                                        <li>
                                            <?php esc_html_e('Use', 'newsletter'); ?>
                                            <code>[newsletter_profile /]</code>
                                            <?php esc_html_e('where you want the edit form to be inserted.', 'newsletter'); ?>
                                        </li>
                                        <li>
                                            <?php esc_html_e('Use', 'newsletter'); ?>
                                            <code>[newsletter_unsubscribe_button label="..." /]</code>
                                            <?php esc_html_e('to add the unsubscribe button.', 'newsletter'); ?>
                                        </li>
                                        <li>
                                            <?php esc_html_e('Use', 'newsletter'); ?>
                                            <code>[newsletter_export_button label="..." /]</code>
                                            <?php esc_html_e('to add the GDPR export button.', 'newsletter'); ?>
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="psource-tab-panel" id="tabs-fields">
                        <?php $this->language_notice() ?>
                        <?php if (!$language) { ?>
                            <table class="widefat" style="width: auto">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Field', 'newsletter') ?></th>
                                        <th><?php esc_html_e('Show', 'newsletter') ?></th>
                                        <th><?php esc_html_e('Required', 'newsletter') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <th><?php esc_html_e('Email', 'newsletter') ?></th>
                                        <td>
                                            <?php $controls->checkbox2('email') ?>
                                        </td>
                                        <td>
                                            <input type="checkbox" checked disabled>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e('First name', 'newsletter') ?></th>
                                        <td>
                                            <?php $controls->checkbox2('name') ?>
                                        </td>
                                        <td>
                                            <?php $controls->checkbox2('name_required') ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e('Last name', 'newsletter') ?></th>
                                        <td>
                                            <?php $controls->checkbox2('surname') ?>
                                        </td>
                                        <td>
                                            <?php $controls->checkbox2('surname_required') ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e('Gender', 'newsletter') ?></th>
                                        <td>
                                            <?php $controls->checkbox2('sex') ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e('Language', 'newsletter') ?></th>
                                        <td>
                                            <?php $controls->checkbox2('language') ?>
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <th style="vertical-align: top">
                                            <?php esc_html_e('Lists', 'newsletter') ?><br>
                                            <a href="?page=newsletter_subscription_lists" target="_blank"><small><?php esc_html_e('Configure', 'newsletter') ?></small></a>
                                        </th>
                                        <td>
                                            <?php $controls->lists_public() ?>
                                        </td>
                                        <td>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th style="vertical-align: top">
                                            <?php esc_html_e('Custom fields', 'newsletter') ?><br>
                                            <a href="?page=newsletter_subscription_customfields" target="_blank"><small><?php esc_html_e('Configure', 'newsletter') ?></small></a>
                                        </th>
                                        <td>
                                            <?php $controls->profiles_public('profiles'); ?>
                                        </td>
                                        <td>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        <?php } ?>
                    </div>
                    <div class="psource-tab-panel" id="tabs-labels">
                        <?php $this->language_notice() ?>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e('Profile saved', 'newsletter') ?></th>
                                <td>
                                    <?php $controls->text('saved', 80, $this->get_default_text('saved')); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Email changed alert', 'newsletter') ?></th>
                                <td>
                                    <?php $controls->text('email_changed', 80, $this->get_default_text('email_changed')); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('General error', 'newsletter') ?></th>
                                <td>
                                    <?php $controls->text('error', 80, $this->get_default_text('error')); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('"Save" label', 'newsletter') ?></th>
                                <td>
                                    <?php $controls->text('save_label', 30, $this->get_default_text('save_label')); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Privacy link text', 'newsletter') ?></th>
                                <td>
                                    <?php $controls->text('privacy_label', 80, $this->get_default_text('privacy_label')); ?>
                                    <p class="description"></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <?php if (NEWSLETTER_DEBUG) { ?>
                        <div class="psource-tab-panel" id="tabs-debug">
                            <pre><?php echo esc_html(wp_json_encode($this->get_db_options('', $language), JSON_PRETTY_PRINT)) ?></pre>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <p>
                <?php $controls->button_save() ?>
                <?php if (current_user_can('administrator')) { ?>
                    <?php $controls->btn_link($this->build_dummy_action_url('p'), __('Preview', 'newsletter'), ['tertiary' => true, 'target' => '_blank']); ?>
                <?php } ?>
            </p>
        </form>

    </div>

</div>
