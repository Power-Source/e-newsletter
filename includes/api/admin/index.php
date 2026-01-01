<?php
/* @var $api NewsletterRestApi */

defined('ABSPATH') || exit;

include_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

// API Key Actions
if ($controls->is_action('create_key')) {
    $name = $controls->data['name'];
    $permissions = $controls->data['permissions'] ?: $api->get_default_permissions();
    
    $api_credentials = $api->create_api_key($name, $permissions);
    
    if ($api_credentials) {
        $controls->messages = 'API-Schlüssel erfolgreich erstellt!';
        $controls->data['new_api_key'] = $api_credentials['api_key'];
        $controls->data['new_api_secret'] = $api_credentials['api_secret'];
    } else {
        $controls->errors = 'Fehler beim Erstellen des API-Schlüssels.';
    }
}

if ($controls->is_action('delete_key')) {
    $key_id = intval($controls->data['key_id']);
    if ($api->delete_api_key($key_id)) {
        $controls->messages = 'API-Schlüssel gelöscht.';
    } else {
        $controls->errors = 'Fehler beim Löschen des API-Schlüssels.';
    }
}

if ($controls->is_action('deactivate_key')) {
    $key_id = intval($controls->data['key_id']);
    if ($api->deactivate_api_key($key_id)) {
        $controls->messages = 'API-Schlüssel deaktiviert.';
    } else {
        $controls->errors = 'Fehler beim Deaktivieren des API-Schlüssels.';
    }
}

$api_keys = $api->get_api_keys();
?>

<div class="wrap" id="tnp-wrap">
    <?php include NEWSLETTER_ADMIN_HEADER; ?>
    
    <div id="tnp-heading">
        <h2><?php _e('REST API', 'newsletter'); ?></h2>
        <p><?php _e('Verwalte API-Schlüssel für den programmatischen Zugriff auf Newsletter-Daten.', 'newsletter'); ?></p>
    </div>

    <div id="tnp-body">
        <?php $controls->show(); ?>

        <!-- API-Dokumentation -->
        <div class="tnp-card" style="margin-bottom: 20px;">
            <h3><i class="fas fa-book"></i> <?php _e('API-Dokumentation', 'newsletter'); ?></h3>
            <p><?php _e('Die Newsletter REST API bietet folgende Endpunkte:', 'newsletter'); ?></p>
            
            <div style="background: #f9f9f9; padding: 15px; border-radius: 4px; margin: 10px 0;">
                <h4><?php _e('Basis-URL:', 'newsletter'); ?></h4>
                <code><?php echo rest_url('newsletter/v2/'); ?></code>
                
                <h4 style="margin-top: 15px;"><?php _e('Verfügbare Endpunkte:', 'newsletter'); ?></h4>
                <ul style="margin-left: 20px;">
                    <li><code>GET /subscribers</code> - <?php _e('Alle Abonnenten abrufen', 'newsletter'); ?></li>
                    <li><code>POST /subscribers</code> - <?php _e('Neuen Abonnenten erstellen', 'newsletter'); ?></li>
                    <li><code>GET /subscribers/{id}</code> - <?php _e('Abonnent per ID abrufen', 'newsletter'); ?></li>
                    <li><code>PUT /subscribers/{id}</code> - <?php _e('Abonnent aktualisieren', 'newsletter'); ?></li>
                    <li><code>DELETE /subscribers/{id}</code> - <?php _e('Abonnent löschen', 'newsletter'); ?></li>
                    <li><code>GET /lists</code> - <?php _e('Alle Listen abrufen', 'newsletter'); ?></li>
                    <li><code>GET /newsletters</code> - <?php _e('Alle Newsletter abrufen', 'newsletter'); ?></li>
                </ul>
                
                <h4 style="margin-top: 15px;"><?php _e('Authentifizierung:', 'newsletter'); ?></h4>
                <p><?php _e('Füge diese Header zu deinen API-Anfragen hinzu:', 'newsletter'); ?></p>
                <pre style="background: #fff; padding: 10px; border: 1px solid #ddd;">X-API-Key: dein_api_schluessel
X-API-Secret: dein_api_secret</pre>
            </div>
        </div>

        <!-- Neuen API-Schlüssel erstellen -->
        <form method="post" action="">
            <?php $controls->init(); ?>
            
            <div class="tnp-card">
                <h3><i class="fas fa-plus"></i> <?php _e('Neuen API-Schlüssel erstellen', 'newsletter'); ?></h3>
                
                <table class="form-table">
                    <tr>
                        <th><?php _e('Name', 'newsletter'); ?></th>
                        <td>
                            <?php $controls->text('name', 50); ?>
                            <p class="description"><?php _e('Beschreibender Name für diesen API-Schlüssel', 'newsletter'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Berechtigungen', 'newsletter'); ?></th>
                        <td>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                                <label><input type="checkbox" name="permissions[subscribers][]" value="read" checked> <?php _e('Abonnenten lesen', 'newsletter'); ?></label>
                                <label><input type="checkbox" name="permissions[subscribers][]" value="write" checked> <?php _e('Abonnenten schreiben', 'newsletter'); ?></label>
                                <label><input type="checkbox" name="permissions[subscribers][]" value="delete"> <?php _e('Abonnenten löschen', 'newsletter'); ?></label>
                                <label><input type="checkbox" name="permissions[lists][]" value="read" checked> <?php _e('Listen lesen', 'newsletter'); ?></label>
                                <label><input type="checkbox" name="permissions[newsletters][]" value="read" checked> <?php _e('Newsletter lesen', 'newsletter'); ?></label>
                            </div>
                        </td>
                    </tr>
                </table>
                
                <p>
                    <?php $controls->button('create_key', __('API-Schlüssel erstellen', 'newsletter'), ['class' => 'button-primary']); ?>
                </p>
            </div>
        </form>

        <!-- Neuer API-Schlüssel anzeigen -->
        <?php if (isset($controls->data['new_api_key'])): ?>
        <div class="tnp-card" style="border-left: 4px solid #00a32a;">
            <h3><i class="fas fa-key"></i> <?php _e('Neuer API-Schlüssel erstellt', 'newsletter'); ?></h3>
            <p><strong><?php _e('Wichtig:', 'newsletter'); ?></strong> <?php _e('Speichere diese Daten sicher. Der Secret wird nicht mehr angezeigt!', 'newsletter'); ?></p>
            
            <div style="background: #f9f9f9; padding: 15px; border-radius: 4px; font-family: monospace;">
                <p><strong><?php _e('API-Schlüssel:', 'newsletter'); ?></strong><br>
                <code style="background: #fff; padding: 5px; display: block; margin-top: 5px;"><?php echo esc_html($controls->data['new_api_key']); ?></code></p>
                
                <p><strong><?php _e('API-Secret:', 'newsletter'); ?></strong><br>
                <code style="background: #fff; padding: 5px; display: block; margin-top: 5px;"><?php echo esc_html($controls->data['new_api_secret']); ?></code></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Vorhandene API-Schlüssel -->
        <div class="tnp-card">
            <h3><i class="fas fa-list"></i> <?php _e('Vorhandene API-Schlüssel', 'newsletter'); ?></h3>
            
            <?php if (empty($api_keys)): ?>
                <p><?php _e('Noch keine API-Schlüssel erstellt.', 'newsletter'); ?></p>
            <?php else: ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'newsletter'); ?></th>
                            <th><?php _e('API-Schlüssel', 'newsletter'); ?></th>
                            <th><?php _e('Status', 'newsletter'); ?></th>
                            <th><?php _e('Erstellt', 'newsletter'); ?></th>
                            <th><?php _e('Zuletzt verwendet', 'newsletter'); ?></th>
                            <th><?php _e('Aktionen', 'newsletter'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($api_keys as $key): ?>
                        <tr>
                            <td><strong><?php echo esc_html($key->name); ?></strong></td>
                            <td><code><?php echo esc_html($key->api_key); ?></code></td>
                            <td>
                                <?php if ($key->status === 'active'): ?>
                                    <span style="color: #00a32a;"><i class="fas fa-check-circle"></i> <?php _e('Aktiv', 'newsletter'); ?></span>
                                <?php else: ?>
                                    <span style="color: #d63638;"><i class="fas fa-times-circle"></i> <?php _e('Inaktiv', 'newsletter'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($key->created))); ?></td>
                            <td><?php echo $key->last_used ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($key->last_used))) : '—'; ?></td>
                            <td>
                                <?php if ($key->status === 'active'): ?>
                                <form method="post" style="display: inline-block;">
                                    <?php $controls->init(); ?>
                                    <input type="hidden" name="key_id" value="<?php echo $key->id; ?>">
                                    <?php $controls->button('deactivate_key', __('Deaktivieren', 'newsletter'), ['class' => 'button button-small', 'onclick' => 'return confirm("' . __('Wirklich deaktivieren?', 'newsletter') . '")']); ?>
                                </form>
                                <?php endif; ?>
                                
                                <form method="post" style="display: inline-block;">
                                    <?php $controls->init(); ?>
                                    <input type="hidden" name="key_id" value="<?php echo $key->id; ?>">
                                    <?php $controls->button('delete_key', __('Löschen', 'newsletter'), ['class' => 'button button-small button-link-delete', 'onclick' => 'return confirm("' . __('Wirklich löschen?', 'newsletter') . '")']); ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- API-Test -->
        <div class="tnp-card">
            <h3><i class="fas fa-flask"></i> <?php _e('API-Test', 'newsletter'); ?></h3>
            <p><?php _e('Teste deine API-Verbindung mit diesem einfachen Tool:', 'newsletter'); ?></p>
            
            <div id="api-test-tool" style="background: #f9f9f9; padding: 15px; border-radius: 4px;">
                <div style="margin-bottom: 10px;">
                    <label><strong><?php _e('API-Schlüssel:', 'newsletter'); ?></strong></label>
                    <input type="text" id="test-api-key" style="width: 100%; margin-top: 5px;" placeholder="tnp_...">
                </div>
                
                <div style="margin-bottom: 10px;">
                    <label><strong><?php _e('API-Secret:', 'newsletter'); ?></strong></label>
                    <input type="password" id="test-api-secret" style="width: 100%; margin-top: 5px;">
                </div>
                
                <div style="margin-bottom: 10px;">
                    <label><strong><?php _e('Endpunkt:', 'newsletter'); ?></strong></label>
                    <select id="test-endpoint" style="width: 100%; margin-top: 5px;">
                        <option value="subscribers">GET /subscribers</option>
                        <option value="lists">GET /lists</option>
                        <option value="newsletters">GET /newsletters</option>
                    </select>
                </div>
                
                <button type="button" id="test-api-btn" class="button"><?php _e('API testen', 'newsletter'); ?></button>
                
                <div id="api-test-result" style="margin-top: 15px; display: none;">
                    <h4><?php _e('Ergebnis:', 'newsletter'); ?></h4>
                    <pre style="background: #fff; padding: 10px; border: 1px solid #ddd; max-height: 300px; overflow: auto;"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#test-api-btn').click(function() {
        const apiKey = $('#test-api-key').val();
        const apiSecret = $('#test-api-secret').val();
        const endpoint = $('#test-endpoint').val();
        
        if (!apiKey || !apiSecret) {
            alert('<?php _e('Bitte API-Schlüssel und Secret eingeben', 'newsletter'); ?>');
            return;
        }
        
        const $result = $('#api-test-result');
        const $pre = $result.find('pre');
        
        $result.show();
        $pre.text('<?php _e('Teste...', 'newsletter'); ?>');
        
        $.ajax({
            url: '<?php echo rest_url('newsletter/v2/'); ?>' + endpoint,
            method: 'GET',
            headers: {
                'X-API-Key': apiKey,
                'X-API-Secret': apiSecret
            },
            success: function(data) {
                $pre.text(JSON.stringify(data, null, 2));
            },
            error: function(xhr) {
                $pre.text('Error: ' + xhr.status + '\n\n' + JSON.stringify(JSON.parse(xhr.responseText), null, 2));
            }
        });
    });
});
</script>

<style>
.tnp-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.tnp-card h3 {
    margin-top: 0;
    color: #23282d;
}

.tnp-card h3 i {
    margin-right: 8px;
    color: #3c434a;
}

code {
    background: #f1f1f1;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: Consolas, Monaco, monospace;
}
</style>
