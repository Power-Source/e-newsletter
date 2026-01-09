<?php
/* @var $this NewsletterUsersAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

$user = $this->get_user((int) $_GET['id'] ?? -1);
if (!$user) {
    die('Benutzer nicht gefunden');
}

?>

<div class="wrap tnp-users tnp-users-edit" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER ?>

    <div id="tnp-heading">
        <?php //$controls->title_help('/subscribers-and-management/') ?>
        <h2><?php echo esc_html($user->email) ?></h2>
        <?php include __DIR__ . '/edit-nav.php' ?>
    </div>

    <div id="tnp-body">

        <?php $controls->show(); ?>

        <form method="post" action="">

            <?php $controls->init(); ?>

            <div class="psource-tabs" id="tabs">
                <div class="psource-tabs-nav">
                    <button class="psource-tab active" data-tab="tabs-newsletters"><?php esc_html_e('Newsletters', 'newsletter') ?></button>
                </div>
                <div class="psource-tabs-content">
                    <div class="psource-tab-panel active tnp-tab" id="tabs-newsletters">
                        <?php if (!has_action('newsletter_user_newsletters_tab') && !has_action('newsletter_users_edit_newsletters')) {
                            do_action('newsletter_user_newsletters_tab', $user->id);
                            do_action('newsletter_users_edit_newsletters', $user->id);
                        } ?>
                    </div>
                </div>
            </div>

        </form>
    </div>

</div>
