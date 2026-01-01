---
layout: psource-theme
title: "PS-eNewsletter Archive"
---

# 📚 PS-eNewsletter Archive

<div class="menu">
  <a href="https://github.com/Power-Source/e-newsletter/discussions" style="color:#38c2bb;">💬 Forum</a>
  <a href="https://github.com/Power-Source/e-newsletter/releases" style="color:#38c2bb;">📝 Download</a>
</div>

## Newsletter-Archiv - Zeige deine versendeten Newsletter auf deiner Website

Das Archive-Addon ermöglicht einen speziellen Shortcode, der auf einer WordPress-Seite verwendet werden kann, um die Archive der versendeten Newsletter anzuzeigen.

## 📋 Inhalt

- [Grundlagen des Archiv-Shortcodes](#grundlagen-des-archiv-shortcodes)
- [Präsentationstext anzeigen](#präsentationstext-anzeigen)
- [Shortcode-Attribute](#shortcode-attribute)
- [Newsletter für bestimmte Listen anzeigen](#newsletter-für-bestimmte-listen-anzeigen)
- [Newsletter von Automated Addon auflisten](#newsletter-von-automated-addon-auflisten)
- [Konfiguration und Einstellungen](#konfiguration-und-einstellungen)

## 🚀 Grundlagen des Archiv-Shortcodes

Der Shortcode, den du auf einer WordPress-Seite verwenden kannst, ist:

```markdown
[newsletter_archive /]
```

### So funktioniert es:

1. **📋 Erste Ansicht:** Liste aller versendeten Newsletter mit Betreff und Sendedatum
2. **📰 Detail-Ansicht:** Klick auf einen Newsletter zeigt ihn mit H2-Überschrift (Betreff) und eingebettetem Frame
3. **🖼️ Frame erforderlich:** Jeder Newsletter ist eine vollständige HTML-Seite und kann nicht direkt eingebettet werden

> **💡 Alternative:** Du kannst einstellen, dass Newsletter-Inhalte in einem neuen Browser-Tab geöffnet werden (siehe Addon-Konfiguration).

> **⚠️ Wichtig:** Einige Page Builder und/oder Themes funktionieren nicht korrekt mit der seiteneingebetteten Newsletter-Ansicht. Verwende in diesem Fall den alternativen Ansichtsmodus.

### Standard-Verhalten

Standardmäßig werden folgende Newsletter aufgelistet:
- ✅ **Versendete** Newsletter (nicht noch sendende)
- ✅ **Nicht-private** Newsletter  
- ✅ **Reguläre** Newsletter

Automatisch generierte Newsletter (z.B. vom Automated Addon) können mit speziellen Shortcode-Attributen ebenfalls angezeigt werden.

## �� Präsentationstext anzeigen

Du kannst einen Einleitungstext über der Newsletter-Liste hinzufügen. Dieser wird ausgeblendet, wenn ein einzelner Newsletter angezeigt wird.

**Beispiel:**
```markdown
[newsletter_archive]
Das ist mein Newsletter-Archiv mit allen wichtigen Nachrichten der letzten Monate. 
Viel Spaß beim Stöbern!
[/newsletter_archive]
```

> **📝 Hinweis:** Achte darauf, dass zwischen den Shortcodes keine zusätzlichen Leerzeichen stehen.

## ⚙️ Shortcode-Attribute

Du kannst das Verhalten des Archivs mit verschiedenen Attributen anpassen:

| Attribut | Beschreibung | Beispiel |
|----------|--------------|----------|
| `max` | Maximale Anzahl der aufgelisteten Newsletter | `[newsletter_archive max="10" /]` |
| `list` | Filter für bestimmte Liste (zeigt nur Newsletter dieser Liste) | `[newsletter_archive list="2" /]` |
| `type` | Newsletter-Typ (z.B. für Automated Addon) | `[newsletter_archive type="automated_1" /]` |
| `show_date` | Datum anzeigen (`true`/`false`) | `[newsletter_archive show_date="true" /]` |
| `separator` | Trennzeichen zwischen Datum und Titel | `[newsletter_archive separator=" | " /]` |
| `title` | Überschrift für die Liste (als H2) | `[newsletter_archive title="Newsletter Archiv" /]` |

### Erweiterte Beispiele

**Newsletter mit Datum und benutzerdefinierten Trennzeichen:**
```markdown
[newsletter_archive show_date="true" separator=" → " max="5" /]
```

**Newsletter mit Überschrift und Einleitungstext:**
```markdown
[newsletter_archive title="Unsere Newsletter" max="10"]
Hier findest du alle unsere Newsletter aus den letzten Monaten. 
Klicke auf einen Titel, um den vollständigen Newsletter zu lesen.
[/newsletter_archive]
```

## 📧 Newsletter für bestimmte Listen anzeigen

Um nur Newsletter anzuzeigen, die an eine bestimmte Liste gesendet wurden:

```markdown
[newsletter_archive list="X" /]
```

**Wobei X die Listennummer ist.**

> **�� Funktionsweise:** Wenn ein Newsletter mit einer Kombination von Listenfiltern konfiguriert wurde (alle, mindestens eine, Ausschlüsse usw.), wird er angezeigt, wenn die angegebene Listennummer in den "passenden Listen" gefunden wird, unabhängig von der Art der Übereinstimmung.

### Praktische Beispiele

**Nur Newsletter für VIP-Kunden (Liste 1):**
```markdown
[newsletter_archive list="1" title="VIP Newsletter" /]
```

**Newsletter für Produktankündigungen (Liste 3) mit Datum:**
```markdown
[newsletter_archive list="3" show_date="true" title="Produktneuheiten" /]
```

## 🤖 Newsletter von Automated Addon auflisten

Um Newsletter anzuzeigen, die vom Automated Addon generiert wurden:

```markdown
[newsletter_archive type="automated_X"]
```

**Wobei X die Kanal-ID-Nummer ist**, die du auf der Hauptverwaltungsseite des Automated Addons findest.

### Beispiele für Automated Newsletter

**Blog-Posts Newsletter (Kanal 1):**
```markdown
[newsletter_archive type="automated_1" title="Blog Updates" max="15" /]
```

**Wöchentliche Zusammenfassung (Kanal 2):**
```markdown
[newsletter_archive type="automated_2" show_date="true" title="Wöchentliche Nachrichten" /]
```

## 🛠️ Konfiguration und Einstellungen

### Archiv-Einstellungen anpassen

Du kannst die Archiv-Einstellungen im WordPress-Admin unter **Newsletter → Archive** konfigurieren:

| Einstellung | Beschreibung |
|-------------|--------------|
| **Newsletter-Anzeige** | Wähle zwischen eingebetteter Ansicht oder neuem Browser-Tab |
| **Datum anzeigen** | Standardmäßig Datum in der Liste anzeigen |
| **Anzeigemodus** | Eingebettet, neuer Tab oder gleiches Fenster |

### Troubleshooting

**❌ Probleme mit Page Buildern:**
- Verwende den "Neuer Tab"-Modus in den Einstellungen
- Teste verschiedene Anzeigemodi

**❌ Newsletter werden nicht angezeigt:**
- Prüfe, ob Newsletter tatsächlich versendet wurden (Status: "sent")
- Überprüfe, ob Newsletter als "privat" markiert sind
- Kontrolliere die Shortcode-Attribute

**❌ Styling-Probleme:**
- Newsletter werden in einem Frame angezeigt, um Style-Konflikte zu vermeiden
- Bei Problemen verwende den externen Ansichtsmodus

## 📚 Vollständige Beispiele

### Einfaches Archiv
```markdown
[newsletter_archive /]
```

### Erweiterte Archive-Seite
```markdown
# Newsletter Archiv

Willkommen in unserem Newsletter-Archiv! Hier findest du alle unsere bisherigen Newsletter.

## Alle Newsletter
[newsletter_archive title="Komplettes Archiv" show_date="true" separator=" - " max="20"]
Durchstöbere alle unsere Newsletter seit dem Start.
[/newsletter_archive]

## VIP Newsletter
[newsletter_archive list="1" title="Exklusive VIP-Inhalte" show_date="true" max="10" /]

## Blog Updates
[newsletter_archive type="automated_1" title="Automatische Blog-Zusammenfassungen" max="15" /]
```

---

**📬 Viel Erfolg mit deinem Newsletter-Archiv!**
