---
layout: psource-theme
title: "PS-eNewsletter Abonnement-Formulare und Shortcodes"
---

<h2 align="center" style="color:#38c2bb;">📚 PS-eNewsletter Abonnement-Formulare und Shortcodes</h2>

<div class="menu">
  <a href="https://github.com/Power-Source/e-newsletter/discussions" style="color:#38c2bb;">💬 Forum</a>
  <a href="https://github.com/Power-Source/e-newsletter/releases" style="color:#38c2bb;">📝 Download</a>
</div>


Hier findest du alles, was du über unsere Shortcodes wissen musst, um Anmeldeformulare auf deiner Website einzubauen.

Shortcodes sind das Herzstück von **Newsletter**, weil du damit flexibel Formulare in Seiten, Beiträge, Widgets, Popups usw. einfügen kannst. Um ein Formular einzubauen, reicht der Shortcode:

```
[newsletter_form]
```

Das Newsletter-Plugin übernimmt dann automatisch den Rest – mit den Feldern und Optionen, die du vorher im Backend eingestellt hast.

---

## Inhalt

- [Der `[newsletter_form]` Shortcode](#der-newsletter_form-shortcode)
- [Shortcode-Attribute](#shortcode-attribute)
- [Das `fields`-Attribut](#das-fields-attribut)
- [Single-Line Formular](#single-line-formular)
- [Der `[newsletter_field]` Shortcode](#der-newsletter_field-shortcode)
- [Labels und Platzhalter](#labels-und-platzhalter)
- [Alle Feldnamen im Überblick](#alle-feldnamen-im-überblick)
- [Formular-Design anpassen](#formular-design-anpassen)

---

## Der `[newsletter_form]` Shortcode

Nutze diesen **nicht** auf der öffentlichen Newsletter-Seite – dort gehört nur `[newsletter]` hin.

Beispiele:

- Landingpages
- Beiträge
- Widgets
- Popups
- Top-/Bottom-Bars

Beispiel:

```
[newsletter_form /]
```

---

## Shortcode-Attribute

| Attribut              | Beschreibung                                                      |
|-----------------------|-------------------------------------------------------------------|
| `ajax="true"`         | Formular wird per Ajax abgesendet                                 |
| `lists="1,2"`         | Automatische Anmeldung zu bestimmten Listen (nur öffentliche)     |
| `lists_field_layout`  | `"dropdown"` statt Checkbox                                       |
| `lists_field_label`   | Label für Listen-Auswahl                                          |
| `show_labels="false"` | Labels ausblenden                                                 |
| `confirmation_url`    | Ziel-URL nach Anmeldung                                           |
| `referrer="xyz"`      | Frei wählbarer Vermerk, z.B. `referrer="widget"`                  |
| `optin="single"`      | Überschreibt globalen Opt-In-Modus (single/double)                |
| `button_color="#f00"` | Button-Farbe                                                      |
| `button_label="Los!"` | Text auf dem Button                                               |
| `show_placeholders`   | Platzhalter anzeigen/ausblenden                                   |

Beispiel:

```
[newsletter_form lists_field_layout="dropdown" lists_field_empty_label="Wählen..." /]
```

---

## Das `fields`-Attribut

Du kannst bestimmen, welche Felder angezeigt werden:

```
[newsletter_form fields="first_name,email,privacy" /]
```

Mögliche Felder:

- `first_name`
- `last_name`
- `email`
- `gender`
- `lists`
- `privacy`
- `customfields`

---

## Single-Line Formular

Beispiel für ein schlankes Formular:

```
[newsletter_form type="minimal" show_name="true" button_label="Abonnieren" /]
```

Weitere Optionen:

| Attribut           | Beschreibung                  |
|--------------------|-------------------------------|
| `placeholder`      | Platzhalter für E-Mail        |
| `name_placeholder` | Platzhalter für Namen         |
| `align`            | Ausrichtung (left, center)    |
| `class`            | Eigene CSS-Klasse             |

---

## Der `[newsletter_field]` Shortcode

Formulare komplett selbst bauen:

```
[newsletter_form button_label="Los!"]
    [newsletter_field name="email" label="Deine beste E-Mail"]
    [newsletter_field name="first_name" label="Dein Name"]
[/newsletter_form]
```

Listen können sichtbar, vorausgewählt oder versteckt eingebaut werden:

```
[newsletter_field name="list" number="2" label="Marketing News" checked="true"]
```

Oder alle Listen gleichzeitig anzeigen:

```
[newsletter_field name="lists" layout="dropdown" first_option_label="Bitte wählen"]
```

Auch benutzerdefinierte Felder (`profile`) sind möglich.

---

## Labels und Platzhalter

Individuell pro Feld definierbar:

```
[newsletter_field name="email" label="" placeholder="Deine beste E-Mail"]
```

---

## Alle Feldnamen im Überblick

- `email`
- `name` / `first_name`
- `surname` / `last_name`
- `gender`
- `list` (mit `number`)
- `lists`
- `profile` (mit `number`)
- `privacy` (optional mit `url`)

---

## Formular-Design anpassen

Eigenes CSS im Plugin unter **Newsletter → Erweiterte Einstellungen → CSS** oder im Theme.

Beispiel:

```css
.tnp-subscription input {
  border-radius: 5px;
  padding: 0.5em;
}
```

---

👉 [Zurück zur Startseite](https://cp-psource.github.io/e-newsletter/)
