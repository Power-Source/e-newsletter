# PS-eNewsletter

**Deutsch** | [English](README.en.md)

[![Version](https://img.shields.io/badge/Version-1.1.3-2271b1?style=flat-square)](readme.txt)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4?style=flat-square&logo=php&logoColor=white)
![WordPress](https://img.shields.io/badge/WordPress-bis%207.1.0-21759b?style=flat-square&logo=wordpress&logoColor=white)
![ClassicPress](https://img.shields.io/badge/ClassicPress-2.7.3-03768e?style=flat-square)
[![Lizenz](https://img.shields.io/badge/Lizenz-GPL--2.0--or--later-2ea44f?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)

Ein selbst gehostetes Newsletter-Plugin fuer ClassicPress. PS-eNewsletter verwaltet Abonnenten, Inhalte, Versand und Auswertung direkt in der eigenen Installation - ohne externe Newsletter-Plattform und ohne laufende Servicegebuehren.


## Was das Plugin kann

- Newsletter mit dem visuellen Builder V2 erstellen, als Vorschau pruefen und versionieren
- Abonnenten verwalten, CSV-Dateien importieren und in Gruppen segmentieren
- Newsletter sofort senden, zeitgesteuert ueber WP-Cron versenden oder Versandlimits festlegen
- SMTP oder den lokalen Mail-Transport verwenden; POP3/IMAP-Bounces verarbeiten
- Oeffnungen, Bounces und Klicks erfassen; Kampagnen mit Kennzahlen und Klick-Drilldowns auswerten
- Wiederkehrende Kampagnen sowie Automationen fuer neue Beitraege, Produkte und Digests ausfuehren
- Anmeldeformulare, Double Opt-in sowie Abmeldung bereitstellen
- Datenschutz-Werkzeuge von ClassicPress fuer Export und Loeschung personenbezogener Daten unterstuetzen
- Optional Zielgruppen aus PS Mitgliedschaften und MarketPress-Produkten einbinden

## Installation

1. Den Ordner `e-newsletter` nach `wp-content/plugins/` kopieren.
2. Das Plugin im ClassicPress-Backend aktivieren.
3. **PS-eNewsletter > Einstellungen** oeffnen und Absenderadresse, Versandmethode und Versandlimit konfigurieren.
4. Eine Gruppe anlegen, Abonnenten hinzufuegen oder importieren.
5. Einen Newsletter erstellen, im Builder bearbeiten und eine Test-Mail versenden.

Bei Netzwerkinstallationen kann das Plugin netzwerkweit aktiviert werden. Das Menue erscheint im Netzwerk-Admin nur bei echter Netzwerkaktivierung.

## Typischer Ablauf

### Newsletter erstellen und senden

1. Unter **Newsletters** einen neuen Newsletter anlegen.
2. Im **Newsletter Builder** Module, Presets, Branding und die responsive Vorschau verwenden.
3. Die Test-Mail an eine konfigurierte Vorschau-Adresse senden.
4. Empfaengergruppen oder Rollen auswaehlen und den Versand direkt, per Zeitplanung oder per WP-Cron starten.

Der Builder speichert den strukturierten Inhalt als Newsletter-Metadaten und schreibt den gerenderten HTML-Inhalt in den bestehenden Newsletter-Datensatz. Die Versionsansicht erlaubt das Pruefen und Wiederherstellen frueherer Stände.

### Kampagnen und Automationen

Unter **Kampagnen & Automationen** lassen sich zwei Typen anlegen:

- **Kampagne:** wiederkehrender Versand in Stunden-, Tages- oder Wochenabstaenden.
- **Automation:** Versand beim Veroeffentlichen eines Beitrags oder Produkts sowie geplante woechentliche oder monatliche Digests.

Jeder Lauf wird mit Versand-, Oeffnungs-, Klick- und Bounce-Werten erfasst. Die Statistikseite zeigt Kennzahlen, Verlaeufe, Top-Links und die Empfaenger hinter einzelnen Klicks.

## Abonnements im Frontend

Das Plugin registriert folgende Shortcodes:

| Shortcode | Zweck |
| --- | --- |
| `[enewsletter_subscribe]` | Anmelde- und Verwaltungsformular fuer Abonnements. |
| `[enewsletter_unsubscribe_message]` | Meldung nach einer Abmeldung. |
| `[enewsletter_subscribe_message]` | Meldung nach einer Anmeldung. |
| `[enews_product]`, `[enews_products]` | Produktinhalte fuer Newsletter, sofern MarketPress verfuegbar ist. |
| `[enews_post]`, `[enews_posts]`, `[enews_post_links]` | Beitragsinhalte und Beitragslinks fuer Newsletter. |

`[enewsletter_subscribe]` unterstuetzt unter anderem `show_name`, `show_groups` und `subscribe_to_groups`.

## Versand und Betrieb

### Versandarten

Der Versand kann ueber den lokalen PHP-Mail-Transport oder SMTP erfolgen. Fuer SMTP stehen ein Verbindungstest sowie Einstellungen fuer Host, Port, Sicherheit und Zugangsdaten zur Verfuegung.

WP-Cron verarbeitet geplante und wartende Sendungen. Auf wenig frequentierten Websites sollte ein echter Server-Cron den ClassicPress-Cron regelmaessig ausloesen, damit Kampagnen und Versandauftraege zeitnah laufen.

### Bounces

Fuer Bounce-Verarbeitung wird die PHP-Erweiterung IMAP benoetigt. Richte dafuer ein separates Postfach ein und hinterlege dessen Zugangsdaten unter **Einstellungen > Bounce-Einstellungen**.

### Debug-Logging

Debug-Logging ist standardmaessig deaktiviert und wird unter **Einstellungen** aktiviert. Ereignisse lassen sich unter **Logs** filtern, herunterladen und leeren.

Die Logdatei wird bei aktivem Debugging unter folgendem Pfad abgelegt:

```
wp-content/uploads/e-newsletter/debug.log
```

Alternativ kann Debugging gezielt ueber die Konstante `ENEWSLETTER_DEBUG` oder den Filter `email_newsletter_debug_enabled` aktiviert werden.

## Datenschutz und Sicherheit

- Abonnenten- und Versanddaten verbleiben in der eigenen ClassicPress-Datenbank.
- Double Opt-in, Abmeldelinks und Ein-Klick-Abmeldung werden unterstuetzt.
- Versand- und Administrationsaktionen verwenden Nonces und Capability-Pruefungen.
- Klick-Tracking verwendet signierte Ziel-Links.
- Das Plugin registriert Exporter und Eraser fuer die ClassicPress-Datenschutzwerkzeuge.

Die konkrete Datenschutzkonfiguration - insbesondere die Rechtsgrundlage, Aufbewahrungsfristen und Hinweise in der Datenschutzerklaerung - liegt weiterhin in der Verantwortung des Website-Betreibers.

## Entwicklung

Der Code verwendet die Textdomain `email-newsletter`. Englische Gettext-Dateien liegen in `languages/`:

- `email-newsletter.pot` - aktuelle Vorlage
- `email-newsletter-en_US.po` - englischer Katalog
- `email-newsletter-en_US.mo` - kompilierter englischer Katalog

Nutzbare Erweiterungspunkte umfassen unter anderem:

```php
add_filter( 'email_newsletter_debug_enabled', function( $enabled ) {
    return $enabled;
} );

add_action( 'enewsletter_before_send', function( $newsletter_id ) {
    // Eigene Versandvorbereitung.
} );

add_action( 'enewsletter_newsletter_saved', function( $newsletter_id, $data, $meta ) {
    // Auf einen gespeicherten Newsletter reagieren.
}, 10, 3 );
```

Die Plugin-Informationen und der klassische WordPress.org-kompatible Changelog stehen in `readme.txt`.

## Lizenz

PS-eNewsletter steht unter der [GNU General Public License v2.0 oder neuer](https://www.gnu.org/licenses/gpl-2.0.html).