<?php
/* @var $options array contains all the options the current block we're ediging contains */
/* @var $controls NewsletterControls */
/* @var $fields NewsletterFields */

$extensions_url = '?page=newsletter_main_extension';
if (class_exists('NewsletterExtensions')) {
    $extensions_url = '?page=newsletter_extensions_index';
}

// https://developer.wordpress.org/reference/classes/wp_user_query/
$authors = get_users(['has_published_posts' => ['post'], 'number' => 50, 'fields' => ['ID', 'display_name']]);
$authors_options = ['' => 'All'];
foreach ($authors as $author) {
    $authors_options[(string) $author->ID] = $author->display_name;
}
if (NEWSLETTER_DEBUG) {
    $authors_options['-1'] = 'Test no valid author';
}
?>
<p>
    Custom post types can be added using our <a href="<?php echo $extensions_url ?>" target="_blank">Advanced Composer Blocks Addon</a>.
</p>

<?php
$fields->select('layout', __('Layout', 'newsletter'),
        [
            'one' => __('One column', 'newsletter'),
            'one-2' => __('One column variant', 'newsletter'),
            'two' => __('Two columns', 'newsletter'),
            'big-image' => __('One column, big image', 'newsletter'),
            'full-post' => __('Full post', 'newsletter')
        ]);
?>

<?php
$fields->block_style('', [
    'default' => __('Default', 'newsletter'),
    'inverted' => __('Inverted', 'newsletter'),
    'boxed' => __('Boxed', 'newsletter'),
])
?>

<div class="psource-accordion">

    <?php if ($context['type'] == 'automated') { ?>
        <div class="psource-accordion-item active">
            <button class="psource-accordion-header"><?php esc_html_e('Automated', 'newsletter'); ?></button>
            <div class="psource-accordion-content">
                <p>
                    <?php esc_html_e('While composing all posts are shown while on sending posts are extracted following the rules below.', 'newsletter'); ?>
                    <a href="https://cp-psource.github.io/e-newsletter/automated/#how-it-works-dynamic-blocks" target="_blank"><?php esc_html_e('Read more', 'newsletter'); ?></a>.
                </p>
                <?php $fields->select('automated_disabled', '', ['' => __('Use the last newsletter date and...', 'newsletter'), '1' => __('Do not consider the last newsletter', 'newsletter')]) ?>

                <div class="tnp-field-row">
                    <div class="tnp-field-col-2">
                        <?php
                        $fields->select('automated_include', __('If there are new posts', 'newsletter'),
                                [
                                    'new' => __('Include only new posts', 'newsletter'),
                                    'max' => __('Include specified max posts', 'newsletter')
                                ],
                                ['description' => '', 'class' => 'tnp-small'])
                        ?>
                    </div>
                    <div class="tnp-field-col-2">
                        <?php
                        $fields->select('automated', __('If there are not new posts', 'newsletter'),
                                [
                                    '' => __('Show the message below', 'newsletter'),
                                    '1' => __('Do not send the newsletter', 'newsletter'),
                                    '2' => __('Remove this block', 'newsletter')
                                ],
                                ['description' => '', 'class' => 'tnp-small'])
                        ?>
                        <?php $fields->text('automated_no_contents', null, ['placeholder' => __('No new posts message', 'newsletter')]) ?>
                    </div>
                </div>
                <div style="clear: both"></div>

                <?php $fields->text('main_title', __('Title', 'newsletter')) ?>
                <?php $fields->font('main_title_font', false, ['family_default' => true, 'size_default' => true, 'weight_default' => true]) ?>
                <?php $fields->align('main_title_align') ?>
            </div>
        </div>
    <?php } ?>

    <div class="psource-accordion-item">
        <button class="psource-accordion-header"><?php esc_html_e('Elements', 'newsletter'); ?></button>
        <div class="psource-accordion-content">
            <div class="tnp-field-row">
                <label class="tnp-row-label"><?php esc_html_e('Post info', 'newsletter') ?></label>
                <div class="tnp-field-col-4">
                    <?php $fields->yesno('show_date', __('Date', 'newsletter')) ?>
                </div>
                <div class="tnp-field-col-4">
                    <?php $fields->yesno('show_author', __('Author', 'newsletter')) ?>
                </div>
                <div class="tnp-field-col-4">
                    <?php $fields->yesno('show_image', __('Image', 'newsletter')) ?>
                </div>
                <div class="tnp-field-col-4">
                    <?php $fields->yesno('image_crop', __('Image crop', 'newsletter')) ?>
                </div>
                <div style="clear: both"></div>
            </div>
            <div class="tnp-field-row">
                <div class="tnp-field-col-4">
                    <?php $fields->number('excerpt_length', __('Excerpt length', 'newsletter'), array('min' => 0)); ?>
                </div>
                <div class="tnp-field-col-4">
                    <?php $fields->select('excerpt_length_type', __('Count', 'newsletter'), ['' => __('Words', 'newsletter'), 'chars' => __('Chars', 'newsletter')]); ?>
                </div>
                <div class="tnp-field-col-4">
                    <?php $fields->yesno('show_read_more_button', __('Button', 'newsletter')); ?>
                </div>
                <div class="tnp-field-col-4"></div>
                <div style="clear: both"></div>
            </div>
        </div>
    </div>

    <div class="psource-accordion-item">
        <button class="psource-accordion-header"><?php esc_html_e('Filters', 'newsletter'); ?></button>
        <div class="psource-accordion-content">
            <div class="tnp-field-row">
                <div class="tnp-field-col-4">
                    <?php $fields->select_number('max', __('Max posts', 'newsletter'), 1, 40); ?>
                </div>
                <div class="tnp-field-col-4">
                    <?php $fields->select_number('post_offset', __('Posts offset', 'newsletter'), 0, 20); ?>
                </div>
                <div class="tnp-field-col-4">
                    <?php $fields->yesno('private', __('Private', 'newsletter')) ?>
                </div>
                <div class="tnp-field-col-4">
                    <?php $fields->yesno('reverse', __('Reverse', 'newsletter')) ?>
                </div>
                <div style="clear: both"></div>
            </div>
            <div class="tnp-field-row">
                <div class="tnp-field-col-2">
                    <?php $fields->select('author', __('Author', 'newsletter'), $authors_options) ?>
                </div>
                <div class="tnp-field-col-2">
                    <?php $fields->language('language', __('Language', 'newsletter')); ?>
                </div>
                <div style="clear: both"></div>
            </div>
        </div>
    </div>

    <div class="psource-accordion-item">
        <button class="psource-accordion-header"><?php esc_html_e('Categories and tags', 'newsletter'); ?></button>
        <div class="psource-accordion-content">
            <?php $fields->categories(); ?>
            <?php $fields->text('tags', __('Tags', 'newsletter'), ['description' => __('Comma separated', 'newsletter')]); ?>
        </div>
    </div>

    <div class="psource-accordion-item">
        <button class="psource-accordion-header"><?php esc_html_e('Texts and buttons', 'newsletter'); ?></button>
        <div class="psource-accordion-content">
            <?php $fields->font('title_font', __('Title font', 'newsletter'), ['family_default' => true, 'size_default' => true, 'weight_default' => true]) ?>
            <?php $fields->font('font', __('Excerpt font', 'newsletter'), ['family_default' => true, 'size_default' => true, 'weight_default' => true]) ?>
            <?php
            $fields->button('button', __('Read more button', 'newsletter'), [
                'url' => false,
                'family_default' => true,
                'size_default' => true,
                'weight_default' => true
            ])
            ?>
        </div>
    </div>

    <div class="psource-accordion-item">
        <button class="psource-accordion-header"><?php esc_html_e('Commons', 'newsletter'); ?></button>
        <div class="psource-accordion-content">
            <?php $fields->padding('text_padding', __('Text padding', 'newsletter'), ['description' => __('Supported only by some layouts', 'newsletter'), 'show_top' => false, 'show_bottom' => false]) ?>
            <?php $fields->block_commons() ?>
        </div>
    </div>
</div>