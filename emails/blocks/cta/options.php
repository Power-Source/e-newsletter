<?php
/* @var $fields NewsletterFields */

?>

<?php $fields->block_style('', ['default' => 'Default', 'wire' => 'Wire', 'inverted' => 'Inverted']) ?>

<div class="psource-accordion">
    <div class="psource-accordion-item active">
        <button class="psource-accordion-header"><?php esc_html_e('Appearance', 'newsletter'); ?></button>
        <div class="psource-accordion-content">
            <?php if ($context['type'] === 'confirmation') { ?>
                <p>Use {confirmation_url} as URL for the button</p>
            <?php } ?>

            <?php
            $fields->button('button', 'Button layout', [
                'family_default' => true,
                'size_default' => true,
                'weight_default' => true,
                'media' => true
            ])
            ?>

            <div class="tnp-field-row">
                <div class="tnp-field-col-2">
                    <?php $fields->size('button_width', __('Width', 'newsletter')) ?>
                </div>
                <div class="tnp-field-col-2">
                    <?php $fields->select('button_align', __('Alignment', 'newsletter'), ['center' => __('Center'), 'left' => __('Left'), 'right' => __('Right')]) ?>
                </div>
                <div style="clear: both"></div>
            </div>
        </div>
    </div>

    <div class="psource-accordion-item">
        <button class="psource-accordion-header"><?php esc_html_e('Lists', 'newsletter'); ?></button>
        <div class="psource-accordion-content">
            <p>List changes on click.</p>
            <div class="tnp-field-row">
                <div class="tnp-field-col-2">
                    <?php $fields->lists_public('list', 'Add to', ['empty_label' => 'None']) ?>
                </div>
                <div class="tnp-field-col-2">
                    <?php $fields->lists_public('unlist', 'Remove from', ['empty_label' => 'None']) ?>
                </div>
                <div style="clear: both"></div>
                <?php if (!method_exists('NewsletterReports', 'build_lists_change_url')) { ?>
                    <label class="tnpf-row-label">Requires the Reports Addon last version</label>
                <?php } ?>
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

