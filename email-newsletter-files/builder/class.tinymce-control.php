<?php
class Builder_TinyMCE_Control extends WP_Customize_Control {
	public $type = 'tinymce';
	
	
	public function render_content() {
		global $enewsletter_tinymce, $email_builder;
		// Fallback: if TinyMCE markup was not prepared yet, build it now
		if ( empty( $enewsletter_tinymce ) && isset( $email_builder ) && method_exists( $email_builder, 'prepare_tinymce' ) ) {
			$email_builder->prepare_tinymce();
		}
		?>
		<span class="customize-control-title"><?php echo $this->label; ?></span>
		<textarea id="<?php echo $this->id; ?>" style="display:none" <?php echo $this->link(); ?>><?php echo esc_textarea($this->value()); ?></textarea>
		<?php
		echo $enewsletter_tinymce;
		?>
		
		<script type="text/javascript">
			jQuery(document).ready( function() {
				var content = 0;
				var editorId = 'content_tinymce';
				var settingField = '<?php echo esc_js( $this->id ); ?>';
				var editorReady = false;
				var bootstrapAttempts = 0;

				function bootstrapEditor() {
					if (typeof tinymce === 'undefined') return false;
					if (!window.tinyMCEPreInit || !tinyMCEPreInit.mceInit || !tinyMCEPreInit.mceInit[editorId]) return false;

					var initCfg = tinyMCEPreInit.mceInit[editorId];
					if (!tinymce.get(editorId)) {
						// Ensure selector points at our textarea
						initCfg.selector = '#'+editorId;
						try {
							tinymce.init(initCfg);
							window.wpActiveEditor = editorId;
						} catch(e) {}
					}

					editorReady = !!tinymce.get(editorId);
					return editorReady;
				}

				function ensureEditorInitialized() {
					if (!window.wp || !wp.editor || !wp.editor.initialize) return;
					if (!window.tinyMCEPreInit || !tinyMCEPreInit.mceInit || !tinyMCEPreInit.mceInit[editorId]) return;
					if (typeof tinymce === 'undefined') return;

					if (!tinymce.get(editorId)) {
						var initCfg = tinyMCEPreInit.mceInit[editorId];
						wp.editor.initialize(editorId, initCfg);
					}

					editorReady = !!tinymce.get(editorId);
				}

				// Try immediately and then retry; fall back to direct tinymce.init if wp.editor.initialize is unavailable
				ensureEditorInitialized();
				bootstrapEditor();
				var initRetries = 0;
				var initTimer = setInterval(function() {
					if (editorReady || initRetries > 20) {
						clearInterval(initTimer);
						return;
					}
					initRetries++;
					ensureEditorInitialized();
					bootstrapEditor();
				}, 300);

				// Safely poll TinyMCE once the editor is available
				tinymce_check_changes = setInterval(function() {
					if (typeof tinymce === 'undefined') return;
					var editor = tinymce.get(editorId);
					if (!editor || typeof editor.getContent !== 'function') return;

					var check_content = editor.getContent({format : 'raw'});
					
					if(check_content != content && check_content != '<p><br data-mce-bogus="1"></p>') {
						content = check_content;

						jQuery('#' + settingField).val(content).trigger('change');
					}
				}, 2000);

				//enables resizing of E-Mail-Inhalt box
				var resize;
				var prev_emce_width = 0;
				jQuery('#accordion-section-builder_email_content').on('mousedown', '.mce-i-resize, #content_tinymce_resize', function(){
					resize_start();
				});
				jQuery('#accordion-section-builder_email_content h3').on('click', function(){
					resize_start();
				});
				jQuery("body").on('mouseup', function() {
				    clearInterval(resize);
				});

				function resize_start() {
				    resize = setInterval(function() {
						emce_width = jQuery('#content_tinymce_ifr').width()+65;
						
						if(emce_width >= '490' && emce_width != prev_emce_width) {
						    jQuery('#customize-controls').css("-webkit-animation", "none");
						    jQuery('#customize-controls').css("-moz-animation", "none");
						    jQuery('#customize-controls').css("-ms-animation", "none");
						    jQuery('#customize-controls').css("animation", "none");
							prev_emce_width = emce_width;
							jQuery('#customize-controls, #customize-footer-actions').css("width", emce_width+"px");
							jQuery('.wp-full-overlay').css("margin-left", emce_width+"px");
							jQuery('.wp-full-overlay-sidebar').css("margin-left", "-"+emce_width+"px");
						}
				    },50);	
				}
			});
		</script>
		<?php
	}
}
?>