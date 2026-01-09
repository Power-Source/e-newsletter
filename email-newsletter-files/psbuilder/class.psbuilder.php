<?php
/**
 * PS Builder - Newsletter Template Management System
 * 
 * Handles all template management operations including:
 * - Template cloning, uploading, exporting, and deletion
 * - Template preview rendering with real data
 * - Template file operations and directory management
 */

class PS_Builder {
    
    public $template_directory = '';
    public $template_custom_directory = '';
    public $plugin_dir = '';
    public $plugin_url = '';
    
    // Reference to main Email_Newsletter class for accessing methods
    private $email_newsletter = null;
    
    public function __construct(&$email_newsletter) {
        $this->email_newsletter = &$email_newsletter;
        $this->template_directory = $email_newsletter->template_directory;
        $this->template_custom_directory = $email_newsletter->template_custom_directory;
        $this->plugin_dir = $email_newsletter->plugin_dir;
        $this->plugin_url = $email_newsletter->plugin_url;
    }
    
    /**
     * Register admin menu for template management
     */
    public function template_management_page() {
        add_submenu_page(
            'newsletters-settings',
            __('Newsletter Templates', 'email-newsletter'),
            __('Templates', 'email-newsletter'),
            'manage_options',
            'newsletters-template-management',
            array($this, 'display_template_management')
        );
    }
    
    /**
     * Display template management page
     */
    public function display_template_management() {
        require_once($this->plugin_dir . 'email-newsletter-files/page-template-management.php');
    }
    
    /**
     * AJAX handler: Clone template
     */
    public function ajax_clone_template() {
        check_ajax_referer('enewsletter_template_actions', 'nonce');
        
        if(!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'email-newsletter'));
        }
        
        $stylesheet = sanitize_text_field($_POST['stylesheet']);
        $new_name = sanitize_text_field($_POST['new_name']);
        
        if(empty($stylesheet) || empty($new_name)) {
            wp_send_json_error(__('Missing required parameters', 'email-newsletter'));
        }
        
        // Register theme directories and get theme
        $this->email_newsletter->register_enewsletter_themes();
        $source_theme = wp_get_theme($stylesheet);
        
        if(!$source_theme->exists()) {
            wp_send_json_error(__('Source template not found', 'email-newsletter'));
        }
        
        // Verify it's actually a newsletter template
        if($source_theme->theme_root != $this->template_directory && $source_theme->theme_root != $this->template_custom_directory) {
            wp_send_json_error(__('Source template not found', 'email-newsletter'));
        }
        
        // Create safe folder name
        $folder_name = sanitize_file_name(str_replace(' ', '-', strtolower($new_name)));
        $dest_path = $this->template_custom_directory . '/' . $folder_name;
        
        // Check if already exists
        if(file_exists($dest_path)) {
            wp_send_json_error(__('A template with this name already exists', 'email-newsletter'));
        }
        
        // Create custom themes directory if it doesn't exist
        if(!file_exists($this->template_custom_directory)) {
            wp_mkdir_p($this->template_custom_directory);
        }
        
        // Copy directory
        $this->recursive_copy($source_theme->get_stylesheet_directory(), $dest_path);
        
        // Update style.css header
        $style_file = $dest_path . '/style.css';
        if(file_exists($style_file)) {
            $style_content = file_get_contents($style_file);
            $style_content = preg_replace('/Theme Name:.*/', 'Theme Name: ' . $new_name, $style_content);
            file_put_contents($style_file, $style_content);
        }
        
        wp_send_json_success(array(
            'message' => __('Template cloned successfully', 'email-newsletter'),
            'stylesheet' => $folder_name,
            'editor_url' => admin_url('admin.php?page=newsletters-template-editor&template=' . urlencode($folder_name))
        ));
    }
    
    /**
     * AJAX handler: Upload template from ZIP
     */
    public function ajax_upload_template() {
        check_ajax_referer('enewsletter_template_actions', 'nonce');
        
        if(!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'email-newsletter'));
        }
        
        if(empty($_FILES['template_zip'])) {
            wp_send_json_error(__('No file uploaded', 'email-newsletter'));
        }
        
        $file = $_FILES['template_zip'];
        
        // Check file type
        if($file['type'] !== 'application/zip' && $file['type'] !== 'application/x-zip-compressed') {
            wp_send_json_error(__('Only ZIP files are allowed', 'email-newsletter'));
        }
        
        // Check for upload errors
        if($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('Upload error occurred', 'email-newsletter'));
        }
        
        // Unzip to custom templates directory
        WP_Filesystem();
        global $wp_filesystem;
        
        $result = unzip_file($file['tmp_name'], $this->template_custom_directory);
        
        if(is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(array('message' => __('Template uploaded successfully', 'email-newsletter')));
    }
    
    /**
     * AJAX handler: Export template as ZIP
     */
    public function ajax_export_template() {
        check_ajax_referer('enewsletter_template_actions', 'nonce');
        
        if(!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'email-newsletter'));
        }
        
        $stylesheet = sanitize_text_field($_GET['stylesheet']);
        
        if(empty($stylesheet)) {
            wp_die(__('No template specified', 'email-newsletter'));
        }
        
        // Register theme directories and get theme
        $this->email_newsletter->register_enewsletter_themes();
        $template = wp_get_theme($stylesheet);
        
        if(!$template->exists()) {
            wp_die(__('Template not found', 'email-newsletter'));
        }
        
        // Verify it's actually a newsletter template
        if($template->theme_root != $this->template_directory && $template->theme_root != $this->template_custom_directory) {
            wp_die(__('Template not found', 'email-newsletter'));
        }
        
        // Create ZIP
        $template_dir = $template->get_stylesheet_directory();
        $zip_file = tempnam(sys_get_temp_dir(), 'template_');
        
        $zip = new ZipArchive();
        if($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            wp_die(__('Could not create ZIP file', 'email-newsletter'));
        }
        
        $this->add_dir_to_zip($zip, $template_dir, $stylesheet);
        $zip->close();
        
        // Send file
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $stylesheet . '.zip"');
        header('Content-Length: ' . filesize($zip_file));
        readfile($zip_file);
        unlink($zip_file);
        die();
    }
    
    /**
     * AJAX handler: Delete custom template
     */
    public function ajax_delete_template() {
        check_ajax_referer('enewsletter_template_actions', 'nonce');
        
        if(!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'email-newsletter'));
        }
        
        $stylesheet = sanitize_text_field($_POST['stylesheet']);
        
        if(empty($stylesheet)) {
            wp_send_json_error(__('No template specified', 'email-newsletter'));
        }
        
        // Register theme directories and get theme
        $this->email_newsletter->register_enewsletter_themes();
        $template = wp_get_theme($stylesheet);
        
        if(!$template->exists()) {
            wp_send_json_error(__('Template not found', 'email-newsletter'));
        }
        
        // Verify it's a custom template (don't allow deleting plugin templates)
        if($template->theme_root != $this->template_custom_directory) {
            wp_send_json_error(__('Only custom templates can be deleted', 'email-newsletter'));
        }
        
        // Delete the template directory
        $template_dir = $template->get_stylesheet_directory();
        if(!$this->delete_dir_recursive($template_dir)) {
            wp_send_json_error(__('Could not delete template directory', 'email-newsletter'));
        }
        
        wp_send_json_success(__('Template successfully deleted', 'email-newsletter'));
    }
    
    /**
     * AJAX handler: Preview template with real rendering and example data
     */
    public function ajax_preview_template() {
        check_ajax_referer('enewsletter_template_preview', 'nonce');
        
        if(!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'email-newsletter'));
        }
        
        $stylesheet = sanitize_text_field($_POST['stylesheet']);
        // Use wp_unslash to properly handle WordPress magic quotes
        $file_content = isset($_POST['file_content']) ? wp_unslash($_POST['file_content']) : '';
        $file_name = isset($_POST['file_name']) ? sanitize_text_field($_POST['file_name']) : '';
        
        if(empty($stylesheet)) {
            wp_send_json_error(__('No template specified', 'email-newsletter'));
        }
        
        // Register theme directories and get template
        $this->email_newsletter->register_enewsletter_themes();
        $theme = wp_get_theme($stylesheet);
        
        if(!$theme->exists()) {
            wp_send_json_error(__('Template not found', 'email-newsletter'));
        }
        
        // Get template directory
        $template_dir = $theme->get_stylesheet_directory();
        $theme_url_data = $this->email_newsletter->get_theme_dir_url($theme, $stylesheet);
        $template_url = $theme_url_data['url'];
        
        // Save original content of edited file
        $original_content = null;
        $temp_file = null;
        if(!empty($file_content) && !empty($file_name)) {
            $original_file = $template_dir . '/' . $file_name;
            
            // Backup original if it exists
            if(file_exists($original_file)) {
                $original_content = file_get_contents($original_file);
            }
            
            // Write modified content temporarily
            file_put_contents($original_file, $file_content);
            $temp_file = $original_file;
        }
        
        try {
            // Get all template parts
            $contents_parts = $this->email_newsletter->get_contents_elements($template_dir);
            
            // Build template HTML - handle both split and unified structures
            $header = isset($contents_parts['header']) && !empty($contents_parts['header']) ? $contents_parts['header'] : '';
            $content = isset($contents_parts['content']) && !empty($contents_parts['content']) ? $contents_parts['content'] : '';
            $footer = isset($contents_parts['footer']) && !empty($contents_parts['footer']) ? $contents_parts['footer'] : '';
            
            // If content is missing, it might be all in template.html
            if(empty($content)) {
                $template_file = $template_dir . '/template.html';
                if(file_exists($template_file)) {
                    $content = file_get_contents($template_file);
                }
            }
            
            // Build complete HTML
            $contents = $header . $content . $footer;
            
            // If still empty, try to load the main template file
            if(empty(trim($contents))) {
                $template_file = $template_dir . '/template.html';
                if(file_exists($template_file)) {
                    $contents = file_get_contents($template_file);
                } else {
                    wp_send_json_error(__('No template content found', 'email-newsletter'));
                }
            }
            
            // Apply styles - combine all available styles
            $all_styles = '';
            foreach(['default_style', 'default_style_header', 'style', 'style_header'] as $style_key) {
                if(isset($contents_parts[$style_key]) && !empty($contents_parts[$style_key])) {
                    $all_styles .= "\n" . $contents_parts[$style_key];
                }
            }
            
            // Apply inline styles
            if(!empty($all_styles)) {
                $contents = $this->email_newsletter->do_inline_styles($contents, $all_styles);
            }

            // Build preview data using core make_email_values so all placeholders resolve
            $example_body = '<h2>' . __('Willkommen!', 'email-newsletter') . '</h2><p>' . __('Dies ist ein Beispiel-Newsletter mit Beispielinhalt.', 'email-newsletter') . '</p>';
            $prepare = array(
                'colors' => array(
                    'bg_color' => '#f0f0f0',
                    'link_color' => '#0073aa',
                    'alternative_color' => '#666666'
                ),
                'standard' => array(
                    'header' => '',
                    'content_header' => '',
                    'footer' => '',
                    'content_footer' => '',
                    'content=email_body' => $example_body,
                    'title=email_title' => __('Beispiel Newsletter-Titel', 'email-newsletter'),
                    'subject=email_subject' => __('Beispiel Betreff', 'email-newsletter'),
                    'from_name' => get_bloginfo('name'),
                    'from_email' => get_bloginfo('admin_email'),
                    'from_emai' => get_bloginfo('admin_email'), // typo fallback
                    'branding_html' => '<p>' . get_bloginfo('description') . '</p>',
                    'contact_info' => get_bloginfo('name') . '<br>' . get_bloginfo('description'),
                    'date' => date_i18n(get_option('date_format', 'F j, Y')),
                    'view_link_text' => __('Im Browser anzeigen', 'email-newsletter'),
                    'header_image' => '<img src="' . esc_url($template_url . 'images/header.jpg') . '" alt="Header" style="max-width:100%; height:auto; display:block;">'
                )
            );

            $contents = $this->email_newsletter->make_email_values($prepare, $contents, 0);

            // Safety: remove any remaining structural placeholders
            $contents = str_replace(array('{HEADER}','{FOOTER}','{CONTENT_HEADER}','{CONTENT_FOOTER}'), '', $contents);
            
            wp_send_json_success(array('html' => $contents));
            
        } finally {
            // Restore original file immediately (in finally to ensure cleanup)
            if($temp_file && $original_content !== null) {
                file_put_contents($temp_file, $original_content);
            } elseif($temp_file && file_exists($temp_file) && $original_content === null) {
                // File didn't exist before, remove it
                @unlink($temp_file);
            }
        }
    }
    
    /**
     * Recursively copy directory
     */
    public function recursive_copy($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ($file = readdir($dir))) {
            if(($file != '.') && ($file != '..')) {
                if(is_dir($src . '/' . $file)) {
                    $this->recursive_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
    
    /**
     * Add directory to ZIP archive recursively
     */
    public function add_dir_to_zip($zip, $dir, $base_name, $parent = '') {
        $files = scandir($dir);
        foreach($files as $file) {
            if($file === '.' || $file === '..') continue;
            
            $file_path = $dir . '/' . $file;
            $zip_path = $parent ? $parent . '/' . $file : $base_name . '/' . $file;
            
            if(is_dir($file_path)) {
                $zip->addEmptyDir($zip_path);
                $this->add_dir_to_zip($zip, $file_path, $base_name, $zip_path);
            } else {
                $zip->addFile($file_path, $zip_path);
            }
        }
    }
    
    /**
     * Recursively delete directory
     */
    public function delete_dir_recursive($dir) {
        if(!is_dir($dir)) return true;
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach($files as $file) {
            $path = $dir . '/' . $file;
            if(is_dir($path)) {
                if(!$this->delete_dir_recursive($path)) {
                    return false;
                }
            } else {
                if(!unlink($path)) {
                    return false;
                }
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * AJAX handler: Save template HTML file
     */
    public function ajax_save_template_file() {
        check_ajax_referer('enewsletter_template_edit', 'nonce');
        
        if(!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'email-newsletter'));
        }
        
        $stylesheet = sanitize_text_field($_POST['stylesheet']);
        $file = sanitize_file_name($_POST['file']);
        $content = wp_unslash($_POST['content']);
        
        if(empty($stylesheet) || empty($file)) {
            wp_send_json_error(__('Missing parameters', 'email-newsletter'));
        }
        
        // Only allow safe filenames
        if(!in_array($file, array('template.html', 'style.css', 'style_header.css'))) {
            wp_send_json_error(__('Invalid file', 'email-newsletter'));
        }
        
        // Register themes and get template
        $this->email_newsletter->register_enewsletter_themes();
        $theme = wp_get_theme($stylesheet);
        
        if(!$theme->exists()) {
            wp_send_json_error(__('Template not found', 'email-newsletter'));
        }
        
        // Check if custom template
        if($theme->theme_root !== $this->template_custom_directory) {
            wp_send_json_error(__('Only custom templates can be edited', 'email-newsletter'));
        }
        
        $template_dir = $theme->get_stylesheet_directory();
        $file_path = $template_dir . '/' . $file;
        
        // Save file
        if(file_put_contents($file_path, $content)) {
            wp_send_json_success(array('message' => __('File saved', 'email-newsletter')));
        } else {
            wp_send_json_error(__('Failed to save file', 'email-newsletter'));
        }
    }
    
    /**
     * AJAX handler: Save all CSS files
     */
    public function ajax_save_template_css() {
        check_ajax_referer('enewsletter_template_edit', 'nonce');
        
        if(!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'email-newsletter'));
        }
        
        $stylesheet = sanitize_text_field($_POST['stylesheet']);
        $style_css = wp_unslash($_POST['style_css']);
        $style_header_css = wp_unslash($_POST['style_header_css']);
        
        if(empty($stylesheet)) {
            wp_send_json_error(__('Missing parameters', 'email-newsletter'));
        }
        
        // Register themes and get template
        $this->email_newsletter->register_enewsletter_themes();
        $theme = wp_get_theme($stylesheet);
        
        if(!$theme->exists()) {
            wp_send_json_error(__('Template not found', 'email-newsletter'));
        }
        
        // Check if custom template
        if($theme->theme_root !== $this->template_custom_directory) {
            wp_send_json_error(__('Only custom templates can be edited', 'email-newsletter'));
        }
        
        $template_dir = $theme->get_stylesheet_directory();
        
        // Save both CSS files
        $saved = true;
        
        if(!empty($style_css)) {
            if(!file_put_contents($template_dir . '/style.css', $style_css)) {
                $saved = false;
            }
        }
        
        if(!empty($style_header_css)) {
            if(!file_put_contents($template_dir . '/style_header.css', $style_header_css)) {
                $saved = false;
            }
        }
        
        if($saved) {
            wp_send_json_success(array('message' => __('CSS saved', 'email-newsletter')));
        } else {
            wp_send_json_error(__('Failed to save CSS', 'email-newsletter'));
        }
    }
}
