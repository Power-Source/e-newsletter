<?php

class Builder_Template_Grid_Control extends WP_Customize_Control {
	public $type = 'email_template_grid';
	public $themes = array();
	public $current_theme = '';

	public function __construct( $manager, $id, $args = array() ) {
		if ( isset( $args['themes'] ) ) {
			$this->themes = $args['themes'];
		}
		if ( isset( $args['current_theme'] ) ) {
			$this->current_theme = $args['current_theme'];
		}
		// Set a default setting if none provided
		if ( ! isset( $args['settings'] ) ) {
			$args['settings'] = 'template';
		}
		parent::__construct( $manager, $id, $args );
	}

	public function render_content() {
		global $email_newsletter;
		?>
		<label class="template-grid-label">
			<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
		</label>
		
		<div class="template-grid-actions">
			<button type="button" class="button button-secondary upload-template-btn">
				<span class="dashicons dashicons-upload"></span>
				<?php _e('Vorlage hochladen (ZIP)', 'email-newsletter'); ?>
			</button>
		</div>
		
		<div class="email-templates-grid">
			<?php foreach( $this->themes as $theme ): ?>
				<?php 
					$is_current = ( $theme->stylesheet === $this->current_theme );
					$theme_url = $email_newsletter->get_theme_dir_url( $theme, $theme->stylesheet );
					$screenshot = $theme_url['url'] . 'screenshot.jpg';
					$is_custom = ( $theme->theme_root === $email_newsletter->template_custom_directory );
				?>
				<div class="email-template-item <?php echo $is_current ? 'current-template' : ''; ?> <?php echo $is_custom ? 'custom-template' : ''; ?>" data-stylesheet="<?php echo esc_attr( $theme->stylesheet ); ?>">
					<?php if( $is_custom ): ?>
						<span class="custom-badge">CUSTOM</span>
					<?php endif; ?>
					
					<img src="<?php echo esc_url( $screenshot ); ?>" class="template-thumbnail" alt="<?php echo esc_attr( $theme->get('Name') ); ?>" />
					<h4 class="template-name"><?php echo esc_html( $theme->get('Name') ); ?></h4>
					
					<div class="template-actions">
						<button type="button" class="button button-primary activate-template-btn">
							<?php _e('Aktivieren', 'email-newsletter'); ?>
						</button>
						
						<?php if( $is_custom ): ?>
							<button type="button" class="button button-secondary edit-template-btn" data-stylesheet="<?php echo esc_attr( $theme->stylesheet ); ?>">
								<span class="dashicons dashicons-edit"></span>
							</button>
							<button type="button" class="button button-secondary export-template-btn" data-stylesheet="<?php echo esc_attr( $theme->stylesheet ); ?>">
								<span class="dashicons dashicons-download"></span>
							</button>
						<?php endif; ?>
						
						<button type="button" class="button button-secondary clone-template-btn" data-stylesheet="<?php echo esc_attr( $theme->stylesheet ); ?>" title="<?php _e('Vorlage klonen', 'email-newsletter'); ?>">
							<span class="dashicons dashicons-admin-page"></span>
						</button>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		
		<!-- Custom Modal -->
		<div id="enewsletter-modal" class="enewsletter-modal" style="display:none;">
			<div class="enewsletter-modal-overlay"></div>
			<div class="enewsletter-modal-content">
				<div class="enewsletter-modal-header">
					<h3 class="enewsletter-modal-title"></h3>
					<button type="button" class="enewsletter-modal-close">&times;</button>
				</div>
				<div class="enewsletter-modal-body"></div>
				<div class="enewsletter-modal-footer">
					<button type="button" class="button button-secondary enewsletter-modal-cancel"><?php _e('Abbrechen', 'email-newsletter'); ?></button>
					<button type="button" class="button button-primary enewsletter-modal-confirm"><?php _e('OK', 'email-newsletter'); ?></button>
				</div>
			</div>
		</div>
		
		<style>
			.enewsletter-modal {
				position: fixed;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				z-index: 999999;
			}
			.enewsletter-modal-overlay {
				position: absolute;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				background: rgba(0, 0, 0, 0.7);
				backdrop-filter: blur(2px);
			}
			.enewsletter-modal-content {
				position: relative;
				background: #fff;
				max-width: 500px;
				margin: 100px auto;
				border-radius: 4px;
				box-shadow: 0 5px 15px rgba(0,0,0,0.3);
				animation: enewsletterModalSlideIn 0.3s ease-out;
			}
			@keyframes enewsletterModalSlideIn {
				from {
					transform: translateY(-50px);
					opacity: 0;
				}
				to {
					transform: translateY(0);
					opacity: 1;
				}
			}
			.enewsletter-modal-header {
				padding: 15px 20px;
				border-bottom: 1px solid #ddd;
				display: flex;
				justify-content: space-between;
				align-items: center;
			}
			.enewsletter-modal-title {
				margin: 0;
				font-size: 18px;
				font-weight: 600;
			}
			.enewsletter-modal-close {
				background: none;
				border: none;
				font-size: 28px;
				line-height: 1;
				color: #666;
				cursor: pointer;
				padding: 0;
				width: 30px;
				height: 30px;
				display: flex;
				align-items: center;
				justify-content: center;
				border-radius: 3px;
				transition: all 0.2s;
			}
			.enewsletter-modal-close:hover {
				background: #f0f0f0;
				color: #000;
			}
			.enewsletter-modal-body {
				padding: 20px;
			}
			.enewsletter-modal-body input[type="text"] {
				width: 100%;
				padding: 8px 12px;
				font-size: 14px;
				border: 1px solid #ddd;
				border-radius: 3px;
				margin-top: 10px;
			}
			.enewsletter-modal-body input[type="text"]:focus {
				border-color: #2271b1;
				outline: none;
				box-shadow: 0 0 0 1px #2271b1;
			}
			.enewsletter-modal-footer {
				padding: 15px 20px;
				border-top: 1px solid #ddd;
				display: flex;
				justify-content: flex-end;
				gap: 10px;
			}
			.enewsletter-modal-message {
				font-size: 14px;
				line-height: 1.6;
			}
			.enewsletter-modal-message.success {
				color: #00a32a;
			}
			.enewsletter-modal-message.error {
				color: #d63638;
			}
		</style>
		
		<script type="text/javascript">
			(function($) {
				// Modal Helper Functions
				window.EnewsletterModal = {
					show: function(title, message, type, callback) {
						var $modal = $('#enewsletter-modal');
						var $footer = $modal.find('.enewsletter-modal-footer');
						
						$modal.find('.enewsletter-modal-title').text(title);
						$modal.find('.enewsletter-modal-body').html('<div class="enewsletter-modal-message ' + (type || '') + '">' + message + '</div>');
						
						if(type === 'success' || type === 'error') {
							$footer.find('.enewsletter-modal-cancel').hide();
							$footer.find('.enewsletter-modal-confirm').text('<?php _e('OK', 'email-newsletter'); ?>').show();
						} else {
							$footer.find('.enewsletter-modal-cancel').show();
							$footer.find('.enewsletter-modal-confirm').text('<?php _e('OK', 'email-newsletter'); ?>').show();
						}
						
						$modal.fadeIn(200);
						
						$modal.off('click').on('click', '.enewsletter-modal-confirm, .enewsletter-modal-close, .enewsletter-modal-cancel, .enewsletter-modal-overlay', function(e) {
							if($(e.target).hasClass('enewsletter-modal-confirm')) {
								if(callback) callback(true);
							} else {
								if(callback) callback(false);
							}
							$modal.fadeOut(200);
						});
					},
					
					prompt: function(title, message, defaultValue, callback) {
						var $modal = $('#enewsletter-modal');
						var $footer = $modal.find('.enewsletter-modal-footer');
						
						$modal.find('.enewsletter-modal-title').text(title);
						$modal.find('.enewsletter-modal-body').html(
							'<div class="enewsletter-modal-message">' + message + '</div>' +
							'<input type="text" class="enewsletter-modal-input" value="' + (defaultValue || '') + '">'
						);
						
						$footer.find('.enewsletter-modal-cancel').show();
						$footer.find('.enewsletter-modal-confirm').text('<?php _e('OK', 'email-newsletter'); ?>').show();
						
						$modal.fadeIn(200, function() {
							$modal.find('.enewsletter-modal-input').focus().select();
						});
						
						var handleSubmit = function(confirmed) {
							var value = confirmed ? $modal.find('.enewsletter-modal-input').val() : null;
							$modal.fadeOut(200);
							if(callback) callback(value);
						};
						
						$modal.off('click keypress').on('click', '.enewsletter-modal-confirm, .enewsletter-modal-close, .enewsletter-modal-cancel, .enewsletter-modal-overlay', function(e) {
							if($(e.target).hasClass('enewsletter-modal-confirm')) {
								handleSubmit(true);
							} else {
								handleSubmit(false);
							}
						}).on('keypress', '.enewsletter-modal-input', function(e) {
							if(e.which === 13) {
								e.preventDefault();
								handleSubmit(true);
							}
						});
					},
					
					alert: function(title, message, type) {
						this.show(title, message, type || 'success');
					}
				};
				
				$(document).ready(function() {
					var $control = $('.customize-control-email_template_grid');
					
					// Activate Template
					$control.on('click', '.activate-template-btn', function(e) {
						e.preventDefault();
						var $item = $(this).closest('.email-template-item');
						var stylesheet = $item.data('stylesheet');
						
						if(stylesheet) {
							$('[data-customize-setting-link="template"]').val(stylesheet).trigger('change');
							
							var interval = setInterval(function() {
								if($('[data-customize-setting-link="template"]').val() === stylesheet) {
									$('#save').trigger('click');
									clearInterval(interval);
								}
							}, 100);
						}
					});
					
					// Clone Template
					$control.on('click', '.clone-template-btn', function(e) {
						e.preventDefault();
						var stylesheet = $(this).data('stylesheet');
						
						EnewsletterModal.prompt(
							'<?php _e('Vorlage klonen', 'email-newsletter'); ?>',
							'<?php _e('Gib einen Namen für die geklonte Vorlage ein:', 'email-newsletter'); ?>',
							'',
							function(newName) {
								if(newName) {
									$.post(ajaxurl, {
										action: 'enewsletter_clone_template',
										stylesheet: stylesheet,
										new_name: newName,
										nonce: '<?php echo wp_create_nonce('enewsletter_template_actions'); ?>'
									}, function(response) {
										if(response.success) {
											EnewsletterModal.alert(
												'<?php _e('Erfolg', 'email-newsletter'); ?>',
												'<?php _e('Vorlage erfolgreich geklont! Editor wird geöffnet...', 'email-newsletter'); ?>',
												'success'
											);
											setTimeout(function() {
												if(response.data && response.data.editor_url) {
													window.open(response.data.editor_url, '_blank');
												}
												location.reload();
											}, 1000);
										} else {
											EnewsletterModal.alert(
												'<?php _e('Fehler', 'email-newsletter'); ?>',
												response.data || '<?php _e('Fehler beim Klonen der Vorlage', 'email-newsletter'); ?>',
												'error'
											);
										}
									});
								}
							}
						);
					});
					
					// Edit Template
					$control.on('click', '.edit-template-btn', function(e) {
						e.preventDefault();
						var stylesheet = $(this).data('stylesheet');
						var editorUrl = '<?php echo admin_url('admin.php?page=newsletters-template-editor'); ?>&template=' + encodeURIComponent(stylesheet);
						window.open(editorUrl, '_blank');
					});
					
					// Export Template
					$control.on('click', '.export-template-btn', function(e) {
						e.preventDefault();
						var stylesheet = $(this).data('stylesheet');
						var exportUrl = '<?php echo admin_url('admin-ajax.php'); ?>?action=enewsletter_export_template&stylesheet=' + encodeURIComponent(stylesheet) + '&nonce=<?php echo wp_create_nonce('enewsletter_template_actions'); ?>';
						window.location.href = exportUrl;
					});
					
					// Upload Template
					$control.on('click', '.upload-template-btn', function(e) {
						e.preventDefault();
						
						// Create file input if not exists
						if(!$('#template-zip-upload').length) {
							$('body').append('<input type="file" id="template-zip-upload" accept=".zip" style="display:none">');
						}
						
						$('#template-zip-upload').off('change').on('change', function() {
							var file = this.files[0];
							if(file) {
								var formData = new FormData();
								formData.append('action', 'enewsletter_upload_template');
								formData.append('template_zip', file);
								formData.append('nonce', '<?php echo wp_create_nonce('enewsletter_template_actions'); ?>');
								
								$.ajax({
									url: ajaxurl,
									type: 'POST',
									data: formData,
									processData: false,
									contentType: false,
									success: function(response) {
										if(response.success) {
											EnewsletterModal.alert(
												'<?php _e('Erfolg', 'email-newsletter'); ?>',
												'<?php _e('Vorlage erfolgreich hochgeladen!', 'email-newsletter'); ?>',
												'success'
											);
											setTimeout(function() { location.reload(); }, 1500);
										} else {
											EnewsletterModal.alert(
												'<?php _e('Fehler', 'email-newsletter'); ?>',
												response.data || '<?php _e('Fehler beim Hochladen der Vorlage', 'email-newsletter'); ?>',
												'error'
											);
										}
									},
									error: function() {
										EnewsletterModal.alert(
											'<?php _e('Fehler', 'email-newsletter'); ?>',
											'<?php _e('Netzwerkfehler beim Hochladen', 'email-newsletter'); ?>',
											'error'
										);
									}
								});
							}
						}).trigger('click');
					});
				});
			})(jQuery);
		</script>
		<?php
	}
}

