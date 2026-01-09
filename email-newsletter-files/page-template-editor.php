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

// Get template directory
$template_dir = $theme->get_stylesheet_directory();
$template_html_file = $template_dir . '/template.html';
$style_css_file = $template_dir . '/style.css';
$style_header_css_file = $template_dir . '/style_header.css';

// Load file content
$template_html_content = file_exists($template_html_file) ? file_get_contents($template_html_file) : '';
$style_css_content = file_exists($style_css_file) ? file_get_contents($style_css_file) : '';
$style_header_css_content = file_exists($style_header_css_file) ? file_get_contents($style_header_css_file) : '';

// Default Lorem Ipsum content
$default_lorem = '<h2>Lorem Ipsum</h2><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>';

if(empty($template_html_content)) {
    $template_html_content = '<table><tr><td>{CONTENT}</td></tr></table>';
}

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
$template_url = $theme_url_data['url'];
?>

<div class="wrap template-editor-wrap">
    <h1><?php echo esc_html($theme->get('Name')); ?> - <?php _e('Editor', 'email-newsletter'); ?></h1>
    
    <div class="editor-container">
        
        <!-- Left Sidebar: File Management -->
        <div class="editor-sidebar left-sidebar">
            <div class="sidebar-section">
                <h3><?php _e('Template Files', 'email-newsletter'); ?></h3>
                <div class="files-list">
                    <div class="file-item active" data-file="template.html">
                        <span class="file-icon">📄</span>
                        <span class="file-name">template.html</span>
                    </div>
                    <div class="file-item" data-file="style.css">
                        <span class="file-icon">🎨</span>
                        <span class="file-name">style.css</span>
                    </div>
                    <div class="file-item" data-file="style_header.css">
                        <span class="file-icon">🎨</span>
                        <span class="file-name">style_header.css</span>
                    </div>
                </div>
            </div>
            
            <hr>
            
            <div class="sidebar-section">
                <h3><?php _e('Zurück', 'email-newsletter'); ?></h3>
                <button type="button" class="button button-secondary" onclick="window.history.back();">
                    <span class="dashicons dashicons-arrow-left"></span>
                    <?php _e('Zur Vorlagenliste', 'email-newsletter'); ?>
                </button>
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
            
            <!-- Settings Panel -->
            <div class="settings-panel">
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
}

.editor-container {
    display: grid;
    grid-template-columns: 250px 1fr 350px;
    gap: 0;
    height: calc(100vh - 180px);
    margin-top: 20px;
    min-height: 600px;
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
    var loremIpsum = '<?php echo esc_js($default_lorem); ?>';
    var settings = <?php echo json_encode($settings); ?>;
    
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
                alert('<?php _e('Datei gespeichert!', 'email-newsletter'); ?>');
                updatePreview();
            } else {
                alert('<?php _e('Fehler beim Speichern', 'email-newsletter'); ?>');
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
                alert('<?php _e('CSS gespeichert!', 'email-newsletter'); ?>');
                updatePreview();
            } else {
                alert('<?php _e('Fehler beim Speichern', 'email-newsletter'); ?>');
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
    
    // Generate CSS from settings
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
    
    // Update live preview
    function updatePreview() {
        var htmlContent = htmlEditor.getValue();
        var styleCss = cssEditor.getValue();
        
        // Sample data for placeholder replacement
        var sampleData = {
            'CONTENT': loremIpsum,
            'EMAIL_TITLE': '<?php _e('Beispiel Newsletter-Titel', 'email-newsletter'); ?>',
            'EMAIL_BODY': loremIpsum,
            'UNSUBSCRIBE_LINK': '<a href="#unsubscribe"><?php _e('Abmelden', 'email-newsletter'); ?></a>',
            'UNSUBSCRIBE_URL': '#unsubscribe',
            'VIEW_LINK': '<a href="#view"><?php _e('Im Browser anzeigen', 'email-newsletter'); ?></a>',
            'VIEW_LINK_TEXT': '<?php _e('Im Browser anzeigen', 'email-newsletter'); ?>',
            'FROM_NAME': '<?php echo esc_js(get_bloginfo('name')); ?>',
            'FROM_EMAIL': '<?php echo esc_js(get_bloginfo('admin_email')); ?>',
            'FROM_EMAI': '<?php echo esc_js(get_bloginfo('admin_email')); ?>',
            'BRANDING_HTML': '<p><?php echo esc_js(get_bloginfo('description')); ?></p>',
            'CONTACT_INFO': '<?php echo esc_js(get_bloginfo('name')); ?><br><?php echo esc_js(get_bloginfo('description')); ?>',
            'HEADER_IMAGE': '<img src="' + '<?php echo esc_js($template_url); ?>' + 'images/header.jpg" alt="Header" style="max-width:100%; height:auto; display:block;">',
            'EMAIL_SUBJECT': '<?php _e('Beispiel Betreff', 'email-newsletter'); ?>',
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
});
</script>
