<?php
/* @var $this NewsletterStatisticsAdmin */
/* @var $controls NewsletterControls */

defined('ABSPATH') || exit;

$email_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$email = $this->get_email($email_id);
if (empty($email)) {
    echo 'Newsletter not found';
    return;
}

// --- HIER: Echte Klickdaten laden ---
$items = $this->get_clicked_urls($email_id); // Muss ein Array von Objekten mit ->url und ->number liefern

// Fallback: Wenn keine Daten, leeres Array
if (!is_array($items)) {
    $items = [];
}

// Gesamtklicks berechnen
$total = array_reduce($items, function ($carry, $item) {
    $carry += $item->number;
    return $carry;
}, 0);

?>
<style>
<?php include __DIR__ . '/style.css'; ?>
</style>
<div class="wrap tnp-statistics tnp-statistics-view" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER; ?>
    <?php include __DIR__ . '/view-heading.php' ?>

    <div id="tnp-body">

        <table class="widefat">
            <colgroup>
                <col class="w-80">
                <col class="w-10">
                <col class="w-10">
            </colgroup>
            <thead>
                <tr class="text-left">
                    <th><?php esc_html_e('Clicked URLs', 'newsletter'); ?></th>
                    <th><?php esc_html_e('Clicks', 'newsletter'); ?></th>
                    <th>%</th>
                    <th><?php esc_html_e('Who clicked...', 'newsletter'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)) : ?>
                    <tr>
                        <td colspan="4"><?php esc_html_e('No clicks recorded for this newsletter.', 'newsletter'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item) : ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url($item->url); ?>" target="_blank">
                                    <?php echo esc_html($item->url); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($item->number); ?></td>
                            <td>
                                <?php
                                echo $total > 0
                                    ? esc_html(NewsletterModule::percent($item->number, $total))
                                    : '0%';
                                ?>
                            </td>
                            <td>
                                <form action="" method="post">
                                    <?php $controls->init(); ?>
                                    <?php $controls->data['url'] = $item->url; ?>
                                    <?php $controls->hidden('url'); ?>
                                    <?php $controls->lists_select(); ?>
                                    <?php $controls->btn('set', __('Add to this list', 'newsletter'), ['secondary' => true]); ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
