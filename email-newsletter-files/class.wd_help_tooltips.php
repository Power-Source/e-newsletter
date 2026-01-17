<?php
/**
 * Very simple tooltip implementation for admin pages.
 */
class PSOURCE_HelpTooltips {

	private $_inline_tips = array();
	private $_bound_tips = array();
	private $_bound_selectors = array();
	private $_icon_url;
	private $_use_notice = true; // kept for API compatibility
	private $_screen_id = false;

	public function __construct() {
		global $wp_version;
		$version = preg_replace('/-.*$/', '', $wp_version);
		if (version_compare($version, '3.3', '>=')) {
			add_action('admin_footer', array($this, 'add_bound_tips'), 999);
			add_action('admin_print_footer_scripts', array($this, 'initialize'));
		}
	}

	public function set_icon_url($icon_url) {
		$this->_icon_url = $icon_url;
	}

	public function set_use_notice($use_notice = true) {
		$this->_use_notice = $use_notice;
	}

	public function set_screen_id($screen_id) {
		$this->_screen_id = $screen_id;
	}

	public function add_tip($tip) {
		if (!$this->_check_screen()) return false;
		$this->_inline_tips[] = $tip;
		return $this->_get_tip_markup($tip);
	}

	public function bind_tip($tip, $bind_to_selector) {
		$tip_id = 'psource-help-tip-for-' . md5($bind_to_selector);
		$this->_bound_tips[$tip_id] = $tip;
		$this->_bound_selectors[$tip_id] = $bind_to_selector;
	}

	public function add_bound_tips() {
		if (!$this->_check_screen()) return false;
		if (!$this->_bound_tips) return false;
		foreach ($this->_bound_tips as $id => $tip) {
			echo $this->_get_tip_markup($tip, 'id="' . $id . '" style="display:none"');
		}
	}

	public function initialize() {
		if (!$this->_check_screen()) return false;
		if (!$this->_inline_tips && !$this->_bound_tips) return false;
		$this->_print_styles();
		$this->_print_scripts();
	}

	private function _check_screen() {
		if (!$this->_screen_id) return true;
		$screen = get_current_screen();
		if (!is_object($screen)) return false;
		if ($this->_screen_id != @$screen->id) return false;
		return true;
	}

	private function _get_tip_markup($tip, $arg = '') {
		return "<span class='psource-help' {$arg}>{$tip}</span>";
	}

	private function _print_styles() {
		if (defined('PSOURCE_TOOLTIPS_CSS_ADDED')) return false;
		define('PSOURCE_TOOLTIPS_CSS_ADDED', true);
		?>
<style type="text/css">
.psource-help { display: none; }
.psource-help-trigger { cursor: help; padding-left: 4px; position: relative; }
.psource-help-trigger span { position: absolute; left: -9999px; }
<?php if ($this->_icon_url) : ?>
.psource-help-trigger { background: url(<?php echo $this->_icon_url; ?>) no-repeat center center; width: 16px; height: 16px; display: inline-block; }
<?php endif; ?>
.psource-tooltip{position:absolute;z-index:9999;max-width:320px;background:#fff;border:1px solid rgba(0,0,0,.15);box-shadow:0 4px 12px rgba(0,0,0,.12);padding:8px 10px;border-radius:4px;font-size:13px;line-height:1.4;color:#111;}
</style>
		<?php
	}

	private function _print_scripts() {
		if (defined('PSOURCE_TOOLTIPS_JS_ADDED')) return false;
		define('PSOURCE_TOOLTIPS_JS_ADDED', true);
		$selectors = json_encode($this->_bound_selectors);
		?>
<script type="text/javascript">
(function($){
	function initialize_help_item($me){
		var $prev = $me.prev();
		var help = '&nbsp;<a class="psource-help-trigger" href="#help"><span><?php _e('Hilfe', 'e-newsletter'); ?></span></a>';
		$prev.length ? $prev.after(help) : $me.before(help);
		$me.hide();
	}

	function get_help_block($me){
		return $me.parent().find('.psource-help');
	}

	function show_simple_tooltip(trigger, html){
		remove_tooltip();
		var $tip = $('<div class="psource-tooltip" role="tooltip"></div>').html(html);
		$('body').append($tip);
		var offset = $(trigger).offset();
		var top = offset.top - ($tip.outerHeight()/2) + ($(trigger).outerHeight()/2);
		var left = offset.left + $(trigger).outerWidth() + 8;
		// Clamp within viewport
		var maxLeft = $(window).width() - $tip.outerWidth() - 8;
		if(left > maxLeft) left = maxLeft;
		if(top < 8) top = 8;
		$tip.css({top: top, left: left});
	}

	function remove_tooltip(){
		$('.psource-tooltip').remove();
	}

	$(function(){
		// Place bound tips next to targets
		$.each(JSON.parse('<?php echo $selectors; ?>'), function(tip_id, selector){
			var $tip = $('#'+tip_id);
			if(!$tip.length) return true;
			var $selector = $(selector);
			if(!$selector.length) return true;
			$selector.append($tip);
		});

		$('.psource-help').each(function(){ initialize_help_item($(this)); });

		$(document).on('mouseenter focus', '.psource-help-trigger', function(){
			var $help = get_help_block($(this));
			if(!$help.length) return;
			show_simple_tooltip(this, $help.html());
		}).on('mouseleave blur click', '.psource-help-trigger', function(){
			remove_tooltip();
		});
	});
})(jQuery);
</script>
		<?php
	}
}

?>
