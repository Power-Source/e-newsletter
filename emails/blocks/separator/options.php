<?php
/* @var $fields NewsletterFields */
?>

<div class="psource-accordion">
    <div class="psource-accordion-item active">
        <button class="psource-accordion-header"><?php esc_html_e('Appearance', 'newsletter'); ?></button>
        <div class="psource-accordion-content">
            <div class="tnp-field-row">
                <div class="tnp-field-col-2">
                    <?php $fields->color('color', __('Color', 'newsletter')) ?>
                </div>
                <div class="tnp-field-col-2">
                    <?php $fields->select('height', __('Height', 'newsletter'), array('0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9)) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="psource-accordion-item">
        <button class="psource-accordion-header"><?php esc_html_e('Commons', 'newsletter'); ?></button>
        <div class="psource-accordion-content">
            <?php $fields->block_commons() ?>
        </div>
    </div>
</div>