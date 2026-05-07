<?php
return array(
	'label' => 'Separator',
	'icon' => 'SE',
	'defaults' => array(
		'color' => '#d1d5db',
		'thickness' => 1,
	),
	'fields' => array(
		array( 'key' => 'color', 'label' => 'Farbe', 'type' => 'color' ),
		array( 'key' => 'thickness', 'label' => 'Stärke', 'type' => 'number', 'min' => 1, 'max' => 8 ),
	),
);