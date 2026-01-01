<?php
/* @var $this NewsletterForms */

defined('ABSPATH') || exit;

require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

$id = (int) $_GET['id'];
$form = $forms->get_form($id);

if (!$form) {
    die('Invalid form ID');
}

if ($controls->is_action('save')) {

    error_log(print_r($_POST, true));
    error_log(print_r($controls->data, true));
    $controls->data['id'] = $form->id;
    $form = $forms->save_form($controls->data);
    $controls->add_toast_saved();
}

if ($controls->is_action('delete')) {
    $forms->delete_form($id);
    $controls->js_redirect('admin.php?page=newsletter_forms_index');
    return;
}

if (!$controls->is_action()) {
    $controls->set_data($form);
}
?>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const {createApp} = Vue;
        createApp().use(window.tnpfdPlugin).mount('#tnpfd-app');
<?php if (!$controls->is_action()) { ?>
            document.dispatchEvent(new Event('openDialog'));
<?php } ?>
    });
    document.addEventListener('tnpfdsave', (e) => {
        document.getElementById('tnpc-form').act.value = 'save';
        document.getElementById('tnpc-form').submit();
    }, false);
</script>

<link href="<?php echo plugin_dir_url(dirname(__DIR__)) . 'newsletter-forms/assets/style.css'; ?>" rel="stylesheet" type="text/css">

<div class="wrap tnp-forms tnp-forms-edit" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">

        <h2><?php echo esc_html($controls->data['name']) ?></h2>

    </div>

    <div id="tnp-body">
        <?php $controls->show(); ?>

        <form method="post" action="" id="tnpc-form">

            <?php $controls->init(); ?>

            <div class="psource-tabs" id="tabs">
                <div class="psource-tabs-nav">
                    <button class="psource-tab active" data-tab="tabs-general"><?php _e('General', 'newsletter') ?></button>
                </div>
                <div class="psource-tabs-content">
                    <div class="psource-tab-panel active tnp-tab" id="tabs-general">
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e('Name', 'newsletter'); ?></th>
                                <td>
                                    <?php $controls->text('name'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Form', 'newsletter'); ?></th>
                                <td>
                                    <a href="javascript:void(0)" class="button-primary" onclick="document.dispatchEvent(new Event('openDialog'))"><?php _e('Edit', 'newsletter'); ?></a>
                                    <?php $controls->hidden('config'); ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Autoresponders', 'newsletter'); ?></th>
                                <td>
                                    <?php if (class_exists('NewsletterAutoresponder')) { ?>
                                        <?php
                                        global $newsletter_autoresponder;
                                        if (isset($newsletter_autoresponder)) {
                                            $autoresponders = $newsletter_autoresponder->get_autoresponders();
                                            foreach ($autoresponders as $autoresponder) {
                                                $controls->checkbox_group('autoresponders', $autoresponder->id, $autoresponder->name);
                                                echo '<br>';
                                            }
                                        } else {
                                            esc_html_e('Autoresponder instance not found.', 'newsletter');
                                        }
                                        ?>
                                    <?php } else { ?>
                                        <?php esc_html_e('The Autoresponder addon is required.', 'newsletter'); ?>
                                    <?php } ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tnp-buttons">
                <?php $controls->button_icon_back('?page=newsletter_forms_index'); ?>
                <input type="hidden" name="act" value="save">
                <?php $controls->button_save(); ?>
                <?php $controls->button_delete(); ?>
            </div>

        </form>


    </div>

</div>
<?php
add_action('admin_footer', function () use ($form, $forms) {
    echo '<div id="tnpfd-app" style="position: absolute; top: 0; left: 0; z-index: 10000; padding: 20px"><form-designer-dialog target="options-config" :debug="false" :config="', esc_attr(json_encode($forms->get_builder_config($form))), '"></form-designer-dialog></div>';
}, 10000);
