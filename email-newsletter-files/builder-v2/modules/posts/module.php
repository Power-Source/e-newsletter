<?php
return array(
	'label' => 'Blog Posts',
	'icon' => 'BP',
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
		'button_text' => 'Weiterlesen',
	),
	'fields' => array(
		array(
			'key' => 'query_mode',
			'label' => 'Inhaltsquelle',
			'type' => 'select',
			'options' => array(
				array( 'value' => 'manual', 'label' => 'Manuell' ),
				array( 'value' => 'latest', 'label' => 'Neueste Inhalte' ),
				array( 'value' => 'trigger', 'label' => 'Trigger-Kontext' ),
			),
		),
		array(
			'key' => 'query_scope',
			'label' => 'Zeitraum',
			'type' => 'select',
			'options' => array(
				array( 'value' => 'all', 'label' => 'Alle' ),
				array( 'value' => 'week', 'label' => 'Diese Woche' ),
				array( 'value' => 'month', 'label' => 'Dieser Monat' ),
			),
		),
		array( 'key' => 'query_limit', 'label' => 'Anzahl', 'type' => 'number', 'min' => 1, 'max' => 24 ),
		array( 'key' => 'ids', 'label' => 'IDs', 'type' => 'text' ),
		array(
			'key' => 'layout',
			'label' => 'Layout',
			'type' => 'select',
			'options' => array(
				array( 'value' => 'single', 'label' => 'Single' ),
				array( 'value' => 'links', 'label' => 'Links' ),
				array( 'value' => 'grid', 'label' => 'Grid' ),
				array( 'value' => 'slider', 'label' => 'Slider' ),
			),
		),
		array( 'key' => 'show_image', 'label' => 'Bild anzeigen', 'type' => 'toggle' ),
		array( 'key' => 'show_excerpt', 'label' => 'Excerpt anzeigen', 'type' => 'toggle' ),
		array( 'key' => 'excerpt_words', 'label' => 'Excerpt-Wörter', 'type' => 'number', 'min' => 8, 'max' => 80 ),
		array( 'key' => 'show_button', 'label' => 'Button anzeigen', 'type' => 'toggle' ),
		array( 'key' => 'track', 'label' => 'Tracking aktiv', 'type' => 'toggle' ),
		array( 'key' => 'button_text', 'label' => 'Button-Text', 'type' => 'text' ),
	),
);