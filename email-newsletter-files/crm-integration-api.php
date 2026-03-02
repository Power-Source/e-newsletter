<?php
/**
 * CRM Integration API für e-Newsletter
 * Bietet Schnittstellen für ps-smart-crm zur Newsletter-Integration
 * 
 * @since 1.0.3
 * @package Email_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Bindet einen CRM-Kunden an Newsletter-Gruppen
 * 
 * @param int $crm_customer_id - ID des CRM-Kunden (ID_kunde)
 * @param int|array $newsletter_group_ids - Newsletter-Gruppen ID(s)
 * @param array $customer_data - Kundendaten (name, email, etc.)
 * 
 * @return array ['success' => bool, 'member_id' => int, 'message' => string]
 */
function enewsletter_crm_add_subscriber( $crm_customer_id, $newsletter_group_ids = array(), $customer_data = array() ) {
	global $email_newsletter;
	
	if ( !$email_newsletter ) return array( 'success' => false, 'message' => 'eNewsletter nicht initialisiert' );
	
	// Ensure $newsletter_group_ids is an array
	if ( !is_array( $newsletter_group_ids ) ) {
		$newsletter_group_ids = array( $newsletter_group_ids );
	}
	
	// Use customer_data if provided
	if ( empty( $customer_data ) || !is_array( $customer_data ) ) {
		return array( 'success' => false, 'message' => 'Kunden-Daten erforderlich' );
	}
	
	// Validate email
	$email = isset( $customer_data['email'] ) ? sanitize_email( $customer_data['email'] ) : '';
	if ( !is_email( $email ) ) {
		return array( 'success' => false, 'message' => 'Ungültige E-Mail-Adresse' );
	}
	
	// Prepare member data
	$member_data = array(
		'member_fname' => isset( $customer_data['firstname'] ) ? sanitize_text_field( $customer_data['firstname'] ) : '',
		'member_lname' => isset( $customer_data['lastname'] ) ? sanitize_text_field( $customer_data['lastname'] ) : '',
		'member_email' => $email,
		'member_info' => wp_json_encode( array(
			'crm_customer_id' => intval( $crm_customer_id ),
			'integration_type' => 'crm_customer'
		) )
	);
	
	// Create or update member
	$result = $email_newsletter->create_update_member_user( '', $member_data, 1 );
	
	if ( !$result || empty( $result['member_id'] ) ) {
		return array(
			'success' => false,
			'message' => 'Fehler beim Erstellen/Aktualisieren des Newsletter-Abonnenten'
		);
	}
	
	$member_id = $result['member_id'];
	
	// Add to groups if provided
	if ( !empty( $newsletter_group_ids ) ) {
		$email_newsletter->add_members_to_groups( $member_id, $newsletter_group_ids );
	}
	
	/**
	 * Hook: Kundendaten als Newsletter-Abonnent hinzugefügt
	 * 
	 * @param int $member_id - Newsletter Member ID
	 * @param int $crm_customer_id - CRM Kunden-ID
	 * @param array $member_data - Newsletter Member Daten
	 */
	do_action( 'enewsletter_crm_subscriber_added', $member_id, $crm_customer_id, $member_data );
	
	return array(
		'success' => true,
		'member_id' => $member_id,
		'message' => 'Newsletter-Abonnent erstellt/aktualisiert'
	);
}

/**
 * Entfernt einen CRM-Kunden von Newsletter-Gruppen oder komplett
 * 
 * @param int $crm_customer_id - ID des CRM-Kunden
 * @param int|array $newsletter_group_ids - Optional: Spezifische Gruppen (-1 = alles entfernen)
 * 
 * @return array ['success' => bool, 'message' => string]
 */
function enewsletter_crm_remove_subscriber( $crm_customer_id, $newsletter_group_ids = -1 ) {
	global $wpdb, $email_newsletter;
	
	if ( !$email_newsletter ) return array( 'success' => false, 'message' => 'eNewsletter nicht initialisiert' );
	
	$tb_prefix = $email_newsletter->tb_prefix;
	
	// Find member by CRM customer ID
	$member = $wpdb->get_row( $wpdb->prepare(
		"SELECT member_id FROM {$tb_prefix}enewsletter_members 
		WHERE member_info LIKE %s",
		'%"crm_customer_id":' . intval( $crm_customer_id ) . '%'
	) );
	
	if ( !$member ) {
		return array( 'success' => false, 'message' => 'Newsletter-Abonnent nicht gefunden' );
	}
	
	$member_id = $member->member_id;
	
	if ( $newsletter_group_ids === -1 ) {
		// Delete completely
		$wpdb->delete( "{$tb_prefix}enewsletter_members", array( 'member_id' => $member_id ) );
		$wpdb->delete( "{$tb_prefix}enewsletter_member_group", array( 'member_id' => $member_id ) );
		
		do_action( 'enewsletter_crm_subscriber_deleted', $member_id, $crm_customer_id );
		
		return array( 'success' => true, 'message' => 'Abonnent und alle Gruppen-Zuordnungen gelöscht' );
	} else {
		// Remove from specific groups
		if ( !is_array( $newsletter_group_ids ) ) {
			$newsletter_group_ids = array( $newsletter_group_ids );
		}
		
		foreach ( $newsletter_group_ids as $group_id ) {
			$wpdb->delete(
				"{$tb_prefix}enewsletter_member_group",
				array( 'member_id' => $member_id, 'group_id' => intval( $group_id ) )
			);
		}
		
		do_action( 'enewsletter_crm_subscriber_unsubscribed_groups', $member_id, $crm_customer_id, $newsletter_group_ids );
		
		return array( 'success' => true, 'message' => 'Von Newsletter-Gruppen abgemeldet' );
	}
}

/**
 * Holt Newsletter-Abonnent-Informationen zu einer CRM-Kunden-ID
 * 
 * @param int $crm_customer_id - CRM Kunden-ID
 * 
 * @return array Subscriber Infos oder false
 */
function enewsletter_crm_get_subscriber( $crm_customer_id ) {
	global $wpdb, $email_newsletter;
	
	if ( !$email_newsletter ) return false;
	
	$tb_prefix = $email_newsletter->tb_prefix;
	
	$member = $wpdb->get_row( $wpdb->prepare(
		"SELECT * FROM {$tb_prefix}enewsletter_members 
		WHERE member_info LIKE %s",
		'%"crm_customer_id":' . intval( $crm_customer_id ) . '%'
	), ARRAY_A );
	
	if ( !$member ) return false;
	
	// Get groups
	$groups = $email_newsletter->get_memeber_groups( $member['member_id'] );
	$member['groups'] = $groups;
	
	return $member;
}

/**
 * Sendet einen Newsletter an CRM-Kundengruppe basierend auf Rollen
 * 
 * @param int $newsletter_id - Newsletter ID
 * @param array $target_data - ['type' => 'crm_groups', 'group_ids' => [...]]
 * 
 * @return array ['success' => bool, 'count' => int, 'message' => string]
 */
function enewsletter_crm_send_to_groups( $newsletter_id, $target_data = array() ) {
	global $email_newsletter;
	
	if ( !$email_newsletter ) return array( 'success' => false, 'message' => 'eNewsletter nicht initialisiert' );
	
	if ( !isset( $target_data['group_ids'] ) || empty( $target_data['group_ids'] ) ) {
		return array( 'success' => false, 'message' => 'Keine Gruppen angegeben' );
	}
	
	$group_ids = $target_data['group_ids'];
	if ( !is_array( $group_ids ) ) {
		$group_ids = array( $group_ids );
	}
	
	// Get all members in groups
	$members = array();
	foreach ( $group_ids as $group_id ) {
		$group_members = $email_newsletter->get_members_of_group( intval( $group_id ) );
		if ( is_array( $group_members ) ) {
			foreach ( $group_members as $member ) {
				if ( !in_array( $member['member_id'], $members ) ) {
					$members[] = $member['member_id'];
				}
			}
		}
	}
	
	if ( empty( $members ) ) {
		return array( 'success' => false, 'message' => 'Keine Abonnenten in Gruppen gefunden' );
	}
	
	// Send newsletter
	$result = $email_newsletter->add_send_email_info( $newsletter_id, $members, array(), 'queue' );
	
	return array(
		'success' => true,
		'count' => $result['count'],
		'send_id' => $result['send_id'],
		'message' => sprintf( 'Newsletter an %d Abonnenten in Warteschlange', $result['count'] )
	);
}

/**
 * Erstellt automatisch eine Newsletter-Serie für neue CRM-Kunden
 * 
 * @param int $crm_customer_id - CRM Kunden-ID
 * @param string $series_key - Identifikator der Serie (z.B. 'welcome_series')
 * @param array $customer_data - Kundendaten
 * 
 * @return array ['success' => bool, 'message' => string]
 */
function enewsletter_crm_trigger_series( $crm_customer_id, $series_key = 'welcome_series', $customer_data = array() ) {
	global $email_newsletter;
	
	if ( !$email_newsletter ) return array( 'success' => false, 'message' => 'eNewsletter nicht initialisiert' );
	
	// Check if series is configured
	$series_config = get_option( 'enewsletter_crm_series_' . $series_key );
	
	if ( !$series_config || empty( $series_config['newsletters'] ) ) {
		return array( 'success' => false, 'message' => 'Serie nicht konfiguriert: ' . $series_key );
	}
	
	// Add subscriber to system first
	$subscriber_result = enewsletter_crm_add_subscriber( $crm_customer_id, array(), $customer_data );
	
	if ( !$subscriber_result['success'] ) {
		return $subscriber_result;
	}
	
	$member_id = $subscriber_result['member_id'];
	
	// Schedule newsletters
	$scheduled_count = 0;
	foreach ( $series_config['newsletters'] as $schedule ) {
		$newsletter_id = intval( $schedule['newsletter_id'] );
		$delay_hours = isset( $schedule['delay_hours'] ) ? intval( $schedule['delay_hours'] ) : 0;
		
		if ( $newsletter_id > 0 ) {
			$timestamp = time() + ( $delay_hours * 3600 );
			
			// Schedule sending
			wp_schedule_single_event(
				$timestamp,
				'enewsletter_crm_send_scheduled_newsletter',
				array( $newsletter_id, $member_id, $crm_customer_id )
			);
			
			$scheduled_count++;
		}
	}
	
	/**
	 * Hook: Serie für Kunden getriggert
	 */
	do_action( 'enewsletter_crm_series_triggered', $crm_customer_id, $series_key, $member_id, $scheduled_count );
	
	return array(
		'success' => true,
		'message' => sprintf( '%d Newsletter geplant für Serie: %s', $scheduled_count, $series_key )
	);
}

/**
 * Holt alle Newsletter-Gruppen
 * 
 * @return array Newsletter groups
 */
function enewsletter_crm_get_all_groups() {
	global $email_newsletter;
	
	if ( !$email_newsletter ) return array();
	
	return $email_newsletter->get_groups();
}

/**
 * Erstellt neue Newsletter-Gruppe
 * 
 * @param string $group_name - Gruppenname
 * @param bool $public - Öffentlich?
 * 
 * @return int|false Group ID oder false
 */
function enewsletter_crm_create_group( $group_name, $public = 0 ) {
	global $wpdb, $email_newsletter;
	
	if ( !$email_newsletter ) return false;
	
	$tb_prefix = $email_newsletter->tb_prefix;
	$group_name = sanitize_text_field( $group_name );
	
	$result = $wpdb->insert(
		"{$tb_prefix}enewsletter_groups",
		array(
			'group_name' => $group_name,
			'public' => intval( $public )
		),
		array( '%s', '%d' )
	);
	
	return $result ? $wpdb->insert_id : false;
}

/**
 * Registered Scheduled Event Handler
 * Sendet geplanten Newsletter an einzelnen Member
 */
function enewsletter_crm_send_scheduled_newsletter( $newsletter_id, $member_id, $crm_customer_id ) {
	global $email_newsletter;
	
	if ( !$email_newsletter ) return;
	
	$email_newsletter->add_send_email_info( $newsletter_id, $member_id, array(), 'queue' );
	
	do_action( 'enewsletter_crm_newsletter_sent_scheduled', $newsletter_id, $member_id, $crm_customer_id );
}

// Register cron event
add_action( 'enewsletter_crm_send_scheduled_newsletter', 'enewsletter_crm_send_scheduled_newsletter', 10, 3 );
