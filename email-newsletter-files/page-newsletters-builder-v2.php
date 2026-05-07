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

if ( isset( $_GET['updated'] ) ) {
	$raw_message = isset( $_GET['message'] ) ? wp_unslash( $_GET['message'] ) : '';
	$message = $raw_message ? urldecode( $raw_message ) : __( 'Newsletter gespeichert.', 'email-newsletter' );
	$message_type = 'true' === sanitize_text_field( wp_unslash( $_GET['updated'] ) ) ? 'updated' : 'error';
}

$newsletter = $newsletter_id ? $this->get_newsletter_data( $newsletter_id ) : false;
?>
<div class="wrap enews-builder-v2-page" style="max-width:none;width:100%;margin:0;">
	<h1><?php _e( 'Newsletter Builder', 'email-newsletter' ); ?></h1>
	<?php if ( ! empty( $message ) ) : ?>
		<div class="notice <?php echo 'error' === $message_type ? 'notice-error' : 'notice-success'; ?> is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
	<?php endif; ?>

	<?php if ( ! $newsletter_id || ! $newsletter ) : ?>
		<div class="notice notice-warning"><p><?php _e( 'Wähle zuerst einen vorhandenen Newsletter aus der Liste aus.', 'email-newsletter' ); ?></p></div>
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
				<a class="button button-secondary" href="<?php echo esc_url( $back_url ); ?>"><?php _e( 'Zurück zur Liste', 'email-newsletter' ); ?></a>
				<div class="enews-builder-v2-toolbar-history">
					<button type="button" class="button" id="enews-builder-v2-undo"><?php _e( 'Rückgängig', 'email-newsletter' ); ?></button>
					<button type="button" class="button" id="enews-builder-v2-redo"><?php _e( 'Wiederholen', 'email-newsletter' ); ?></button>
				</div>
				<div class="enews-builder-v2-toolbar-view">
					<button type="button" class="button is-active" id="enews-builder-v2-view-desktop"><?php _e( 'Desktop', 'email-newsletter' ); ?></button>
					<button type="button" class="button" id="enews-builder-v2-view-mobile"><?php _e( 'Mobil', 'email-newsletter' ); ?></button>
				</div>
				<label for="enews-builder-v2-subject" class="screen-reader-text"><?php _e( 'E-Mail-Betreff', 'email-newsletter' ); ?></label>
				<input type="text" id="enews-builder-v2-subject" value="<?php echo esc_attr( $newsletter['subject'] ); ?>" placeholder="<?php esc_attr_e( 'E-Mail-Betreff', 'email-newsletter' ); ?>" style="min-width:280px;" />
				<button type="submit" class="button button-primary"><?php _e( 'Newsletter speichern', 'email-newsletter' ); ?></button>
				<div class="enews-builder-v2-toolbar-test">
					<input type="email" id="enews-builder-v2-preview-email" value="<?php echo esc_attr( isset( $this->settings['preview_email'] ) && ! empty( $this->settings['preview_email'] ) ? $this->settings['preview_email'] : $this->settings['from_email'] ); ?>" placeholder="<?php esc_attr_e( 'Test-E-Mail-Adresse', 'email-newsletter' ); ?>" />
					<button type="button" class="button" id="enews-builder-v2-send-test"><?php _e( 'Test-Mail (Live)', 'email-newsletter' ); ?></button>
					<span class="description" id="enews-builder-v2-send-test-status"></span>
				</div>
			</div>

			<div id="enews-builder-v2-app" class="enews-builder-v2-app">
				<div class="enews-builder-v2-sidebar enews-builder-v2-sidebar-left">
					<div class="enews-builder-v2-panel enews-builder-v2-panel-intro">
						<p class="enews-builder-v2-eyebrow"><?php _e( 'Composer', 'email-newsletter' ); ?></p>
						<h2><?php _e( 'Bausteine & Templates', 'email-newsletter' ); ?></h2>
						<p class="description"><?php _e( 'Links startest Du mit Vorlagen und fügst strukturierte Module ein. Rechts bearbeitest Du den aktuell gewählten Block.', 'email-newsletter' ); ?></p>
					</div>

					<div class="enews-builder-v2-panel">
						<div class="enews-builder-v2-panel-head">
							<div>
								<p class="enews-builder-v2-eyebrow"><?php _e( 'Templates', 'email-newsletter' ); ?></p>
								<h3><?php _e( 'Presets', 'email-newsletter' ); ?></h3>
							</div>
						</div>
						<p class="description"><?php _e( 'Starte mit einer typischen Newsletter-Struktur statt auf leerem Canvas.', 'email-newsletter' ); ?></p>
						<div class="enews-builder-v2-preset-actions">
							<button type="button" class="button button-secondary" id="enews-builder-v2-save-preset"><?php _e( 'Als Preset speichern', 'email-newsletter' ); ?></button>
							<button type="button" class="button" id="enews-builder-v2-delete-preset"><?php _e( 'Preset löschen', 'email-newsletter' ); ?></button>
						</div>
						<div id="enews-builder-v2-presets" class="enews-builder-v2-presets"></div>
					</div>

					<div class="enews-builder-v2-panel">
						<div class="enews-builder-v2-panel-head">
							<div>
								<p class="enews-builder-v2-eyebrow"><?php _e( 'Insert', 'email-newsletter' ); ?></p>
								<h3><?php _e( 'Module', 'email-newsletter' ); ?></h3>
							</div>
						</div>
						<p class="description"><?php _e( 'Per Klick hinzufügen oder direkt in den Canvas ziehen.', 'email-newsletter' ); ?></p>
						<label for="enews-builder-v2-module-search" class="screen-reader-text"><?php _e( 'Module durchsuchen', 'email-newsletter' ); ?></label>
						<input type="search" id="enews-builder-v2-module-search" class="enews-builder-v2-module-search" placeholder="<?php esc_attr_e( 'Module filtern ...', 'email-newsletter' ); ?>" />
						<div id="enews-builder-v2-palette" class="enews-builder-v2-palette"></div>
					</div>
				</div>

				<div class="enews-builder-v2-canvas-wrap">
					<div class="enews-builder-v2-canvas-header">
						<p class="enews-builder-v2-eyebrow"><?php _e( 'Stage', 'email-newsletter' ); ?></p>
						<h2><?php _e( 'Canvas', 'email-newsletter' ); ?></h2>
						<p class="description"><?php _e( 'Sections und Rows strukturieren die Mail. Jeder Block lässt sich direkt selektieren, verschieben und rechts bearbeiten.', 'email-newsletter' ); ?></p>
					</div>
					<div id="enews-builder-v2-canvas" class="enews-builder-v2-canvas"></div>
					<div class="enews-builder-v2-preview-wrap">
						<p class="enews-builder-v2-eyebrow"><?php _e( 'Render', 'email-newsletter' ); ?></p>
						<h2><?php _e( 'Vorschau', 'email-newsletter' ); ?></h2>
						<p class="description"><?php _e( 'Die Vorschau wird serverseitig aus genau demselben Renderer erzeugt wie der gespeicherte Builder-Inhalt.', 'email-newsletter' ); ?></p>
						<div id="enews-builder-v2-preview" class="enews-builder-v2-preview enews-builder-v2-preview-desktop"></div>
					</div>
				</div>

				<div class="enews-builder-v2-sidebar enews-builder-v2-sidebar-right">
					<div class="enews-builder-v2-panel enews-builder-v2-panel-sticky">
						<div class="enews-builder-v2-panel-head">
							<div>
								<p class="enews-builder-v2-eyebrow"><?php _e( 'Inspector', 'email-newsletter' ); ?></p>
								<h3><?php _e( 'Einstellungen', 'email-newsletter' ); ?></h3>
							</div>
						</div>
						<p id="enews-builder-v2-selection-meta" class="description enews-builder-v2-selection-meta"><?php _e( 'Wähle einen Block im Canvas aus, um ihn zu bearbeiten.', 'email-newsletter' ); ?></p>
						<div id="enews-builder-v2-settings-panel" class="enews-builder-v2-settings-panel"></div>
					</div>
				</div>

			</div>
		</form>
	<?php endif; ?>
</div>
