<?php
/* @var $this NewsletterSubscription */
/* @var $wpdb wpdb */
defined('ABSPATH') || exit;

global $wpdb;

include_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

$items = $wpdb->get_results("select * from {$wpdb->options} where option_name like 'newsletter_subscription%' order by option_name");
array_walk($items, function ($item) {
    $item->option_name = strtoupper(substr($item->option_name, 24));
    if (empty($item->option_name)) $item->option_name = 'Main';
});
?>

<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER ?>

    <div id="tnp-heading">
        <?php include __DIR__ . '/nav.php' ?>
    </div>

    <div id="tnp-body">

        <?php $controls->show(); ?>
        <?php $controls->init(); ?>

        <div class="psource-tabs" id="tabs">
            <div class="psource-tabs-nav">
                <?php foreach ($items as $index => $item) { ?>
                    <button class="psource-tab<?php if ($index === 0) echo ' active'; ?>" data-tab="tabs-<?php echo esc_attr($item->option_name) ?>">
                        <?php echo esc_html($item->option_name); ?>
                    </button>
                <?php } ?>
            </div>
            <div class="psource-tabs-content">
                <?php foreach ($items as $index => $item) { ?>
                    <div class="psource-tab-panel<?php if ($index === 0) echo ' active'; ?>" id="tabs-<?php echo esc_attr($item->option_name) ?>">
                        <pre><?php echo esc_html(json_encode(maybe_unserialize($item->option_value), JSON_PRETTY_PRINT)) ?></pre>
                    </div>
                <?php } ?>
            </div>
        </div>

    </div>

</div>
