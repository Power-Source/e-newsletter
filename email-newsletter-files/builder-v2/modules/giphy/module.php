<?php
return array(
	'label' => 'Giphy',
	'icon' => 'GI',
	'defaults' => array(
		'gif_url' => '',
		'alt' => '',
		'caption' => '',
		'link' => '',
		'align' => 'center',
		'width' => 600,
	),
	'fields' => array(
		array( 'key' => 'gif_url', 'label' => 'GIF-URL', 'type' => 'text' ),
		array( 'key' => 'alt', 'label' => 'Alt-Text', 'type' => 'text' ),
		array( 'key' => 'caption', 'label' => 'Caption', 'type' => 'textarea' ),
		array( 'key' => 'link', 'label' => 'Link', 'type' => 'text' ),
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
		array( 'key' => 'width', 'label' => 'Breite', 'type' => 'number', 'min' => 80, 'max' => 1200 ),
	),
);