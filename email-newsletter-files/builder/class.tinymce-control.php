<?php
class Builder_TinyMCE_Control extends WP_Customize_Control {
	public $type = 'tinymce';
	
	
	public function render_content() {
		global $email_builder;
		$has_mp_products = class_exists( 'MP_Product' ) && method_exists( 'MP_Product', 'get_post_type' );
		$has_wp_posts = true;
		$search_nonce = wp_create_nonce( 'enews_builder_search_items' );
		$editor_id = 'content_tinymce';
		$setting_id = $this->id;
		?>
		<span class="customize-control-title"><?php echo $this->label; ?></span>
		<input type="hidden" id="enews-setting-<?php echo esc_attr( $setting_id ); ?>" value="<?php echo esc_attr( $this->value() ); ?>" <?php $this->link(); ?> />
		
		<!-- TinyMCE Editor - render directly -->
		<?php
		$tinymce_options = array(
			'teeny' => false,
			'media_buttons' => true,
			'quicktags' => false,
			'textarea_rows' => 25,
			'drag_drop_upload' => true,
			'tinymce' => array(
				'wp_skip_init' => false,
				'theme_advanced_disable' => '',
				'theme_advanced_buttons1_add' => 'code',
				'theme_advanced_resize_horizontal' => true,
				'add_unload_trigger' => false,
				'resize' => 'both'
			),
			'editor_css' => '<style type="text/css">body { background:#ffffff; color:#111111; } a { color:#2271b1; }</style>',
		);
		wp_editor( $this->value(), $editor_id, $tinymce_options );
		?>
		
		<!-- Icon Toolbar template; moved into media buttons row via JS -->
		<div id="enews-inline-toolbar" class="enews-insert-toolbar" role="toolbar" aria-label="Newsletter Elemente einfuegen" style="display:none;">
			<?php if ( $has_mp_products ) : ?>
				<button type="button" id="enews-product-insert-open" class="button enews-toolbar-button" title="<?php echo esc_attr__( 'Produkte einfuegen', 'email-newsletter' ); ?>" aria-label="<?php echo esc_attr__( 'Produkte einfuegen', 'email-newsletter' ); ?>">
					<span class="dashicons dashicons-cart"></span>
				</button>
			<?php endif; ?>
			<?php if ( $has_wp_posts ) : ?>
				<button type="button" id="enews-post-insert-open" class="button enews-toolbar-button" title="<?php echo esc_attr__( 'Beitraege einfuegen', 'email-newsletter' ); ?>" aria-label="<?php echo esc_attr__( 'Beitraege einfuegen', 'email-newsletter' ); ?>">
					<span class="dashicons dashicons-admin-post"></span>
				</button>
			<?php endif; ?>
		</div>
		<?php if ( $has_mp_products ) : ?>
		<div id="enews-product-insert-dialog" class="enews-insert-dialog" style="display:none;">
			<p style="margin:0 0 8px 0;"><strong><?php _e('Produkt in Newsletter einfügen', 'email-newsletter'); ?></strong></p>
			<p style="margin:0 0 8px 0;">
				<label for="enews-product-search"><?php _e('Produkt suchen', 'email-newsletter'); ?></label><br />
				<input type="text" id="enews-product-search" value="" placeholder="<?php echo esc_attr__( 'Titel oder ID eingeben ...', 'email-newsletter' ); ?>" class="enews-search-input" />
				<div id="enews-product-suggestions" class="enews-suggestions" aria-live="polite"></div>
			</p>
			<p style="margin:0 0 8px 0;">
				<label for="enews-product-ids"><?php _e('Produkt-IDs (kommagetrennt)', 'email-newsletter'); ?></label><br />
				<input type="text" id="enews-product-ids" value="" placeholder="123,456" style="width:100%;max-width:100%;" />
			</p>
			<p style="margin:0 0 8px 0;">
				<label><input type="checkbox" id="enews-product-show-image" checked="checked" /> <?php _e('Bild anzeigen', 'email-newsletter'); ?></label><br />
				<label><input type="checkbox" id="enews-product-show-price" checked="checked" /> <?php _e('Preis anzeigen', 'email-newsletter'); ?></label><br />
				<label><input type="checkbox" id="enews-product-show-old-price" checked="checked" /> <?php _e('Altpreis anzeigen (wenn reduziert)', 'email-newsletter'); ?></label><br />
				<label><input type="checkbox" id="enews-product-show-button" checked="checked" /> <?php _e('Button anzeigen', 'email-newsletter'); ?></label>
			</p>
			<p style="margin:0 0 8px 0;">
				<label><input type="checkbox" id="enews-product-show-badge" /> <?php _e('Rabatt-Badge anzeigen', 'email-newsletter'); ?></label><br />
				<input type="text" id="enews-product-badge-text" value="<?php echo esc_attr__( 'Sale', 'email-newsletter' ); ?>" style="width:100%;max-width:100%;" />
			</p>
			<p style="margin:0 0 8px 0;">
				<label for="enews-product-layout"><?php _e('Layout', 'email-newsletter'); ?></label><br />
				<select id="enews-product-layout" style="width:100%;max-width:100%;">
					<option value="list"><?php _e('Liste (1 Spalte)', 'email-newsletter'); ?></option>
					<option value="grid"><?php _e('Grid (2 Spalten)', 'email-newsletter'); ?></option>
				</select>
			</p>
			<p style="margin:0 0 8px 0;">
				<label><input type="checkbox" id="enews-product-track" checked="checked" /> <?php _e('Tracking-Parameter an Links', 'email-newsletter'); ?></label>
			</p>
			<p style="margin:0 0 10px 0;">
				<label for="enews-product-button-text"><?php _e('Button-Text', 'email-newsletter'); ?></label><br />
				<input type="text" id="enews-product-button-text" value="<?php echo esc_attr__( 'Zum Produkt', 'email-newsletter' ); ?>" style="width:100%;max-width:100%;" />
			</p>
			<p style="margin:0;display:flex;gap:8px;">
				<button type="button" id="enews-product-insert-do" class="button button-primary"><?php _e('Shortcode einfügen', 'email-newsletter'); ?></button>
				<button type="button" id="enews-product-insert-close" class="button"><?php _e('Schließen', 'email-newsletter'); ?></button>
			</p>
		</div>
		<?php endif; ?>

		<?php if ( $has_wp_posts ) : ?>
		<div id="enews-post-insert-dialog" class="enews-insert-dialog" style="display:none;">
			<p style="margin:0 0 8px 0;"><strong><?php _e('Beiträge in Newsletter einfügen', 'email-newsletter'); ?></strong></p>
			<p style="margin:0 0 8px 0;">
				<label for="enews-post-search"><?php _e('Beitrag suchen', 'email-newsletter'); ?></label><br />
				<input type="text" id="enews-post-search" value="" placeholder="<?php echo esc_attr__( 'Titel oder ID eingeben ...', 'email-newsletter' ); ?>" class="enews-search-input" />
				<div id="enews-post-suggestions" class="enews-suggestions" aria-live="polite"></div>
			</p>
			<p style="margin:0 0 8px 0;">
				<label for="enews-post-ids"><?php _e('Beitrags-IDs (kommagetrennt)', 'email-newsletter'); ?></label><br />
				<input type="text" id="enews-post-ids" value="" placeholder="11,22,33" style="width:100%;max-width:100%;" />
			</p>
			<p style="margin:0 0 8px 0;">
				<label for="enews-post-layout"><?php _e('Darstellung', 'email-newsletter'); ?></label><br />
				<select id="enews-post-layout" style="width:100%;max-width:100%;">
					<option value="single"><?php _e('Single Post', 'email-newsletter'); ?></option>
					<option value="links"><?php _e('Links mit Vorschau', 'email-newsletter'); ?></option>
					<option value="grid"><?php _e('Grid (2 Spalten)', 'email-newsletter'); ?></option>
					<option value="slider"><?php _e('Slider (Mail-Fallback auf Grid)', 'email-newsletter'); ?></option>
				</select>
			</p>
			<p style="margin:0 0 8px 0;">
				<label><input type="checkbox" id="enews-post-show-image" checked="checked" /> <?php _e('Bild anzeigen', 'email-newsletter'); ?></label><br />
				<label><input type="checkbox" id="enews-post-show-excerpt" checked="checked" /> <?php _e('Vorschau/Excerpt anzeigen', 'email-newsletter'); ?></label><br />
				<label><input type="checkbox" id="enews-post-show-button" checked="checked" /> <?php _e('Button anzeigen', 'email-newsletter'); ?></label><br />
				<label><input type="checkbox" id="enews-post-track" checked="checked" /> <?php _e('Tracking-Parameter an Links', 'email-newsletter'); ?></label>
			</p>
			<p style="margin:0 0 10px 0;display:flex;gap:8px;">
				<input type="number" min="8" max="80" id="enews-post-excerpt-words" value="24" style="width:90px;" />
				<input type="text" id="enews-post-button-text" value="<?php echo esc_attr__( 'Weiterlesen', 'email-newsletter' ); ?>" style="flex:1;" />
			</p>
			<p style="margin:0;display:flex;gap:8px;">
				<button type="button" id="enews-post-insert-do" class="button button-primary"><?php _e('Shortcode einfügen', 'email-newsletter'); ?></button>
				<button type="button" id="enews-post-insert-close" class="button"><?php _e('Schließen', 'email-newsletter'); ?></button>
			</p>
		</div>
		<?php endif; ?>
		
		<script type="text/javascript">
			jQuery(document).ready( function() {
				jQuery('.mp-shortcode-builder-button').hide();

				var editorId = '<?php echo esc_js( $editor_id ); ?>';
				var settingId = '<?php echo esc_js( $setting_id ); ?>';
				var settingInputId = 'enews-setting-' + settingId;
				var ajaxUrl = '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>';
				var searchNonce = '<?php echo esc_js( $search_nonce ); ?>';
				var content = '';
				var editorReady = false;

				function getPlainEditorField() {
					return jQuery('#' + editorId);
				}

				function updateSetting(value) {
					var nextValue = String(value || '');
					var $settingInput = jQuery('#' + settingInputId);
					if ($settingInput.length) {
						$settingInput.val(nextValue).trigger('input').trigger('change');
					}
					if (window.wp && typeof wp.customize === 'function') {
						try {
							wp.customize(settingId, function(setting) {
								if (setting && typeof setting.set === 'function') {
									setting.set(nextValue);
								}
							});
						} catch (err) {}
					}
				}

				function mountToolbarInMediaButtons() {
					var $toolbar = jQuery('#enews-inline-toolbar');
					if (!$toolbar.length || $toolbar.data('mounted')) {
						return;
					}
					var $mediaRow = jQuery('#wp-' + editorId + '-media-buttons');
					if (!$mediaRow.length) {
						$mediaRow = jQuery('#wp-' + editorId + '-editor-tools .wp-media-buttons');
					}
					if (!$mediaRow.length) {
						return;
					}
					$toolbar
						.css({
							display: 'inline-flex',
							gap: '6px',
							marginLeft: '8px',
							verticalAlign: 'middle'
						})
						.appendTo($mediaRow)
						.data('mounted', true);
				}

				function normalizeIds(value) {
					var seen = {};
					var out = [];
					String(value || '').split(',').forEach(function(part) {
						var id = String(part).trim();
						if (!/^\d+$/.test(id)) {
							return;
						}
						if (!seen[id]) {
							seen[id] = true;
							out.push(id);
						}
					});
					return out.join(',');
				}

				function addIdToTarget(targetInputSelector, id) {
					var current = jQuery(targetInputSelector).val();
					var next = normalizeIds((current ? current + ',' : '') + String(id || ''));
					jQuery(targetInputSelector).val(next).trigger('input').trigger('change');
				}

				function renderSuggestions(containerSelector, items, targetInputSelector) {
					var $container = jQuery(containerSelector);
					if (!$container.length) {
						return;
					}

					if (!items || !items.length) {
						$container.html('<div class="enews-suggestion-empty"><?php echo esc_js( __( 'Keine Treffer', 'email-newsletter' ) ); ?></div>').show();
						return;
					}

					var html = '<ul class="enews-suggestion-list">';
					items.forEach(function(item) {
						html += '<li><button type="button" class="enews-suggestion-item" data-id="' + item.id + '"><span class="enews-suggestion-title"></span><span class="enews-suggestion-id">#' + item.id + '</span></button></li>';
					});
					html += '</ul>';
					$container.html(html).show();

					$container.find('.enews-suggestion-item').each(function(index) {
						jQuery(this).find('.enews-suggestion-title').text(items[index].title || '');
					});

					$container.find('.enews-suggestion-item').off('click').on('click', function() {
						var id = jQuery(this).data('id');
						addIdToTarget(targetInputSelector, id);
					});
				}

				function searchItems(itemType, term, containerSelector, targetInputSelector) {
					jQuery.post(ajaxUrl, {
						action: 'enews_builder_search_items',
						nonce: searchNonce,
						item_type: itemType,
						term: term,
						limit: 12
					}, function(response) {
						if (!response || !response.success) {
							return;
						}
						renderSuggestions(containerSelector, response.data, targetInputSelector);
					});
				}

				function bindSearchInput(inputSelector, itemType, containerSelector, targetInputSelector) {
					var timer = null;
					var $input = jQuery(inputSelector);
					if (!$input.length) {
						return;
					}

					$input.on('input', function() {
						var term = jQuery.trim($input.val() || '');
						clearTimeout(timer);
						timer = setTimeout(function() {
							searchItems(itemType, term, containerSelector, targetInputSelector);
						}, 180);
					});

					$input.on('focus', function() {
						searchItems(itemType, jQuery.trim($input.val() || ''), containerSelector, targetInputSelector);
					});
				}

				function ensureVisualEditorVisible() {
					if (typeof switchEditors !== 'undefined' && switchEditors && typeof switchEditors.go === 'function') {
						try {
							switchEditors.go(editorId, 'tmce');
						} catch (err) {}
					}
				}

				function hidePlainEditorFallback() {
					var $plainEditor = getPlainEditorField();
					if (!$plainEditor.length) {
						return;
					}
					$plainEditor.hide();
				}

				function hasTinyEditor() {
					return (typeof tinymce !== 'undefined' && !!tinymce.get(editorId));
				}

				function insertContentIntoEditor(shortcode) {
					if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
						tinymce.get(editorId).execCommand('mceInsertContent', false, shortcode);
						updateSetting(tinymce.get(editorId).getContent({format : 'raw'}));
						return;
					}

					var $plainEditor = getPlainEditorField();
					if ($plainEditor.length) {
						var existing = $plainEditor.val() || '';
						var next = existing ? (existing + '\n' + shortcode) : shortcode;
						$plainEditor.val(next).trigger('input').trigger('change');
						updateSetting(next);
						return;
					}

					updateSetting(content + shortcode);
				}

				function syncSettingFromEditor() {
					if (typeof tinymce !== 'undefined' && tinymce.get(editorId)) {
						var currentEditorContent = tinymce.get(editorId).getContent({format : 'raw'});
						if (currentEditorContent !== content && currentEditorContent !== '<p><br data-mce-bogus="1"></p>') {
							content = currentEditorContent;
							updateSetting(content);
						}
						return;
					}

					var $plainEditor = getPlainEditorField();
					if ($plainEditor.length) {
						var plainValue = $plainEditor.val() || '';
						if (plainValue !== content) {
							content = plainValue;
							updateSetting(plainValue);
						}
					}
				}

				function enablePlainEditorFallback() {
					var $plainEditor = getPlainEditorField();
					if (!$plainEditor.length) {
						return;
					}

					$plainEditor
						.css({
							display: 'block',
							minHeight: '220px',
							width: '100%',
							backgroundColor: '#ffffff',
							color: '#111111',
							border: '1px solid #ccd0d4'
						})
						.prop('disabled', false)
						.prop('readonly', false);

					$plainEditor.off('input.enewsFallback change.enewsFallback').on('input.enewsFallback change.enewsFallback', function() {
						syncSettingFromEditor();
					});
				}

				jQuery('#enews-product-insert-open').on('click', function() {
					jQuery('#enews-product-insert-dialog').slideDown(120);
				});

				jQuery('#enews-product-insert-close').on('click', function() {
					jQuery('#enews-product-insert-dialog').slideUp(120);
				});

				bindSearchInput('#enews-product-search', 'product', '#enews-product-suggestions', '#enews-product-ids');

				jQuery('#enews-product-insert-do').on('click', function() {
					var ids = normalizeIds(jQuery('#enews-product-ids').val());
					if (!ids) {
						alert('<?php echo esc_js( __( 'Bitte mindestens eine Produkt-ID auswaehlen.', 'email-newsletter' ) ); ?>');
						return;
					}

					var showImage = jQuery('#enews-product-show-image').is(':checked') ? '1' : '0';
					var showPrice = jQuery('#enews-product-show-price').is(':checked') ? '1' : '0';
					var showOldPrice = jQuery('#enews-product-show-old-price').is(':checked') ? '1' : '0';
					var showButton = jQuery('#enews-product-show-button').is(':checked') ? '1' : '0';
					var showBadge = jQuery('#enews-product-show-badge').is(':checked') ? '1' : '0';
					var layout = jQuery('#enews-product-layout').val() || 'list';
					var track = jQuery('#enews-product-track').is(':checked') ? '1' : '0';
					var badgeText = jQuery.trim(jQuery('#enews-product-badge-text').val() || '');
					var buttonText = jQuery.trim(jQuery('#enews-product-button-text').val() || '');
					buttonText = buttonText.replace(/\"/g, '&quot;');
					badgeText = badgeText.replace(/\"/g, '&quot;');

					var shortcode = '[enews_products ids="' + ids + '" layout="' + layout + '" show_image="' + showImage + '" show_price="' + showPrice + '" show_old_price="' + showOldPrice + '" show_button="' + showButton + '" show_badge="' + showBadge + '" track="' + track + '"';
					if (buttonText) {
						shortcode += ' button_text="' + buttonText + '"';
					}
					if (badgeText) {
						shortcode += ' badge_text="' + badgeText + '"';
					}
					shortcode += ']';

					insertContentIntoEditor(shortcode);
					jQuery('#enews-product-insert-dialog').slideUp(120);
				});

				jQuery('#enews-post-insert-open').on('click', function() {
					jQuery('#enews-post-insert-dialog').slideDown(120);
				});

				jQuery('#enews-post-insert-close').on('click', function() {
					jQuery('#enews-post-insert-dialog').slideUp(120);
				});

				bindSearchInput('#enews-post-search', 'post', '#enews-post-suggestions', '#enews-post-ids');

				jQuery('#enews-post-insert-do').on('click', function() {
					var ids = normalizeIds(jQuery('#enews-post-ids').val());
					if (!ids) {
						alert('<?php echo esc_js( __( 'Bitte mindestens eine Beitrags-ID auswaehlen.', 'email-newsletter' ) ); ?>');
						return;
					}

					var layout = jQuery('#enews-post-layout').val() || 'grid';
					var showImage = jQuery('#enews-post-show-image').is(':checked') ? '1' : '0';
					var showExcerpt = jQuery('#enews-post-show-excerpt').is(':checked') ? '1' : '0';
					var showButton = jQuery('#enews-post-show-button').is(':checked') ? '1' : '0';
					var track = jQuery('#enews-post-track').is(':checked') ? '1' : '0';
					var excerptWords = parseInt(jQuery('#enews-post-excerpt-words').val(), 10);
					if (!excerptWords || excerptWords < 8) excerptWords = 24;
					var buttonText = jQuery.trim(jQuery('#enews-post-button-text').val() || '');
					buttonText = buttonText.replace(/\"/g, '&quot;');

					var shortcode = '';
					if (layout === 'single') {
						var firstId = ids.split(',')[0];
						shortcode = '[enews_post id="' + firstId + '" show_image="' + showImage + '" show_excerpt="' + showExcerpt + '" excerpt_words="' + excerptWords + '" show_button="' + showButton + '" track="' + track + '"';
						if (buttonText) {
							shortcode += ' button_text="' + buttonText + '"';
						}
						shortcode += ']';
					} else if (layout === 'links') {
						shortcode = '[enews_post_links ids="' + ids + '" show_image="' + showImage + '" show_excerpt="' + showExcerpt + '" excerpt_words="' + excerptWords + '" track="' + track + '"]';
					} else {
						shortcode = '[enews_posts ids="' + ids + '" layout="' + layout + '" show_image="' + showImage + '" show_excerpt="' + showExcerpt + '" excerpt_words="' + excerptWords + '" show_button="' + showButton + '" track="' + track + '"';
						if (buttonText) {
							shortcode += ' button_text="' + buttonText + '"';
						}
						shortcode += ']';
					}

					insertContentIntoEditor(shortcode);
					jQuery('#enews-post-insert-dialog').slideUp(120);
				});

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

					editorReady = hasTinyEditor();
					if (editorReady) {
						hidePlainEditorFallback();
						ensureVisualEditorVisible();
					}
					return editorReady;
				}

				function ensureEditorInitialized() {
					editorReady = hasTinyEditor();
					if (editorReady) {
						hidePlainEditorFallback();
						ensureVisualEditorVisible();
					}
				}

				// Mount toolbar into the same row as "Medien hinzufuegen"
				mountToolbarInMediaButtons();

				// Use legacy init first; bootstrap only if needed
				ensureEditorInitialized();
				bootstrapEditor();
				var initRetries = 0;
				var initTimer = setInterval(function() {
					if (editorReady || initRetries > 30) {
						clearInterval(initTimer);
						if (!editorReady) {
							enablePlainEditorFallback();
						} else {
							hidePlainEditorFallback();
							ensureVisualEditorVisible();
						}
						return;
					}
					initRetries++;
					mountToolbarInMediaButtons();
					ensureEditorInitialized();
					bootstrapEditor();
				}, 300);

				// Safely poll TinyMCE once the editor is available
				tinymce_check_changes = setInterval(function() {
					syncSettingFromEditor();
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