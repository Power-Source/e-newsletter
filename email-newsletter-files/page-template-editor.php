<?php
global $email_newsletter;

// Security check
if(!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Get template parameter
$template_slug = isset($_GET['template']) ? sanitize_text_field($_GET['template']) : '';

if(empty($template_slug)) {
    wp_die(__('No template specified', 'email-newsletter'));
}

// Register theme directories and find template
$email_newsletter->register_enewsletter_themes();
$template = wp_get_theme($template_slug);

if(!$template->exists()) {
    wp_die(__('Template not found', 'email-newsletter'));
}

// Verify it's actually a newsletter template
if($template->theme_root != $email_newsletter->template_directory && $template->theme_root != $email_newsletter->template_custom_directory) {
    wp_die(__('Template not found', 'email-newsletter'));
}

// Check if it's a custom template
$is_custom = ($template->theme_root === $email_newsletter->template_custom_directory);

if(!$is_custom) {
    wp_die(__('You can only edit custom templates. Please clone this template first.', 'email-newsletter'));
}

// Get template directory
$template_dir = $template->get_stylesheet_directory();

// Get list of template files
$template_files = array();
$files = scandir($template_dir);
foreach($files as $file) {
    if($file === '.' || $file === '..') continue;
    if(is_file($template_dir . '/' . $file)) {
        $template_files[] = $file;
    }
}

// Current file being edited
$current_file = isset($_GET['file']) ? sanitize_text_field($_GET['file']) : 'style.css';
if(!in_array($current_file, $template_files)) {
    $current_file = $template_files[0];
}

$current_file_path = $template_dir . '/' . $current_file;
$file_content = file_exists($current_file_path) ? file_get_contents($current_file_path) : '';

// Save handler
if(isset($_POST['save_template']) && check_admin_referer('save_template_' . $template_slug)) {
    $new_content = wp_unslash($_POST['file_content']);
    file_put_contents($current_file_path, $new_content);
    echo '<div class="notice notice-success"><p>' . __('Template saved successfully!', 'email-newsletter') . '</p></div>';
    $file_content = $new_content;
}

// Determine file type for syntax highlighting
$file_ext = pathinfo($current_file, PATHINFO_EXTENSION);
$editor_mode = 'htmlmixed';
switch($file_ext) {
    case 'css':
        $editor_mode = 'css';
        break;
    case 'js':
        $editor_mode = 'javascript';
        break;
    case 'php':
        $editor_mode = 'application/x-httpd-php';
        break;
    default:
        $editor_mode = 'htmlmixed';
}
?>

<div class="wrap template-editor-wrap">
    <h1><?php echo esc_html($template->get('Name')); ?> - <?php _e('Template Editor', 'email-newsletter'); ?></h1>
    
    <div class="template-editor-container">
        <!-- Sidebar with file list and components -->
        <div class="template-sidebar">
            <div class="sidebar-section">
                <h3><?php _e('Template Files', 'email-newsletter'); ?></h3>
                <ul class="template-files-list">
                    <?php foreach($template_files as $file): ?>
                        <li class="<?php echo ($file === $current_file) ? 'active' : ''; ?>">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=newsletters-template-editor&template=' . urlencode($template_slug) . '&file=' . urlencode($file))); ?>">
                                <span class="dashicons dashicons-media-code"></span>
                                <?php echo esc_html($file); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="sidebar-section components-library">
                <h3><?php _e('Components Library', 'email-newsletter'); ?></h3>
                <p class="description"><?php _e('Click to insert at cursor position', 'email-newsletter'); ?></p>
                
                <div class="component-category">
                    <h4><?php _e('Layout', 'email-newsletter'); ?></h4>
                    <button type="button" class="button component-btn" data-component="table-container">
                        <?php _e('Email Container', 'email-newsletter'); ?>
                    </button>
                    <button type="button" class="button component-btn" data-component="spacer">
                        <?php _e('Spacer', 'email-newsletter'); ?>
                    </button>
                    <button type="button" class="button component-btn" data-component="divider">
                        <?php _e('Divider', 'email-newsletter'); ?>
                    </button>
                </div>
                
                <div class="component-category">
                    <h4><?php _e('Content', 'email-newsletter'); ?></h4>
                    <button type="button" class="button component-btn" data-component="heading">
                        <?php _e('Heading', 'email-newsletter'); ?>
                    </button>
                    <button type="button" class="button component-btn" data-component="text">
                        <?php _e('Text Block', 'email-newsletter'); ?>
                    </button>
                    <button type="button" class="button component-btn" data-component="button">
                        <?php _e('Button', 'email-newsletter'); ?>
                    </button>
                    <button type="button" class="button component-btn" data-component="image">
                        <?php _e('Image', 'email-newsletter'); ?>
                    </button>
                </div>
                
                <div class="component-category">
                    <h4><?php _e('Dynamic Content', 'email-newsletter'); ?></h4>
                    <button type="button" class="button component-btn" data-component="placeholder-title">
                        {{EMAIL_TITLE}}
                    </button>
                    <button type="button" class="button component-btn" data-component="placeholder-content">
                        {{CONTENT}}
                    </button>
                    <button type="button" class="button component-btn" data-component="placeholder-unsubscribe">
                        {{UNSUBSCRIBE_LINK}}
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Main editor area -->
        <div class="template-editor-main">
            <form method="post" id="template-editor-form">
                <?php wp_nonce_field('save_template_' . $template_slug); ?>
                
                <div class="editor-toolbar">
                    <button type="submit" name="save_template" class="button button-primary">
                        <span class="dashicons dashicons-saved"></span>
                        <?php _e('Save', 'email-newsletter'); ?>
                    </button>
                    <button type="button" id="preview-toggle" class="button">
                        <span class="dashicons dashicons-visibility"></span>
                        <span class="preview-text"><?php _e('Live-Vorschau ausblenden', 'email-newsletter'); ?></span>
                    </button>
                    <label class="auto-preview-label">
                        <input type="checkbox" id="auto-preview" checked>
                        <?php _e('Auto-Update', 'email-newsletter'); ?>
                    </label>
                    <span class="editor-file-name"><?php echo esc_html($current_file); ?></span>
                </div>
                
                <textarea name="file_content" id="code-editor"><?php echo esc_textarea($file_content); ?></textarea>
            </form>
        </div>
        
        <!-- Preview panel -->
        <div class="template-preview-panel" id="preview-panel">
            <div class="preview-header">
                <h3><?php _e('Live-Vorschau', 'email-newsletter'); ?></h3>
                <button type="button" id="refresh-preview" class="button button-small" title="<?php _e('Vorschau aktualisieren', 'email-newsletter'); ?>">
                    <span class="dashicons dashicons-update"></span>
                </button>
            </div>
            <iframe id="preview-iframe" frameborder="0"></iframe>
        </div>
    </div>
</div>

<!-- Component Templates (hidden) -->
<script type="text/template" id="component-templates">
{
    "table-container": "<table width=\"600\" align=\"center\" cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse:collapse;\">\n\t<tr>\n\t\t<td>\n\t\t\t<!-- Content here -->\n\t\t</td>\n\t</tr>\n</table>",
    "spacer": "<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\"><tr><td height=\"20\" style=\"font-size:1px;line-height:1px;\">&nbsp;</td></tr></table>",
    "divider": "<table width=\"100%\" cellpadding=\"0\" cellspacing=\"0\"><tr><td style=\"border-top:1px solid #dddddd;padding:10px 0;\"></td></tr></table>",
    "heading": "<h2 style=\"color:#333;font-family:Arial,sans-serif;font-size:24px;font-weight:bold;margin:0 0 10px;\">Your Heading</h2>",
    "text": "<p style=\"color:#666;font-family:Arial,sans-serif;font-size:14px;line-height:1.6;margin:0 0 15px;\">Your text here...</p>",
    "button": "<table cellpadding=\"0\" cellspacing=\"0\">\n\t<tr>\n\t\t<td align=\"center\" style=\"background-color:#0073aa;border-radius:4px;\">\n\t\t\t<a href=\"#\" style=\"background-color:#0073aa;border:1px solid #0073aa;border-radius:4px;color:#ffffff;display:inline-block;font-family:Arial,sans-serif;font-size:16px;font-weight:bold;padding:12px 24px;text-decoration:none;\">Click Here</a>\n\t\t</td>\n\t</tr>\n</table>",
    "image": "<img src=\"https://via.placeholder.com/600x300\" alt=\"Image\" style=\"display:block;max-width:100%;height:auto;\" />",
    "placeholder-title": "{{EMAIL_TITLE}}",
    "placeholder-content": "{{CONTENT}}",
    "placeholder-unsubscribe": "<a href=\"{{UNSUBSCRIBE_LINK}}\" style=\"color:#666;text-decoration:underline;\">Unsubscribe</a>"
}
</script>

<style>
.template-editor-wrap {
    margin-right: 0;
}
.template-editor-container {
    display: flex;
    gap: 0;
    margin-top: 20px;
    height: calc(100vh - 150px);
}
.template-editor-container.preview-hidden .template-preview-panel {
    display: none;
}
.template-editor-container.preview-hidden .template-editor-main {
    border-right: 1px solid #ddd;
}
.template-sidebar {
    width: 280px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    overflow-y: auto;
    flex-shrink: 0;
}
.sidebar-section {
    padding: 15px;
    border-bottom: 1px solid #ddd;
}
.sidebar-section h3 {
    margin: 0 0 10px;
    font-size: 14px;
    font-weight: 600;
}
.template-files-list {
    list-style: none;
    margin: 0;
    padding: 0;
}
.template-files-list li {
    margin: 0;
}
.template-files-list li a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 10px;
    text-decoration: none;
    color: #333;
    border-radius: 3px;
    transition: background 0.2s;
}
.template-files-list li a:hover {
    background: #fff;
}
.template-files-list li.active a {
    background: #0073aa;
    color: white;
}
.template-files-list .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
.components-library .component-category {
    margin-bottom: 15px;
}
.components-library .component-category h4 {
    margin: 0 0 8px;
    font-size: 13px;
    font-weight: 600;
    color: #666;
}
.component-btn {
    display: block;
    width: 100%;
    margin-bottom: 5px;
    text-align: left;
    font-size: 12px;
}
.template-editor-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: white;
    border: 1px solid #ddd;
    border-left: none;
}
#template-editor-form {
    display: flex;
    flex-direction: column;
    height: 100%;
}
.editor-toolbar {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    background: #f9f9f9;
    border-bottom: 1px solid #ddd;
}
.auto-preview-label {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    margin: 0;
    cursor: pointer;
}
.auto-preview-label input {
    margin: 0;
}
.editor-file-name {
    margin-left: auto;
    color: #666;
    font-size: 13px;
    font-family: monospace;
}
#code-editor {
    flex: 1;
    border: none;
    font-family: 'Courier New', monospace;
    font-size: 14px;
    padding: 15px;
    resize: none;
}
.CodeMirror {
    height: 100% !important;
    font-size: 14px;
}
.template-preview-panel {
    width: 50%;
    min-width: 400px;
    background: white;
    border: 1px solid #ddd;
    border-left: none;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    transition: all 0.3s;
}
.template-preview-panel.hidden {
    width: 0;
    min-width: 0;
    border: none;
}
.preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 15px;
    background: #f9f9f9;
    border-bottom: 1px solid #ddd;
}
.preview-header h3 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}
#refresh-preview {
    padding: 2px 8px;
}
#refresh-preview.spinning .dashicons {
    animation: spin 0.5s linear;
}
}
#preview-iframe {
    flex: 1;
    width: 100%;
}
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>

<script>
jQuery(document).ready(function($) {
    // Initialize CodeMirror
    var editor = CodeMirror.fromTextArea(document.getElementById('code-editor'), {
        mode: '<?php echo $editor_mode; ?>',
        theme: 'monokai',
        lineNumbers: true,
        lineWrapping: true,
        indentUnit: 4,
        indentWithTabs: true,
        matchBrackets: true,
        autoCloseBrackets: true,
        extraKeys: {
            'Ctrl-S': function(cm) {
                $('#template-editor-form').submit();
            },
            'Cmd-S': function(cm) {
                $('#template-editor-form').submit();
            }
        }
    });
    
    // Parse component templates
    var components = JSON.parse($('#component-templates').html());
    
    // Component insertion
    $('.component-btn').on('click', function() {
        var componentKey = $(this).data('component');
        var componentCode = components[componentKey];
        
        if(componentCode) {
            var doc = editor.getDoc();
            var cursor = doc.getCursor();
            doc.replaceRange(componentCode + '\n', cursor);
            editor.focus();
        }
    });
    
    // Preview functions
    var previewVisible = true;
    var autoPreview = true;
    var previewTimeout = null;
    
    function updatePreview() {
        if(!previewVisible) return;
        
        var content = editor.getValue();
        var iframe = document.getElementById('preview-iframe');
        var doc = iframe.contentDocument || iframe.contentWindow.document;
        doc.open();
        doc.write(content);
        doc.close();
    }
    
    // Initial preview
    updatePreview();
    
    // Preview toggle
    $('#preview-toggle').on('click', function() {
        previewVisible = !previewVisible;
        $('#preview-panel').toggleClass('hidden');
        $('.template-editor-container').toggleClass('preview-hidden');
        
        if(previewVisible) {
            $(this).find('.preview-text').text('<?php _e('Live-Vorschau ausblenden', 'email-newsletter'); ?>');
            updatePreview();
        } else {
            $(this).find('.preview-text').text('<?php _e('Live-Vorschau anzeigen', 'email-newsletter'); ?>');
        }
    });
    
    // Auto-preview checkbox
    $('#auto-preview').on('change', function() {
        autoPreview = $(this).is(':checked');
        if(autoPreview) {
            updatePreview();
        }
    });
    
    // Manual refresh
    $('#refresh-preview').on('click', function() {
        updatePreview();
        $(this).addClass('spinning');
        setTimeout(function() {
            $('#refresh-preview').removeClass('spinning');
        }, 500);
    });
    
    // Live preview on change
    editor.on('change', function() {
        if(!autoPreview || !previewVisible) return;
        
        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(function() {
            updatePreview();
        }, 500);
    });
    
    $('#close-preview').on('click', function() {
        $('#preview-panel').removeClass('active');
    });
    
    // Save shortcut feedback
    $('#template-editor-form').on('submit', function() {
        var $btn = $(this).find('[name="save_template"]');
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Saving...');
    });
});
</script>

<style>
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.dashicons.spin {
    animation: spin 1s linear infinite;
}
</style>
