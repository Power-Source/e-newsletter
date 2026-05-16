=== PS Newsletter ===
Contributors: PSOURCE
Tags: newsletter
Requires at least: 4.9
Tested up to: WordPress 6.4 
ClassicPress: 2.7.0
Stable tag: 1.1.0
Requires PHP: 8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Datenschutzkonformer Newsletter für ClassicPress zum Selbsthosten - Keine Drittanbieterdienste, keine Abokosten, keine Datenschutz-Sorgen!

== Description ==

Der wohl einfachste und sicherste Newsletter für ClassicPress

Mit dem PS Newsletter Plugin übernimmst Du die volle Kontrolle über Deine Newsletter und Abonnenten-Daten. 

Versende Deine Newsletter per direkt aus Deinem ClassicPress heraus, sollte Dein Hosting zu schwach sein, 
steht Dir eine Integration für einen SMPTP Versand zur Verfügung, zB. für GMAIL/OUTLOOK.

Biete mit Newsletter-Gruppen die Möglichkeit Deine Benutzer nur die Themen zu abonnieren welche für sie wirklich relevant sind.

Bestimme wann und an wem ein Newsletter versendet wird.

Erhalte eine Übersicht über versendete, geöffnete und abgelehnte Newsletter.

Integration mit PS Mitgliedschaften und PS Events, versende Newsletter nur an bestimmte Mitgliedschaften oder informiere Benutzer über
anstehende Events, damit Sie eine Teilnahme buchen können und dannach laufend über den Status des Events informiert bleiben.

Wir lieben OpenSource, wenn Du Vorschläge oder Idee hast, so teile uns diese doch mit, wir bemühen uns stehts unsere Plugins und Themes 
zu verbessern.

== ChangeLog ==

= 1.1.0 =
* Multisite-Fix: PS-eNewsletter-Menue erscheint im Netzwerk-Admin nur noch bei echter Netzwerkaktivierung

= 1.0.9 =
* Versand-Fix: Mail-Transport-Kompatibilitaet fuer den klassischen mail()-Pfad wiederhergestellt
* SMTP-Fix: Verbindungsdaten (Host/Port/Sicherheit) werden normalisiert und mit sinnvoller Timeout-Logik verarbeitet
* SMTP-Test: Test-Endpoint auf dieselbe Normalisierung wie der echte Versandpfad gebracht
* Security: Sensible AJAX-Endpunkte nicht mehr oeffentlich (nopriv) registriert
* Security: change_groups AJAX mit Login-, Capability- und Nonce-Pruefung abgesichert
* Security: redirect_to Eingaben serverseitig via wp_validate_redirect gehaertet
* Security: XSS-Fix in Subscribe-/Unsubscribe-Message-Shortcodes (sanitize + escape)
* Privacy: WP-Exporter um Status sowie Sent/Opened/Bounced Werte erweitert
* Privacy: WP-Eraser entfernt zusaetzlich Tracking-Reste aus send_members und campaign_clicks

= 1.0.8 =
* Builder V2 UI: Admin-Layout auf echte Vollbreite umgestellt (kein schmales Wrap-Layout mehr)
* Builder V2 UI: Zusätzlicher Fullwidth-Fallback direkt am Seitencontainer ergänzt, damit Theme-/Admin-CSS die Breite nicht wieder einschränkt
* Builder V2 UX: Linke Modul-Palette, mittlere Stage und rechter Inspector visuell klarer strukturiert
* Builder V2 Sprache: Deutsche Texte auf saubere Umlaute korrigiert (z. B. "Rückgängig", "löschen", "Wähle", "Beiträge", "Schriftgröße")
* Builder V2 Module: Feldlabels und Standardtexte in den neuen Modulen sprachlich vereinheitlicht
* Builder V2 Renderer: Shortcodes aus HTML-/Beitrags-/Produkt-Modulen werden im Send-Modus jetzt korrekt aufgelöst (nicht mehr als Roh-Shortcode versendet)
* Builder V2: Versionshistorie pro Newsletter ergänzt (inkl. Wiederherstellung)
* Builder V2: Versionsvorschau mit aktuellem-vs-Version Vergleich vor der Wiederherstellung ergänzt
* Kampagnen-Metriken: KPI-Karten für Open Rate, Click Rate und Reaktivität ergänzt
* Kampagnen-Metriken: KPI-Verlauf als Charts (Open/Click Rate) ergänzt
* Kampagnen-Metriken: Klick-Drilldown "Wer hat geklickt" inkl. Direktaktion "zur Gruppe hinzufügen" ergänzt
* Click-Tracking: Signierte Tracking-Links für manipulationssicherere Redirects ergänzt

= 1.0.7 =
* Builder V2: Initialisierung stabilisiert, damit Module in der Palette wieder erscheinen und das Canvas nicht leer bleibt
* Builder V2: "Block hinzufügen" pro Spalte zuverlässig verdrahtet
* Live-Vorschau: Lade-/Fehlerzustände verbessert und Vorschau-Refresh robuster gemacht
* Builder-State: Fallback auf Legacy-Inhalt ergänzt, wenn kein valider gespeicherter State vorhanden ist

= 1.0.6 =
* Builder-Canvas: Grid-Zeilenhoehe stabilisiert, damit nebeneinander/untereinander Layouts nicht mehr ungewollt weggedrueckt werden
* Builder-Canvas: Inline-Editor beeinflusst die persistierten Grid-Zeilen nicht mehr
* Mail-Renderer: Grid-Rowspans werden korrekt verarbeitet, damit mehrspaltige Newsletter in empfangenen E-Mails nicht zerstoert ankommen

= 1.0.5 =
* Canvas sortiert korrekt bei Preset-Auswahl
* Builder: Preset-ID-Kollisionen behoben und Grid-Konflikte (Überlappungen) visuell markiert
* Builder: Query-Modus für Beiträge/Produkte (manuell oder neueste Inhalte inkl. Limit)
* Kampagnen & Automationen: neues gemeinsames Dashboard mit klarer Trennung der Typen
* Kampagnen-Statusaktionen ergänzt (Pausieren, Fortsetzen, Stoppen, Löschen)
* Automationen: Trigger bei neuem Beitrag sowie geplanter Digest-Rhythmus erweitert
* Versandlogik: Dedupe pro Kampagne/Run für robuste Vermeidung von Doppelsendungen
* Metriken: neue Kampagnen-Statistikseite mit Run-Historie und zusammengefassten Kennzahlen
* Click-Tracking für Kampagnen-Runs ergänzt (Redirect-Endpoint, Klickzählung, Top-Links)
* Admin-UI modernisiert (globales UI-Layer ohne jQuery UI und ohne CDN)
* Newsletter-Liste: Hero-Übersicht mit Gesamtwerten und meistgelesenem Newsletter ergänzt
* Veralteten Hinweis zu enewsletter-custom-themes aus der Newsletter-Liste entfernt

= 1.0.4 =
* Colorpicker ersetzt
* Customizer-Builder durch Drag and Drop Editor ersetzt

= 1.0.3 =

* Etliche Bugfixes
* Verbesserte PhP 8.4.10 Kompatibilität
* Textoptimierungen

= 1.0.2 =

* Templatebuilder eingebaut

= 1.0.1 =

Eliche Bugfixes
Etliche Security Fixes

= 1.0.0 = 

* Release

