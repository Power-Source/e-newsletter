<?php
/* @var $options array contains all the options the current block we're ediging contains */
/* @var $controls NewsletterControls */
/* @var $fields NewsletterFields */
?>

<p><?php esc_html_e('Social profiles can be configured on company info page.', 'newsletter'); ?></p>

<div class="psource-accordion">
    <div class="psource-accordion-item active">
        <button class="psource-accordion-header"><?php esc_html_e('Appearance', 'newsletter'); ?></button>
        <div class="psource-accordion-content">
            <?php $fields->select('type', __('Type', 'newsletter'), [
                '1' => __('Round colored', 'newsletter'),
                '2' => __('Round monochrome', 'newsletter'),
                '3' => __('White logo', 'newsletter'),
                '4' => __('Black logo', 'newsletter')
            ]) ?>

            <?php $fields->select('width', __('Size', 'newsletter'), [
                '16' => '16 px',
                '24' => '24 px',
                '32' => '32 px'
            ]) ?>
        </div>
    </div>

    <div class="psource-accordion-item">
        <button class="psource-accordion-header"><?php esc_html_e('Commons', 'newsletter'); ?></button>
        <div class="psource-accordion-content">
            <?php $fields->block_commons() ?>
        </div>
    </div>
</div>
