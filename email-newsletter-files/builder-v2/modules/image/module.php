<?php
return array(
	'label' => 'Image',
	'icon' => 'IM',
	'defaults' => array(
		'media_id' => 0,
		'url' => '',
		'alt' => '',
		'link' => '',
		'align' => 'center',
		'width' => 600,
	),
	'fields' => array(
		array( 'key' => 'url', 'label' => 'Bild-URL', 'type' => 'text' ),
		array( 'key' => 'alt', 'label' => 'Alt-Text', 'type' => 'text' ),
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