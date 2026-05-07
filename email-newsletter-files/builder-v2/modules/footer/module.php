<?php
return array(
	'label' => 'Footer',
	'icon' => 'FT',
	'defaults' => array(
		'lock_full_width' => '1',
		'company' => get_bloginfo( 'name' ),
		'address' => '',
		'legal_text' => 'Du erhältst diese E-Mail, weil Du mit uns in Kontakt stehst.',
		'manage_url' => '',
		'view_url' => '{VIEW_LINK}',
		'unsubscribe_url' => '{UNSUBSCRIBE_URL}',
		'align' => 'center',
		'background' => '#f8fafc',
		'text_color' => '#64748b',
		'link_color' => '#2563eb',
	),
	'fields' => array(
		array( 'key' => 'company', 'label' => 'Firma', 'type' => 'text' ),
		array( 'key' => 'address', 'label' => 'Adresse', 'type' => 'textarea' ),
		array( 'key' => 'legal_text', 'label' => 'Hinweistext', 'type' => 'textarea' ),
		array( 'key' => 'manage_url', 'label' => 'Profil-URL', 'type' => 'text' ),
		array( 'key' => 'view_url', 'label' => 'Browser-Ansicht URL', 'type' => 'text' ),
		array( 'key' => 'unsubscribe_url', 'label' => 'Abmelde-URL', 'type' => 'text' ),
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
		array( 'key' => 'link_color', 'label' => 'Linkfarbe', 'type' => 'color' ),
	),
);