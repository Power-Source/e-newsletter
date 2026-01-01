<?php
/* @var $options array contains all the options the current block we're ediging contains */
/* @var $controls NewsletterControls */
/* @var $fields NewsletterFields */
?>
<p>
    <?php
    printf(
        esc_html__('We suggest %s to get all images you need directly on the media gallery.', 'newsletter'),
        '<a href="https://wordpress.org/plugins/instant-images/" target="_blank">Instant Images</a>'
    );
    ?>
</p>

<?php $controls->hidden('placeholder') ?>

<div class="psource-accordion">
    <div class="psource-accordion-item active">
        <button class="psource-accordion-header"><?php esc_html_e('Media gallery', 'newsletter'); ?></button>
        <div class="psource-accordion-content">
            <?php $fields->media('image', __('Choose an image', 'newsletter'), ['alt' => true]) ?>
        </div>
    </div>

    <div class="psource-accordion-item">
        <button class="psource-accordion-header"><?php esc_html_e('External URL', 'newsletter'); ?></button>
        <div class="psource-accordion-content">
            <p>
                <?php esc_html_e('Use a direct image URL to external services', 'newsletter'); ?>
                (<?php printf(__('for example %s.', 'newsletter'), '<a href="https://niftyimages.com/" target="_blank">niftyimages.com</a>'); ?>)
                <strong><?php esc_html_e('It has priority over the media selected from your gallery.', 'newsletter'); ?></strong>
            </p>
            <?php $fields->url('image-url', __('Image URL', 'newsletter')) ?>
            <?php $fields->text('image-alt', __('Alternative text', 'newsletter')) ?>
        </div>
    </div>

    <div class="psource-accordion-item">
        <button class="psource-accordion-header"><?php esc_html_e('Link and appearance', 'newsletter'); ?></button>
        <div class="psource-accordion-content">
            <?php $fields->url('url', __('Link URL', 'newsletter')) ?>
            <div class="tnp-field-row">
                <div class="tnp-field-col-2">
                    <?php $fields->size('width', __('Width', 'newsletter')) ?>
                </div>
                <div class="tnp-field-col-2">
                    <?php $fields->align(); ?>
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


