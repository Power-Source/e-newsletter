<?php
$newsletter_id = isset( $_REQUEST['newsletter_id'] ) ? intval( $_REQUEST['newsletter_id'] ) : 0;
$message = '';
$message_type = 'updated';
$default_back_url = admin_url( 'admin.php?page=newsletters' );
$return_url = isset( $_REQUEST['return'] ) ? wp_unslash( $_REQUEST['return'] ) : '';
$back_url = $return_url ? wp_validate_redirect( $return_url, $default_back_url ) : $default_back_url;

if ( ! current_user_can( 'save_newsletter' ) ) {
	wp_die( __( 'Dazu hast Du keine Berechtigung.', 'email-newsletter' ) );
}

if ( $newsletter_id && isset( $_POST['enews_builder_v2_action'] ) && 'save' === $_POST['enews_builder_v2_action'] ) {
	check_admin_referer( 'enews_builder_v2_save_' . $newsletter_id );
	$raw_state = isset( $_POST['builder_state_json'] ) ? wp_unslash( $_POST['builder_state_json'] ) : '';
	$decoded = json_decode( $raw_state, true );

	if ( is_array( $decoded ) ) {
		$result = $this->builder_v2->save_state( $newsletter_id, $decoded );
		$message = __( 'Builder gespeichert. Der Newsletter-Inhalt wurde aktualisiert.', 'email-newsletter' );
	} else {
		$message = __( 'Der Builder-Status konnte nicht gelesen werden.', 'email-newsletter' );
		$message_type = 'error';
	}
}

$newsletter = $newsletter_id ? $this->get_newsletter_data( $newsletter_id ) : false;
?>
<div class="wrap enews-builder-v2-page">
	<h1><?php _e( 'Newsletter Builder', 'email-newsletter' ); ?></h1>
	<?php if ( ! empty( $message ) ) : ?>
		<div class="notice <?php echo 'error' === $message_type ? 'notice-error' : 'notice-success'; ?> is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! $newsletter_id || ! $newsletter ) : ?>
		<div class="notice notice-warning"><p><?php _e( 'Waehle zuerst einen vorhandenen Newsletter aus der Liste aus.', 'email-newsletter' ); ?></p></div>
		<p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=newsletters' ) ); ?>"><?php _e( 'Zur Newsletter-Liste', 'email-newsletter' ); ?></a></p>
	<?php else : ?>
		<p class="description">
			<?php printf( __( 'Du bearbeitest Newsletter #%1$d mit dem Betreff "%2$s". Dieser Builder speichert JSON im Meta und schreibt den gerenderten Inhalt in das bestehende Content-Feld.', 'email-newsletter' ), intval( $newsletter['newsletter_id'] ), esc_html( $newsletter['subject'] ) ); ?>
		</p>

		<form method="post" action="<?php echo esc_url( add_query_arg( array( 'page' => 'newsletters-builder-v2', 'newsletter_id' => intval( $newsletter_id ), 'return' => $back_url ), admin_url( 'admin.php' ) ) ); ?>" id="enews-builder-v2-form" novalidate>
			<?php wp_nonce_field( 'enews_builder_v2_save_' . $newsletter_id ); ?>
			<input type="hidden" name="enews_builder_v2_action" value="save" />
			<input type="hidden" name="builder_state_json" id="builder_state_json" value="" />

			<div class="enews-builder-v2-toolbar">
				<a class="button button-secondary" href="<?php echo esc_url( $back_url ); ?>"><?php _e( 'Zurueck zur Liste', 'email-newsletter' ); ?></a>
				<button type="submit" class="button button-primary"><?php _e( 'Builder speichern', 'email-newsletter' ); ?></button>
				<div class="enews-builder-v2-toolbar-test">
					<input type="email" id="enews-builder-v2-preview-email" value="<?php echo esc_attr( isset( $this->settings['preview_email'] ) && ! empty( $this->settings['preview_email'] ) ? $this->settings['preview_email'] : $this->settings['from_email'] ); ?>" placeholder="<?php esc_attr_e( 'Test-E-Mail-Adresse', 'email-newsletter' ); ?>" />
					<button type="button" class="button" id="enews-builder-v2-send-test"><?php _e( 'Test-Mail (Live)', 'email-newsletter' ); ?></button>
					<span class="description" id="enews-builder-v2-send-test-status"></span>
				</div>
			</div>

			<div id="enews-builder-v2-app" class="enews-builder-v2-app">
				<div class="enews-builder-v2-sidebar">
					<div class="enews-builder-v2-accordion-section is-open" data-accordion-item>
						<button type="button" class="enews-builder-v2-accordion-toggle" data-accordion-toggle aria-expanded="true" aria-controls="enews-builder-v2-presets-wrap"><?php _e( 'Template-Presets', 'email-newsletter' ); ?></button>
						<div id="enews-builder-v2-presets-wrap" class="enews-builder-v2-accordion-panel" data-accordion-panel>
							<p class="description"><?php _e( 'Starte mit einer typischen Newsletter-Struktur.', 'email-newsletter' ); ?></p>
							<div id="enews-builder-v2-presets" class="enews-builder-v2-presets"></div>
						</div>
					</div>

					<div class="enews-builder-v2-accordion-section" data-accordion-item>
						<button type="button" class="enews-builder-v2-accordion-toggle" data-accordion-toggle aria-expanded="false" aria-controls="enews-builder-v2-branding-wrap"><?php _e( 'Mail-Rahmen & Branding', 'email-newsletter' ); ?></button>
						<div id="enews-builder-v2-branding-wrap" class="enews-builder-v2-accordion-panel" data-accordion-panel hidden>
							<div id="enews-builder-v2-settings-panel"></div>
						</div>
					</div>

					<div class="enews-builder-v2-accordion-section is-open" data-accordion-item>
						<button type="button" class="enews-builder-v2-accordion-toggle" data-accordion-toggle aria-expanded="true" aria-controls="enews-builder-v2-modules-wrap"><?php _e( 'Module', 'email-newsletter' ); ?></button>
						<div id="enews-builder-v2-modules-wrap" class="enews-builder-v2-accordion-panel" data-accordion-panel>
							<p class="description"><?php _e( 'Per Klick hinzufuegen oder per Drag&Drop in den Canvas ziehen.', 'email-newsletter' ); ?></p>
							<div id="enews-builder-v2-palette" class="enews-builder-v2-palette"></div>
						</div>
					</div>
				</div>

				<div class="enews-builder-v2-canvas-wrap">
					<div class="enews-builder-v2-canvas-header">
						<h2><?php _e( 'Canvas', 'email-newsletter' ); ?></h2>
						<p class="description"><?php _e( 'Module werden direkt im Canvas bearbeitet. Die Vorschau unten orientiert sich am gespeicherten Newsletter-HTML.', 'email-newsletter' ); ?></p>
					</div>
					<div id="enews-builder-v2-canvas" class="enews-builder-v2-canvas"></div>
					<div class="enews-builder-v2-preview-wrap">
						<h2><?php _e( 'Vorschau', 'email-newsletter' ); ?></h2>
						<p class="description"><?php _e( 'Die Vorschau wird serverseitig aus genau demselben Renderer erzeugt wie der gespeicherte Builder-Inhalt.', 'email-newsletter' ); ?></p>
						<div id="enews-builder-v2-preview" class="enews-builder-v2-preview"></div>
					</div>
				</div>

			</div>
		</form>
	<?php endif; ?>
</div>
