# SMTP-Konfiguration

Das SMTP-Feature ermöglicht es dir, deine Newsletter über einen SMTP-Service zu versenden. Die SMTP-Funktionalität ist jetzt direkt in das PS-eNewsletter Plugin integriert und bietet eine zuverlässige E-Mail-Zustellung.

## 📋 Inhalt

- [Konfiguration](#-konfiguration)
- [Unsichere SSL-Option](#-unsichere-ssl-option)
- [Häufige Fragen](#-häufige-fragen)
- [Problemlösung](#-problemlösung)
- [Schnellstart](#-schnellstart)

## 🔧 Konfiguration

Du findest die SMTP-Einstellungen im Newsletter-Admin unter **SMTP-Konfiguration**. Hier kannst du:

- ✅ SMTP aktivieren/deaktivieren
- 🌐 Host und Port konfigurieren
- 🔐 Authentifizierung einrichten
- 🧪 Test-E-Mails versenden

### Empfohlene Alternative SMTP-Plugins

Falls du eine systemweite SMTP-Lösung bevorzugst, empfehlen wir diese WordPress-Plugins:

- **WP Mail SMTP** - Beliebte und zuverlässige Lösung
- **Post SMTP Mailer** - Erweiterte Funktionen
- **Easy WP SMTP** - Einfach zu konfigurieren

> 💡 **Tipp**: Diese Plugins integrieren sich nahtlos mit dem Newsletter-Plugin und sorgen dafür, dass alle E-Mails deines Blogs über den gewählten SMTP versendet werden.

## 🔒 Unsichere SSL-Option

Einige nicht korrekt aktualisierte Server haben möglicherweise fehlende SSL-Zertifikate, wodurch PHP einen Fehler auslöst, wenn es versucht, eine sichere Verbindung zu einem SMTP herzustellen. Die Option "Unsichere SSL-Verbindungen" umgeht dieses Problem.

**Wann solltest du es aktivieren?**
- ✅ Bei SSL-Verbindungsfehlern trotz korrekter Einstellungen
- ✅ Bei älteren Servern mit veralteten Zertifikaten

## ❓ Häufige Fragen

### Gibt es eine Möglichkeit, mit einem öffentlichen SMTP zu testen?

**Ja!** Wir empfehlen den fantastischen **[Mailtrap](https://mailtrap.io/)**-Service. Er simuliert einen SMTP-Server und bietet eine coole Konsole, um zu sehen, was passiert. Sehr empfehlenswert für Tests!

### Werden nur Newsletter über SMTP versendet?

**Ja**, das SMTP-Feature funktioniert nur mit Newsletters und Service-Nachrichten (wie Willkommens-E-Mails, Aktivierungs-E-Mails usw.). Alle anderen E-Mails deines Blogs werden nicht über dieses Feature versendet.

### Sollte ich einen SMTP verwenden?

**Definitiv ja!** Egal ob du dich über dieses Feature oder ein Drittanbieter-SMTP-Plugin mit einem SMTP verbindest - normalerweise erhalten deine E-Mails eine bessere Bewertung und werden seltener als Spam markiert.

### Kann ich den SMTP meines Hosting-Pakets verwenden?

**Absolut!** Das ist sogar eine sehr gute Wahl. Wir empfehlen, ein spezielles Postfach für deinen Blog zu erstellen (z.B. `newsletter@domain.com` oder `blog@domain.com`) und dieses Konto zum Versenden von E-Mails zu verwenden.

> 💌 **Vorteil**: Du kannst dieses Postfach überwachen, um eingehende Fehlermeldungen zu überprüfen (sogenannte DSN-Nachrichten).

### Kann ich Gmail mit diesem SMTP-Feature verwenden?

**Nein**, Google hat die Standard-Authentifizierungsmethoden abgeschafft, die normalerweise von SMTP-Servern verwendet werden. Viele SMTP-Plugins haben jedoch mittlerweile OAuth2 implementiert, das von Google benötigt wird.

## 🔧 Problemlösung

### "Connection failed" beim Testen - was bedeutet das?

Das passiert normalerweise in zwei Fällen:

1. **❌ Falsche Konfiguration**
   - Host-Parameter ist falsch konfiguriert
   - Du verwendest den falschen Port/Protokoll

2. **🚫 Provider-Blockierung** *(häufigster Fall)*
   - Dein Provider blockiert Verbindungen zu externen SMTPs
   - Kontaktiere den Support deines Providers

> ⚠️ **Wenn du sicher bist, dass deine Einstellungen korrekt sind, kontaktiere deinen Provider!**

### Die "Von"-Adresse ist nicht die, die ich eingestellt habe

Manchmal zwingen Provider die "Von"-Adresse dazu, mit dem Konto übereinzustimmen, das zur Authentifizierung mit ihrem SMTP verwendet wird. Das kann auch mit dem "Return-Path" passieren.

**💡 Lösung**: Erstelle ein spezielles Postfach für deinen Blog und verwende diese Adresse.

---

## 🚀 Schnellstart

1. **Gehe zu**: Newsletter → SMTP-Konfiguration
2. **Aktiviere** SMTP
3. **Trage ein**: Host, Port, Benutzername, Passwort
4. **Teste**: Sende eine Test-E-Mail
5. **Speichere** die Einstellungen

> ⚡ **Performance-Tipp**: Die meisten SMTP-Services bieten deutlich bessere Zustellraten als der Standard-PHP-Mail-Versand!

## 📧 Beliebte SMTP-Services

| Service | Kostenlose E-Mails/Monat | Besonderheiten |
|---------|--------------------------|----------------|
| **Mailgun** | 5.000 | Entwicklerfreundlich |
| **SendGrid** | 40.000 | Sehr zuverlässig |
| **Amazon SES** | 62.000 | AWS-Integration |
| **Mailjet** | 6.000 | EU-Server verfügbar |

## 🛡️ Sicherheits-Tipps

- 🔑 Verwende immer starke Passwörter für SMTP-Konten
- 🔐 Aktiviere Zwei-Faktor-Authentifizierung, wenn verfügbar
- 📱 Überwache dein SMTP-Konto regelmäßig auf ungewöhnliche Aktivitäten
- 🚫 Teile deine SMTP-Zugangsdaten niemals mit anderen

## 🔄 Häufige SMTP-Ports

| Port | Protokoll | Beschreibung |
|------|-----------|--------------|
| **25** | SMTP | Standard (oft blockiert) |
| **587** | SMTP+STARTTLS | **Empfohlen** |
| **465** | SMTPS | SSL/TLS verschlüsselt |
| **2525** | SMTP | Alternative zu Port 25 |

---

*Diese Dokumentation bezieht sich auf das integrierte SMTP-Feature des PS-eNewsletter Plugins.*
