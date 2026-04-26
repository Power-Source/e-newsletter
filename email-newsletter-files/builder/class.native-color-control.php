<?php
class Builder_Native_Color_Control extends WP_Customize_Control {
	public $type = 'builder_native_color';

	private function normalize_hex_color( $color ) {
		$color = trim( (string) $color );
		if ( preg_match( '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $color ) ) {
			return $color;
		}
		return '#000000';
	}

	public function render_content() {
		$value = $this->normalize_hex_color( $this->value() );
		$control_id = 'builder-native-color-' . sanitize_html_class( str_replace( array( '[', ']' ), '-', $this->id ) );
		$setting_id = esc_js( $this->id );
		?>
		<label>
			<?php if ( ! empty( $this->label ) ) : ?>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php endif; ?>
			<div class="builder-native-color-control" style="display:flex; gap:8px; align-items:center;">
				<input type="hidden" id="<?php echo esc_attr( $control_id ); ?>-setting" <?php $this->link(); ?> value="<?php echo esc_attr( $value ); ?>" />
				<input type="color" id="<?php echo esc_attr( $control_id ); ?>-picker" value="<?php echo esc_attr( $value ); ?>" style="width:44px; height:30px; padding:0; border:1px solid #ccd0d4;" />
				<input type="text" id="<?php echo esc_attr( $control_id ); ?>-text" value="<?php echo esc_attr( $value ); ?>" placeholder="#000000" style="width:110px;" />
			</div>
		</label>
		<script type="text/javascript">
			jQuery(function($) {
				var $setting = $('#<?php echo esc_js( $control_id ); ?>-setting');
				var $picker = $('#<?php echo esc_js( $control_id ); ?>-picker');
				var $text = $('#<?php echo esc_js( $control_id ); ?>-text');

				function normalizeHex(value) {
					if (!value) return '';
					value = String(value).trim();
					if (!value) return '';
					if (value.charAt(0) !== '#') value = '#' + value;
					if (/^#[0-9a-fA-F]{3}$/.test(value) || /^#[0-9a-fA-F]{6}$/.test(value)) return value;
					return '';
				}

				function applyColor(value, fromSetting) {
					var normalized = normalizeHex(value);
					if (!normalized) return;
					$text.val(normalized);
					$picker.val(normalized);
					if (!fromSetting) {
						$setting.val(normalized).trigger('input').trigger('change');
					}
				}

				$picker.on('input change', function() {
					applyColor($(this).val(), false);
				});

				$text.on('input change blur', function() {
					applyColor($(this).val(), false);
				});

				$setting.on('input change', function() {
					applyColor($(this).val(), true);
				});

				try {
					if (window.wp && wp.customize) {
						wp.customize('<?php echo $setting_id; ?>', function(value) {
							value.bind(function(to) {
								applyColor(to, true);
							});
						});
					}
				} catch (e) {
					// Keep control functional even if customize binding is unavailable.
				}
			});
		</script>
		<?php
	}
}
