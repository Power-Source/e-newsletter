<?php
return array(
	'label' => 'Header',
	'icon' => 'HD',
	'defaults' => array(
		'logo_url' => '',
		'logo_alt' => '',
		'logo_link' => home_url( '/' ),
		'title' => get_bloginfo( 'name' ),
		'subtitle' => '',
		'align' => 'center',
		'background' => '#ffffff',
		'text_color' => '#111827',
	),
	'fields' => array(
		array( 'key' => 'logo_url', 'label' => 'Logo-URL', 'type' => 'text' ),
		array( 'key' => 'logo_alt', 'label' => 'Logo Alt-Text', 'type' => 'text' ),
		array( 'key' => 'logo_link', 'label' => 'Logo-Link', 'type' => 'text' ),
		array( 'key' => 'title', 'label' => 'Titel', 'type' => 'text' ),
		array( 'key' => 'subtitle', 'label' => 'Untertitel', 'type' => 'text' ),
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
	),
);