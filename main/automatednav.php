<?php
defined('ABSPATH') || exit;
?>
<h2><?php echo esc_html($channel['name'] ?? ''); ?></h2>
<ul class="tnp-nav">
    <li class="<?php echo $_GET['page'] === ''?'active':''?>"><a href="?page=newsletter_main_automatedindex">&laquo;</a></li>
    <li class="<?php echo $_GET['page'] === 'newsletter_main_automatededit'?'active':''?>"><a href="?page=newsletter_main_automatededit&id=<?php echo urlencode((string)($channel['id'] ?? '')); ?>"><?php _e('Settings', 'newsletter')?></a></li>
    <li class="<?php echo $_GET['page'] === 'newsletter_main_automatedtemplate'?'active':''?>"><a href="?page=newsletter_main_automatedtemplate&id=<?php echo urlencode((string)($channel['id'] ?? '')); ?>"><?php _e('Template', 'newsletter')?></a></li>
    <li class="<?php echo $_GET['page'] === 'newsletter_main_automatednewsletters'?'active':''?>"><a href="?page=newsletter_main_automatednewsletters&id=<?php echo urlencode((string)($channel['id'] ?? '')); ?>"><?php _e('Newsletters', 'newsletter')?></a></li>
</ul>
