<?php
/* @var $this NewsletterArchive */
require_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

if (!$controls->is_action()) {
    $controls->data = $this->options;
} else {
    if ($controls->is_action('save')) {
        $this->save_options($controls->data);
        $controls->add_toast_saved();
    }
}
?>

<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER; ?>
    <div id="tnp-heading">
        <h2>Newsletter Archive</h2>
    </div>

    <div id="tnp-body">
        <?php $controls->show(); ?>

        <p>
            <strong><?php _e('Embed the newsletter archive', 'newsletter'); ?></strong><br>
            <?php _e('Add the following shortcode to any WordPress page to display an archive of all sent newsletters:', 'newsletter'); ?>
        </p>
        <div style="margin-bottom:10px;">
            <input type="text" value="[newsletter_archive /]" id="archive-shortcode" readonly style="width:220px; font-family:monospace;">
            <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('archive-shortcode').value)"><?php _e('Copy', 'newsletter'); ?></button>
        </div>

        <p>
            <strong><?php _e('Example with an introduction text:', 'newsletter'); ?></strong><br>
            <code>
                [newsletter_archive]<br>
                <?php _e('Welcome to our newsletter archive!', 'newsletter'); ?><br>
                [/newsletter_archive]
            </code>
            <br>
            <?php _e('The introduction text is only shown in the list view.', 'newsletter'); ?>
        </p>

        <p>
            <strong><?php _e('Shortcode attributes:', 'newsletter'); ?></strong><br>
            <ul>
                <li><code>max</code> – <?php _e('Maximum number of newsletters to display (e.g. <code>[newsletter_archive max="10" /]</code>)', 'newsletter'); ?></li>
                <li><code>list</code> – <?php _e('Show only newsletters sent to a specific list (e.g. <code>[newsletter_archive list="1" /]</code>)', 'newsletter'); ?></li>
                <li><code>type</code> – <?php _e('Show only newsletters of a certain type, e.g. for Automated Addon: <code>[newsletter_archive type="automated_1"]</code>', 'newsletter'); ?></li>
                <li><code>show_date</code> – <?php _e('Show the date (<code>true</code> or <code>false</code>)', 'newsletter'); ?></li>
                <li><code>separator</code> – <?php _e('Separator between date and title (e.g. <code>[newsletter_archive separator=" | " /]</code>)', 'newsletter'); ?></li>
                <li><code>title</code> – <?php _e('Title for the list as H2', 'newsletter'); ?></li>
            </ul>
        </p>

        <p>
            <strong><?php _e('Note:', 'newsletter'); ?></strong><br>
            <?php _e('If you have issues with the embedded view (for example, due to a page builder or theme), you can set the archive to open newsletters in a new tab in the addon settings.', 'newsletter'); ?>
        </p>

        <form action="" method="post">
            <?php $controls->init(); ?>

            <table class="form-table">
                <tr valign="top">
                    <th><?php _e('Show newsletter date?', 'newsletter'); ?></th>
                    <td>
                        <?php $controls->checkbox('date'); ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th><?php _e('Showing the newsletter', 'newsletter'); ?></th>
                    <td>
                        <?php $controls->select('show', ['' => __('Embedded the same page', 'newsletter'), 'blank' => __('In a new browser page', 'newsletter'), 'self' => __('In the same browser page', 'newsletter')]); ?>
                        <p class="description">
                            <?php _e('Some page builder or some page filters do not allow to show the newsletter in the same page: use an alternative option.', 'newsletter'); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <p>
                <?php $controls->button_save(); ?>
            </p>
        </form>
    </div>
    
</div>