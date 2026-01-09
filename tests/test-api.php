<?php
/**
 * Newsletter API Test Script
 * 
 * Dieses Script testet die grundlegenden Funktionen der neuen Newsletter REST API
 */

// WordPress laden
define('WP_USE_THEMES', false);
require_once('../../../wp-config.php');

echo "<h1>Newsletter REST API Test</h1>\n";

// 1. Plugin-Status überprüfen
echo "<h2>1. Plugin-Status</h2>\n";
if (class_exists('NewsletterRestApi')) {
    echo "✅ NewsletterRestApi-Klasse ist geladen<br>\n";
    
    $api = NewsletterRestApi::instance();
    if ($api) {
        echo "✅ NewsletterRestApi-Instanz verfügbar<br>\n";
    } else {
        echo "❌ NewsletterRestApi-Instanz nicht verfügbar<br>\n";
    }
} else {
    echo "❌ NewsletterRestApi-Klasse nicht gefunden<br>\n";
    exit;
}

// 2. Controller-Klassen überprüfen
echo "<h2>2. Controller-Klassen</h2>\n";
$controllers = [
    'Newsletter_REST_Controller' => 'Basis-Controller',
    'Newsletter_REST_Subscribers_Controller' => 'Subscribers Controller',
    'Newsletter_REST_Lists_Controller' => 'Lists Controller', 
    'Newsletter_REST_Newsletters_Controller' => 'Newsletters Controller'
];

foreach ($controllers as $class => $name) {
    if (class_exists($class)) {
        echo "✅ $name ($class) geladen<br>\n";
    } else {
        echo "❌ $name ($class) nicht gefunden<br>\n";
    }
}

// 3. REST-Routes überprüfen
echo "<h2>3. REST API Endpunkte</h2>\n";
$rest_server = rest_get_server();
$routes = $rest_server->get_routes();

$newsletter_routes = [];
foreach ($routes as $route => $handlers) {
    if (strpos($route, '/newsletter/v2/') === 0) {
        $newsletter_routes[] = $route;
    }
}

if (!empty($newsletter_routes)) {
    echo "✅ Newsletter v2 API-Endpunkte gefunden:<br>\n";
    foreach ($newsletter_routes as $route) {
        echo "&nbsp;&nbsp;- $route<br>\n";
    }
} else {
    echo "❌ Keine Newsletter v2 API-Endpunkte gefunden<br>\n";
}

// 4. API-Keys Tabelle überprüfen
echo "<h2>4. Datenbank-Tabelle</h2>\n";
global $wpdb;
$table_name = $wpdb->prefix . 'newsletter_api_keys';

$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
if ($table_exists) {
    echo "✅ API-Keys Tabelle existiert ($table_name)<br>\n";
    
    $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    echo "&nbsp;&nbsp;- Anzahl API-Keys: $count<br>\n";
} else {
    echo "❌ API-Keys Tabelle nicht gefunden ($table_name)<br>\n";
}

echo "<h2>Test abgeschlossen</h2>\n";
echo "<p>Wenn alle Tests ✅ zeigen, ist die API erfolgreich integriert!</p>\n";
?>
