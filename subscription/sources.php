<?php
/* @var $this NewsletterSubscriptionAdmin */
/* @var $controls NewsletterControls */
/* @var $logger NewsletterLogger */

use Newsletter\Integrations;

defined('ABSPATH') || exit;

$extensions_url = '?page=newsletter_main_extensions';
if (class_exists('NewsletterExtensions')) {
    $extensions_url = '?page=newsletter_extensions_index';
}
?>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php $controls->title_help('/subscription') ?>
        <?php include __DIR__ . '/nav-forms.php' ?>
    </div>

    <div id="tnp-body">
        <?php $controls->show(); ?>

        <p>
            Quick access to all subscription sources.
        </p>
        <form method="post" action="">
            <?php $controls->init(); ?>

            <table class="widefat" style="width: auto">
                <thead>
                    <tr>
                        <th style="width: 30rem" colspan="2">Form</th>

                        <th style="width: 5rem">&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>Newsletter</td>
                        <td>Standard form</td>
                        <td style="white-space: nowrap">
                            <?php $controls->button_icon_configure('?page=newsletter_subscription_form') ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Newsletter</td>
                        <td>After posts' content</td>

                        <td style="white-space: nowrap">
                            <?php $controls->button_icon_configure('?page=newsletter_subscription_inject') ?>
                        </td>
                    </tr>

                    <tr>
                        <td>Newsletter</td>
                        <td>Simple popup</td>

                        <td style="white-space: nowrap">
                            <?php $controls->button_icon_configure('?page=newsletter_subscription_popup') ?>
                        </td>
                    </tr>

                    <tr>
                        <td>WordPress</td>
                        <td>WP User Registration</td>

                        <td style="white-space: nowrap">
                           <?php $controls->button_icon_configure('?page=newsletter_wpusers_index') ?>
                        </td>
                    </tr>

                    <?php Integrations::source_rows(Integrations::get_elementor_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_cf7_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_gravityforms_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_wpforms_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_forminator_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_formidable_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_ninjaforms_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_fluentforms_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_woocommerce_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_edd_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_ultimatemember_sources(), $controls) ?>

                    <?php Integrations::source_rows(Integrations::get_pmpro_sources(), $controls) ?>

                </tbody>
            </table>

        </form>
    </div>
</div>