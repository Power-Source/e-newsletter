<?php
/**
 * Cron-Simulation für AutomatedChannels
 * Aufruf: wp-admin/admin.php?page=newsletter_main_index&test_cron=1
 */

defined('ABSPATH') || exit;

if (!current_user_can('manage_options')) {
    wp_die('Keine Berechtigung');
}

require_once NEWSLETTER_DIR . '/main/automated_channels.php';

echo '<div class="wrap"><h1>Cron-Simulation für Automated Newsletters</h1>';

// Test-Kanal vorbereiten
$test_id = 'cron_test_' . time();
$test_channel = AutomatedChannels::normalize([
    'id' => $test_id,
    'name' => 'Cron Test Channel',
    'enabled' => 1,
    'frequency' => 'weekly',
    'day_1' => 1, // Montag
    'hour' => 10,
    'hour2_enabled' => 1,
    'hour2' => 14,
    'subject' => 'Test {date}',
    'list' => 1,
], $test_id);

$all = AutomatedChannels::all();
$all[$test_id] = $test_channel;
AutomatedChannels::save($all);

echo '<h2>Test-Kanal angelegt</h2>';
echo '<pre>';
print_r($test_channel);
echo '</pre>';

// Simuliere verschiedene Zeitpunkte
$scenarios = [
    [
        'time' => '2026-01-12 10:00:00', // Montag 10:00 (Erstes Fenster)
        'description' => 'Montag 10:00 - Erstes Fenster (w1)',
    ],
    [
        'time' => '2026-01-12 14:00:00', // Montag 14:00 (Zweites Fenster)
        'description' => 'Montag 14:00 - Zweites Fenster (w2)',
    ],
    [
        'time' => '2026-01-12 18:00:00', // Montag 18:00 (Kein Fenster)
        'description' => 'Montag 18:00 - Kein aktives Fenster',
    ],
    [
        'time' => '2026-01-13 10:00:00', // Dienstag 10:00 (Tag nicht aktiv)
        'description' => 'Dienstag 10:00 - Tag nicht konfiguriert',
    ],
];

foreach ($scenarios as $scenario) {
    echo '<h2>' . esc_html($scenario['description']) . '</h2>';
    
    $test_time = strtotime($scenario['time']);
    $day = (int)date('N', $test_time);
    $hour = (int)date('G', $test_time);
    
    echo '<p>Zeit: ' . date('Y-m-d H:i:s D', $test_time) . ' (Tag=' . $day . ', Stunde=' . $hour . ')</p>';
    
    // Kanal neu laden
    $channel = AutomatedChannels::get($test_id);
    
    // Tag-Check
    $day_key = 'day_' . $day;
    $day_enabled = !empty($channel[$day_key]);
    
    echo '<p>Tag aktiv (' . $day_key . '): ' . ($day_enabled ? '<strong style="color:green;">JA</strong>' : '<strong style="color:red;">NEIN</strong>') . '</p>';
    
    if (!$day_enabled) {
        echo '<p style="color: orange;">→ Kein Versand (Tag nicht konfiguriert)</p>';
        continue;
    }
    
    // Fenster prüfen
    $windows = [
        ['hour' => (int)$channel['hour'], 'suffix' => 'w1', 'last_sent_key' => 'last_sent_1'],
    ];
    if (!empty($channel['hour2_enabled'])) {
        $windows[] = ['hour' => (int)$channel['hour2'], 'suffix' => 'w2', 'last_sent_key' => 'last_sent_2'];
    }
    
    echo '<p>Konfigurierte Fenster:</p><ul>';
    foreach ($windows as $w) {
        echo '<li>Fenster ' . $w['suffix'] . ': Stunde ' . $w['hour'] . ' (last_sent: ' . date('Y-m-d H:i:s', $channel[$w['last_sent_key']] ?? 0) . ')</li>';
    }
    echo '</ul>';
    
    $today_start = strtotime('today', $test_time);
    $sent_this_run = false;
    
    foreach ($windows as $window) {
        if ($window['hour'] != $hour) {
            continue;
        }
        
        echo '<p style="background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107;">';
        echo '✓ Stunde passt zu Fenster <strong>' . $window['suffix'] . '</strong><br>';
        
        $last_sent = $channel[$window['last_sent_key']] ?? 0;
        echo 'last_sent: ' . date('Y-m-d H:i:s', $last_sent) . '<br>';
        echo 'today_start: ' . date('Y-m-d H:i:s', $today_start) . '<br>';
        
        if ($last_sent >= $today_start) {
            echo '<strong style="color: orange;">→ Bereits heute versendet (Duplikat verhindert)</strong>';
        } else {
            echo '<strong style="color: green;">→ WÜRDE JETZT VERSENDET</strong><br>';
            echo 'Email-Type: <code>automated_' . $channel['id'] . '_' . $window['suffix'] . '</code><br>';
            
            // Simuliere Versand
            $channel[$window['last_sent_key']] = $test_time;
            $channel['sent'] = ($channel['sent'] ?? 0) + 1;
            $channel['email'] = 'email_id_' . time() . '_' . $window['suffix'];
            
            $all[$test_id] = $channel;
            AutomatedChannels::save($all);
            
            echo 'Updated last_sent_' . substr($window['suffix'], 1) . ' = ' . date('Y-m-d H:i:s', $test_time);
            
            $sent_this_run = true;
        }
        echo '</p>';
    }
    
    if (!$sent_this_run) {
        $matched_window = false;
        foreach ($windows as $w) {
            if ($w['hour'] == $hour) {
                $matched_window = true;
                break;
            }
        }
        if (!$matched_window) {
            echo '<p style="color: gray;">→ Kein Versand (Stunde passt zu keinem Fenster)</p>';
        }
    }
    
    echo '<hr>';
}

// Finaler Status
echo '<h2>Finaler Kanal-Status</h2>';
$final = AutomatedChannels::get($test_id);
echo '<pre>';
print_r($final);
echo '</pre>';

echo '<h3>Zusammenfassung</h3>';
echo '<table class="widefat" style="max-width: 800px;">';
echo '<tr><th>Feld</th><th>Wert</th></tr>';
echo '<tr><td>Gesamt versendet</td><td><strong>' . ($final['sent'] ?? 0) . '</strong></td></tr>';
echo '<tr><td>last_sent_1 (Fenster 1)</td><td>' . date('Y-m-d H:i:s', $final['last_sent_1'] ?? 0) . '</td></tr>';
echo '<tr><td>last_sent_2 (Fenster 2)</td><td>' . date('Y-m-d H:i:s', $final['last_sent_2'] ?? 0) . '</td></tr>';
echo '<tr><td>Letztes Email-ID</td><td>' . ($final['email'] ?? 'keine') . '</td></tr>';
echo '</table>';

// Cleanup
echo '<h2>Cleanup</h2>';
$all = AutomatedChannels::all();
if (isset($all[$test_id])) {
    unset($all[$test_id]);
    AutomatedChannels::save($all);
    echo '<p style="color: green;">✓ Test-Kanal gelöscht</p>';
}

echo '<p><a href="?page=newsletter_main_automatedindex" class="button">Zurück zur Übersicht</a></p>';
echo '</div>';
