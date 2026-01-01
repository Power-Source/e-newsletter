<?php
if (!isset($forms)) {
    die('FEHLER: $forms ist nicht gesetzt!');
}
/* @var $this NewsletterForms */
defined('ABSPATH') || exit;

require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';

$controls = new NewsletterControls();

//$logger = $this->get_admin_logger();

if ($controls->is_action('add')) {
    $data = array('name' => 'New form', 'config' => '{"settings":[{"id":"referrer","value":""},{"id":"welcome_url","value":""},{"id":"lists","value":[]}],"blocks":[{"name":"Email","content":"","placeholder":"Your best email","label":"Email","icon":"IconTitle","type":"InputText","id":"email"},{"name":"Submit","placeholder":"","label":"Subscribe","icon":"IconTitle","type":"InputButton","id":"submit"}]}');
    $data = $forms->save_form($data);
    $controls->js_redirect('?page=newsletter_forms_edit&id=' . $data->id);
}

if ($controls->is_action('delete')) {
    $form_id = (int) $_POST['btn'];
    $forms->store->delete($forms->table, $form_id);
    $controls->add_message_deleted();
}

if ($controls->is_action('copy')) {
    //$logger->info('Copy of series ' . $_POST['btn']);
    //$this->copy_autoresponder($_POST['btn']);
    //$controls->messages .= __('Series duplicated.', 'newsletter-autoresponder');
}

$forms = $forms->get_forms();

$controls->warnings[] = __('Please note: this is an experimental feature that could change without notice.', 'newsletter');
?>

<div class="wrap tnp-emails tnp-emails-index" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">

        <h2><?php _e('Forms', 'newsletter') ?></h2>
        <p>
            <?php _e('Shortcodes can be used on posts, pages, and every widget area.', 'newsletter'); ?>
        </p>

    </div>

    <div id="tnp-body">
        <?php $controls->show(); ?>
        <form method="post" action="">
            <?php $controls->init(); ?>

            <div class="tnp-buttons">
                <?php $controls->button('add', 'New') ?>
            </div>

            <table class="widefat" style="width: 100%">
                <thead>
                    <tr>
                        <th class="tnp-table-id">Id</th>
                        <th><?php _e('Name', 'newsletter') ?></th>
                        <th>Shortcode</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($forms as $form) { ?>
                        <tr>
                            <td><?php echo $form->id; ?></td>
                            <td><?php echo esc_html($form->name) ?></td>
                            <td><code>[tnp_form id="<?php echo esc_html($form->id) ?>"]</code></td>
                            <td style="white-space: nowrap">
                                <?php $controls->button_icon_edit('?page=newsletter_forms_index&id=' . $form->id); ?>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </form>
    </div>


</div>
