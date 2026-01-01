<?php
/* @var $fields NewsletterFields */
?>

<?php
$fields->select('editor', __('Editor', 'newsletter'), [
    'default' => __('Default', 'newsletter'),
    'full' => __('Full', 'newsletter'),
], ['reload' => true]);

$background = $options['block_background'] ?? '#aaa';
$color = $options['font_color'] ?? '#fff';

?>

<div class="psource-accordion">
    <div class="psource-accordion-item active">
        <button class="psource-accordion-header"><?php esc_html_e('Appearance', 'newsletter'); ?></button>
        <div class="psource-accordion-content">
            <?php if ($options['editor'] === 'full') { ?>
                <?php $fields->wp_editor_simple('text', __('Text', 'newsletter'), ['background' => $background, 'color' => $color]); ?>
            <?php } else { ?>
                <?php $fields->textarea('text', __('Text', 'newsletter')); ?>
            <?php } ?>
            <?php $fields->font('font', false, ['family_default' => true, 'size_default' => true, 'weight_default' => true]); ?>
            <?php $fields->align(); ?>
        </div>
    </div>

    <div class="psource-accordion-item">
        <button class="psource-accordion-header"><?php esc_html_e('Commons', 'newsletter'); ?></button>
        <div class="psource-accordion-content">
            <?php $fields->block_commons() ?>
        </div>
    </div>
</div>
