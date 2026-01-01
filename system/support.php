<?php
/* @var $this NewsletterSystemAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;
?>

<style>
<?php include __DIR__ . '/css/system.css' ?>
</style>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">

        <h2><?php esc_html_e('Support', 'newsletter') ?></h2>

    </div>

    <div id="tnp-body">
        <?php $controls->show(); ?>

        <form method="post" action="">
            <?php $controls->init(); ?>
            <div class="tnp-dashboard">
                <div class="tnp-cards-container">
                    <div class="tnp-card">
                        <div class="tnp-card-title"><?php esc_html_e('How to get support', 'newsletter'); ?></div>

                        <h3><i class="fas fa-book"></i> <?php esc_html_e('Documentation', 'newsletter'); ?></h3>
                        <p>
                            <?php
                            printf(
                                esc_html__('We have %1$sextensive documentation%2$s about PS eNewsletter settings and features and the free and commercial addons.', 'newsletter'),
                                '<a href="https://cp-psource.github.io/e-newsletter/documentation" target="_blank">',
                                '</a>'
                            );
                            ?>
                        </p>

                        <h3><i class="fas fa-comment"></i> <?php esc_html_e('Forum', 'newsletter'); ?></h3>
                        <p>
                            <?php
                            printf(
                                esc_html__('We run a %1$ssupport forum%2$s where you can send your requests for help, new features, ideas and so on.', 'newsletter'),
                                '<a href="https://github.com/Power-Source/e-newsletter/discussions" target="_blank">',
                                '</a>'
                            );
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
    

</div>
