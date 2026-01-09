<?php
// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_fopen
// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_fclose

defined('ABSPATH') || exit;

/* @var $this NewsletterImport */
/* @var $controls NewsletterControls */

if (!$controls->is_action()) {
    $controls->data = $this->options;
    $controls->data['import_as'] = '';
} else {
    if ($controls->is_action('delete')) {
        $this->stop();
        $controls->js_redirect("admin.php?page=newsletter_import_index");
    }

    if ($controls->is_action('import')) {

        if (empty($controls->data['import_as'])) {
            $controls->errors = 'Bitte wähle den Status der importierten Abonnenten aus.';
        } elseif (empty($controls->data['email'])) {
            $controls->errors = 'Bitte ordne mindestens das E-Mail-Feld auf der Registerkarte "Felder" zu.';
        } else {
            $this->save_options($controls->data);
            // Patch for a bug in NewsletterAddon
            $this->options = $controls->data;
            $this->start();
            $controls->js_redirect("admin.php?page=newsletter_import_csv");
        }
    }
}

$csv_fields = array('' => 'None');
$headers = [];

$handle = fopen($this->get_filename(), 'r');
if ($handle) {
    $lines = []; // Not necessary as array, but the code has been copied from elsewhere

    while (($line = fgets($handle)) !== false) {
        $line = trim($line);
        if (empty($line)) {
            continue;
        }
        $lines[] = $line;
        break;
    }
    fclose($handle);

    $headers = str_getcsv($lines[0], $controls->data['delimiter'], '"');
    for ($i = 0; $i < count($headers); $i++) {
        $csv_fields['' . $i + 1] = $headers[$i];
    }
} else {
    $controls->errors = __('Importdatei kann nicht gelesen werden. Verwende die Schaltfläche "Löschen" und starte neu.', 'newsletter-import');
}
?>
<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_ADMIN_HEADER; ?>

    <div id="tnp-heading">
        <?php //$controls->title_help('/addons/extended-features/advanced-import/') ?>
        <h2>Import</h2>
        <?php include __DIR__ . '/nav.php' ?>
    </div>

    <div id="tnp-body">

        <?php $controls->show() ?>

        <h3>Step 3/4 - Map the fields and set the import options</h3>
        <form method="post" action="" enctype="multipart/form-data">
            <?php $controls->init(); ?>
            <?php $controls->hidden('delimiter'); // From previous step ?>

            <div class="psource-tabs" id="tabs">
                <div class="psource-tabs-nav">
                    <button class="psource-tab active" data-tab="tabs-settings"><?php esc_html_e('Einstellungen', 'newsletter-import') ?></button>
                    <button class="psource-tab" data-tab="tabs-fields"><?php esc_html_e('Felder', 'newsletter-import') ?></button>
                    <button class="psource-tab" data-tab="tabs-lists"><?php esc_html_e('Listen', 'newsletter-import') ?></button>
                    <button class="psource-tab" data-tab="tabs-extra"><?php esc_html_e('Benutzerdefinierte Felder', 'newsletter-import') ?></button>
                </div>
                <div class="psource-tabs-content">
                    <div class="psource-tab-panel active" id="tabs-settings">
                        <table class="form-table">
                            <tr>
                                <th>When a subscriber is already present<br><small>Identified by it's email</small></th>
                                <td>
                                    <?php $controls->select('mode', array('update' => 'Aktualisieren', 'overwrite' => 'Überschreiben', 'skip' => 'Überspringen')); ?>
                                    <p class="description">
                                        <strong>Update</strong>: <?php esc_html_e('Die Abonnentendaten werden aktualisiert, bestehende Listen bleiben unverändert und neue werden hinzugefügt.', 'newsletter') ?><br />
                                        <strong>Overwrite</strong>: <?php esc_html_e('Die Abonnentendaten werden gelöscht und erneut gesetzt.', 'newsletter') ?><br />
                                        <strong>Skip</strong>: <?php esc_html_e('Der Abonnent wird nicht geändert.', 'newsletter') ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Abonnenten importieren als', 'newsletter') ?></th>
                                <td>
                                    <?php
                                    $controls->select('import_as', [
                                        'C' => __('Bestätigt', 'newsletter'),
                                        'S' => __('Nicht bestätigt', 'newsletter'),
                                        'U' => __('Abgemeldet', 'newsletter'),
                                        'B' => __('Zurückgewiesen', 'newsletter'),
                                        TNP_User::STATUS_COMPLAINED => __('Beschwert', 'newsletter'),
                                            ], 'Select...');
                                    ?>
                                    <br>
                                    <?php $controls->checkbox('override_status', __('Status vorhandener Benutzer überschreiben', 'newsletter')) ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="psource-tab-panel" id="tabs-fields">
                        <table class="widefat" style="width: auto">
                            <thead>
                                <tr>
                                    <th>Abonnentenfeld</th>
                                    <th>CSV-Spalte</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Email</td>
                                    <td><?php $controls->select('email', $csv_fields) ?></td>
                                </tr>
                                <tr>
                                    <td>First name</td>
                                    <td><?php $controls->select('first_name', $csv_fields) ?></td>
                                </tr>
                                <tr>
                                    <td>Last name</td>
                                    <td><?php $controls->select('last_name', $csv_fields) ?></td>
                                </tr>
                                <tr>
                                    <td>Language</td>
                                    <td>
                                        <?php $controls->select('language', $csv_fields) ?>
                                        <div class="description">
                                            Es sollte ein 2-stelliger Kleinbuchstabencode sein (<a href="https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes" target="_blank">ISO 639-1</a>)
                                            oder der 2-stellige Kleinbuchstabencode, der von Ihrem Mehrsprachen-Plugin verwendet wird.
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Geschlecht</td>
                                    <td>
                                        <?php $controls->select('gender', $csv_fields) ?>
                                        <div class="description">
                                            Es sollte "f" oder "m" oder "n" sein.
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>IP-Adresse</td>
                                    <td><?php $controls->select('ip', $csv_fields) ?></td>
                                </tr>
                                <tr>
                                    <td>Land</td>
                                    <td>
                                        <?php $controls->select('country', $csv_fields) ?>
                                        <p class="description">
                                            Es sollte der Ländercode <a href="https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2" target="_blank">ISO 3166-1 alpha 2 code</a> sein.
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Region</td>
                                    <td>
                                        <?php $controls->select('region', $csv_fields) ?>
                                        <p class="description">Kann ein Bundesland, Landkreis, Provinz und so weiter sein</p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Stadt</td>
                                    <td>
                                        <?php $controls->select('city', $csv_fields) ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="psource-tab-panel" id="tabs-lists">
                        <p>
                            Listen können nicht mit CSV-Feldern zugewiesen werden.
                        </p>
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e('Lists', 'newsletter') ?></th>
                                <td>
                                    <?php $controls->preferences_group('lists', true); ?>
                                    <div class="hints">
                                        Jeder erstellte oder aktualisierte Abonnent wird mit den ausgewählten Listen verknüpft.
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="psource-tab-panel" id="tabs-extra">
                        <p><a href="?page=newsletter_subscription_customfields">Benutzerdefinierte Felder verwalten</a>.</p>
                        <?php
                        $profiles = Newsletter::instance()->get_profiles();
                        ?>
                        <?php if (empty($profiles)) { ?>
                            <p style="font-weight: strong">
                                Es sind keine zusätzlichen Profilfelder definiert.
                            </p>
                        <?php } else { ?>
                            <table class="widefat" style="width: auto">
                                <thead>
                                    <tr>
                                        <th>Abonnentenfeld</th>
                                        <th>CSV-Spalte</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($profiles as $profile) { ?>
                                        <tr>
                                            <td><?php echo esc_html($profile->name) ?></td>
                                            <td><?php $controls->select('profile_' . $profile->id, $csv_fields) ?></td>
                                        <?php } ?>
                                </tbody>
                            </table>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <p>
                <?php $controls->button_back('?page=newsletter_import_csv'); ?>

                <?php $controls->button_delete('delete', 'Delete the file'); ?>
                <?php $controls->button_confirm('import', 'Import'); ?>
            </p>

        </form>
    </div>

</div>
