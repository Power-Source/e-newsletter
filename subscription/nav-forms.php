<?php
$p = sanitize_key($_GET['page'] ?? '');
?>
<ul class="tnp-nav">
    <li class="tnp-nav-title"><?php esc_html_e('Forms', 'newsletter'); ?></li>
    <li class="<?php echo $p === 'newsletter_subscription_sources' ? 'active' : '' ?>">
        <a href="?page=newsletter_subscription_sources"><?php esc_html_e('All', 'newsletter'); ?></a>
    </li>
    <li class="<?php echo $p === 'newsletter_subscription_form' ? 'active' : '' ?>">
        <a href="?page=newsletter_subscription_form"><?php esc_html_e('Standard', 'newsletter'); ?></a>
    </li>
    <li class="<?php echo $p === 'newsletter_subscription_inject' ? 'active' : '' ?>">
        <a href="?page=newsletter_subscription_inject"><?php esc_html_e('Inside posts', 'newsletter'); ?></a>
    </li>
    <li class="<?php echo $p === 'newsletter_subscription_popup' ? 'active' : '' ?>">
        <a href="?page=newsletter_subscription_popup"><?php esc_html_e('Popup', 'newsletter'); ?></a>
    </li>
    <li class="<?php echo $p === 'newsletter_subscription_shortcodes' ? 'active' : '' ?>">
        <a href="?page=newsletter_subscription_shortcodes"><?php esc_html_e('Shortcodes and Widgets', 'newsletter'); ?></a>
    </li>
    <li class="<?php echo $p === 'newsletter_subscription_forms' ? 'active' : '' ?>">
        <a href="?page=newsletter_subscription_forms"><?php esc_html_e('HTML Forms', 'newsletter'); ?></a>
    </li>
    <?php if (class_exists('NewsletterLeads')) { ?>
    <li class="<?php echo $p === 'newsletter_leads_index' ? 'active' : '' ?>">
        <a href="?page=newsletter_leads_index"><?php esc_html_e('Leads Addon', 'newsletter'); ?></a>
    </li>
    <?php } ?>
</ul>
<?php
unset($p);
?>