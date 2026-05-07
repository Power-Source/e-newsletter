<?php
return array(
	'label' => 'Heading',
	'icon' => 'H',
	'defaults' => array(
		'text' => 'Neue Überschrift',
		'level' => 'h2',
		'align' => 'left',
		'color' => '#111827',
		'font_size' => 30,
	),
	'fields' => array(
		array( 'key' => 'text', 'label' => 'Text', 'type' => 'text' ),
		array(
			'key' => 'level',
			'label' => 'Ebene',
			'type' => 'select',
			'options' => array(
				array( 'value' => 'h1', 'label' => 'H1' ),
				array( 'value' => 'h2', 'label' => 'H2' ),
				array( 'value' => 'h3', 'label' => 'H3' ),
			),
		),
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
		array( 'key' => 'color', 'label' => 'Farbe', 'type' => 'color' ),
		array( 'key' => 'font_size', 'label' => 'Schriftgröße', 'type' => 'number', 'min' => 14, 'max' => 54 ),
	),
);