# Automated

**Automated** ist ein Add-on für das Newsletter-Plugin, das automatisch Newsletter generiert und versendet – basierend auf aktuellen Inhalten deines Blogs. Es ist nicht auf neue Beiträge beschränkt, sondern kann auch Custom Post Types, Produkte, Events usw. verwenden.

Die automatisierte Erstellung von Newslettern kann stündlich, täglich, wöchentlich oder monatlich in vielfältiger Weise geplant werden. Blog-Inhalte können nach Kategorie und Post-Typ gefiltert werden. Es können beliebig viele Kanäle erstellt werden, sodass Abonnenten aus deinem Informationsangebot wählen können.

Kompatibel mit WPML und Polylang, kann Inhalte in einer bestimmten Sprache extrahieren.

---

## Inhalt

- [Kanal einrichten](#kanal-einrichten)
- [Zielgruppen-Targeting](#zielgruppen-targeting)
- [Funktionsweise: Dynamische Blöcke](#funktionsweise-dynamische-blöcke)
- [Dynamische Blöcke und Betreff](#dynamische-blöcke-und-betreff)
- [Planung und Versandzeiten](#planung-und-versandzeiten)
- [Versandzeiten und Sommerzeit (DST)](#versandzeiten-und-sommerzeit-dst)
- [Vorschau für Composer-Newsletter](#vorschau-für-composer-newsletter)
- [Letztes Versanddatum](#letztes-versanddatum)
- [Erstellte Newsletter und Statistiken](#erstellte-newsletter-und-statistiken)
- [Kanal-Abonnement](#kanal-abonnement)
- [Kanal bewerben](#kanal-bewerben)
- [Kanal bei alten Abonnenten bewerben](#kanal-bei-alten-abonnenten-bewerben)
- [Newsletter-Design-Ideen](#newsletter-design-ideen)
- [Zwei Beitragsblöcke](#zwei-beitragsblöcke)
- [Verschiedene Blocktypen mischen](#verschiedene-blocktypen-mischen)
- [Alte Kanalformate (veraltet)](#alte-kanalformate-veraltet)
- [Beiträge extrahieren](#beiträge-extrahieren)
- [Newsletter ohne neue Beiträge generieren](#newsletter-ohne-neue-beiträge-generieren)
- [Themes und Vorschau für alte Kanäle](#themes-und-vorschau-für-alte-kanäle)
- [Erweiterte Themen](#erweiterte-themen)
- [Import von Feed-by-Mail-Abonnenten](#import-von-feed-by-mail-abonnenten)
- [Allgemeine Konfiguration und Admin-User](#allgemeine-konfiguration-und-admin-user)
- [Häufige Probleme](#häufige-probleme)

---

## Kanal einrichten

Ein Kanal besteht aus einfachen Einstellungen:

- Zeitplan für die Newsletter-Erstellung (stündlich, täglich, wöchentlich – an bestimmten Tagen, monatlich – an bestimmten Tagen)
- Versandzeit (Uhrzeit, einmal oder zweimal täglich)
- Newsletter-Design mit dynamischen Blöcken, die Inhalte aus dem Blog holen
- Dynamischer Betreff

---

## Zielgruppen-Targeting

Standardmäßig richtet sich dein Kanal an alle Abonnenten. Du kannst aber auch gezielt eine bestimmte Liste oder Sprache ansprechen.

---

## Funktionsweise: Dynamische Blöcke

Beim Generieren eines Newsletters werden alle Template-Blöcke aktualisiert. Statische Blöcke bleiben gleich, dynamische Blöcke (Beiträge, Events, ...) werden mit neuen Inhalten gefüllt.

Ein dynamischer Block kann so konfiguriert werden, dass er:

- die Newsletter-Erstellung stoppt, wenn keine neuen Inhalte vorhanden sind
- sich selbst überspringt, wenn es keine neuen Inhalte gibt
- einen einfachen Text anzeigt, wenn nichts zu zeigen ist
- immer die neuesten Inhalte anzeigt, egal ob sie schon verschickt wurden

Die Definition von „neuem Inhalt“ ist konfigurierbar. Du kannst z.B. einstellen, dass nur neue Beiträge verschickt werden, aber auch alte erneut angezeigt werden dürfen.

---

## Dynamische Blöcke und Betreff

Beim Erstellen eines neuen Newsletters prüft Automated, ob mindestens ein dynamischer Block neue Inhalte hat. Falls nicht, wird kein Newsletter generiert.

Jeder dynamische Block kann einen Betreff vorschlagen. Der erste Vorschlag wird übernommen, sofern kein allgemeiner Betreff gesetzt ist. Mit dem Tag `{dynamic_subject}` kannst du den dynamischen Betreff in deinem eigenen Betreff verwenden, z.B.:

```
{date} Updates: {dynamic_subject}
```

---

## Planung und Versandzeiten

- **Stündlich:** Prüft jede Stunde auf neue Inhalte.
- **Täglich:** Über Wochenplan alle Tage aktivieren.
- **Wöchentlich:** Wähle beliebige Wochentage (z.B. Montag und Donnerstag).
- **Monatlich:** Nach Wochentagen (z.B. erster Montag im Monat).

---

## Versandzeiten und Sommerzeit (DST)

Ein Kanal kann so eingestellt werden, dass der Newsletter zu einer bestimmten Uhrzeit verschickt wird. Optional kann ein zweiter Versandzeitpunkt pro Tag eingestellt werden.

**Hinweis:** Bei Zeitumstellung (Sommerzeit) kann es zu Verschiebungen kommen. Speichere die Kanaleinstellungen nach der Zeitumstellung neu ab, um das zu korrigieren.

---

## Vorschau für Composer-Newsletter

Im Vorschau-Tab siehst du, wie der Newsletter aktuell aussehen würde. Wenn die Bedingungen für einen Versand nicht erfüllt sind, bleibt die Vorschau leer und eine Info wird angezeigt.

---

## Letztes Versanddatum

Das letzte Versanddatum wird genutzt, um neue Beiträge zu erkennen. Es kann im Status-Panel geändert werden (z.B. nach Serverproblemen).

---

## Erstellte Newsletter und Statistiken

Im Tab „Newsletters“ findest du alle generierten Newsletter eines Kanals, ihren Status (gesendet, wird gesendet) und Aktions-Buttons (löschen, Statistik).

---

## Kanal-Abonnement

Jeder Kanal ist mit einer Liste verknüpft. Beim Abonnieren kannst du dem Nutzer anbieten, sich für bestimmte Listen/Kanäle anzumelden oder ihn automatisch zu einer Liste hinzufügen.

---

## Kanal bewerben

Mit neuen Shortcodes kannst du gezielt ein Formular für einen bestimmten Kanal anzeigen, z.B.:

```shortcode
[newsletter_form button_label="Go!" lists="1"]
[newsletter_field name="email" label="Deine beste E-Mail"]
[/newsletter_form]
```

---

## Kanal bei alten Abonnenten bewerben

Mache die Liste öffentlich (im Profil sichtbar) und sende einen Newsletter, um auf den neuen Kanal hinzuweisen.  
Oder: Füge alle alten Abonnenten zur Liste hinzu und informiere sie über die neue Option (mit Opt-out-Möglichkeit).

---

## Mehrsprachigkeit

Für mehrsprachige Seiten (WPML, Polylang) kannst du pro Sprache einen Kanal anlegen und gezielt Inhalte und Empfänger steuern.

---

## Newsletter-Design-Ideen

### Zwei Beitragsblöcke

Beispiel: Ein Hauptblock oben (nur bei neuen Inhalten generieren), ein Nebenblock weiter unten (immer anzeigen oder nur bei neuen Inhalten).

### Verschiedene Blocktypen mischen

Du kannst Blöcke für Produkte, Events, Custom Post Types usw. kombinieren und flexibel konfigurieren, welche Blöcke zwingend neue Inhalte brauchen.

---

## Alte Kanalformate (veraltet)

Alte Kanäle mit klassischen oder eigenen Themes werden weiterhin unterstützt.

---

## Beiträge extrahieren

Beiträge können ohne Filter oder nach Kategorien/Post-Typen gefiltert werden. Die Filter greifen ab dem letzten Versanddatum.

---

## Newsletter ohne neue Beiträge generieren

Mit speziellen Themes kann ein Newsletter auch ohne neue Beiträge generiert werden (z.B. für Events oder Produkte).

---

## Themes und Vorschau für alte Kanäle

Im Theme-Panel kannst du ein Theme wählen und konfigurieren. Es gibt zwei Vorschauen:
- Theme-Vorschau (generisch)
- Newsletter-Vorschau (real, kann leer sein)

Eigene Themes können im Ordner `wp-content/extensions/newsletter-automated/themes` abgelegt werden.

---

## Erweiterte Themen

### Import von Feed-by-Mail-Abonnenten

Du kannst alte Feed-by-Mail-Abonnenten in eine Kanal-Liste importieren.

**Schritte:**
1. Kanal und Ziel-Liste anlegen
2. Kanal speichern
3. Import-Button nutzen

Abgemeldete Abonnenten werden nicht importiert.

---

## Allgemeine Konfiguration und Admin-User

Automated generiert Newsletter im Hintergrund, auch wenn niemand eingeloggt ist.  
Falls Inhalte nicht gefunden werden (z.B. private Beiträge), kann ein Admin-User simuliert werden.

---

## Häufige Probleme

### Falsche Versandzeit (Sommerzeit)

Nach Zeitumstellung Kanaleinstellungen neu speichern.

### Newsletter-Generierung wurde übersprungen

Siehe Kanal-Log. Meist ist die Template-Konfiguration schuld (z.B. keine neuen Beiträge gefunden).

### Es werden zwei Newsletter generiert

Prüfe, ob ein zweiter Versandzeitpunkt im Kanal eingestellt ist.

---