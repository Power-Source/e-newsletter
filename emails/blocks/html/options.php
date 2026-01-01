<?php

/* @var $options array contains all the options the current block we're ediging contains */
/* @var $controls NewsletterControls */


$default_options = array(
    'block_background' => '#ffffff',
);

$options = array_merge($default_options, $options);
?>

<style>
    .CodeMirror {
        height: 400px;
    }
</style>

<script>
    var templateEditor;
    jQuery(function () {
        templateEditor = CodeMirror.fromTextArea(document.getElementById("options-html"), {
            lineNumbers: true,
            mode: 'htmlmixed',
            lineWrapping: true,
            //extraKeys: {"Ctrl-Space": "autocomplete"}
        });
    });
</script>

<div class="psource-accordion">
    <div class="psource-accordion-item active">
        <button class="psource-accordion-header"><?php esc_html_e('Appearance', 'newsletter'); ?></button>
        <div class="psource-accordion-content">
            <p>
                <a href="https://cp-psource.github.io/e-newsletter/newsletter-tags/"
                target="_blank"><?php esc_html_e('You can use tags to inject subscriber fields', 'newsletter'); ?></a>.
            </p>
            <table class="form-table">
                <tr>
                    <td>
                        <?php $controls->textarea('html', '100%', '300px') ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="psource-accordion-item">
        <button class="psource-accordion-header"><?php esc_html_e('Commons', 'newsletter'); ?></button>
        <div class="psource-accordion-content">
            <?php $fields->block_commons() ?>
        </div>
    </div>
</div>
