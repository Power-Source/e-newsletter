<?php
return array(
	'label' => 'Text',
	'icon' => 'TX',
	'defaults' => array(
		'text' => 'Absatztext',
		'align' => 'left',
		'color' => '#374151',
		'font_size' => 16,
	),
	'fields' => array(
		array( 'key' => 'text', 'label' => 'Text', 'type' => 'textarea' ),
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
		array( 'key' => 'font_size', 'label' => 'Schriftgröße', 'type' => 'number', 'min' => 12, 'max' => 24 ),
	),
);