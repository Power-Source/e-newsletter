<?php
/**
 * AJAX Handler for Content Typography Settings
 */

if (!defined('ABSPATH')) {
    exit;
}

function enewsletter_ajax_save_content_typography($email_newsletter_instance) {
    check_ajax_referer('enewsletter_template_edit', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(__('Insufficient permissions', 'email-newsletter'));
    }
    
    $stylesheet = isset($_POST['stylesheet']) ? sanitize_text_field($_POST['stylesheet']) : '';
    $settings = isset($_POST['settings']) ? (array) $_POST['settings'] : array();
    
    if (empty($stylesheet)) {
        wp_send_json_error(__('Missing stylesheet parameter', 'email-newsletter'));
    }
    
    $email_newsletter_instance->register_enewsletter_themes();
    $theme = wp_get_theme($stylesheet);
    
    if (!$theme->exists()) {
        wp_send_json_error(__('Template not found', 'email-newsletter'));
    }
    
    $theme_path = $theme->get_stylesheet_directory();
    $index_file = trailingslashit($theme_path) . 'index.php';
    
    if (file_exists($index_file)) {
        $content = file_get_contents($index_file);
    } else {
        $content = "<?php\n// Template Typography Settings\n?>";
    }
    
    $typography_settings = array(
        'font_family' => isset($settings['font_family']) ? sanitize_text_field($settings['font_family']) : '',
        'h1_size' => isset($settings['h1_size']) ? sanitize_text_field($settings['h1_size']) : '',
        'h1_color' => isset($settings['h1_color']) ? sanitize_hex_color($settings['h1_color']) : '',
        'h2_size' => isset($settings['h2_size']) ? sanitize_text_field($settings['h2_size']) : '',
        'h2_color' => isset($settings['h2_color']) ? sanitize_hex_color($settings['h2_color']) : '',
        'h3_size' => isset($settings['h3_size']) ? sanitize_text_field($settings['h3_size']) : '',
        'h3_color' => isset($settings['h3_color']) ? sanitize_hex_color($settings['h3_color']) : '',
        'p_size' => isset($settings['p_size']) ? sanitize_text_field($settings['p_size']) : '',
        'p_line_height' => isset($settings['p_line_height']) ? sanitize_text_field($settings['p_line_height']) : '',
        'link_decoration' => isset($settings['link_decoration']) ? sanitize_text_field($settings['link_decoration']) : '',
        'blockquote_color' => isset($settings['blockquote_color']) ? sanitize_hex_color($settings['blockquote_color']) : '',
        'blockquote_border' => isset($settings['blockquote_border']) ? sanitize_text_field($settings['blockquote_border']) : '',
        'container_border' => isset($settings['container_border']) ? sanitize_text_field($settings['container_border']) : '',
        'container_padding' => isset($settings['container_padding']) ? sanitize_text_field($settings['container_padding']) : ''
    );
    
    $defines_to_remove = array(
        'BUILDER_CONTENT_FONT_FAMILY',
        'BUILDER_CONTENT_H1_SIZE',
        'BUILDER_CONTENT_H1_COLOR',
        'BUILDER_CONTENT_H2_SIZE',
        'BUILDER_CONTENT_H2_COLOR',
        'BUILDER_CONTENT_H3_SIZE',
        'BUILDER_CONTENT_H3_COLOR',
        'BUILDER_CONTENT_P_SIZE',
        'BUILDER_CONTENT_P_LINE_HEIGHT',
        'BUILDER_CONTENT_LINK_DECORATION',
        'BUILDER_CONTENT_BLOCKQUOTE_COLOR',
        'BUILDER_CONTENT_BLOCKQUOTE_BORDER',
        'BUILDER_CONTENT_CONTAINER_BORDER',
        'BUILDER_CONTENT_CONTAINER_PADDING'
    );
    
    foreach ($defines_to_remove as $define_name) {
        $pattern = sprintf("/define\('%s',\s*'[^']*'\);\s*\n/", $define_name);
        $content = preg_replace($pattern, '', $content);
    }
    
    $new_defines_lines = array();
    $new_defines_lines[] = '';
    $new_defines_lines[] = '// Content Typography Settings';
    $new_defines_lines[] = sprintf("define('BUILDER_CONTENT_FONT_FAMILY', '%s');", esc_attr($typography_settings['font_family']));
    $new_defines_lines[] = sprintf("define('BUILDER_CONTENT_H1_SIZE', '%s');", esc_attr($typography_settings['h1_size']));
    $new_defines_lines[] = sprintf("define('BUILDER_CONTENT_H1_COLOR', '%s');", esc_attr($typography_settings['h1_color']));
    $new_defines_lines[] = sprintf("define('BUILDER_CONTENT_H2_SIZE', '%s');", esc_attr($typography_settings['h2_size']));
    $new_defines_lines[] = sprintf("define('BUILDER_CONTENT_H2_COLOR', '%s');", esc_attr($typography_settings['h2_color']));
    $new_defines_lines[] = sprintf("define('BUILDER_CONTENT_H3_SIZE', '%s');", esc_attr($typography_settings['h3_size']));
    $new_defines_lines[] = sprintf("define('BUILDER_CONTENT_H3_COLOR', '%s');", esc_attr($typography_settings['h3_color']));
    $new_defines_lines[] = sprintf("define('BUILDER_CONTENT_P_SIZE', '%s');", esc_attr($typography_settings['p_size']));
    $new_defines_lines[] = sprintf("define('BUILDER_CONTENT_P_LINE_HEIGHT', '%s');", esc_attr($typography_settings['p_line_height']));
    $new_defines_lines[] = sprintf("define('BUILDER_CONTENT_LINK_DECORATION', '%s');", esc_attr($typography_settings['link_decoration']));
    $new_defines_lines[] = sprintf("define('BUILDER_CONTENT_BLOCKQUOTE_COLOR', '%s');", esc_attr($typography_settings['blockquote_color']));
    $new_defines_lines[] = sprintf("define('BUILDER_CONTENT_BLOCKQUOTE_BORDER', '%s');", esc_attr($typography_settings['blockquote_border']));
    $new_defines_lines[] = sprintf("define('BUILDER_CONTENT_CONTAINER_BORDER', '%s');", esc_attr($typography_settings['container_border']));
    $new_defines_lines[] = sprintf("define('BUILDER_CONTENT_CONTAINER_PADDING', '%s');", esc_attr($typography_settings['container_padding']));
    
    $new_defines = implode("\n", $new_defines_lines) . "\n";
    
    if (strpos($content, '?>') !== false) {
        $content = str_replace('?>', $new_defines . '?>', $content);
    } else {
        $content .= $new_defines;
    }
    
    if (file_put_contents($index_file, $content)) {
        wp_send_json_success(array('message' => __('Typography settings saved', 'email-newsletter')));
    } else {
        wp_send_json_error(__('Failed to save typography settings', 'email-newsletter'));
    }
}
