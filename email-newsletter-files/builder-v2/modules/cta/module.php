<?php
return array(
	'label' => 'Call To Action',
	'icon' => 'CA',
	'defaults' => array(
		'title' => 'Call to Action',
		'text' => 'Kurzer Hinweistext für den CTA-Bereich.',
		'button_label' => 'Mehr erfahren',
		'button_url' => home_url( '/' ),
		'align' => 'center',
		'background' => '#eff6ff',
		'text_color' => '#0f172a',
		'button_background' => '#2563eb',
		'button_color' => '#ffffff',
	),
	'fields' => array(
		array( 'key' => 'title', 'label' => 'Titel', 'type' => 'text' ),
		array( 'key' => 'text', 'label' => 'Text', 'type' => 'textarea' ),
		array( 'key' => 'button_label', 'label' => 'Button-Text', 'type' => 'text' ),
		array( 'key' => 'button_url', 'label' => 'Button-Link', 'type' => 'text' ),
		array(
			'key' => 'align',
			'label' => 'Ausrichtung',
			'type' => 'select',
			'options' => array(
				array( 'value' => 'left', 'label' => 'Links' ),
				array( 'value' => 'center', 'label' => 'Zentriert' ),
				array( 'value' => 'right', 'label' => 'Rechts' ),
			),
		),
		array( 'key' => 'background', 'label' => 'Hintergrund', 'type' => 'color' ),
		array( 'key' => 'text_color', 'label' => 'Textfarbe', 'type' => 'color' ),
		array( 'key' => 'button_background', 'label' => 'Button-Hintergrund', 'type' => 'color' ),
		array( 'key' => 'button_color', 'label' => 'Button-Farbe', 'type' => 'color' ),
	),
);