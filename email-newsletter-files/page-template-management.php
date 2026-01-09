<?php
global $email_newsletter;

// Security check
if(!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Register theme directories to find all templates
$email_newsletter->register_enewsletter_themes();

// Get all available templates (both plugin and custom)
$plugin_themes = wp_get_themes(array('allowed' => null, 'blog_id' => 0, 'errors' => null), $email_newsletter->template_directory);
$custom_themes = is_dir($email_newsletter->template_custom_directory) ? wp_get_themes(array('allowed' => null, 'blog_id' => 0, 'errors' => null), $email_newsletter->template_custom_directory) : array();

// Separate themes
$all_templates = array();
$plugin_templates = array();
$custom_templates = array();

// Filter to ensure only newsletter template roots are included
if($plugin_themes) {
    foreach($plugin_themes as $theme) {
        if($theme->theme_root === $email_newsletter->template_directory) {
            $plugin_templates[$theme->stylesheet] = $theme;
            $all_templates[$theme->stylesheet] = $theme;
        }
    }
}

if($custom_themes) {
    foreach($custom_themes as $theme) {
        if($theme->theme_root === $email_newsletter->template_custom_directory) {
            $custom_templates[$theme->stylesheet] = $theme;
            $all_templates[$theme->stylesheet] = $theme;
        }
    }
}
?>

<div class="wrap">
    <h1><?php _e('Newsletter Templates', 'email-newsletter'); ?></h1>
    <p><?php _e('Verwalte deine E-Mail-Vorlagen. Klone oder bearbeite bestehende Vorlagen oder lade neue hoch.', 'email-newsletter'); ?></p>
    
    <!-- Upload Section -->
    <div class="template-upload-section">
        <h2><?php _e('Template hochladen', 'email-newsletter'); ?></h2>
        <p><?php _e('Lade eine ZIP-Datei mit deiner Template-Struktur hoch.', 'email-newsletter'); ?></p>
        <form id="template-upload-form" method="post" enctype="multipart/form-data">
            <input type="file" id="template-zip-file" name="template_zip" accept=".zip" required>
            <button type="button" id="upload-template-btn" class="button button-primary">
                <span class="dashicons dashicons-upload"></span>
                <?php _e('Template hochladen', 'email-newsletter'); ?>
            </button>
            <span id="upload-status" class="upload-status"></span>
        </form>
    </div>
    
    <!-- Custom Templates Section -->
    <?php if(!empty($custom_templates)): ?>
    <div class="template-section">
        <h2><?php _e('Meine Custom Templates', 'email-newsletter'); ?></h2>
        <div class="templates-grid">
            <?php foreach($custom_templates as $stylesheet => $theme):
                $theme_url_data = $email_newsletter->get_theme_dir_url($theme, $stylesheet);
                $screenshot = $theme_url_data['url'] . 'screenshot.jpg';
            ?>
                <div class="template-card custom-template-card" data-stylesheet="<?php echo esc_attr($stylesheet); ?>" data-custom="1">
                    <div class="template-thumbnail">
                        <img src="<?php echo esc_url($screenshot); ?>" alt="<?php echo esc_attr($theme->get('Name')); ?>" class="card-image">
                    </div>
                    
                    <div class="template-info">
                        <h3><?php echo esc_html($theme->get('Name')); ?></h3>
                        <p class="template-description"><?php echo esc_html(substr($theme->get('Description'), 0, 100)); ?></p>
                        <div class="template-meta">
                            <span class="custom-badge"><?php _e('CUSTOM', 'email-newsletter'); ?></span>
                        </div>
                    </div>
                    
                    <div class="template-actions">
                        <button type="button" class="button button-secondary edit-template-btn" title="<?php _e('Bearbeiten', 'email-newsletter'); ?>" data-stylesheet="<?php echo esc_attr($stylesheet); ?>">
                            <span class="dashicons dashicons-edit"></span>
                            <?php _e('Bearbeiten', 'email-newsletter'); ?>
                        </button>
                        <button type="button" class="button button-secondary clone-template-btn" title="<?php _e('Klonen', 'email-newsletter'); ?>" data-stylesheet="<?php echo esc_attr($stylesheet); ?>">
                            <span class="dashicons dashicons-admin-page"></span>
                            <?php _e('Klonen', 'email-newsletter'); ?>
                        </button>
                        <button type="button" class="button button-secondary export-template-btn" title="<?php _e('Exportieren', 'email-newsletter'); ?>" data-stylesheet="<?php echo esc_attr($stylesheet); ?>">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Exportieren', 'email-newsletter'); ?>
                        </button>
                        <button type="button" class="button button-danger delete-template-btn" title="<?php _e('Löschen', 'email-newsletter'); ?>" data-stylesheet="<?php echo esc_attr($stylesheet); ?>">
                            <span class="dashicons dashicons-trash"></span>
                            <?php _e('Löschen', 'email-newsletter'); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else: ?>
    <div class="template-section empty-state">
        <p><?php _e('Du hast noch keine Custom Templates. Erstelle einen durch Klonen einer Vorlage oder Hochladen einer ZIP-Datei.', 'email-newsletter'); ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Plugin Templates Section -->
    <?php if(!empty($plugin_templates)): ?>
    <div class="template-section">
        <h2><?php _e('Verfügbare Vorlagen', 'email-newsletter'); ?></h2>
        <p><?php _e('Diese Vorlagen werden mit dem Plugin geliefert. Du kannst sie klonen, um deine eigenen Versionen zu erstellen.', 'email-newsletter'); ?></p>
        <div class="templates-grid">
            <?php foreach($plugin_templates as $stylesheet => $theme):
                $theme_url_data = $email_newsletter->get_theme_dir_url($theme, $stylesheet);
                $screenshot = $theme_url_data['url'] . 'screenshot.jpg';
            ?>
                <div class="template-card plugin-template-card" data-stylesheet="<?php echo esc_attr($stylesheet); ?>" data-custom="0">
                    <div class="template-thumbnail">
                        <img src="<?php echo esc_url($screenshot); ?>" alt="<?php echo esc_attr($theme->get('Name')); ?>" class="card-image">
                    </div>
                    
                    <div class="template-info">
                        <h3><?php echo esc_html($theme->get('Name')); ?></h3>
                        <p class="template-description"><?php echo esc_html(substr($theme->get('Description'), 0, 100)); ?></p>
                    </div>
                    
                    <div class="template-actions">
                        <button type="button" class="button button-primary clone-template-btn" title="<?php _e('Klonen', 'email-newsletter'); ?>" data-stylesheet="<?php echo esc_attr($stylesheet); ?>">
                            <span class="dashicons dashicons-admin-page"></span>
                            <?php _e('Klonen', 'email-newsletter'); ?>
                        </button>
                        <button type="button" class="button button-secondary export-template-btn" title="<?php _e('Exportieren', 'email-newsletter'); ?>" data-stylesheet="<?php echo esc_attr($stylesheet); ?>">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Exportieren', 'email-newsletter'); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.wrap {
    max-width: 1400px;
    margin: 0 auto;
}

.template-upload-section {
    background: #f9f9f9;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 30px;
}

.template-upload-section h2 {
    margin-top: 0;
}

.template-upload-section form {
    display: flex;
    gap: 10px;
    align-items: center;
}

.template-upload-section input[type="file"] {
    flex: 1;
}

.upload-status {
    margin-left: 10px;
    display: inline-block;
    min-width: 200px;
}

.upload-status.success {
    color: #00a32a;
}

.upload-status.error {
    color: #d63638;
}

.template-section {
    margin-bottom: 40px;
}

.template-section h2 {
    border-bottom: 2px solid #0073aa;
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.template-card {
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.2s;
    display: flex;
    flex-direction: column;
}

.template-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-color: #0073aa;
}

.template-thumbnail {
    width: 100%;
    height: 150px;
    overflow: hidden;
    background: #f0f0f0;
}

.template-thumbnail .card-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.template-info {
    padding: 15px;
    flex: 1;
    border-bottom: 1px solid #eee;
}

.template-info h3 {
    margin: 0 0 5px;
    font-size: 16px;
    font-weight: 600;
}

.template-description {
    margin: 0;
    font-size: 13px;
    color: #666;
    line-height: 1.4;
}

.template-meta {
    margin-top: 10px;
}

.custom-badge {
    display: inline-block;
    background: #2ecc71;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
}

.template-actions {
    padding: 10px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.template-actions button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
    font-size: 13px;
    padding: 8px 10px;
    width: 100%;
}

.template-actions .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.button.button-danger {
    background-color: #d63638;
    border-color: #d63638;
    color: white;
}

.button.button-danger:hover {
    background-color: #b81c23;
    border-color: #b81c23;
}

.empty-state {
    background: #f9f9f9;
    padding: 40px;
    text-align: center;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.empty-state p {
    margin: 0;
    color: #666;
    font-size: 15px;
}
</style>

<script>
jQuery(document).ready(function($) {
    var ajaxurl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';
    var nonce = '<?php echo wp_create_nonce('enewsletter_template_actions'); ?>';
    
    // Clone template
    $(document).on('click', '.clone-template-btn', function(e) {
        e.preventDefault();
        var stylesheet = $(this).data('stylesheet');
        var newName = prompt('<?php _e('Gib einen Namen für die geklonte Vorlage ein:', 'email-newsletter'); ?>', stylesheet + '-copy');
        
        if(newName) {
            $.post(ajaxurl, {
                action: 'enewsletter_clone_template',
                stylesheet: stylesheet,
                new_name: newName,
                nonce: nonce
            }, function(response) {
                if(response.success) {
                    alert('<?php _e('Vorlage erfolgreich geklont!', 'email-newsletter'); ?>');
                    location.reload();
                } else {
                    alert(response.data || '<?php _e('Fehler beim Klonen', 'email-newsletter'); ?>');
                }
            });
        }
    });
    
    // Edit template
    $(document).on('click', '.edit-template-btn', function(e) {
        e.preventDefault();
        var $card = $(this).closest('.template-card');
        var isCustom = $card.data('custom') === 1 || $card.data('custom') === '1';
        if(!isCustom) {
            alert('<?php _e('Bitte klone diese Vorlage zuerst. Originale können nicht direkt bearbeitet werden.', 'email-newsletter'); ?>');
            return;
        }
        var stylesheet = $(this).data('stylesheet');
        var editorUrl = '<?php echo esc_js(admin_url('admin.php?page=newsletters-template-editor')); ?>&template=' + encodeURIComponent(stylesheet);
        window.open(editorUrl, '_blank');
    });
    
    // Export template
    $(document).on('click', '.export-template-btn', function(e) {
        e.preventDefault();
        var stylesheet = $(this).data('stylesheet');
        var exportUrl = ajaxurl + '?action=enewsletter_export_template&stylesheet=' + encodeURIComponent(stylesheet) + '&nonce=' + nonce;
        window.location.href = exportUrl;
    });
    
    // Delete template
    $(document).on('click', '.delete-template-btn', function(e) {
        e.preventDefault();
        if(!confirm('<?php _e('Möchtest du diese Vorlage wirklich löschen? Dies kann nicht rückgängig gemacht werden.', 'email-newsletter'); ?>')) {
            return;
        }
        
        var $btn = $(this);
        var stylesheet = $btn.data('stylesheet');
        
        $.post(ajaxurl, {
            action: 'enewsletter_delete_template',
            stylesheet: stylesheet,
            nonce: nonce
        }, function(response) {
            if(response.success) {
                alert('<?php _e('Vorlage erfolgreich gelöscht!', 'email-newsletter'); ?>');
                location.reload();
            } else {
                alert(response.data || '<?php _e('Fehler beim Löschen', 'email-newsletter'); ?>');
            }
        });
    });
    
    // Upload template
    $('#upload-template-btn').on('click', function(e) {
        e.preventDefault();
        var file = $('#template-zip-file')[0].files[0];
        
        if(!file) {
            alert('<?php _e('Bitte wähle eine ZIP-Datei aus.', 'email-newsletter'); ?>');
            return;
        }
        
        if(file.type !== 'application/zip' && file.type !== 'application/x-zip-compressed') {
            alert('<?php _e('Nur ZIP-Dateien sind erlaubt.', 'email-newsletter'); ?>');
            return;
        }
        
        var formData = new FormData();
        formData.append('action', 'enewsletter_upload_template');
        formData.append('template_zip', file);
        formData.append('nonce', nonce);
        
        var $status = $('#upload-status');
        $status.removeClass('success error').text('<?php _e('Lädt...', 'email-newsletter'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if(response.success) {
                    $status.addClass('success').text('✓ <?php _e('Template erfolgreich hochgeladen!', 'email-newsletter'); ?>');
                    setTimeout(function() { location.reload(); }, 1500);
                } else {
                    $status.addClass('error').text('✗ ' + (response.data || '<?php _e('Fehler beim Upload', 'email-newsletter'); ?>'));
                }
            },
            error: function() {
                $status.addClass('error').text('✗ <?php _e('Netzwerkfehler', 'email-newsletter'); ?>');
            }
        });
    });
});
</script>
