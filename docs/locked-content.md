---
layout: psource-theme
title: "PS-eNewsletter Locked Content"
---

<h2 align="center" style="color:#38c2bb;">📚 PS-eNewsletter Locked Content</h2>

<div class="menu">
  <a href="https://github.com/Power-Source/e-newsletter/discussions" style="color:#38c2bb;">💬 Forum</a>
  <a href="https://github.com/Power-Source/e-newsletter/releases" style="color:#38c2bb;">📝 Download</a>
</div>

# Locked Content

**Locked Content** ist ein cooles Feature für das Newsletter-Plugin. Damit kannst du bestimmte Blog-Inhalte für nicht-abonnierte Besucher verstecken – und natürlich für Abonnenten wieder freischalten. Das eignet sich super für alles, was du als „Premium-Inhalt“ siehst, also Inhalte, für die sich ein Abo lohnt.

Um einen Inhalt zu sperren, pack einfach den Bereich, den du verstecken willst, in diesen Shortcode:

```
[newsletter_lock]
 ...dein geheimer Inhalt...
[/newsletter_lock]
```

Wenn jemand deinen Beitrag liest und nicht abonniert ist, wird der Inhalt zwischen den Shortcodes durch eine Nachricht ersetzt. Diese Nachricht kannst du im Newsletter-Hauptmenü einstellen. Sie sollte erklären, warum der Inhalt versteckt ist – und die Leute natürlich zum Abonnieren einladen.

Zum Beispiel könnte die Nachricht so aussehen (mit dem Minimal-Formular):

```
Um den ganzen Artikel zu lesen, abonniere unseren Newsletter.
[newsletter_form type="minimal"]
```

Lässt du die Nachricht leer, wird automatisch das komplette Anmeldeformular angezeigt.

Du kannst sogar ganze Beitragsgruppen sperren, indem du in den Einstellungen einfach einen Tag, Kategorienamen oder eine ID angibst. Dann werden alle Beiträge mit diesem Tag oder in dieser Kategorie komplett versteckt – nur der Titel und die Ersatznachricht bleiben sichtbar.

## Wie wird der Inhalt freigeschaltet?

**Hinweis:** Der „versteckte“ Inhalt wird trotzdem angezeigt, wenn ein eingeloggter Nutzer mit Veröffentlichungsrechten (z.B. Admin, Redakteur, Autor) die Seite besucht.

Sobald das Abo bestätigt ist (sofort bei Single-Opt-In oder nach Bestätigung bei Double-Opt-In), merkt sich ein Cookie das Abo und du kannst den Nutzer direkt zum Premium-Inhalt schicken. Du kannst den Link zum Inhalt z.B. in die Willkommensseite oder Willkommensmail packen.

Wenn du z.B. alle Artikel einer Kategorie „premium“ sperrst, kannst du diese Kategorie in der Willkommensmail verlinken. Dann sieht der neue Abonnent alle Premium-Inhalte, solange er bestätigt bleibt.

## Das alte {unlock_url}

Das `{unlock_url}`-Tag brauchst du nicht mehr, es bleibt nur aus Kompatibilitätsgründen erhalten. Die Ziel-URL stellst du im Locked Content-Menü ein.

## Styling

Die Box, die den versteckten Inhalt ersetzt, ist schlicht gehalten – etwas Abstand, dünner Rahmen. Willst du sie selbst stylen, kannst du eigene CSS-Regeln in den Newsletter-Einstellungen hinterlegen. Zum Beispiel für einen gestrichelten Rahmen:

```css
.tnp-lock {
    border: 3px dashed #888;
}
```

## Caches

Cache-Plugins können manchmal gesperrte oder freigeschaltete Seiten falsch cachen. Die Erweiterung versucht, das zu verhindern. Du kannst aber auch erzwingen, dass Seiten mit dem „newsletter“-Cookie nie aus dem Cache ausgeliefert werden.