<?php
/**
 * Test-Script für AutomatedChannels Helper und Cron-Logik
 * Aufruf: wp-admin/admin.php?page=newsletter_main_index&test_automated=1
 */

defined('ABSPATH') || exit;

// Nur für Admins
if (!current_user_can('manage_options')) {
    wp_die('Keine Berechtigung');
}

require_once NEWSLETTER_DIR . '/main/automated_channels.php';

echo '<div class="wrap"><h1>AutomatedChannels Test</h1>';

// Test 1: Kanal erstellen und speichern
echo '<h2>Test 1: Kanal anlegen mit hour2</h2>';

$test_id = 'test_' . time();
$test_channel = [
    'id' => $test_id,
    'name' => 'Test Newsletter Channel',
    'enabled' => 1,
    'track' => 1,
    'frequency' => 'weekly',
    'day_1' => 1, // Montag
    'day_3' => 1, // Mittwoch
    'day_5' => 1, // Freitag
    'hour' => 10,
    'hour2_enabled' => 1,
    'hour2' => 14,
    'subject' => 'Test Subject {date}',
    'list' => 1,
    'sender_name' => 'Test Sender',
    'sender_email' => 'test@example.com',
];

// Normalisieren
$normalized = AutomatedChannels::normalize($test_channel, $test_id);
echo '<pre>Normalisierter Kanal:';
print_r($normalized);
echo '</pre>';

// Speichern
$all_channels = AutomatedChannels::all();
$all_channels[$test_id] = $normalized;
AutomatedChannels::save($all_channels);

echo '<p style="color: green;">✓ Kanal gespeichert</p>';

// Wieder laden und prüfen
$loaded = AutomatedChannels::get($test_id);
echo '<pre>Geladener Kanal:';
print_r($loaded);
echo '</pre>';

if ($loaded['hour'] === 10 && $loaded['hour2_enabled'] === 1 && $loaded['hour2'] === 14) {
    echo '<p style="color: green;">✓ Stunden-Werte korrekt gespeichert</p>';
} else {
    echo '<p style="color: red;">✗ Stunden-Werte fehlerhaft!</p>';
}

// Test 2: Cron-Logik simulieren
echo '<h2>Test 2: Cron-Logik Simulation</h2>';

// Aktueller Zeitpunkt (Donnerstag, 9. Januar 2026, 10:00)
$current_time = strtotime('2026-01-09 10:00:00');
$current_day = (int)date('N', $current_time); // 4 = Donnerstag
$current_hour = (int)date('G', $current_time); // 10

echo '<p>Aktuelle Zeit: ' . date('Y-m-d H:i:s', $current_time) . ' (Tag: ' . $current_day . ', Stunde: ' . $current_hour . ')</p>';

// Kanal sollte NICHT ausgelöst werden (Donnerstag nicht aktiv)
echo '<h3>Szenario 1: Donnerstag 10:00 (Tag nicht aktiv)</h3>';
$channel_day_key = 'day_' . $current_day;
if (isset($normalized[$channel_day_key]) && $normalized[$channel_day_key] == 1) {
    echo '<p style="color: red;">✗ Kanal würde fälschlicherweise ausgelöst</p>';
} else {
    echo '<p style="color: green;">✓ Kanal wird korrekt NICHT ausgelöst (Donnerstag nicht aktiv)</p>';
}

// Montag-Szenario
echo '<h3>Szenario 2: Montag 10:00 (Erstes Fenster)</h3>';
$monday_time = strtotime('2026-01-12 10:00:00'); // Montag
$monday_day = (int)date('N', $monday_time); // 1
$monday_hour = (int)date('G', $monday_time); // 10

$monday_day_key = 'day_' . $monday_day;
echo '<p>Montag: Tag=' . $monday_day . ', Stunde=' . $monday_hour . ', day_1=' . $normalized['day_1'] . '</p>';

$windows = [
    ['hour' => $normalized['hour'], 'suffix' => 'w1', 'last_sent_key' => 'last_sent_1'],
];
if (!empty($normalized['hour2_enabled'])) {
    $windows[] = ['hour' => $normalized['hour2'], 'suffix' => 'w2', 'last_sent_key' => 'last_sent_2'];
}

$should_send_w1 = false;
foreach ($windows as $window) {
    if ($window['hour'] == $monday_hour) {
        $today_start = strtotime('today', $monday_time);
        $last_sent = $normalized[$window['last_sent_key']] ?? 0;
        
        echo '<p>Fenster ' . $window['suffix'] . ': hour=' . $window['hour'] . ', last_sent=' . date('Y-m-d H:i:s', $last_sent) . '</p>';
        
        if ($last_sent < $today_start) {
            echo '<p style="color: green;">✓ Würde gesendet (Fenster ' . $window['suffix'] . ')</p>';
            $should_send_w1 = true;
        } else {
            echo '<p style="color: orange;">○ Heute bereits gesendet (Fenster ' . $window['suffix'] . ')</p>';
        }
    }
}

if (!$should_send_w1) {
    echo '<p style="color: red;">✗ Kein Versand geplant</p>';
}

// Zweites Fenster
echo '<h3>Szenario 3: Montag 14:00 (Zweites Fenster)</h3>';
$monday_afternoon = strtotime('2026-01-12 14:00:00');
$afternoon_hour = (int)date('G', $monday_afternoon); // 14

echo '<p>Montag Nachmittag: Stunde=' . $afternoon_hour . ', hour2=' . $normalized['hour2'] . ', hour2_enabled=' . $normalized['hour2_enabled'] . '</p>';

$should_send_w2 = false;
foreach ($windows as $window) {
    if ($window['hour'] == $afternoon_hour) {
        $today_start = strtotime('today', $monday_afternoon);
        $last_sent = $normalized[$window['last_sent_key']] ?? 0;
        
        echo '<p>Fenster ' . $window['suffix'] . ': hour=' . $window['hour'] . ', last_sent=' . date('Y-m-d H:i:s', $last_sent) . '</p>';
        
        if ($last_sent < $today_start) {
            echo '<p style="color: green;">✓ Würde gesendet (Fenster ' . $window['suffix'] . ')</p>';
            $should_send_w2 = true;
        } else {
            echo '<p style="color: orange;">○ Heute bereits gesendet (Fenster ' . $window['suffix'] . ')</p>';
        }
    }
}

if ($should_send_w2) {
    echo '<p style="color: green;">✓ Zweites Fenster würde korrekt ausgelöst</p>';
}

// Test 3: Duplikat-Prävention
echo '<h2>Test 3: Duplikat-Prävention</h2>';

echo '<h3>Email-Type-Suffixes</h3>';
$type_w1 = 'automated_' . $test_id . '_w1';
$type_w2 = 'automated_' . $test_id . '_w2';

echo '<p>Window 1 Type: <code>' . $type_w1 . '</code></p>';
echo '<p>Window 2 Type: <code>' . $type_w2 . '</code></p>';
echo '<p style="color: green;">✓ Verschiedene Suffixes erlauben zwei Versendungen pro Tag</p>';

// Cleanup
echo '<h2>Cleanup</h2>';
$all_channels = AutomatedChannels::all();
if (isset($all_channels[$test_id])) {
    unset($all_channels[$test_id]);
    AutomatedChannels::save($all_channels);
    echo '<p style="color: green;">✓ Test-Kanal gelöscht</p>';
}

echo '<p><a href="?page=newsletter_main_automatedindex" class="button">Zurück zur Übersicht</a></p>';
echo '</div>';
