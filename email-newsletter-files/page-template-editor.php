<?php
global $email_newsletter;

// Security check
if(!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Get template parameter
$stylesheet = isset($_GET['template']) ? sanitize_text_field($_GET['template']) : '';

if(empty($stylesheet)) {
    wp_die(__('Vorlage nicht angegeben', 'email-newsletter'));
}

// Register theme directories and find template
$email_newsletter->register_enewsletter_themes();
$theme = wp_get_theme($stylesheet);

if(!$theme->exists()) {
    wp_die(__('Vorlage nicht gefunden', 'email-newsletter'));
}

// Verify it's actually a newsletter template
if($theme->theme_root != $email_newsletter->template_directory && $theme->theme_root != $email_newsletter->template_custom_directory) {
    wp_die(__('Vorlage nicht gefunden', 'email-newsletter'));
}

// Check if it's a custom template
$is_custom = ($theme->theme_root === $email_newsletter->template_custom_directory);

if(!$is_custom) {
    wp_die(__('Nur Custom-Vorlagen können bearbeitet werden. Bitte klone diese Vorlage zuerst.', 'email-newsletter'));
}

// Ensure media library scripts are available for media pickers
wp_enqueue_script('jquery');
if(function_exists('wp_enqueue_media')) {
    wp_enqueue_media();
} else {
    wp_enqueue_script('media-upload');
    wp_enqueue_script('thickbox');
    wp_enqueue_style('thickbox');
}

// Get template directory
$template_dir = $theme->get_stylesheet_directory();
$template_html_file = $template_dir . '/template.html';
$style_css_file = $template_dir . '/style.css';
$style_header_css_file = $template_dir . '/style_header.css';
$index_php_file = $template_dir . '/index.php';

// Load file content
$template_html_content = file_exists($template_html_file) ? file_get_contents($template_html_file) : '';
$style_css_content = file_exists($style_css_file) ? file_get_contents($style_css_file) : '';
$style_header_css_content = file_exists($style_header_css_file) ? file_get_contents($style_header_css_file) : '';
$index_php_content = file_exists($index_php_file) ? file_get_contents($index_php_file) : '';

// Parse index.php settings
$builder_settings = array(
    'bg_color' => '#ffffff',
    'bg_image' => '',
    'header_image' => '',
    'link_color' => '#0073aa',
    'body_color' => '#333333',
    'alternative_color' => '#666666',
    'title_color' => '#000000',
    'email_title' => 'Standard-E-Mail-Titel',
    'use_default_header_footer' => true,
    'use_default_styles' => true,
);

if(!empty($index_php_content)) {
    // Parse define statements
    if(preg_match("/define\('BUILDER_DEFAULT_BG_COLOR',\s*'([^']+)'\)/", $index_php_content, $m)) {
        $builder_settings['bg_color'] = $m[1];
    }
    if(preg_match("/define\('BUILDER_DEFAULT_BG_IMAGE',\s*'([^']+)'\)/", $index_php_content, $m)) {
        $builder_settings['bg_image'] = $m[1];
    }
    if(preg_match("/define\('BUILDER_DEFAULT_HEADER_IMAGE',\s*'([^']+)'\)/", $index_php_content, $m)) {
        $builder_settings['header_image'] = $m[1];
    }
    if(preg_match("/define\('BUILDER_DEFAULT_LINK_COLOR',\s*'([^']+)'\)/", $index_php_content, $m)) {
        $builder_settings['link_color'] = $m[1];
    }
    if(preg_match("/define\('BUILDER_DEFAULT_BODY_COLOR',\s*'([^']+)'\)/", $index_php_content, $m)) {
        $builder_settings['body_color'] = $m[1];
    }
    if(preg_match("/define\('BUILDER_DEFAULT_ALTERNATIVE_COLOR',\s*'([^']+)'\)/", $index_php_content, $m)) {
        $builder_settings['alternative_color'] = $m[1];
    }
    if(preg_match("/define\('BUILDER_DEFAULT_TITLE_COLOR',\s*'([^']+)'\)/", $index_php_content, $m)) {
        $builder_settings['title_color'] = $m[1];
    }
    if(preg_match("/define\('BUILDER_DEFAULT_EMAIL_TITLE',\s*'([^']+)'\)/", $index_php_content, $m)) {
        $builder_settings['email_title'] = $m[1];
    }
    if(preg_match("/define\('BUILDER_SETTING_USE_DEFAULT_HEADER_FOOTER',\s*(true|false)\)/", $index_php_content, $m)) {
        $builder_settings['use_default_header_footer'] = ($m[1] === 'true');
    }
    if(preg_match("/define\('BUILDER_SETTING_USE_DEFAULT_STYLES',\s*(true|false)\)/", $index_php_content, $m)) {
        $builder_settings['use_default_styles'] = ($m[1] === 'true');
    }
}

// Default Lorem Ipsum content
$default_lorem = '<h2>Lorem Ipsum</h2><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>';

if(empty($template_html_content)) {
    $template_html_content = '<table><tr><td>{CONTENT}</td></tr></table>';
}

// Load branding settings from global plugin settings
$branding_settings = array(
    'company_name' => isset($email_newsletter->settings['company_name']) ? $email_newsletter->settings['company_name'] : get_bloginfo('name'),
    'address' => isset($email_newsletter->settings['address']) ? $email_newsletter->settings['address'] : '',
    'phone' => isset($email_newsletter->settings['phone']) ? $email_newsletter->settings['phone'] : '',
    'email' => isset($email_newsletter->settings['email']) ? $email_newsletter->settings['email'] : get_bloginfo('admin_email'),
    'website' => isset($email_newsletter->settings['website']) ? $email_newsletter->settings['website'] : get_bloginfo('url'),
    'facebook' => isset($email_newsletter->settings['facebook']) ? $email_newsletter->settings['facebook'] : '',
    'twitter' => isset($email_newsletter->settings['twitter']) ? $email_newsletter->settings['twitter'] : '',
    'instagram' => isset($email_newsletter->settings['instagram']) ? $email_newsletter->settings['instagram'] : '',
    'linkedin' => isset($email_newsletter->settings['linkedin']) ? $email_newsletter->settings['linkedin'] : '',
    'youtube' => isset($email_newsletter->settings['youtube']) ? $email_newsletter->settings['youtube'] : '',
    'branding_layout' => isset($email_newsletter->settings['branding_layout']) ? $email_newsletter->settings['branding_layout'] : 'footer',
);

// Parse settings from CSS
$settings = array(
    'body_bg_color' => '#ffffff',
    'body_text_color' => '#333333',
    'body_font_size' => '14px',
    'body_font_family' => 'Arial, sans-serif',
    'heading_color' => '#000000',
    'link_color' => '#0073aa',
    'button_bg_color' => '#0073aa',
    'button_text_color' => '#ffffff',
    'header_bg_color' => '#f5f5f5',
    'footer_bg_color' => '#f5f5f5',
    'container_padding' => '20px',
    'container_margin' => '0px',
);

$theme_url_data = $email_newsletter->get_theme_dir_url($theme, $stylesheet);
$template_url = trailingslashit($theme_url_data['url']);
?>

<div class="wrap template-editor-wrap">
    <h1><?php echo esc_html($theme->get('Name')); ?> - <?php _e('Editor', 'email-newsletter'); ?></h1>
    
    <div class="editor-container">
        
        <!-- Left Sidebar: Template Settings -->
        <div class="editor-sidebar left-sidebar">
            
            <!-- Accordion Section: Template Einstellungen -->
            <div class="sidebar-accordion-section">
                <h3 class="accordion-header active">
                    <span class="dashicons dashicons-admin-appearance"></span>
                    <?php _e('Template Einstellungen', 'email-newsletter'); ?>
                    <span class="accordion-toggle dashicons dashicons-arrow-up-alt2"></span>
                </h3>
                
                <div class="accordion-content" style="display: block;">
                    <div class="template-settings-form">
                    <div class="setting-group">
                        <label><?php _e('Hintergrundfarbe:', 'email-newsletter'); ?></label>
                        <input type="color" id="builder-bg-color" class="builder-color-input" value="<?php echo esc_attr($builder_settings['bg_color']); ?>">
                    </div>
                    
                    <div class="setting-group">
                        <label><?php _e('Hintergrundbild:', 'email-newsletter'); ?></label>
                        <button type="button" class="button button-small select-bg-image-btn">
                            <span class="dashicons dashicons-format-image"></span>
                            <?php _e('Bild wählen', 'email-newsletter'); ?>
                        </button>
                        <input type="text" id="builder-bg-image" class="builder-text-input" value="<?php echo esc_attr($builder_settings['bg_image']); ?>" placeholder="images/bg.jpg">
                    </div>
                    
                    <div class="setting-group">
                        <label><?php _e('Header Bild:', 'email-newsletter'); ?></label>
                        <button type="button" class="button button-small select-header-image-btn">
                            <span class="dashicons dashicons-format-image"></span>
                            <?php _e('Bild wählen', 'email-newsletter'); ?>
                        </button>
                        <input type="text" id="builder-header-image" class="builder-text-input" value="<?php echo esc_attr($builder_settings['header_image']); ?>" placeholder="images/header.jpg">
                    </div>
                    
                    <div class="setting-group">
                        <label><?php _e('Link Farbe:', 'email-newsletter'); ?></label>
                        <input type="color" id="builder-link-color" class="builder-color-input" value="<?php echo esc_attr($builder_settings['link_color']); ?>">
                    </div>
                    
                    <div class="setting-group">
                        <label><?php _e('Text Farbe:', 'email-newsletter'); ?></label>
                        <input type="color" id="builder-body-color" class="builder-color-input" value="<?php echo esc_attr($builder_settings['body_color']); ?>">
                    </div>
                    
                    <div class="setting-group">
                        <label><?php _e('Alternative Farbe:', 'email-newsletter'); ?></label>
                        <input type="color" id="builder-alternative-color" class="builder-color-input" value="<?php echo esc_attr($builder_settings['alternative_color']); ?>">
                    </div>
                    
                    <div class="setting-group">
                        <label><?php _e('Titel Farbe:', 'email-newsletter'); ?></label>
                        <input type="color" id="builder-title-color" class="builder-color-input" value="<?php echo esc_attr($builder_settings['title_color']); ?>">
                    </div>
                    
                    <div class="setting-group">
                        <label><?php _e('Standard E-Mail Titel:', 'email-newsletter'); ?></label>
                        <input type="text" id="builder-email-title" class="builder-text-input" value="<?php echo esc_attr($builder_settings['email_title']); ?>">
                    </div>
                    
                    <div class="setting-group">
                        <label>
                            <input type="checkbox" id="builder-use-header-footer" class="builder-checkbox" <?php checked($builder_settings['use_default_header_footer']); ?>>
                            <?php _e('Standard Header/Footer', 'email-newsletter'); ?>
                        </label>
                    </div>
                    
                    <div class="setting-group">
                        <label>
                            <input type="checkbox" id="builder-use-styles" class="builder-checkbox" <?php checked($builder_settings['use_default_styles']); ?>>
                            <?php _e('Standard Styles', 'email-newsletter'); ?>
                        </label>
                    </div>
                    
                    <button type="button" class="button button-primary save-builder-settings-btn" style="width: 100%; margin-top: 10px;">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('Einstellungen speichern', 'email-newsletter'); ?>
                    </button>
                </div>
                </div>
            </div>
            
            <!-- Accordion Section: Branding & Kontakt -->
            <div class="sidebar-accordion-section">
                <h3 class="accordion-header">
                    <span class="dashicons dashicons-id"></span>
                    <?php _e('Branding & Kontakt', 'email-newsletter'); ?>
                    <span class="accordion-toggle dashicons dashicons-arrow-down-alt2"></span>
                </h3>
                
                <div class="accordion-content" style="display: none;">
                <div class="branding-settings-form">
                    <div class="setting-group">
                        <label><?php _e('Firmenname:', 'email-newsletter'); ?></label>
                        <input type="text" id="branding-company-name" class="builder-text-input" value="<?php echo esc_attr($branding_settings['company_name']); ?>" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                    </div>
                    
                    <div class="setting-group">
                        <label><?php _e('Adresse:', 'email-newsletter'); ?></label>
                        <textarea id="branding-address" class="builder-textarea" rows="2" placeholder="Musterstraße 123, 12345 Stadt"><?php echo esc_textarea($branding_settings['address']); ?></textarea>
                    </div>
                    
                    <div class="setting-group">
                        <label><?php _e('Telefon:', 'email-newsletter'); ?></label>
                        <input type="text" id="branding-phone" class="builder-text-input" value="<?php echo esc_attr($branding_settings['phone']); ?>" placeholder="+49 123 456789">
                    </div>
                    
                    <div class="setting-group">
                        <label><?php _e('E-Mail:', 'email-newsletter'); ?></label>
                        <input type="email" id="branding-email" class="builder-text-input" value="<?php echo esc_attr($branding_settings['email']); ?>" placeholder="<?php echo esc_attr(get_bloginfo('admin_email')); ?>">
                    </div>
                    
                    <div class="setting-group">
                        <label><?php _e('Website:', 'email-newsletter'); ?></label>
                        <input type="url" id="branding-website" class="builder-text-input" value="<?php echo esc_attr($branding_settings['website']); ?>" placeholder="https://example.com">
                    </div>
                    
                    <div class="setting-group">
                        <label><?php _e('Layout:', 'email-newsletter'); ?></label>
                        <select id="branding-layout" class="builder-select">
                            <option value="footer" <?php selected($branding_settings['branding_layout'], 'footer'); ?>><?php _e('Footer (Standard)', 'email-newsletter'); ?></option>
                            <option value="two-column" <?php selected($branding_settings['branding_layout'], 'two-column'); ?>><?php _e('Zweispaltig', 'email-newsletter'); ?></option>
                            <option value="centered" <?php selected($branding_settings['branding_layout'], 'centered'); ?>><?php _e('Zentriert', 'email-newsletter'); ?></option>
                        </select>
                    </div>
                    
                    <div class="setting-group">
                        <label><?php _e('Soziale Netzwerke:', 'email-newsletter'); ?></label>
                        <div class="social-inputs">
                            <input type="url" id="branding-facebook" class="builder-text-input social-input" value="<?php echo esc_attr($branding_settings['facebook']); ?>" placeholder="Facebook URL">
                            <input type="url" id="branding-twitter" class="builder-text-input social-input" value="<?php echo esc_attr($branding_settings['twitter']); ?>" placeholder="Twitter URL">
                            <input type="url" id="branding-instagram" class="builder-text-input social-input" value="<?php echo esc_attr($branding_settings['instagram']); ?>" placeholder="Instagram URL">
                            <input type="url" id="branding-linkedin" class="builder-text-input social-input" value="<?php echo esc_attr($branding_settings['linkedin']); ?>" placeholder="LinkedIn URL">
                            <input type="url" id="branding-youtube" class="builder-text-input social-input" value="<?php echo esc_attr($branding_settings['youtube']); ?>" placeholder="YouTube URL">
                        </div>
                    </div>
                </div>
                </div>
            </div>
            
            <!-- Accordion Section: Aktionen -->
            <div class="sidebar-accordion-section">
                <h3 class="accordion-header">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('Aktionen', 'email-newsletter'); ?>
                    <span class="accordion-toggle dashicons dashicons-arrow-down-alt2"></span>
                </h3>
                
                <div class="accordion-content" style="display: none; padding: 15px;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=newsletters-template-management')); ?>" class="button button-secondary" style="width: 100%; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <span class="dashicons dashicons-arrow-left"></span>
                    <?php _e('Zur Vorlagenliste', 'email-newsletter'); ?>
                </a>
                </div>
            </div>
        </div>
        
        <!-- Center: Editor & Settings -->
        <div class="editor-center">
            
            <!-- Editor Toolbar -->
            <div class="editor-toolbar">
                <div class="file-tabs">
                    <button class="file-tab active" data-file="template.html">
                        <span class="tab-icon">📄</span>
                        template.html
                    </button>
                    <button class="file-tab" data-file="style.css">
                        <span class="tab-icon">🎨</span>
                        style.css
                    </button>
                    <button class="file-tab" data-file="style_header.css">
                        <span class="tab-icon">🎨</span>
                        style_header.css
                    </button>
                </div>
                <div class="toolbar-actions">
                    <button type="button" class="button button-small save-file-btn" data-file="template.html" style="display:inline-block;">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('Speichern', 'email-newsletter'); ?>
                    </button>
                    <button type="button" class="button button-small save-all-css-btn" data-file="css" style="display:none;">
                        <span class="dashicons dashicons-yes"></span>
                        <?php _e('CSS speichern', 'email-newsletter'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Template HTML Editor -->
            <div class="editor-panel template-html-panel active">
                <textarea id="template-html-editor" class="code-editor" data-file="template.html"><?php echo esc_textarea($template_html_content); ?></textarea>
            </div>
            
            <!-- CSS Editor -->
            <div class="editor-panel css-editor-panel">
                <!-- CSS Tabs -->
                <div class="css-tabs">
                    <button class="css-tab active" data-tab="style-css">style.css</button>
                    <button class="css-tab" data-tab="style-header-css">style_header.css</button>
                </div>
                
                <textarea id="style-css-editor" class="code-editor css-editor active" data-file="style.css"><?php echo esc_textarea($style_css_content); ?></textarea>
                <textarea id="style-header-css-editor" class="code-editor css-editor" data-file="style_header.css" style="display:none;"><?php echo esc_textarea($style_header_css_content); ?></textarea>
            </div>
            
            <!-- Settings Panel (currently not used, hidden) -->
            <div class="settings-panel" style="display: none;">
                <div class="editor-header">
                    <h3><?php _e('Design Settings', 'email-newsletter'); ?></h3>
                </div>
                
                <div class="settings-content">
                    
                    <!-- Colors Section -->
                    <div class="settings-section">
                        <h4><?php _e('Farben', 'email-newsletter'); ?></h4>
                        
                        <div class="setting-row">
                            <label><?php _e('Hintergrundfarbe:', 'email-newsletter'); ?></label>
                            <input type="color" class="color-picker" data-setting="body_bg_color" value="<?php echo esc_attr($settings['body_bg_color']); ?>">
                        </div>
                        
                        <div class="setting-row">
                            <label><?php _e('Textfarbe:', 'email-newsletter'); ?></label>
                            <input type="color" class="color-picker" data-setting="body_text_color" value="<?php echo esc_attr($settings['body_text_color']); ?>">
                        </div>
                        
                        <div class="setting-row">
                            <label><?php _e('Link-Farbe:', 'email-newsletter'); ?></label>
                            <input type="color" class="color-picker" data-setting="link_color" value="<?php echo esc_attr($settings['link_color']); ?>">
                        </div>
                        
                        <div class="setting-row">
                            <label><?php _e('Überschriften-Farbe:', 'email-newsletter'); ?></label>
                            <input type="color" class="color-picker" data-setting="heading_color" value="<?php echo esc_attr($settings['heading_color']); ?>">
                        </div>
                        
                        <div class="setting-row">
                            <label><?php _e('Button-Hintergrund:', 'email-newsletter'); ?></label>
                            <input type="color" class="color-picker" data-setting="button_bg_color" value="<?php echo esc_attr($settings['button_bg_color']); ?>">
                        </div>
                        
                        <div class="setting-row">
                            <label><?php _e('Button-Text:', 'email-newsletter'); ?></label>
                            <input type="color" class="color-picker" data-setting="button_text_color" value="<?php echo esc_attr($settings['button_text_color']); ?>">
                        </div>
                    </div>
                    
                    <!-- Spacing Section -->
                    <div class="settings-section">
                        <h4><?php _e('Abstände', 'email-newsletter'); ?></h4>
                        
                        <div class="setting-row">
                            <label><?php _e('Container Padding:', 'email-newsletter'); ?></label>
                            <input type="text" class="spacing-input" data-setting="container_padding" value="<?php echo esc_attr($settings['container_padding']); ?>">
                        </div>
                        
                        <div class="setting-row">
                            <label><?php _e('Container Margin:', 'email-newsletter'); ?></label>
                            <input type="text" class="spacing-input" data-setting="container_margin" value="<?php echo esc_attr($settings['container_margin']); ?>">
                        </div>
                    </div>
                    
                    <!-- Typography Section -->
                    <div class="settings-section">
                        <h4><?php _e('Typografie', 'email-newsletter'); ?></h4>
                        
                        <div class="setting-row">
                            <label><?php _e('Schriftgröße (Body):', 'email-newsletter'); ?></label>
                            <input type="text" class="spacing-input" data-setting="body_font_size" value="<?php echo esc_attr($settings['body_font_size']); ?>">
                        </div>
                        
                        <div class="setting-row">
                            <label><?php _e('Schriftfamilie:', 'email-newsletter'); ?></label>
                            <select class="typography-select" data-setting="body_font_family">
                                <option value="Arial, sans-serif" <?php selected($settings['body_font_family'], 'Arial, sans-serif'); ?>>Arial</option>
                                <option value="Georgia, serif" <?php selected($settings['body_font_family'], 'Georgia, serif'); ?>>Georgia</option>
                                <option value="Times New Roman, serif" <?php selected($settings['body_font_family'], 'Times New Roman, serif'); ?>>Times New Roman</option>
                                <option value="Courier New, monospace" <?php selected($settings['body_font_family'], 'Courier New, monospace'); ?>>Courier New</option>
                                <option value="Verdana, sans-serif" <?php selected($settings['body_font_family'], 'Verdana, sans-serif'); ?>>Verdana</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Image Section -->
                    <div class="settings-section">
                        <h4><?php _e('Header Bild', 'email-newsletter'); ?></h4>
                        
                        <div class="setting-row">
                            <button type="button" class="button select-header-image-btn">
                                <span class="dashicons dashicons-format-image"></span>
                                <?php _e('Bild wählen', 'email-newsletter'); ?>
                            </button>
                            <input type="hidden" id="header-image-url" data-setting="header_image_url" value="">
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <!-- Right Sidebar: Live Preview -->
        <div class="editor-sidebar right-sidebar">
            <div class="sidebar-section">
                <div class="preview-header">
                    <h3><?php _e('Live-Vorschau', 'email-newsletter'); ?></h3>
                    <button type="button" class="button button-small refresh-preview-btn" title="<?php _e('Vorschau aktualisieren', 'email-newsletter'); ?>">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                </div>
                <div class="live-preview-container">
                    <iframe id="preview-frame" class="preview-frame" srcdoc="<p><?php _e('Laden...', 'email-newsletter'); ?></p>"></iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.template-editor-wrap {
    margin-top: -20px;
    position: relative;
}

#wpfooter {
    display: none !important;
}

#wpcontent {
    padding-bottom: 0 !important;
}

.editor-container {
    display: grid;
    grid-template-columns: 250px 1fr 350px;
    gap: 0;
    height: calc(100vh - 180px);
    margin-top: 20px;
    min-height: 600px;
    position: relative;
    z-index: 1;
}

.editor-sidebar {
    background: #f9f9f9;
    border: 1px solid #ddd;
    overflow-y: auto;
    padding: 15px;
    display: flex;
    flex-direction: column;
}

.left-sidebar {
    border-right: 0;
}

.right-sidebar {
    border-left: 0;
    display: flex;
    flex-direction: column;
}

.right-sidebar .sidebar-section {
    display: flex;
    flex-direction: column;
    flex: 1;
    margin: 0;
}

.sidebar-section {
    margin-bottom: 20px;
}

.sidebar-section h3 {
    margin: 0 0 12px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    color: #666;
}

.sidebar-section hr {
    border: none;
    border-top: 1px solid #ddd;
    margin: 15px 0;
}

.files-list {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.file-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    border-radius: 3px;
    cursor: pointer;
    background: white;
    border: 1px solid #ddd;
    font-size: 13px;
    transition: all 0.2s;
}

.file-item:hover {
    background: #fff;
    border-color: #0073aa;
}

.file-item.active {
    background: #0073aa;
    color: white;
    border-color: #0073aa;
}

.file-icon {
    font-size: 16px;
}

.editor-center {
    background: white;
    border: 1px solid #ddd;
    border-left: 0;
    border-right: 0;
    display: flex;
    flex-direction: column;
    position: relative;
    height: 100%;
}

.editor-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    border-bottom: 1px solid #ddd;
    background: #f9f9f9;
    gap: 15px;
    flex-wrap: wrap;
}

.file-tabs {
    display: flex;
    gap: 5px;
}

.file-tab {
    padding: 8px 15px;
    border: 1px solid #ddd;
    background: white;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    border-radius: 3px 3px 0 0;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    gap: 5px;
}

.file-tab:hover {
    background: #f0f0f0;
}

.file-tab.active {
    background: white;
    border-bottom-color: #0073aa;
    color: #0073aa;
}

.tab-icon {
    font-size: 16px;
}

.toolbar-actions {
    display: flex;
    gap: 10px;
}

.editor-panel {
    display: none;
    flex: 1;
    flex-direction: column;
}

.editor-panel.active {
    display: flex;
}

.template-html-panel {
    flex: 1;
}

.css-editor-panel {
    flex: 1;
}

.editor-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    border-bottom: 1px solid #ddd;
    background: #f5f5f5;
}

.editor-header h3 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.code-editor {
    flex: 1;
    border: none !important;
    padding: 0 !important;
    margin: 0 !important;
    font-family: 'Monaco', 'Courier New', monospace !important;
    font-size: 12px !important;
}

.css-tabs {
    display: flex;
    gap: 0;
    padding: 0 15px;
    border-bottom: 1px solid #ddd;
    background: #f9f9f9;
}

.css-tab {
    padding: 10px 15px;
    border: none;
    background: none;
    cursor: pointer;
    color: #666;
    border-bottom: 3px solid transparent;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
}

.css-tab:hover {
    color: #0073aa;
}

.css-tab.active {
    color: #0073aa;
    border-bottom-color: #0073aa;
}

.settings-panel {
    display: none;
    flex: 1;
    flex-direction: column;
}

.settings-panel.active {
    display: flex;
}

.settings-content {
    flex: 1;
    overflow-y: auto;
    padding: 15px;
}

.settings-section {
    margin-bottom: 25px;
}

.settings-section h4 {
    margin: 0 0 12px;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    color: #666;
    border-bottom: 1px solid #ddd;
    padding-bottom: 8px;
}

.setting-row {
    display: flex;
    flex-direction: column;
    margin-bottom: 12px;
}

.setting-row label {
    font-size: 12px;
    font-weight: 500;
    margin-bottom: 6px;
    color: #333;
}

.setting-row input[type="color"] {
    height: 40px;
    border: 1px solid #ddd;
    border-radius: 3px;
    cursor: pointer;
}

.setting-row input[type="text"],
.setting-row select {
    padding: 8px 10px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 12px;
    font-family: inherit;
}

.select-header-image-btn {
    width: 100%;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    flex-shrink: 0;
}

.preview-header h3 {
    margin: 0;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    color: #666;
}

.refresh-preview-btn {
    padding: 4px 8px;
    height: auto;
}

.live-preview-container {
    flex: 1;
    border: 1px solid #ddd;
    border-radius: 3px;
    overflow: hidden;
    display: flex;
}

.preview-frame {
    width: 100%;
    height: 100%;
    border: none;
    flex: 1;
}

.button.button-small {
    padding: 6px 10px;
    font-size: 12px;
    height: auto;
}

.CodeMirror {
    height: 100% !important;
    font-size: 13px !important;
    flex: 1;
}

.CodeMirror-scroll {
    height: 100%;
}

.left-sidebar {
    overflow-y: auto;
}

.sidebar-accordion-section {
    border-bottom: 1px solid #ddd;
}

.accordion-header {
    margin: 0;
    padding: 12px 15px;
    background: #f9f9f9;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 13px;
    font-weight: 600;
    text-transform: uppercase;
    color: #666;
    transition: all 0.2s;
    user-select: none;
}

.accordion-header:hover {
    background: #f0f0f0;
    color: #0073aa;
}

.accordion-header.active {
    background: #0073aa;
    color: white;
}

.accordion-header .dashicons:first-child {
    margin-right: 8px;
    font-size: 16px;
}

.accordion-toggle {
    font-size: 18px;
    transition: transform 0.2s;
}

.accordion-header.active .accordion-toggle {
    transform: rotate(180deg);
}

/* Inline Benachrichtigungen */
.enewsletter-notification {
    position: fixed;
    top: 32px;
    right: 20px;
    min-width: 300px;
    max-width: 500px;
    padding: 15px 20px;
    background: white;
    border-left: 4px solid #0073aa;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    border-radius: 3px;
    z-index: 100000;
    animation: slideInRight 0.3s ease-out, fadeOut 0.3s ease-in 2.7s;
    opacity: 1;
}

.enewsletter-notification.success {
    border-left-color: #46b450;
}

.enewsletter-notification.error {
    border-left-color: #dc3232;
}

.enewsletter-notification.warning {
    border-left-color: #ffb900;
}

.enewsletter-notification .notification-icon {
    display: inline-block;
    margin-right: 10px;
    font-size: 18px;
    vertical-align: middle;
}

.enewsletter-notification.success .notification-icon {
    color: #46b450;
}

.enewsletter-notification.error .notification-icon {
    color: #dc3232;
}

.enewsletter-notification.warning .notification-icon {
    color: #ffb900;
}

.enewsletter-notification .notification-message {
    display: inline-block;
    vertical-align: middle;
    font-size: 14px;
    color: #23282d;
    font-weight: 500;
}

@keyframes slideInRight {
    from {
        transform: translateX(400px);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes fadeOut {
    to {
        opacity: 0;
        transform: translateX(400px);
    }
}

/* Modal Dialog */
.enewsletter-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 100001;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.2s ease-out;
}

.enewsletter-modal {
    background: white;
    border-radius: 3px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.3);
    min-width: 400px;
    max-width: 600px;
    animation: scaleIn 0.2s ease-out;
}

.enewsletter-modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #ddd;
}

.enewsletter-modal-header h2 {
    margin: 0;
    font-size: 18px;
    color: #23282d;
}

.enewsletter-modal-body {
    padding: 24px;
    font-size: 14px;
    color: #555;
    line-height: 1.6;
}

.enewsletter-modal-footer {
    padding: 16px 24px;
    border-top: 1px solid #ddd;
    text-align: right;
    background: #f5f5f5;
}

.enewsletter-modal-footer .button {
    margin-left: 8px;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes scaleIn {
    from {
        transform: scale(0.9);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

.accordion-content {
    padding: 15px;
    background: white;
    overflow: hidden;
    transition: all 0.3s ease;
}

.template-settings-form {
    margin-top: 0;
}

.branding-settings-form {
    margin-top: 0;
}

.builder-textarea {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 13px;
    font-family: inherit;
    resize: vertical;
}

.builder-select {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 13px;
}

.social-inputs {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.social-input {
    font-size: 12px !important;
}

.setting-group {
    margin-bottom: 15px;
}

.setting-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    font-size: 12px;
}

.builder-color-input {
    width: 100%;
    height: 35px;
    border: 1px solid #ddd;
    border-radius: 3px;
    cursor: pointer;
}

.builder-text-input {
    width: 100%;
    padding: 6px 8px;
    border: 1px solid #ddd;
    border-radius: 3px;
    font-size: 13px;
}

.builder-text-input.small-text {
    margin-top: 5px;
}

.select-bg-image-btn,
.select-header-image-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

.builder-checkbox {
    margin-right: 8px;
}

.save-builder-settings-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>

<script>
jQuery(document).ready(function($) {
    var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
    var nonce = '<?php echo wp_create_nonce('enewsletter_template_edit'); ?>';
    var stylesheet = '<?php echo esc_js($stylesheet); ?>';
    var loremIpsum = <?php echo json_encode($default_lorem); ?>;
    var settings = <?php echo json_encode($settings); ?>;
    var builderSettings = <?php echo wp_json_encode($builder_settings); ?>;
    var brandingSettings = <?php echo wp_json_encode($branding_settings); ?>;
    
    // Benachrichtigungssystem
    window.showNotification = function(message, type) {
        type = type || 'info';
        var iconClass = type === 'success' ? 'dashicons-yes' : 
                       type === 'error' ? 'dashicons-warning' : 
                       type === 'warning' ? 'dashicons-info' : 'dashicons-info';
        
        var $notification = $('<div class="enewsletter-notification ' + type + '">' +
            '<span class="notification-icon dashicons ' + iconClass + '"></span>' +
            '<span class="notification-message">' + message + '</span>' +
        '</div>');
        
        $('body').append($notification);
        
        setTimeout(function() {
            $notification.remove();
        }, 3000);
    };

    // Modal Dialog System
    window.showModal = function(title, message, onConfirm, confirmText, cancelText) {
        confirmText = confirmText || '<?php _e('Bestätigen', 'email-newsletter'); ?>';
        cancelText = cancelText || '<?php _e('Abbrechen', 'email-newsletter'); ?>';
        
        var $modal = $('<div class="enewsletter-modal-overlay">' +
            '<div class="enewsletter-modal">' +
                '<div class="enewsletter-modal-header">' +
                    '<h2>' + title + '</h2>' +
                '</div>' +
                '<div class="enewsletter-modal-body">' +
                    message +
                '</div>' +
                '<div class="enewsletter-modal-footer">' +
                    '<button class="button modal-cancel">' + cancelText + '</button>' +
                    '<button class="button button-primary modal-confirm">' + confirmText + '</button>' +
                '</div>' +
            '</div>' +
        '</div>');
        
        $('body').append($modal);
        
        $modal.find('.modal-cancel').on('click', function() {
            $modal.remove();
        });
        
        $modal.find('.modal-confirm').on('click', function() {
            $modal.remove();
            if (onConfirm) onConfirm();
        });
        
        $modal.on('click', function(e) {
            if ($(e.target).hasClass('enewsletter-modal-overlay')) {
                $modal.remove();
            }
        });
    };
    
    // Initialize CodeMirror editors
    var htmlEditor = CodeMirror.fromTextArea(document.getElementById('template-html-editor'), {
        mode: 'htmlmixed',
        theme: 'monokai',
        lineNumbers: true,
        lineWrapping: true,
        indentUnit: 4,
        indentWithTabs: false,
        matchBrackets: true,
        autoCloseBrackets: true,
        styleActiveLine: true,
        highlightSelectionMatches: {showToken: /\w/, annotateScrollbar: true}
    });
    
    var cssEditor = CodeMirror.fromTextArea(document.getElementById('style-css-editor'), {
        mode: 'css',
        theme: 'monokai',
        lineNumbers: true,
        lineWrapping: true,
        indentUnit: 4,
        indentWithTabs: false,
        matchBrackets: true,
        autoCloseBrackets: true,
        styleActiveLine: true
    });
    
    var cssHeaderEditor = CodeMirror.fromTextArea(document.getElementById('style-header-css-editor'), {
        mode: 'css',
        theme: 'monokai',
        lineNumbers: true,
        lineWrapping: true,
        indentUnit: 4,
        indentWithTabs: false,
        matchBrackets: true,
        autoCloseBrackets: true,
        styleActiveLine: true
    });
    
    // Accordion functionality
    $('.accordion-header').on('click', function() {
        var $header = $(this);
        var $content = $header.next('.accordion-content');
        var isActive = $header.hasClass('active');
        
        // Close all accordion sections
        $('.accordion-header').removeClass('active');
        $('.accordion-content').slideUp(200);
        $('.accordion-toggle').removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
        
        // Open clicked section if it wasn't active
        if(!isActive) {
            $header.addClass('active');
            $content.slideDown(200);
            $header.find('.accordion-toggle').removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
        }
    });
    
    // File tab switching
    $('.file-tab').on('click', function() {
        var file = $(this).data('file');
        
        $('.file-tab').removeClass('active');
        $(this).addClass('active');
        
        $('.editor-panel').removeClass('active');
        $('.save-file-btn, .save-all-css-btn').hide();
        
        if(file === 'template.html') {
            $('.template-html-panel').addClass('active');
            $('.save-file-btn').show();
            htmlEditor.refresh();
        } else if(file === 'style.css' || file === 'style_header.css') {
            $('.css-editor-panel').addClass('active');
            $('.save-all-css-btn').show();
            $('.css-tab[data-tab="style-css"]').click();
        }
    });
    
    // CSS tabs
    $('.css-tab').on('click', function() {
        var tab = $(this).data('tab');
        
        $('.css-tab').removeClass('active');
        $(this).addClass('active');
        
        if(tab === 'style-css') {
            cssEditor.getWrapperElement().style.display = 'block';
            cssHeaderEditor.getWrapperElement().style.display = 'none';
            cssEditor.refresh();
        } else if(tab === 'style-header-css') {
            cssEditor.getWrapperElement().style.display = 'none';
            cssHeaderEditor.getWrapperElement().style.display = 'block';
            cssHeaderEditor.refresh();
        }
        
        updatePreview();
    });
    
    // Save template.html
    $(document).on('click', '.save-file-btn', function() {
        var content = htmlEditor.getValue();
        
        $.post(ajaxurl, {
            action: 'enewsletter_save_template_file',
            stylesheet: stylesheet,
            file: 'template.html',
            content: content,
            nonce: nonce
        }, function(response) {
            if(response.success) {
                showNotification('<?php _e('Datei gespeichert!', 'email-newsletter'); ?>', 'success');
                updatePreview();
            } else {
                showNotification('<?php _e('Fehler beim Speichern', 'email-newsletter'); ?>', 'error');
            }
        });
    });
    
    // Save all CSS
    $('.save-all-css-btn').on('click', function() {
        var styleCss = cssEditor.getValue();
        var styleHeaderCss = cssHeaderEditor.getValue();
        
        $.post(ajaxurl, {
            action: 'enewsletter_save_template_css',
            stylesheet: stylesheet,
            style_css: styleCss,
            style_header_css: styleHeaderCss,
            nonce: nonce
        }, function(response) {
            if(response.success) {
                showNotification('<?php _e('CSS gespeichert!', 'email-newsletter'); ?>', 'success');
                updatePreview();
            } else {
                showNotification('<?php _e('Fehler beim Speichern', 'email-newsletter'); ?>', 'error');
            }
        });
    });
    
    // Color picker changes
    $(document).on('change', '.color-picker', function() {
        var setting = $(this).data('setting');
        var value = $(this).val();
        settings[setting] = value;
        generateCSS();
        updatePreview();
    });
    
    // Spacing input changes
    $(document).on('change', '.spacing-input', function() {
        var setting = $(this).data('setting');
        var value = $(this).val();
        settings[setting] = value;
        generateCSS();
        updatePreview();
    });
    
    // Typography changes
    $(document).on('change', '.typography-select', function() {
        var setting = $(this).data('setting');
        var value = $(this).val();
        settings[setting] = value;
        generateCSS();
        updatePreview();
    });
    
    // Select header image from media library
    $('.select-header-image-btn').on('click', function(e) {
        e.preventDefault();
        
        var mediaUploader = wp.media({
            title: '<?php _e('Header Bild wählen', 'email-newsletter'); ?>',
            button: {
                text: '<?php _e('Bild wählen', 'email-newsletter'); ?>'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#header-image-url').val(attachment.url);
            updatePreview();
        });
        
        mediaUploader.open();
    });
    
    // Refresh preview button
    $('.refresh-preview-btn').on('click', function() {
        updatePreview();
    });
    
    // Generate CSS from settings (legacy settings tab)
    function generateCSS() {
        var css = `/* Generated CSS from Settings */
body {
    background-color: ${settings.body_bg_color};
    color: ${settings.body_text_color};
    font-size: ${settings.body_font_size};
    font-family: ${settings.body_font_family};
}

h1, h2, h3, h4, h5, h6 {
    color: ${settings.heading_color};
}

a {
    color: ${settings.link_color};
}

.btn, button {
    background-color: ${settings.button_bg_color};
    color: ${settings.button_text_color};
}

.container {
    padding: ${settings.container_padding};
    margin: ${settings.container_margin};
}`;
        
        $('#style-css-editor').val(css);
    }
    
    // Generate Branding HTML
    function generateBrandingHTML() {
        var layout = brandingSettings.branding_layout || 'footer';
        var companyName = brandingSettings.company_name || '';
        var address = brandingSettings.address || '';
        var phone = brandingSettings.phone || '';
        var email = brandingSettings.email || '';
        var website = brandingSettings.website || '';
        
        // Social Media Icons
        var socialHtml = '';
        var socialLinks = [];
        if(brandingSettings.facebook) socialLinks.push('<a href="' + brandingSettings.facebook + '" style="text-decoration:none; margin:0 5px;">📘 Facebook</a>');
        if(brandingSettings.twitter) socialLinks.push('<a href="' + brandingSettings.twitter + '" style="text-decoration:none; margin:0 5px;">🐦 Twitter</a>');
        if(brandingSettings.instagram) socialLinks.push('<a href="' + brandingSettings.instagram + '" style="text-decoration:none; margin:0 5px;">📷 Instagram</a>');
        if(brandingSettings.linkedin) socialLinks.push('<a href="' + brandingSettings.linkedin + '" style="text-decoration:none; margin:0 5px;">💼 LinkedIn</a>');
        if(brandingSettings.youtube) socialLinks.push('<a href="' + brandingSettings.youtube + '" style="text-decoration:none; margin:0 5px;">▶️ YouTube</a>');
        
        if(socialLinks.length > 0) {
            socialHtml = '<div style="margin-top:15px; text-align:center;">' + socialLinks.join(' ') + '</div>';
        }
        
        // Layout templates
        if(layout === 'footer') {
            return `<div style="text-align:center; padding:20px; background-color:#f5f5f5; color:#666; font-size:12px;">
                <strong>${companyName}</strong><br>
                ${address ? address + '<br>' : ''}
                ${phone ? 'Tel: ' + phone + '<br>' : ''}
                ${email ? 'E-Mail: <a href="mailto:' + email + '">' + email + '</a><br>' : ''}
                ${website ? '<a href="' + website + '">Website</a><br>' : ''}
                ${socialHtml}
            </div>`;
        } else if(layout === 'two-column') {
            return `<table style="width:100%; max-width:600px; margin:20px auto;">
                <tr>
                    <td style="width:50%; padding:15px; vertical-align:top;">
                        <strong>${companyName}</strong><br>
                        ${address ? address + '<br>' : ''}
                        ${phone ? 'Tel: ' + phone + '<br>' : ''}
                    </td>
                    <td style="width:50%; padding:15px; vertical-align:top;">
                        ${email ? 'E-Mail: <a href="mailto:' + email + '">' + email + '</a><br>' : ''}
                        ${website ? '<a href="' + website + '">Website</a><br>' : ''}
                        ${socialHtml}
                    </td>
                </tr>
            </table>`;
        } else if(layout === 'centered') {
            return `<div style="text-align:center; padding:30px;">
                <h3>${companyName}</h3>
                ${address ? '<p>' + address + '</p>' : ''}
                <p>
                    ${phone ? 'Tel: ' + phone + ' | ' : ''}
                    ${email ? 'E-Mail: <a href="mailto:' + email + '">' + email + '</a>' : ''}
                </p>
                ${website ? '<p><a href="' + website + '">Besuchen Sie unsere Website</a></p>' : ''}
                ${socialHtml}
            </div>`;
        }
        
        return '';
    }
    
    // Update live preview
    function updatePreview() {
        var htmlContent = htmlEditor.getValue();
        var styleCss = cssEditor.getValue();
        var builderCss = '';
        var templateBase = '<?php echo esc_js($template_url); ?>';
        var headerImagePath = builderSettings.header_image ? builderSettings.header_image : '';
        var bgImagePath = builderSettings.bg_image ? builderSettings.bg_image : '';
        var resolvedHeader = '';
        var resolvedBg = '';
        if(headerImagePath) {
            resolvedHeader = /^https?:\/\//i.test(headerImagePath) ? headerImagePath : templateBase + headerImagePath.replace(/^\//, '');
        }
        if(bgImagePath) {
            resolvedBg = /^https?:\/\//i.test(bgImagePath) ? bgImagePath : templateBase + bgImagePath.replace(/^\//, '');
        }
        
        // Apply builder settings to live CSS
        builderCss += 'body { background-color: ' + (builderSettings.bg_color || '#ffffff') + ';';
        if(resolvedBg) {
            builderCss += ' background-image: url("' + resolvedBg + '"); background-size: cover; background-repeat: no-repeat;';
        }
        builderCss += ' color: ' + (builderSettings.body_color || '#333333') + '; }';
        builderCss += ' a { color: ' + (builderSettings.link_color || '#0073aa') + '; }';
        builderCss += ' h1, h2, h3, h4, h5, h6 { color: ' + (builderSettings.title_color || '#000000') + '; }';
        builderCss += ' .alt, .alternate, .alt-row { background-color: ' + (builderSettings.alternative_color || '#666666') + '; }';
        
        // Sample data for placeholder replacement
        var sampleData = {
            'CONTENT': loremIpsum,
            'EMAIL_TITLE': builderSettings.email_title ? builderSettings.email_title : '<?php _e('Beispiel Newsletter-Titel', 'email-newsletter'); ?>',
            'EMAIL_BODY': loremIpsum,
            'UNSUBSCRIBE_LINK': '<a href="#unsubscribe"><?php _e('Abmelden', 'email-newsletter'); ?></a>',
            'UNSUBSCRIBE_URL': '#unsubscribe',
            'VIEW_LINK': '<a href="#view"><?php _e('Im Browser anzeigen', 'email-newsletter'); ?></a>',
            'VIEW_LINK_TEXT': '<?php _e('Im Browser anzeigen', 'email-newsletter'); ?>',
            'FROM_NAME': '<?php echo esc_js(get_bloginfo('name')); ?>',
            'FROM_EMAIL': '<?php echo esc_js(get_bloginfo('admin_email')); ?>',
            'FROM_EMAI': '<?php echo esc_js(get_bloginfo('admin_email')); ?>',
            'BRANDING_HTML': generateBrandingHTML(),
            'CONTACT_INFO': generateBrandingHTML(),
            'HEADER_IMAGE': headerImagePath ? '<img src="' + resolvedHeader + '" alt="Header" style="max-width:100%; height:auto; display:block;">' : '',
            'EMAIL_SUBJECT': builderSettings.email_title ? builderSettings.email_title : '<?php _e('Beispiel Betreff', 'email-newsletter'); ?>',
            'DATE': new Date().toLocaleDateString()
        };
        
        // Replace all placeholders (both {PLACEHOLDER} and {{PLACEHOLDER}} format)
        for (var key in sampleData) {
            var value = sampleData[key];
            htmlContent = htmlContent.replace(new RegExp('\\{\\{' + key + '\\}\\}', 'g'), value);
            htmlContent = htmlContent.replace(new RegExp('\\{' + key + '\\}', 'g'), value);
        }
        
        // Build preview HTML
        var previewHtml = `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; padding: 20px; }
        table { width: 100%; max-width: 600px; margin: 0 auto; background-color: white; }
            ${styleCss}
            ${builderCss}
    </style>
</head>
<body>
    ${htmlContent}
</body>
</html>`;
        
        var frame = document.getElementById('preview-frame');
        frame.srcdoc = previewHtml;
    }
    
    // Initial preview
    updatePreview();

    // Live update for color inputs
    $('.builder-color-input').on('input change', function() {
        var id = $(this).attr('id');
        switch(id) {
            case 'builder-bg-color':
                builderSettings.bg_color = $(this).val();
                break;
            case 'builder-link-color':
                builderSettings.link_color = $(this).val();
                break;
            case 'builder-body-color':
                builderSettings.body_color = $(this).val();
                break;
            case 'builder-alternative-color':
                builderSettings.alternative_color = $(this).val();
                break;
            case 'builder-title-color':
                builderSettings.title_color = $(this).val();
                break;
        }
        updatePreview();
    });

    // Live update for text input
    $('#builder-email-title').on('input change', function() {
        builderSettings.email_title = $(this).val();
        updatePreview();
    });

    // Live update for checkboxes
    $('#builder-use-header-footer, #builder-use-styles').on('change', function() {
        builderSettings.use_default_header_footer = $('#builder-use-header-footer').is(':checked');
        builderSettings.use_default_styles = $('#builder-use-styles').is(':checked');
        updatePreview();
    });
    
    // Live update for branding inputs
    $('#branding-company-name, #branding-address, #branding-phone, #branding-email, #branding-website, #branding-facebook, #branding-twitter, #branding-instagram, #branding-linkedin, #branding-youtube').on('input change', function() {
        brandingSettings.company_name = $('#branding-company-name').val();
        brandingSettings.address = $('#branding-address').val();
        brandingSettings.phone = $('#branding-phone').val();
        brandingSettings.email = $('#branding-email').val();
        brandingSettings.website = $('#branding-website').val();
        brandingSettings.facebook = $('#branding-facebook').val();
        brandingSettings.twitter = $('#branding-twitter').val();
        brandingSettings.instagram = $('#branding-instagram').val();
        brandingSettings.linkedin = $('#branding-linkedin').val();
        brandingSettings.youtube = $('#branding-youtube').val();
        updatePreview();
    });
    
    // Live update for branding layout
    $('#branding-layout').on('change', function() {
        brandingSettings.branding_layout = $(this).val();
        updatePreview();
    });
    
    // Builder Settings: Media Picker for Background Image
    $('.select-bg-image-btn').on('click', function(e) {
        e.preventDefault();
        
        var mediaUploader = wp.media({
            title: '<?php _e('Hintergrundbild wählen', 'email-newsletter'); ?>',
            button: {
                text: '<?php _e('Bild wählen', 'email-newsletter'); ?>'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            var url = attachment.url;
            $('#builder-bg-image').val(url);
            builderSettings.bg_image = url;
            updatePreview();
        });
        
        mediaUploader.open();
    });
    
    // Builder Settings: Media Picker for Header Image
    $('.select-header-image-btn').on('click', function(e) {
        e.preventDefault();
        
        var mediaUploader = wp.media({
            title: '<?php _e('Header Bild wählen', 'email-newsletter'); ?>',
            button: {
                text: '<?php _e('Bild wählen', 'email-newsletter'); ?>'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            var url = attachment.url;
            $('#builder-header-image').val(url);
            builderSettings.header_image = url;
            updatePreview();
        });
        
        mediaUploader.open();
    });
    
    // Save Builder Settings
    $('.save-builder-settings-btn').on('click', function() {
        var builderSettingsPayload = {
            bg_color: $('#builder-bg-color').val(),
            bg_image: $('#builder-bg-image').val(),
            header_image: $('#builder-header-image').val(),
            link_color: $('#builder-link-color').val(),
            body_color: $('#builder-body-color').val(),
            alternative_color: $('#builder-alternative-color').val(),
            title_color: $('#builder-title-color').val(),
            email_title: $('#builder-email-title').val(),
            use_default_header_footer: $('#builder-use-header-footer').is(':checked'),
            use_default_styles: $('#builder-use-styles').is(':checked')
        };
        
        $.post(ajaxurl, {
            action: 'enewsletter_save_builder_settings',
            stylesheet: stylesheet,
            settings: builderSettingsPayload,
            nonce: nonce
        }, function(response) {
            if(response.success) {
                builderSettings = builderSettingsPayload;
                showNotification('<?php _e('Einstellungen gespeichert!', 'email-newsletter'); ?>', 'success');
                updatePreview();
            } else {
                showNotification('<?php _e('Fehler beim Speichern', 'email-newsletter'); ?>', 'error');
            }
        });
    });
});

</script>
