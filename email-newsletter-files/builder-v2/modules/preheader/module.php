<?php
return array(
	'label' => 'Preheader',
	'icon' => 'PH',
	'defaults' => array(
		'text' => 'Few words summary',
		'view_url' => '{VIEW_LINK}',
		'view_label' => 'View online',
		'align' => 'center',
		'text_color' => '#64748b',
	),
	'fields' => array(
		array( 'key' => 'text', 'label' => 'Preheader', 'type' => 'text' ),
		array( 'key' => 'view_url', 'label' => 'Browser-URL', 'type' => 'text' ),
		array( 'key' => 'view_label', 'label' => 'Browser-Link Text', 'type' => 'text' ),
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
		array( 'key' => 'text_color', 'label' => 'Textfarbe', 'type' => 'color' ),
	),
);