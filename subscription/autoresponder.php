<?php
/* @var $this NewsletterSubscriptionAdmin */
/* @var $controls NewsletterControls */
/* @var $logger NewsletterLogger */

defined('ABSPATH') || exit;
?>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php $controls->title_help('/subscription') ?>
        <?php include __DIR__ . '/nav.php' ?>

    </div>

    <div id="tnp-body">


        <?php $controls->show(); ?>

        <p>
            <?php
            printf(
                esc_html__('Configure your welcome/follow series on the %s.', 'newsletter'),
                '<a href="?page=newsletter_main_autoresponderindex">' . esc_html__('Autoresponder settings page', 'newsletter') . '</a>'
            );
            ?>
        </p>

    </div>
</div>

