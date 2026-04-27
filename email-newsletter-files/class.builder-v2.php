<?php
class Email_Newsletter_Builder_V2 {
	var $plugin;
	var $meta_key = 'builder_v2_state';

	function __construct( $plugin ) {
		$this->plugin = $plugin;
	}

	function get_available_modules() {
		return array(
			'heading' => array(
				'label' => __( 'Ueberschrift', 'email-newsletter' ),
				'icon'  => 'H',
				'defaults' => array(
					'text' => __( 'Neue Ueberschrift', 'email-newsletter' ),
					'level' => 'h2',
					'align' => 'left',
					'color' => '#111827',
					'font_size' => 30,
				),
			),
			'text' => array(
				'label' => __( 'Text', 'email-newsletter' ),
				'icon'  => 'T',
				'defaults' => array(
					'text' => __( 'Absatztext', 'email-newsletter' ),
					'align' => 'left',
					'color' => '#374151',
					'font_size' => 16,
				),
			),
			'button' => array(
				'label' => __( 'Button', 'email-newsletter' ),
				'icon'  => 'B',
				'defaults' => array(
					'label' => __( 'Mehr erfahren', 'email-newsletter' ),
					'url' => home_url( '/' ),
					'align' => 'center',
					'background' => '#2563eb',
					'color' => '#ffffff',
					'radius' => 6,
				),
			),
			'image' => array(
				'label' => __( 'Bild', 'email-newsletter' ),
				'icon'  => 'I',
				'defaults' => array(
					'media_id' => 0,
					'url' => '',
					'alt' => '',
					'link' => '',
					'align' => 'center',
					'width' => 600,
				),
			),
			'hero' => array(
				'label' => __( 'Hero', 'email-newsletter' ),
				'icon'  => 'HR',
				'defaults' => array(
					'lock_full_width' => '1',
					'image_url' => '',
					'image_alt' => '',
					'eyebrow' => __( 'Highlight', 'email-newsletter' ),
					'title' => __( 'Hero Titel', 'email-newsletter' ),
					'text' => __( 'Kurzer Einleitungstext fuer den Hero-Bereich.', 'email-newsletter' ),
					'button_label' => __( 'Jetzt ansehen', 'email-newsletter' ),
					'button_url' => home_url( '/' ),
					'align' => 'center',
					'background' => '#0f172a',
					'text_color' => '#ffffff',
					'button_background' => '#f97316',
					'button_color' => '#ffffff',
				),
			),
			'columns_2' => array(
				'label' => __( '2 Spalten', 'email-newsletter' ),
				'icon'  => '2C',
				'defaults' => array(
					'left_html' => '<h3>Links</h3><p>Inhalt links</p>',
					'right_html' => '<h3>Rechts</h3><p>Inhalt rechts</p>',
					'left_background' => '#ffffff',
					'right_background' => '#ffffff',
					'gap' => 20,
				),
			),
			'cta_box' => array(
				'label' => __( 'CTA Box', 'email-newsletter' ),
				'icon'  => 'CTA',
				'defaults' => array(
					'title' => __( 'Call to Action', 'email-newsletter' ),
					'text' => __( 'Kurzer Hinweistext fuer den CTA-Bereich.', 'email-newsletter' ),
					'button_label' => __( 'Mehr erfahren', 'email-newsletter' ),
					'button_url' => home_url( '/' ),
					'align' => 'center',
					'background' => '#eff6ff',
					'text_color' => '#0f172a',
					'button_background' => '#2563eb',
					'button_color' => '#ffffff',
				),
			),
			'divider' => array(
				'label' => __( 'Trenner', 'email-newsletter' ),
				'icon'  => '-',
				'defaults' => array(
					'color' => '#d1d5db',
					'thickness' => 1,
				),
			),
			'spacer' => array(
				'label' => __( 'Abstand', 'email-newsletter' ),
				'icon'  => 'S',
				'defaults' => array(
					'height' => 24,
				),
			),
			'social' => array(
				'label' => __( 'Social', 'email-newsletter' ),
				'icon'  => 'SO',
				'defaults' => array(
					'title' => __( 'Folge uns', 'email-newsletter' ),
					'align' => 'center',
					'facebook' => '',
					'instagram' => '',
					'linkedin' => '',
					'x' => '',
					'youtube' => '',
				),
			),
			'footer' => array(
				'label' => __( 'Footer', 'email-newsletter' ),
				'icon'  => 'FT',
				'defaults' => array(
					'lock_full_width' => '1',
					'company' => get_bloginfo( 'name' ),
					'address' => '',
					'legal_text' => __( 'Du erhaeltst diese E-Mail, weil Du mit uns in Kontakt stehst.', 'email-newsletter' ),
					'manage_url' => '',
					'view_url' => '{VIEW_LINK}',
					'unsubscribe_url' => '{UNSUBSCRIBE_URL}',
					'align' => 'center',
					'background' => '#f8fafc',
					'text_color' => '#64748b',
					'link_color' => '#2563eb',
				),
			),
			'html' => array(
				'label' => __( 'HTML / Shortcode', 'email-newsletter' ),
				'icon'  => '</>',
				'defaults' => array(
					'html' => '',
				),
			),
			'products' => array(
				'label' => __( 'Produkte', 'email-newsletter' ),
				'icon'  => 'P',
				'defaults' => array(
					'query_mode' => 'manual',
					'query_limit' => 6,
					'ids' => '',
					'layout' => 'single',
					'show_image' => '1',
					'show_price' => '1',
					'show_old_price' => '1',
					'show_button' => '1',
					'show_badge' => '0',
					'track' => '1',
					'badge_text' => __( 'Sale', 'email-newsletter' ),
					'button_text' => __( 'Zum Produkt', 'email-newsletter' ),
				),
			),
			'posts' => array(
				'label' => __( 'Beitraege', 'email-newsletter' ),
				'icon'  => 'A',
				'defaults' => array(
					'query_mode' => 'manual',
					'query_scope' => 'all',
					'query_limit' => 6,
					'ids' => '',
					'layout' => 'single',
					'show_image' => '1',
					'show_excerpt' => '1',
					'excerpt_words' => 24,
					'show_button' => '1',
					'track' => '1',
					'button_text' => __( 'Weiterlesen', 'email-newsletter' ),
				),
			),
		);
	}

	function get_default_state() {
		$settings = is_array( $this->plugin->settings ) ? $this->plugin->settings : array();
		$view_browser = isset( $settings['view_browser'] ) ? $settings['view_browser'] : __( '<a href="{VIEW_LINK}" title="View e-mail in browser">E-Mail im Browser anzeigen</a>', 'email-newsletter' );
		$branding_html = isset( $settings['branding_html'] ) ? $settings['branding_html'] : $this->plugin->get_default_builder_var( 'branding_html' );
		$contact_info = isset( $settings['contact_info'] ) ? $settings['contact_info'] : '';

		return array(
			'global' => array(
				'email_title' => $this->plugin->get_default_builder_var( 'email_title' ),
				'full_width' => '0',
				'content_width' => 600,
				'background_color' => '#eef2f7',
				'content_background' => '#ffffff',
				'text_color' => '#374151',
				'font_family' => 'Arial, sans-serif',
				'font_css_url' => '',
				'heading_font_size' => 30,
				'heading_color' => '#111827',
				'heading_decoration' => 'none',
				'paragraph_font_size' => 16,
				'paragraph_color' => '#374151',
				'paragraph_decoration' => 'none',
				'quote_font_size' => 20,
				'quote_color' => '#1f2937',
				'quote_decoration' => 'none',
				'section_gap' => 20,
				'branding_html' => $branding_html,
				'contact_info' => $contact_info,
				'view_browser_html' => $view_browser,
			),
			'modules' => array(),
		);
	}

	function get_newsletter_global_defaults( $newsletter_id = 0 ) {
		$state = $this->get_default_state();
		if ( ! $newsletter_id ) {
			return $state['global'];
		}

		$newsletter = $this->plugin->get_newsletter_data( $newsletter_id );
		$state['global']['email_title'] = $this->plugin->get_newsletter_meta( $newsletter_id, 'email_title', $state['global']['email_title'] );
		$state['global']['branding_html'] = $this->plugin->get_newsletter_meta( $newsletter_id, 'branding_html', $state['global']['branding_html'] );
		$state['global']['contact_info'] = ! empty( $newsletter['contact_info'] ) ? $newsletter['contact_info'] : $state['global']['contact_info'];

		return $state['global'];
	}

	function has_saved_state( $newsletter_id ) {
		$saved = $this->plugin->get_newsletter_meta( $newsletter_id, $this->meta_key );
		if ( empty( $saved ) ) {
			return false;
		}

		$decoded = json_decode( $saved, true );
		return is_array( $decoded );
	}

	function build_module( $type, $overrides = array() ) {
		$available = $this->get_available_modules();
		if ( ! isset( $available[ $type ] ) ) {
			return array();
		}

		$module_id = function_exists( 'wp_generate_uuid4' )
			? 'preset_' . wp_generate_uuid4()
			: uniqid( 'preset_', true );

		return array(
			'id' => $module_id,
			'type' => $type,
			'settings' => array_merge( $available[ $type ]['defaults'], $overrides ),
		);
	}

	function get_template_presets() {
		$brand_name = get_bloginfo( 'name' );

		return array(
			'welcome' => array(
				'label' => __( 'Willkommen', 'email-newsletter' ),
				'description' => __( 'Hero + Begruessung + CTA + Footer', 'email-newsletter' ),
				'state' => array(
					'global' => array(
						'content_width' => 600,
						'background_color' => '#f1f5f9',
						'content_background' => '#ffffff',
						'text_color' => '#334155',
						'font_family' => 'Arial, sans-serif',
						'section_gap' => 20,
					),
					'modules' => array(
						$this->build_module( 'hero', array(
							'eyebrow' => __( 'Willkommen', 'email-newsletter' ),
							'title' => __( 'Schoen, dass Du da bist', 'email-newsletter' ),
							'text' => __( 'Hier findest Du die wichtigsten Updates und Angebote auf einen Blick.', 'email-newsletter' ),
						) ),
						$this->build_module( 'text', array(
							'text' => __( 'Danke fuer Dein Interesse. Wir freuen uns auf den Austausch mit Dir.', 'email-newsletter' ),
						) ),
						$this->build_module( 'cta_box', array(
							'title' => __( 'Starte jetzt', 'email-newsletter' ),
							'button_label' => __( 'Zum Angebot', 'email-newsletter' ),
						) ),
						$this->build_module( 'footer', array(
							'company' => $brand_name,
						) ),
					),
				),
			),
			'product_highlight' => array(
				'label' => __( 'Produkt-Highlight', 'email-newsletter' ),
				'description' => __( 'Hero + Produkte + CTA + Social + Footer', 'email-newsletter' ),
				'state' => array(
					'global' => array(
						'content_width' => 620,
						'background_color' => '#f8fafc',
						'content_background' => '#ffffff',
						'text_color' => '#1f2937',
						'font_family' => 'Arial, sans-serif',
						'section_gap' => 18,
					),
					'modules' => array(
						$this->build_module( 'hero', array(
							'eyebrow' => __( 'Neu im Shop', 'email-newsletter' ),
							'title' => __( 'Unsere Empfehlung der Woche', 'email-newsletter' ),
						) ),
						$this->build_module( 'products', array(
							'layout' => 'grid',
							'show_badge' => '1',
							'badge_text' => __( 'Top', 'email-newsletter' ),
						) ),
						$this->build_module( 'cta_box', array(
							'title' => __( 'Mehr entdecken', 'email-newsletter' ),
							'button_label' => __( 'Zum Shop', 'email-newsletter' ),
						) ),
						$this->build_module( 'social', array(
							'title' => __( 'Folge uns fuer mehr Updates', 'email-newsletter' ),
						) ),
						$this->build_module( 'footer', array(
							'company' => $brand_name,
						) ),
					),
				),
			),
			'news_digest' => array(
				'label' => __( 'News-Digest', 'email-newsletter' ),
				'description' => __( 'Heading + Beitraege + 2 Spalten + Footer', 'email-newsletter' ),
				'state' => array(
					'global' => array(
						'content_width' => 620,
						'background_color' => '#eef2ff',
						'content_background' => '#ffffff',
						'text_color' => '#334155',
						'font_family' => 'Arial, sans-serif',
						'section_gap' => 18,
					),
					'modules' => array(
						$this->build_module( 'heading', array(
							'text' => __( 'Aktuelle News', 'email-newsletter' ),
							'level' => 'h2',
							'align' => 'left',
						) ),
						$this->build_module( 'posts', array(
							'layout' => 'links',
							'show_image' => '0',
							'show_button' => '0',
						) ),
						$this->build_module( 'columns_2', array(
							'left_html' => '<h3>' . esc_html__( 'Termine', 'email-newsletter' ) . '</h3><p>' . esc_html__( 'Teaser fuer kommende Events.', 'email-newsletter' ) . '</p>',
							'right_html' => '<h3>' . esc_html__( 'Tipps', 'email-newsletter' ) . '</h3><p>' . esc_html__( 'Kurz und praktisch zusammengefasst.', 'email-newsletter' ) . '</p>',
						) ),
						$this->build_module( 'footer', array(
							'company' => $brand_name,
						) ),
					),
				),
			),
		);
	}

	function get_state( $newsletter_id ) {
		$state = $this->get_default_state();
		$state['global'] = $this->get_newsletter_global_defaults( $newsletter_id );
		$saved = $this->plugin->get_newsletter_meta( $newsletter_id, $this->meta_key );

		if ( ! empty( $saved ) ) {
			$decoded = json_decode( $saved, true );
			if ( is_array( $decoded ) ) {
				return $this->migrate_html_shortcodes_to_typed( $this->sanitize_state( $decoded, $newsletter_id ) );
			}
		}

		$newsletter = $this->plugin->get_newsletter_data( $newsletter_id );
		if ( ! empty( $newsletter['content'] ) ) {
			$state['modules'][] = array(
				'id' => 'legacy_' . $newsletter_id,
				'type' => 'html',
				'settings' => array(
					'html' => $newsletter['content'],
				),
			);
		}

		return $this->migrate_html_shortcodes_to_typed( $state );
	}

	function sanitize_state( $state, $newsletter_id = 0 ) {
		$defaults = $this->get_default_state();
		$defaults['global'] = $this->get_newsletter_global_defaults( $newsletter_id );
		$modules_map = $this->get_available_modules();
		$sanitized = $defaults;

		if ( isset( $state['global'] ) && is_array( $state['global'] ) ) {
			$sanitized['global']['email_title'] = sanitize_text_field( $this->get_array_value( $state['global'], 'email_title', $defaults['global']['email_title'] ) );
			$sanitized['global']['full_width'] = $this->sanitize_bool_string( $this->get_array_value( $state['global'], 'full_width', $defaults['global']['full_width'] ) );
			$sanitized['global']['content_width'] = $this->sanitize_int( $state['global'], 'content_width', 420, 760, $defaults['global']['content_width'] );
			$sanitized['global']['background_color'] = $this->sanitize_color( $this->get_array_value( $state['global'], 'background_color', $defaults['global']['background_color'] ), $defaults['global']['background_color'] );
			$sanitized['global']['content_background'] = $this->sanitize_color( $this->get_array_value( $state['global'], 'content_background', $defaults['global']['content_background'] ), $defaults['global']['content_background'] );
			$sanitized['global']['text_color'] = $this->sanitize_color( $this->get_array_value( $state['global'], 'text_color', $defaults['global']['text_color'] ), $defaults['global']['text_color'] );
			$sanitized['global']['font_family'] = sanitize_text_field( $this->get_array_value( $state['global'], 'font_family', $defaults['global']['font_family'] ) );
			$sanitized['global']['font_css_url'] = esc_url_raw( $this->get_array_value( $state['global'], 'font_css_url', '' ) );
			$sanitized['global']['heading_font_size'] = $this->sanitize_int( $state['global'], 'heading_font_size', 12, 72, $defaults['global']['heading_font_size'] );
			$sanitized['global']['heading_color'] = $this->sanitize_color( $this->get_array_value( $state['global'], 'heading_color', $defaults['global']['heading_color'] ), $defaults['global']['heading_color'] );
			$sanitized['global']['heading_decoration'] = $this->sanitize_text_decoration( $this->get_array_value( $state['global'], 'heading_decoration', $defaults['global']['heading_decoration'] ) );
			$sanitized['global']['paragraph_font_size'] = $this->sanitize_int( $state['global'], 'paragraph_font_size', 10, 36, $defaults['global']['paragraph_font_size'] );
			$sanitized['global']['paragraph_color'] = $this->sanitize_color( $this->get_array_value( $state['global'], 'paragraph_color', $defaults['global']['paragraph_color'] ), $defaults['global']['paragraph_color'] );
			$sanitized['global']['paragraph_decoration'] = $this->sanitize_text_decoration( $this->get_array_value( $state['global'], 'paragraph_decoration', $defaults['global']['paragraph_decoration'] ) );
			$sanitized['global']['quote_font_size'] = $this->sanitize_int( $state['global'], 'quote_font_size', 10, 42, $defaults['global']['quote_font_size'] );
			$sanitized['global']['quote_color'] = $this->sanitize_color( $this->get_array_value( $state['global'], 'quote_color', $defaults['global']['quote_color'] ), $defaults['global']['quote_color'] );
			$sanitized['global']['quote_decoration'] = $this->sanitize_text_decoration( $this->get_array_value( $state['global'], 'quote_decoration', $defaults['global']['quote_decoration'] ) );
			$sanitized['global']['section_gap'] = $this->sanitize_int( $state['global'], 'section_gap', 0, 60, $defaults['global']['section_gap'] );
			$sanitized['global']['branding_html'] = wp_kses_post( $this->get_array_value( $state['global'], 'branding_html', $defaults['global']['branding_html'] ) );
			$sanitized['global']['contact_info'] = wp_kses_post( $this->get_array_value( $state['global'], 'contact_info', $defaults['global']['contact_info'] ) );
			$sanitized['global']['view_browser_html'] = wp_kses_post( $this->get_array_value( $state['global'], 'view_browser_html', $defaults['global']['view_browser_html'] ) );
		}

		if ( empty( $state['modules'] ) || ! is_array( $state['modules'] ) ) {
			return $sanitized;
		}

		foreach ( $state['modules'] as $module ) {
			$module_type = isset( $module['type'] ) ? sanitize_key( $module['type'] ) : '';
			if ( 'product' === $module_type ) {
				$module_type = 'products';
			} elseif ( 'post' === $module_type ) {
				$module_type = 'posts';
			}

			if ( empty( $module_type ) || ! isset( $modules_map[ $module_type ] ) ) {
				continue;
			}

			$type = $module_type;
			$raw_settings = isset( $module['settings'] ) && is_array( $module['settings'] ) ? $module['settings'] : array();
			$settings = array_merge( $modules_map[ $type ]['defaults'], $raw_settings );

			switch ( $type ) {
				case 'heading':
					$settings['text'] = sanitize_text_field( $settings['text'] );
					$settings['level'] = in_array( $settings['level'], array( 'h1', 'h2', 'h3' ), true ) ? $settings['level'] : 'h2';
					$settings['align'] = $this->sanitize_align( $settings['align'] );
					$settings['color'] = $this->sanitize_color( $settings['color'], '#111827' );
					$settings['font_size'] = max( 14, min( 54, intval( $settings['font_size'] ) ) );
					break;
				case 'text':
					$settings['text'] = wp_kses_post( $settings['text'] );
					$settings['align'] = $this->sanitize_align( $settings['align'] );
					$settings['color'] = $this->sanitize_color( $settings['color'], '#374151' );
					$settings['font_size'] = max( 12, min( 24, intval( $settings['font_size'] ) ) );
					break;
				case 'button':
					$settings['label'] = sanitize_text_field( $settings['label'] );
					$settings['url'] = esc_url_raw( $settings['url'] );
					$settings['align'] = $this->sanitize_align( $settings['align'] );
					$settings['background'] = $this->sanitize_color( $settings['background'], '#2563eb' );
					$settings['color'] = $this->sanitize_color( $settings['color'], '#ffffff' );
					$settings['radius'] = max( 0, min( 30, intval( $settings['radius'] ) ) );
					break;
				case 'image':
					$settings['media_id'] = max( 0, intval( $settings['media_id'] ) );
					$settings['url'] = esc_url_raw( $settings['url'] );
					$settings['alt'] = sanitize_text_field( $settings['alt'] );
					$settings['link'] = esc_url_raw( $settings['link'] );
					$settings['align'] = $this->sanitize_align( $settings['align'] );
					$settings['width'] = max( 80, min( 1200, intval( $settings['width'] ) ) );
					break;
				case 'hero':
					$settings['image_url'] = esc_url_raw( $settings['image_url'] );
					$settings['image_alt'] = sanitize_text_field( $settings['image_alt'] );
					$settings['eyebrow'] = sanitize_text_field( $settings['eyebrow'] );
					$settings['title'] = sanitize_text_field( $settings['title'] );
					$settings['text'] = wp_kses_post( $settings['text'] );
					$settings['button_label'] = sanitize_text_field( $settings['button_label'] );
					$settings['button_url'] = esc_url_raw( $settings['button_url'] );
					$settings['align'] = $this->sanitize_align( $settings['align'] );
					$settings['background'] = $this->sanitize_color( $settings['background'], '#0f172a' );
					$settings['text_color'] = $this->sanitize_color( $settings['text_color'], '#ffffff' );
					$settings['button_background'] = $this->sanitize_color( $settings['button_background'], '#f97316' );
					$settings['button_color'] = $this->sanitize_color( $settings['button_color'], '#ffffff' );
					break;
				case 'columns_2':
					$settings['left_html'] = wp_kses_post( $settings['left_html'] );
					$settings['right_html'] = wp_kses_post( $settings['right_html'] );
					$settings['left_background'] = $this->sanitize_color( $settings['left_background'], '#ffffff' );
					$settings['right_background'] = $this->sanitize_color( $settings['right_background'], '#ffffff' );
					$settings['gap'] = max( 0, min( 40, intval( $settings['gap'] ) ) );
					break;
				case 'cta_box':
					$settings['title'] = sanitize_text_field( $settings['title'] );
					$settings['text'] = wp_kses_post( $settings['text'] );
					$settings['button_label'] = sanitize_text_field( $settings['button_label'] );
					$settings['button_url'] = esc_url_raw( $settings['button_url'] );
					$settings['align'] = $this->sanitize_align( $settings['align'] );
					$settings['background'] = $this->sanitize_color( $settings['background'], '#eff6ff' );
					$settings['text_color'] = $this->sanitize_color( $settings['text_color'], '#0f172a' );
					$settings['button_background'] = $this->sanitize_color( $settings['button_background'], '#2563eb' );
					$settings['button_color'] = $this->sanitize_color( $settings['button_color'], '#ffffff' );
					break;
				case 'divider':
					$settings['color'] = $this->sanitize_color( $settings['color'], '#d1d5db' );
					$settings['thickness'] = max( 1, min( 8, intval( $settings['thickness'] ) ) );
					break;
				case 'spacer':
					$settings['height'] = max( 4, min( 120, intval( $settings['height'] ) ) );
					break;
				case 'social':
					$settings['title'] = sanitize_text_field( $settings['title'] );
					$settings['align'] = $this->sanitize_align( $settings['align'] );
					$settings['facebook'] = esc_url_raw( $settings['facebook'] );
					$settings['instagram'] = esc_url_raw( $settings['instagram'] );
					$settings['linkedin'] = esc_url_raw( $settings['linkedin'] );
					$settings['x'] = esc_url_raw( $settings['x'] );
					$settings['youtube'] = esc_url_raw( $settings['youtube'] );
					break;
				case 'footer':
					$settings['company'] = sanitize_text_field( $settings['company'] );
					$settings['address'] = sanitize_textarea_field( $settings['address'] );
					$settings['legal_text'] = sanitize_textarea_field( $settings['legal_text'] );
					$settings['manage_url'] = $this->sanitize_dynamic_url( $settings['manage_url'] );
					$settings['view_url'] = $this->sanitize_dynamic_url( $settings['view_url'] );
					$settings['unsubscribe_url'] = $this->sanitize_dynamic_url( $settings['unsubscribe_url'] );
					$settings['align'] = $this->sanitize_align( $settings['align'] );
					$settings['background'] = $this->sanitize_color( $settings['background'], '#f8fafc' );
					$settings['text_color'] = $this->sanitize_color( $settings['text_color'], '#64748b' );
					$settings['link_color'] = $this->sanitize_color( $settings['link_color'], '#2563eb' );
					break;
				case 'html':
					$settings['html'] = wp_kses_post( $settings['html'] );
					break;
				case 'products':
					$settings['query_mode'] = in_array( $settings['query_mode'], array( 'manual', 'latest', 'trigger' ), true ) ? $settings['query_mode'] : 'manual';
					$settings['query_limit'] = max( 1, min( 24, intval( $settings['query_limit'] ) ) );
					$settings['ids'] = $this->sanitize_ids( $settings['ids'] );
					$settings['layout'] = in_array( $settings['layout'], array( 'single', 'list', 'grid' ), true ) ? $settings['layout'] : 'single';
					$settings['show_image'] = $this->sanitize_bool_string( $settings['show_image'] );
					$settings['show_price'] = $this->sanitize_bool_string( $settings['show_price'] );
					$settings['show_old_price'] = $this->sanitize_bool_string( $settings['show_old_price'] );
					$settings['show_button'] = $this->sanitize_bool_string( $settings['show_button'] );
					$settings['show_badge'] = $this->sanitize_bool_string( $settings['show_badge'] );
					$settings['track'] = $this->sanitize_bool_string( $settings['track'] );
					$settings['badge_text'] = sanitize_text_field( $settings['badge_text'] );
					$settings['button_text'] = sanitize_text_field( $settings['button_text'] );
					break;
				case 'posts':
					$settings['query_mode'] = in_array( $settings['query_mode'], array( 'manual', 'latest', 'trigger' ), true ) ? $settings['query_mode'] : 'manual';
					$settings['query_scope'] = in_array( $settings['query_scope'], array( 'all', 'week', 'month' ), true ) ? $settings['query_scope'] : 'all';
					$settings['query_limit'] = max( 1, min( 24, intval( $settings['query_limit'] ) ) );
					$settings['ids'] = $this->sanitize_ids( $settings['ids'] );
					$settings['layout'] = in_array( $settings['layout'], array( 'single', 'links', 'grid', 'slider' ), true ) ? $settings['layout'] : 'grid';
					$settings['show_image'] = $this->sanitize_bool_string( $settings['show_image'] );
					$settings['show_excerpt'] = $this->sanitize_bool_string( $settings['show_excerpt'] );
					$settings['excerpt_words'] = max( 8, min( 80, intval( $settings['excerpt_words'] ) ) );
					$settings['show_button'] = $this->sanitize_bool_string( $settings['show_button'] );
					$settings['track'] = $this->sanitize_bool_string( $settings['track'] );
					$settings['button_text'] = sanitize_text_field( $settings['button_text'] );
					break;
			}

			$sanitized['modules'][] = array(
				'id' => sanitize_html_class( isset( $module['id'] ) ? $module['id'] : uniqid( 'mod_', false ) ),
				'type' => $type,
				'settings' => array_merge(
					$settings,
					array(
						'lock_full_width' => $this->sanitize_bool_string( $this->get_array_value( $settings, 'lock_full_width', in_array( $type, array( 'hero', 'footer' ), true ) ? '1' : '0' ) ),
						'grid_span' => $this->sanitize_int( $settings, 'grid_span', 3, 12, 12 ),
						'grid_col' => $this->sanitize_int( $settings, 'grid_col', 1, 12, 1 ),
						'grid_row' => $this->sanitize_int( $settings, 'grid_row', 1, 999, 1 ),
						'grid_rows' => $this->sanitize_int( $settings, 'grid_rows', 1, 999, 1 ),
						'canvas_min_height' => $this->sanitize_int( $settings, 'canvas_min_height', 0, 480, 0 ),
					)
				),
			);
		}

		return $sanitized;
	}

	function save_state( $newsletter_id, $state ) {
		global $wpdb;

		$sanitized = $this->sanitize_state( $state, $newsletter_id );
		$content = $this->render_state_to_content( $sanitized );

		$this->plugin->update_newsletter_meta( $newsletter_id, $this->meta_key, wp_json_encode( $sanitized ) );
		$this->plugin->update_newsletter_meta( $newsletter_id, 'email_title', $sanitized['global']['email_title'] );
		$this->plugin->update_newsletter_meta( $newsletter_id, 'branding_html', $sanitized['global']['branding_html'] );
		$wpdb->update(
			$this->plugin->tb_prefix . 'enewsletter_newsletters',
			array(
				'content' => $content,
				'contact_info' => $sanitized['global']['contact_info'],
			),
			array( 'newsletter_id' => $newsletter_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		return array(
			'state' => $sanitized,
			'content' => $content,
		);
	}

	function render_state_to_content( $state ) {
		return $this->render_state( $state, 'storage' );
	}

	function render_state_to_preview( $state ) {
		return $this->render_full_email_document( $state, 'preview' );
	}

	function render_newsletter_email( $newsletter_id, $mode = 'send', $context = array() ) {
		$state = $this->get_state( $newsletter_id );
		return $this->render_full_email_document( $state, $mode, $newsletter_id, $context );
	}

	function render_state( $state, $mode = 'storage', $context = array() ) {
		$global = $state['global'];
		$is_full_width = '1' === (string) $global['full_width'];
		$inner_width = $is_full_width ? '100%' : intval( $global['content_width'] );
		$inner_style = $is_full_width
			? 'width:100%;max-width:none;background:' . esc_attr( $global['content_background'] ) . ';margin:0 auto;'
			: 'width:100%;max-width:' . intval( $global['content_width'] ) . 'px;background:' . esc_attr( $global['content_background'] ) . ';margin:0 auto;';
		$modules = isset( $state['modules'] ) && is_array( $state['modules'] ) ? $state['modules'] : array();
		usort(
			$modules,
			array( $this, 'sort_modules_by_grid' )
		);
		$rows = array();
		$current_row = array();
		$current_span = 0;
		$current_grid_row = 1;

		foreach ( $modules as $module ) {
			$content = $this->render_module_content( $module, $global, $mode, $context );
			if ( '' === $content ) {
				continue;
			}

			$span = $this->get_module_span( $module );
			$module_grid_row = $this->get_module_row( $module );
			$module_grid_col = $this->get_module_col( $module );

			if ( ! empty( $current_row ) && $module_grid_row !== $current_grid_row ) {
				$rows[] = $this->render_grid_row( $current_row, $global );
				$current_row = array();
				$current_span = 0;
			}

			$current_grid_row = $module_grid_row;

			$target_start = max( 1, min( 12, $module_grid_col ) );
			if ( $target_start > ( $current_span + 1 ) ) {
				$spacer_span = $target_start - ( $current_span + 1 );
				$current_row[] = array(
					'module' => array( 'settings' => array() ),
					'content' => '&nbsp;',
					'span' => $spacer_span,
					'is_spacer' => true,
				);
				$current_span += $spacer_span;
			}

			if ( ! empty( $current_row ) && ( $current_span + $span ) > 12 ) {
				$rows[] = $this->render_grid_row( $current_row, $global );
				$current_row = array();
				$current_span = 0;
				$current_grid_row = $module_grid_row;
			}

			$current_row[] = array(
				'module' => $module,
				'content' => $content,
				'span' => $span,
			);
			$current_span += $span;

			if ( $current_span >= 12 ) {
				$rows[] = $this->render_grid_row( $current_row, $global );
				$current_row = array();
				$current_span = 0;
			}
		}

		if ( ! empty( $current_row ) ) {
			$rows[] = $this->render_grid_row( $current_row, $global );
		}

		if ( empty( $rows ) ) {
			$rows[] = $this->wrap_row( '&nbsp;', $global, 20 );
		}

		return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background:' . esc_attr( $global['background_color'] ) . ';"><tr><td align="center" style="padding:' . ( $is_full_width ? '24px 0' : '24px 12px' ) . ';"><table role="presentation" width="' . esc_attr( $inner_width ) . '" cellpadding="0" cellspacing="0" border="0" style="' . $inner_style . '">' . implode( '', $rows ) . '</table></td></tr></table>';
	}

	function render_full_email_document( $state, $mode = 'send', $newsletter_id = 0, $context = array() ) {
		$global = $state['global'];
		$is_full_width = '1' === (string) $global['full_width'];
		$shell_width = $is_full_width ? '100%' : intval( $global['content_width'] );
		$shell_style = $is_full_width ? 'width:100%;max-width:none;margin:0 auto;' : 'width:100%;max-width:' . intval( $global['content_width'] ) . 'px;margin:0 auto;';
		$font_css_url = ! empty( $global['font_css_url'] ) ? esc_url( $global['font_css_url'] ) : '';
		$font_link = '';
		if ( ! empty( $font_css_url ) ) {
			$font_link = '<link rel="stylesheet" type="text/css" href="' . $font_css_url . '" />';
		}
		$typography_css = $this->build_typography_css( $global );
		$title = ! empty( $global['email_title'] ) ? $global['email_title'] : __( 'Newsletter', 'email-newsletter' );
		$view_link = 'preview' === $mode ? '#' : '{VIEW_LINK}';
		$view_browser = str_replace( '{VIEW_LINK}', $view_link, $global['view_browser_html'] );
		$tracker = 'preview' === $mode ? '' : '{OPENED_TRACKER}';
		$branding = $this->render_optional_shell_block( $global['branding_html'], $global, 'padding:18px 24px 6px 24px;text-align:left;' );
		$contact_info = $this->render_optional_shell_block( $global['contact_info'], $global, 'padding:18px 24px 24px 24px;text-align:left;font-size:13px;line-height:1.6;color:#64748b;' );
		$view_browser_row = $this->render_optional_shell_block( $view_browser, $global, 'padding:18px 24px 4px 24px;text-align:center;font-size:12px;line-height:1.5;color:#64748b;' );
		$content = $this->render_state( $state, $mode, $context );

		return '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /><meta name="viewport" content="width=device-width" /><title>' . esc_html( $title ) . '</title>' . $font_link . '<style type="text/css">' . $typography_css . '@media only screen and (max-width:640px){.enews-grid-col{display:block!important;width:100%!important;padding-left:0!important;padding-right:0!important;}}</style></head><body style="margin:0;padding:0;background:' . esc_attr( $global['background_color'] ) . ';">'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;background:' . esc_attr( $global['background_color'] ) . ';margin:0;padding:0;">'
			. '<tr><td align="center" style="padding:' . ( $is_full_width ? '0' : '0 12px' ) . ';">'
			. '<table role="presentation" width="' . esc_attr( $shell_width ) . '" cellpadding="0" cellspacing="0" border="0" style="' . $shell_style . '">'
			. $view_browser_row
			. $branding
			. '<tr><td style="padding:0;">' . $content . '</td></tr>'
			. $contact_info
			. '</table>'
			. '</td></tr>'
			. '</table>'
			. $tracker
			. '</body></html>';
	}

	function render_optional_shell_block( $html, $global, $style ) {
		if ( '' === trim( wp_strip_all_tags( str_replace( '&nbsp;', ' ', (string) $html ) ) ) && '' === trim( (string) preg_replace( '/<[^>]+>/', '', (string) $html ) ) ) {
			return '';
		}

		return '<tr><td style="' . esc_attr( $style ) . 'font-family:' . esc_attr( $global['font_family'] ) . ';background:' . esc_attr( $global['content_background'] ) . ';">' . $html . '</td></tr>';
	}

	function get_module_span( $module ) {
		if ( empty( $module['settings'] ) || ! is_array( $module['settings'] ) ) {
			return 12;
		}

		if ( isset( $module['settings']['lock_full_width'] ) && '1' === (string) $module['settings']['lock_full_width'] ) {
			return 12;
		}

		return $this->sanitize_int( $module['settings'], 'grid_span', 3, 12, 12 );
	}

	function get_module_col( $module ) {
		if ( empty( $module['settings'] ) || ! is_array( $module['settings'] ) ) {
			return 1;
		}

		return $this->sanitize_int( $module['settings'], 'grid_col', 1, 12, 1 );
	}

	function get_module_row( $module ) {
		if ( empty( $module['settings'] ) || ! is_array( $module['settings'] ) ) {
			return 1;
		}

		return $this->sanitize_int( $module['settings'], 'grid_row', 1, 999, 1 );
	}

	function sort_modules_by_grid( $a, $b ) {
		$row_a = $this->get_module_row( $a );
		$row_b = $this->get_module_row( $b );
		if ( $row_a !== $row_b ) {
			return $row_a - $row_b;
		}

		$col_a = $this->get_module_col( $a );
		$col_b = $this->get_module_col( $b );
		if ( $col_a !== $col_b ) {
			return $col_a - $col_b;
		}

		return 0;
	}

	function render_grid_row( $row_modules, $global ) {
		if ( empty( $row_modules ) ) {
			return '';
		}

		$column_gap = max( 0, min( 16, intval( round( intval( $global['section_gap'] ) / 2 ) ) ) );
		$cells = array();
		$colgroup = '';
		for ( $i = 0; $i < 12; $i++ ) {
			$colgroup .= '<col style="width:8.3333%;">';
		}

		foreach ( $row_modules as $entry ) {
			$is_spacer = ! empty( $entry['is_spacer'] );
			$span = $is_spacer ? max( 1, min( 12, intval( $entry['span'] ) ) ) : max( 3, min( 12, intval( $entry['span'] ) ) );
			$width = round( ( $span / 12 ) * 100, 4 );
			$settings = isset( $entry['module']['settings'] ) && is_array( $entry['module']['settings'] ) ? $entry['module']['settings'] : array();
			$min_height = $this->sanitize_int( $settings, 'canvas_min_height', 0, 480, 0 );
			$inner_style = 0 < $min_height ? 'min-height:' . intval( $min_height ) . 'px;' : '';

			if ( $is_spacer ) {
				$cells[] = '<td class="enews-grid-col" colspan="' . intval( $span ) . '" width="' . esc_attr( $width ) . '%" valign="top" style="width:' . esc_attr( $width ) . '%;max-width:' . esc_attr( $width ) . '%;padding:0 ' . intval( $column_gap ) . 'px;vertical-align:top;font-size:0;line-height:0;">&nbsp;</td>';
				continue;
			}

			$cells[] = '<td class="enews-grid-col" colspan="' . intval( $span ) . '" width="' . esc_attr( $width ) . '%" valign="top" style="width:' . esc_attr( $width ) . '%;max-width:' . esc_attr( $width ) . '%;padding:0 ' . intval( $column_gap ) . 'px;vertical-align:top;color:' . esc_attr( $global['text_color'] ) . ';font-family:' . esc_attr( $global['font_family'] ) . ';"><div style="' . esc_attr( $inner_style ) . '">' . $entry['content'] . '</div></td>';
		}

		return '<tr><td style="padding:' . intval( $global['section_gap'] ) . 'px 24px;"><table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;table-layout:fixed;"><colgroup>' . $colgroup . '</colgroup><tr>' . implode( '', $cells ) . '</tr></table></td></tr>';
	}

	function render_module_row( $module, $global, $mode = 'storage' ) {
		$content = $this->render_module_content( $module, $global, $mode );
		if ( '' === $content ) {
			return '';
		}

		return $this->wrap_row( $content, $global );
	}

	function render_module_content( $module, $global, $mode = 'storage', $context = array() ) {
		$type = $module['type'];
		$settings = $module['settings'];
		$is_preview = 'preview' === $mode;

		switch ( $type ) {
			case 'heading':
				$tag = $settings['level'];
				return '<' . $tag . ' style="margin:0;color:' . esc_attr( $settings['color'] ) . ';font-size:' . intval( $settings['font_size'] ) . 'px;line-height:1.2;font-family:' . esc_attr( $global['font_family'] ) . ';font-weight:700;text-align:' . esc_attr( $settings['align'] ) . ';">' . esc_html( $settings['text'] ) . '</' . $tag . '>';
			case 'text':
				$text = wpautop( $settings['text'] );
				return '<div style="color:' . esc_attr( $settings['color'] ) . ';font-size:' . intval( $settings['font_size'] ) . 'px;line-height:1.6;font-family:' . esc_attr( $global['font_family'] ) . ';text-align:' . esc_attr( $settings['align'] ) . ';">' . $text . '</div>';
			case 'button':
				return $this->render_button_markup( $settings['label'], $settings['url'], $settings['background'], $settings['color'], $settings['radius'], $settings['align'], $global['font_family'] );
			case 'image':
				return $this->render_image_row( $settings, $global );
			case 'hero':
				return $this->render_hero_row( $settings, $global );
			case 'columns_2':
				$left = $is_preview ? do_shortcode( $settings['left_html'] ) : $settings['left_html'];
				$right = $is_preview ? do_shortcode( $settings['right_html'] ) : $settings['right_html'];
				return '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr><td width="50%" valign="top" style="padding-right:' . intval( $settings['gap'] / 2 ) . 'px;background:' . esc_attr( $settings['left_background'] ) . ';">' . $left . '</td><td width="50%" valign="top" style="padding-left:' . intval( $settings['gap'] / 2 ) . 'px;background:' . esc_attr( $settings['right_background'] ) . ';">' . $right . '</td></tr></table>';
			case 'cta_box':
				return $this->render_cta_row( $settings, $global );
			case 'divider':
				return '<div style="height:' . intval( $settings['thickness'] ) . 'px;line-height:' . intval( $settings['thickness'] ) . 'px;background:' . esc_attr( $settings['color'] ) . ';font-size:0;">&nbsp;</div>';
			case 'spacer':
				return '<div style="font-size:0;line-height:0;height:' . intval( $settings['height'] ) . 'px;">&nbsp;</div>';
			case 'social':
				return $this->render_social_row( $settings, $global );
			case 'footer':
				return $this->render_footer_row( $settings, $global );
			case 'html':
				if ( '' === trim( $settings['html'] ) ) {
					return '';
				}
				return $is_preview ? do_shortcode( $settings['html'] ) : $settings['html'];
			case 'products':
				$settings['ids'] = $this->resolve_module_item_ids( 'products', $settings, $context );
				if ( empty( $settings['ids'] ) ) {
					return '';
				}
				$shortcode = $this->build_products_shortcode( $settings );
				return $is_preview ? do_shortcode( $shortcode ) : $shortcode;
			case 'posts':
				$settings['ids'] = $this->resolve_module_item_ids( 'posts', $settings, $context );
				if ( empty( $settings['ids'] ) ) {
					return '';
				}
				$shortcode = $this->build_posts_shortcode( $settings );
				return $is_preview ? do_shortcode( $shortcode ) : $shortcode;
		}

		return '';
	}

	function render_image_row( $settings, $global ) {
		if ( empty( $settings['url'] ) ) {
			return '';
		}

		$img = '<img src="' . esc_url( $settings['url'] ) . '" alt="' . esc_attr( $settings['alt'] ) . '" width="' . intval( $settings['width'] ) . '" style="display:block;width:100%;max-width:' . intval( $settings['width'] ) . 'px;height:auto;border:0;">';
		if ( ! empty( $settings['link'] ) ) {
			$img = '<a href="' . esc_url( $settings['link'] ) . '" target="_blank" rel="noopener">' . $img . '</a>';
		}

		return '<div style="text-align:' . esc_attr( $settings['align'] ) . ';">' . $img . '</div>';
	}

	function render_hero_row( $settings, $global ) {
		$image = '';
		if ( ! empty( $settings['image_url'] ) ) {
			$image = '<tr><td style="padding:0;"><img src="' . esc_url( $settings['image_url'] ) . '" alt="' . esc_attr( $settings['image_alt'] ) . '" style="display:block;width:100%;height:auto;border:0;"></td></tr>';
		}

		$eyebrow = '';
		if ( ! empty( $settings['eyebrow'] ) ) {
			$eyebrow = '<div style="font-size:12px;letter-spacing:1px;text-transform:uppercase;opacity:0.85;margin-bottom:10px;">' . esc_html( $settings['eyebrow'] ) . '</div>';
		}

		$text = '';
		if ( ! empty( $settings['text'] ) ) {
			$text = '<div style="margin:0 0 18px 0;font-size:16px;line-height:1.6;">' . wpautop( $settings['text'] ) . '</div>';
		}

		$button = '';
		if ( ! empty( $settings['button_label'] ) ) {
			$button = $this->render_button_markup( $settings['button_label'], $settings['button_url'], $settings['button_background'], $settings['button_color'], 6, $settings['align'], $global['font_family'] );
		}

		$html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:' . esc_attr( $settings['background'] ) . ';"><tbody>' . $image . '<tr><td style="padding:30px 24px;color:' . esc_attr( $settings['text_color'] ) . ';font-family:' . esc_attr( $global['font_family'] ) . ';text-align:' . esc_attr( $settings['align'] ) . ';">' . $eyebrow . '<div style="font-size:34px;line-height:1.15;font-weight:700;margin:0 0 12px 0;">' . esc_html( $settings['title'] ) . '</div>' . $text . $button . '</td></tr></tbody></table>';

		return $html;
	}

	function render_cta_row( $settings, $global ) {
		$button = '';
		if ( ! empty( $settings['button_label'] ) ) {
			$button = $this->render_button_markup( $settings['button_label'], $settings['button_url'], $settings['button_background'], $settings['button_color'], 6, $settings['align'], $global['font_family'] );
		}

		$html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:' . esc_attr( $settings['background'] ) . ';"><tr><td style="padding:28px 24px;color:' . esc_attr( $settings['text_color'] ) . ';font-family:' . esc_attr( $global['font_family'] ) . ';text-align:' . esc_attr( $settings['align'] ) . ';"><div style="font-size:28px;line-height:1.2;font-weight:700;margin:0 0 10px 0;">' . esc_html( $settings['title'] ) . '</div><div style="font-size:16px;line-height:1.6;margin:0 0 18px 0;">' . wpautop( $settings['text'] ) . '</div>' . $button . '</td></tr></table>';

		return $html;
	}

	function render_social_row( $settings, $global ) {
		$links = array_filter(
			array(
				'Facebook' => $settings['facebook'],
				'Instagram' => $settings['instagram'],
				'LinkedIn' => $settings['linkedin'],
				'X' => $settings['x'],
				'YouTube' => $settings['youtube'],
			)
		);

		if ( empty( $links ) ) {
			return '';
		}

		$buttons = array();
		foreach ( $links as $label => $url ) {
			$buttons[] = '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" style="display:inline-block;padding:8px 12px;margin:4px;border:1px solid #cbd5e1;border-radius:999px;color:' . esc_attr( $global['text_color'] ) . ';text-decoration:none;font-family:' . esc_attr( $global['font_family'] ) . ';font-size:13px;">' . esc_html( $label ) . '</a>';
		}

		$title = '';
		if ( ! empty( $settings['title'] ) ) {
			$title = '<div style="font-size:16px;font-weight:700;margin:0 0 10px 0;">' . esc_html( $settings['title'] ) . '</div>';
		}

		return '<div style="text-align:' . esc_attr( $settings['align'] ) . ';">' . $title . implode( '', $buttons ) . '</div>';
	}

	function render_footer_row( $settings, $global ) {
		$view_url = ! empty( $settings['view_url'] ) ? $settings['view_url'] : '{VIEW_LINK}';
		$unsubscribe_url = ! empty( $settings['unsubscribe_url'] ) ? $settings['unsubscribe_url'] : '{UNSUBSCRIBE_URL}';
		$manage_url = ! empty( $settings['manage_url'] ) ? $settings['manage_url'] : '';

		$links = array();
		if ( ! empty( $manage_url ) ) {
			$links[] = $this->render_dynamic_link( $manage_url, __( 'Profil verwalten', 'email-newsletter' ), $settings['link_color'] );
		}
		if ( ! empty( $view_url ) ) {
			$links[] = $this->render_dynamic_link( $view_url, __( 'Im Browser ansehen', 'email-newsletter' ), $settings['link_color'] );
		}
		if ( ! empty( $unsubscribe_url ) ) {
			$links[] = $this->render_dynamic_link( $unsubscribe_url, __( 'Abmelden', 'email-newsletter' ), $settings['link_color'] );
		}

		$address = '';
		if ( ! empty( $settings['address'] ) ) {
			$address = '<div style="margin:8px 0;white-space:pre-line;">' . esc_html( $settings['address'] ) . '</div>';
		}

		$link_line = '';
		if ( ! empty( $links ) ) {
			$link_line = '<div style="margin-top:10px;">' . implode( ' &nbsp;|&nbsp; ', $links ) . '</div>';
		}

		$html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:' . esc_attr( $settings['background'] ) . ';"><tr><td style="padding:22px 24px;color:' . esc_attr( $settings['text_color'] ) . ';font-family:' . esc_attr( $global['font_family'] ) . ';font-size:13px;line-height:1.6;text-align:' . esc_attr( $settings['align'] ) . ';"><strong>' . esc_html( $settings['company'] ) . '</strong>' . $address . '<div>' . esc_html( $settings['legal_text'] ) . '</div>' . $link_line . '</td></tr></table>';

		return $html;
	}

	function render_button_markup( $label, $url, $background, $color, $radius, $align, $font_family ) {
		if ( empty( $label ) ) {
			return '';
		}

		return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" align="' . esc_attr( $align ) . '"><tr><td bgcolor="' . esc_attr( $background ) . '" style="border-radius:' . intval( $radius ) . 'px;background:' . esc_attr( $background ) . ';"><a href="' . esc_url( $url ) . '" style="display:inline-block;padding:12px 22px;color:' . esc_attr( $color ) . ';text-decoration:none;font-family:' . esc_attr( $font_family ) . ';font-size:16px;font-weight:700;">' . esc_html( $label ) . '</a></td></tr></table>';
	}

	function build_products_shortcode( $settings ) {
		if ( 'single' === $settings['layout'] ) {
			$ids = explode( ',', (string) $settings['ids'] );
			$single_id = intval( trim( $ids[0] ) );
			if ( $single_id <= 0 ) {
				return '';
			}

			$shortcode = '[enews_product id="' . esc_attr( $single_id ) . '" show_image="' . esc_attr( $settings['show_image'] ) . '" show_price="' . esc_attr( $settings['show_price'] ) . '" show_old_price="' . esc_attr( $settings['show_old_price'] ) . '" show_button="' . esc_attr( $settings['show_button'] ) . '" show_badge="' . esc_attr( $settings['show_badge'] ) . '" track="' . esc_attr( $settings['track'] ) . '"';
		} else {
			$shortcode = '[enews_products ids="' . esc_attr( $settings['ids'] ) . '" layout="' . esc_attr( $settings['layout'] ) . '" show_image="' . esc_attr( $settings['show_image'] ) . '" show_price="' . esc_attr( $settings['show_price'] ) . '" show_old_price="' . esc_attr( $settings['show_old_price'] ) . '" show_button="' . esc_attr( $settings['show_button'] ) . '" show_badge="' . esc_attr( $settings['show_badge'] ) . '" track="' . esc_attr( $settings['track'] ) . '"';
		}

		if ( ! empty( $settings['badge_text'] ) ) {
			$shortcode .= ' badge_text="' . esc_attr( $settings['badge_text'] ) . '"';
		}
		if ( ! empty( $settings['button_text'] ) ) {
			$shortcode .= ' button_text="' . esc_attr( $settings['button_text'] ) . '"';
		}
		$shortcode .= ']';

		return $shortcode;
	}

	function resolve_module_item_ids( $item_type, $settings, $context = array() ) {
		$query_mode = isset( $settings['query_mode'] ) ? $settings['query_mode'] : 'manual';
		if ( 'trigger' === $query_mode ) {
			return $this->resolve_trigger_module_item_ids( $item_type, $settings, $context );
		}

		if ( 'latest' === $query_mode ) {
			$layout = isset( $settings['layout'] ) ? (string) $settings['layout'] : '';
			$default_limit = ( 'single' === $layout ) ? 1 : 6;
			$limit = isset( $settings['query_limit'] ) ? intval( $settings['query_limit'] ) : $default_limit;
			if ( 'single' === $layout ) {
				$limit = 1;
			}
			$limit = max( 1, min( 24, $limit ) );

			if ( 'posts' === $item_type ) {
				$scope = isset( $settings['query_scope'] ) ? (string) $settings['query_scope'] : 'all';
				$ids = $this->get_latest_item_ids( $item_type, $limit, $scope );
				return implode( ',', $ids );
			}

			$items = $this->search_items( $item_type, '', array(), $limit );
			$ids = array();
			foreach ( (array) $items as $item ) {
				if ( isset( $item['id'] ) ) {
					$ids[] = intval( $item['id'] );
				}
			}
			$ids = array_values( array_unique( array_filter( $ids ) ) );
			return implode( ',', $ids );
		}

		return isset( $settings['ids'] ) ? $this->sanitize_ids( $settings['ids'] ) : '';
	}

	function resolve_trigger_module_item_ids( $item_type, $settings, $context = array() ) {
		$layout = isset( $settings['layout'] ) ? (string) $settings['layout'] : '';
		$limit = isset( $settings['query_limit'] ) ? intval( $settings['query_limit'] ) : ( 'single' === $layout ? 1 : 6 );
		if ( 'single' === $layout ) {
			$limit = 1;
		}
		$limit = max( 1, min( 24, $limit ) );

		$post_types = $this->get_post_types_for_item_type( $item_type );
		if ( empty( $post_types ) ) {
			return '';
		}

		$source_post_id = isset( $context['source_post_id'] ) ? intval( $context['source_post_id'] ) : 0;
		$ids = array();

		if ( $source_post_id > 0 ) {
			$source_post_type = get_post_type( $source_post_id );
			if ( $source_post_type && in_array( $source_post_type, $post_types, true ) && 'publish' === get_post_status( $source_post_id ) ) {
				$ids[] = $source_post_id;
			}
		}

		if ( count( $ids ) < $limit ) {
			$args = array(
				'post_type' => $post_types,
				'post_status' => 'publish',
				'posts_per_page' => $limit - count( $ids ),
				'orderby' => 'date',
				'order' => 'DESC',
				'fields' => 'ids',
				'no_found_rows' => true,
				'ignore_sticky_posts' => true,
			);

			if ( ! empty( $ids ) ) {
				$args['post__not_in'] = $ids;
			}

			if ( 'posts' === $item_type && $source_post_id > 0 && in_array( $source_post_id, $ids, true ) ) {
				$source_categories = wp_get_post_categories( $source_post_id, array( 'fields' => 'ids' ) );
				if ( ! empty( $source_categories ) ) {
					$args['category__in'] = array_map( 'intval', $source_categories );
				}
			}

			if ( 'posts' === $item_type ) {
				$scope = isset( $settings['query_scope'] ) ? (string) $settings['query_scope'] : 'all';
				$date_query = $this->build_posts_date_query_for_scope( $scope );
				if ( ! empty( $date_query ) ) {
					$args['date_query'] = array( $date_query );
				}
			}

			$latest_ids = get_posts( $args );
			if ( ! empty( $latest_ids ) ) {
				$ids = array_merge( $ids, array_map( 'intval', $latest_ids ) );
			}
		}

		$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
		if ( empty( $ids ) ) {
			return '';
		}

		if ( count( $ids ) > $limit ) {
			$ids = array_slice( $ids, 0, $limit );
		}

		return implode( ',', $ids );
	}

	function get_latest_item_ids( $item_type, $limit, $scope = 'all' ) {
		$post_types = $this->get_post_types_for_item_type( $item_type );
		if ( empty( $post_types ) ) {
			return array();
		}

		$args = array(
			'post_type' => $post_types,
			'post_status' => 'publish',
			'posts_per_page' => max( 1, min( 24, intval( $limit ) ) ),
			'orderby' => 'date',
			'order' => 'DESC',
			'fields' => 'ids',
			'no_found_rows' => true,
			'ignore_sticky_posts' => true,
		);

		if ( 'posts' === $item_type ) {
			$date_query = $this->build_posts_date_query_for_scope( $scope );
			if ( ! empty( $date_query ) ) {
				$args['date_query'] = array( $date_query );
			}
		}

		$ids = get_posts( $args );
		$ids = array_values( array_unique( array_filter( array_map( 'intval', (array) $ids ) ) ) );
		return $ids;
	}

	function build_posts_date_query_for_scope( $scope ) {
		$scope = is_string( $scope ) ? strtolower( trim( $scope ) ) : 'all';
		$now = current_time( 'timestamp' );

		if ( 'week' === $scope ) {
			$week_start = intval( date( 'N', $now ) );
			$start = mktime( 0, 0, 0, intval( date( 'n', $now ) ), intval( date( 'j', $now ) ) - ( $week_start - 1 ), intval( date( 'Y', $now ) ) );
			$end = mktime( 23, 59, 59, intval( date( 'n', $now ) ), intval( date( 'j', $now ) ) + ( 7 - $week_start ), intval( date( 'Y', $now ) ) );
			return array(
				'after' => gmdate( 'Y-m-d H:i:s', $start ),
				'before' => gmdate( 'Y-m-d H:i:s', $end ),
				'inclusive' => true,
			);
		}

		if ( 'month' === $scope ) {
			$year = intval( date( 'Y', $now ) );
			$month = intval( date( 'n', $now ) );
			return array(
				'year' => $year,
				'monthnum' => $month,
				'inclusive' => true,
			);
		}

		return array();
	}

	function build_posts_shortcode( $settings ) {
		if ( 'single' === $settings['layout'] ) {
			$ids = explode( ',', $settings['ids'] );
			$shortcode = '[enews_post id="' . esc_attr( trim( $ids[0] ) ) . '" show_image="' . esc_attr( $settings['show_image'] ) . '" show_excerpt="' . esc_attr( $settings['show_excerpt'] ) . '" excerpt_words="' . intval( $settings['excerpt_words'] ) . '" show_button="' . esc_attr( $settings['show_button'] ) . '" track="' . esc_attr( $settings['track'] ) . '"';
			if ( ! empty( $settings['button_text'] ) ) {
				$shortcode .= ' button_text="' . esc_attr( $settings['button_text'] ) . '"';
			}
			$shortcode .= ']';
		} elseif ( 'links' === $settings['layout'] ) {
			$shortcode = '[enews_post_links ids="' . esc_attr( $settings['ids'] ) . '" show_image="' . esc_attr( $settings['show_image'] ) . '" show_excerpt="' . esc_attr( $settings['show_excerpt'] ) . '" excerpt_words="' . intval( $settings['excerpt_words'] ) . '" track="' . esc_attr( $settings['track'] ) . '"]';
		} else {
			$shortcode = '[enews_posts ids="' . esc_attr( $settings['ids'] ) . '" layout="' . esc_attr( $settings['layout'] ) . '" show_image="' . esc_attr( $settings['show_image'] ) . '" show_excerpt="' . esc_attr( $settings['show_excerpt'] ) . '" excerpt_words="' . intval( $settings['excerpt_words'] ) . '" show_button="' . esc_attr( $settings['show_button'] ) . '" track="' . esc_attr( $settings['track'] ) . '"';
			if ( ! empty( $settings['button_text'] ) ) {
				$shortcode .= ' button_text="' . esc_attr( $settings['button_text'] ) . '"';
			}
			$shortcode .= ']';
		}

		return $shortcode;
	}

	function wrap_row( $html, $global, $padding = null ) {
		$padding = is_null( $padding ) ? intval( $global['section_gap'] ) : intval( $padding );
		return '<tr><td style="padding:' . $padding . 'px 24px;color:' . esc_attr( $global['text_color'] ) . ';font-family:' . esc_attr( $global['font_family'] ) . ';">' . $html . '</td></tr>';
	}

	function sanitize_text_decoration( $value ) {
		$allowed = array( 'none', 'underline', 'line-through', 'overline' );
		$value = is_string( $value ) ? strtolower( trim( $value ) ) : 'none';
		return in_array( $value, $allowed, true ) ? $value : 'none';
	}

	function build_typography_css( $global ) {
		$heading_size = intval( $this->get_array_value( $global, 'heading_font_size', 30 ) );
		$heading_color = $this->sanitize_color( $this->get_array_value( $global, 'heading_color', '#111827' ), '#111827' );
		$heading_decoration = $this->sanitize_text_decoration( $this->get_array_value( $global, 'heading_decoration', 'none' ) );
		$paragraph_size = intval( $this->get_array_value( $global, 'paragraph_font_size', 16 ) );
		$paragraph_color = $this->sanitize_color( $this->get_array_value( $global, 'paragraph_color', '#374151' ), '#374151' );
		$paragraph_decoration = $this->sanitize_text_decoration( $this->get_array_value( $global, 'paragraph_decoration', 'none' ) );
		$quote_size = intval( $this->get_array_value( $global, 'quote_font_size', 20 ) );
		$quote_color = $this->sanitize_color( $this->get_array_value( $global, 'quote_color', '#1f2937' ), '#1f2937' );
		$quote_decoration = $this->sanitize_text_decoration( $this->get_array_value( $global, 'quote_decoration', 'none' ) );
		$font_family = sanitize_text_field( $this->get_array_value( $global, 'font_family', 'Arial, sans-serif' ) );

		return 'h1,h2,h3{font-family:' . esc_attr( $font_family ) . ';font-size:' . $heading_size . 'px;color:' . esc_attr( $heading_color ) . ';text-decoration:' . esc_attr( $heading_decoration ) . ';}'
			. 'p,li{font-family:' . esc_attr( $font_family ) . ';font-size:' . $paragraph_size . 'px;color:' . esc_attr( $paragraph_color ) . ';text-decoration:' . esc_attr( $paragraph_decoration ) . ';}'
			. 'blockquote{font-family:' . esc_attr( $font_family ) . ';font-size:' . $quote_size . 'px;color:' . esc_attr( $quote_color ) . ';text-decoration:' . esc_attr( $quote_decoration ) . ';margin:0;padding-left:14px;border-left:3px solid #cbd5e1;font-style:italic;}'
			. 'a{color:inherit;}';
	}

	function ajax_preview() {
		if ( ! current_user_can( 'save_newsletter' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'email-newsletter' ) ), 403 );
		}

		check_ajax_referer( 'enews_builder_v2_preview', 'nonce' );

		$raw_state = isset( $_POST['state'] ) ? wp_unslash( $_POST['state'] ) : '';
		$decoded = json_decode( $raw_state, true );

		if ( ! is_array( $decoded ) ) {
			wp_send_json_error( array( 'message' => __( 'Ungueltiger Builder-Status.', 'email-newsletter' ) ), 400 );
		}

		$newsletter_id = isset( $_POST['newsletter_id'] ) ? intval( $_POST['newsletter_id'] ) : 0;
		$state = $this->sanitize_state( $decoded, $newsletter_id );
		wp_send_json_success( array( 'html' => $this->render_state_to_preview( $state ) ) );
	}

	function ajax_search_items() {
		if ( ! current_user_can( 'save_newsletter' ) ) {
			wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'email-newsletter' ) ), 403 );
		}

		check_ajax_referer( 'enews_builder_v2_search_items', 'nonce' );

		$item_type = isset( $_POST['itemType'] ) ? sanitize_key( wp_unslash( $_POST['itemType'] ) ) : '';
		$query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
		$include_ids_raw = isset( $_POST['includeIds'] ) ? wp_unslash( $_POST['includeIds'] ) : '';
		$include_ids = $this->sanitize_ids( $include_ids_raw );
		$include_ids = empty( $include_ids ) ? array() : array_map( 'intval', explode( ',', $include_ids ) );

		$items = $this->search_items( $item_type, $query, $include_ids, 20 );
		wp_send_json_success( array( 'items' => $items ) );
	}

	function search_items( $item_type, $query = '', $include_ids = array(), $limit = 20 ) {
		$post_types = $this->get_post_types_for_item_type( $item_type );
		if ( empty( $post_types ) ) {
			return array();
		}

		$args = array(
			'post_type' => $post_types,
			'post_status' => 'publish',
			'posts_per_page' => max( 1, min( 50, intval( $limit ) ) ),
			'orderby' => 'date',
			'order' => 'DESC',
			'fields' => 'ids',
			'no_found_rows' => true,
			'ignore_sticky_posts' => true,
		);

		if ( ! empty( $query ) ) {
			$args['s'] = $query;
		}

		if ( ! empty( $include_ids ) ) {
			$args['post__in'] = array_map( 'intval', $include_ids );
			$args['orderby'] = 'post__in';
		}

		$post_ids = get_posts( $args );
		$items = array();

		foreach ( $post_ids as $post_id ) {
			$title = get_the_title( $post_id );
			if ( '' === trim( (string) $title ) ) {
				$title = sprintf( __( '(Ohne Titel) #%d', 'email-newsletter' ), intval( $post_id ) );
			}

			$items[] = array(
				'id' => intval( $post_id ),
				'title' => $title,
				'type' => get_post_type( $post_id ),
			);
		}

		return $items;
	}

	function get_post_types_for_item_type( $item_type ) {
		if ( 'products' === $item_type ) {
			$candidates = array( 'product', 'mp_product' );
			$types = array();
			foreach ( $candidates as $candidate ) {
				if ( post_type_exists( $candidate ) ) {
					$types[] = $candidate;
				}
			}

			return $types;
		}

		if ( 'posts' === $item_type ) {
			return array( 'post' );
		}

		return array();
	}

	function sanitize_ids( $ids ) {
		$ids = array_filter( array_map( 'trim', explode( ',', (string) $ids ) ) );
		$ids = array_filter( $ids, 'is_numeric' );
		$ids = array_unique( $ids );
		return implode( ',', $ids );
	}

	function sanitize_bool_string( $value ) {
		return in_array( (string) $value, array( '0', 'false', '' ), true ) ? '0' : '1';
	}

	function sanitize_align( $value ) {
		return in_array( $value, array( 'left', 'center', 'right' ), true ) ? $value : 'left';
	}

	function sanitize_color( $value, $default ) {
		$value = trim( (string) $value );
		if ( preg_match( '/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/', $value ) ) {
			return $value;
		}
		return $default;
	}

	function sanitize_dynamic_url( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return '';
		}
		if ( preg_match( '/^\{[A-Z_]+\}$/', $value ) ) {
			return $value;
		}
		return esc_url_raw( $value );
	}

	function render_dynamic_link( $url, $label, $color ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return '';
		}

		if ( preg_match( '/^\{[A-Z_]+\}$/', $url ) ) {
			return '<a href="' . esc_attr( $url ) . '" style="color:' . esc_attr( $color ) . ';text-decoration:none;">' . esc_html( $label ) . '</a>';
		}

		return '<a href="' . esc_url( $url ) . '" style="color:' . esc_attr( $color ) . ';text-decoration:none;">' . esc_html( $label ) . '</a>';
	}

	function sanitize_int( $source, $key, $min, $max, $default ) {
		$value = isset( $source[ $key ] ) ? intval( $source[ $key ] ) : $default;
		return max( $min, min( $max, $value ) );
	}

	function get_array_value( $source, $key, $default = '' ) {
		return isset( $source[ $key ] ) ? $source[ $key ] : $default;
	}

	function migrate_html_shortcodes_to_typed( $state ) {
		if ( empty( $state['modules'] ) || ! is_array( $state['modules'] ) ) {
			return $state;
		}

		$available = $this->get_available_modules();
		$migrated = array();

		foreach ( $state['modules'] as $module ) {
			if ( ! isset( $module['type'] ) || 'html' !== $module['type'] ) {
				$migrated[] = $module;
				continue;
			}

			$html = isset( $module['settings']['html'] ) ? (string) $module['settings']['html'] : '';
			$shortcode = $this->parse_legacy_module_shortcode( $html );
			if ( ! $shortcode || ! isset( $available[ $shortcode['type'] ] ) ) {
				$migrated[] = $module;
				continue;
			}

			$base_settings = $available[ $shortcode['type'] ]['defaults'];
			$old_settings = isset( $module['settings'] ) && is_array( $module['settings'] ) ? $module['settings'] : array();
			$new_settings = array_merge( $base_settings, $shortcode['attrs'] );
			if ( ! empty( $shortcode['forced_layout'] ) ) {
				$new_settings['layout'] = $shortcode['forced_layout'];
			}
			if ( 'posts' === $shortcode['type'] && empty( $new_settings['ids'] ) && ! empty( $shortcode['attrs']['id'] ) ) {
				$new_settings['ids'] = $shortcode['attrs']['id'];
			}

			$new_settings['lock_full_width'] = isset( $old_settings['lock_full_width'] ) ? $old_settings['lock_full_width'] : ( isset( $new_settings['lock_full_width'] ) ? $new_settings['lock_full_width'] : '0' );
			$new_settings['grid_span'] = isset( $old_settings['grid_span'] ) ? $old_settings['grid_span'] : ( isset( $new_settings['grid_span'] ) ? $new_settings['grid_span'] : 12 );
			$new_settings['grid_col'] = isset( $old_settings['grid_col'] ) ? $old_settings['grid_col'] : ( isset( $new_settings['grid_col'] ) ? $new_settings['grid_col'] : 1 );
			$new_settings['grid_row'] = isset( $old_settings['grid_row'] ) ? $old_settings['grid_row'] : ( isset( $new_settings['grid_row'] ) ? $new_settings['grid_row'] : 1 );
			$new_settings['grid_rows'] = isset( $old_settings['grid_rows'] ) ? $old_settings['grid_rows'] : ( isset( $new_settings['grid_rows'] ) ? $new_settings['grid_rows'] : 1 );
			$new_settings['canvas_min_height'] = isset( $old_settings['canvas_min_height'] ) ? $old_settings['canvas_min_height'] : ( isset( $new_settings['canvas_min_height'] ) ? $new_settings['canvas_min_height'] : 0 );

			$module['type'] = $shortcode['type'];
			$module['settings'] = $new_settings;
			$migrated[] = $module;
		}

		$state['modules'] = $migrated;
		return $state;
	}

	function parse_legacy_module_shortcode( $html ) {
		$trimmed = trim( (string) $html );
		if ( '' === $trimmed ) {
			return false;
		}

		if ( ! preg_match( '/^\[(enews_products|enews_posts|enews_post_links|enews_post)\s*([^\]]*)\]$/i', $trimmed, $matches ) ) {
			return false;
		}

		$tag = strtolower( $matches[1] );
		$attrs = $this->parse_shortcode_attrs( isset( $matches[2] ) ? $matches[2] : '' );

		if ( 'enews_products' === $tag ) {
			return array(
				'type' => 'products',
				'forced_layout' => '',
				'attrs' => $attrs,
			);
		}

		if ( 'enews_post_links' === $tag ) {
			return array(
				'type' => 'posts',
				'forced_layout' => 'links',
				'attrs' => $attrs,
			);
		}

		if ( 'enews_post' === $tag ) {
			return array(
				'type' => 'posts',
				'forced_layout' => 'single',
				'attrs' => $attrs,
			);
		}

		return array(
			'type' => 'posts',
			'forced_layout' => '',
			'attrs' => $attrs,
		);
	}

	function parse_shortcode_attrs( $raw_attrs ) {
		$attrs = array();
		if ( ! is_string( $raw_attrs ) || '' === trim( $raw_attrs ) ) {
			return $attrs;
		}

		if ( preg_match_all( '/(\w+)="([^"]*)"/', $raw_attrs, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$key = isset( $match[1] ) ? sanitize_key( $match[1] ) : '';
				if ( '' === $key ) {
					continue;
				}
				$attrs[ $key ] = isset( $match[2] ) ? $match[2] : '';
			}
		}

		return $attrs;
	}
}
