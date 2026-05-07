<?php
return array(
	'label' => 'Social',
	'icon' => 'SO',
	'defaults' => array(
		'title' => 'Folge uns',
		'align' => 'center',
		'facebook' => '',
		'instagram' => '',
		'linkedin' => '',
		'x' => '',
		'youtube' => '',
	),
	'fields' => array(
		array( 'key' => 'title', 'label' => 'Titel', 'type' => 'text' ),
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
		array( 'key' => 'facebook', 'label' => 'Facebook URL', 'type' => 'text' ),
		array( 'key' => 'instagram', 'label' => 'Instagram URL', 'type' => 'text' ),
		array( 'key' => 'linkedin', 'label' => 'LinkedIn URL', 'type' => 'text' ),
		array( 'key' => 'x', 'label' => 'X / Twitter URL', 'type' => 'text' ),
		array( 'key' => 'youtube', 'label' => 'YouTube URL', 'type' => 'text' ),
	),
);