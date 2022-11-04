[![deutsche Version](https://logos.oxidmodule.com/de2_xs.svg)](README.md)
[![english version](https://logos.oxidmodule.com/en2_xs.svg)](README.en.md)

# D³ WebAuthn / FIDO2 Login für OXID eShop

Mit diesem Modul kann die Anmeldung im OXID-Shop mit einem Hardwaretoken anstelle eines Passworts durchgeführt werden. 

Hierbei wird die Anmeldung im Frontend und (sofern für den Benutzer erlaubt) auch im Backend gesichert.

Sicherheitsschlüssel sind Geräte, die kryptografische Schlüssel beeinhalten. Diese können für die Zwei-Faktor-Authentifizierung verwendet werden. Der Sicherheitsschlüssel muss den Standard "[WebAuthn](https://w3c.github.io/webauthn/#webauthn-authenticator)" unterstützen.

Die Schlüsselverwaltung erfolgt im Adminbereich sowie im "Mein Konto" des Benutzers.

## Inhaltsverzeichnis

- [Installation](#installation)
- [Verwendung](#verwendung)
- [Changelog](#changelog)
- [Beitragen](#beitragen)
- [Lizenz](#lizenz)
- [weitere Lizenzen und Nutzungsbedingungen](#weitere-lizenzen-und-nutzungsbedingungen)

## Installation

Dieses Paket erfordert einen mit Composer installierten OXID eShop in einer in der [composer.json](composer.json) definierten Version.

Öffnen Sie eine Kommandozeile und navigieren Sie zum Stammverzeichnis des Shops (Elternverzeichnis von source und vendor). Führen Sie den folgenden Befehl aus. Passen Sie die Pfadangaben an Ihre Installationsumgebung an.

```bash
php composer require d3/oxwebauthn:^1.0
```

Wird ein Hinweis auf ein unpassendes Paket "symfony/process" gezeigt, muss dieses geändert werden. Fügen Sie dazu in den oben genannten Befehl bitte den Schalter `-W` ein (`... require -W ...`).

Aktivieren Sie das Modul im Shopadmin unter "Erweiterungen -> Module".

## Verwendung

Die Eröffnung des Shopkontos erfolgt (wie gewohnt) mit Benutzername und Passwort. Im Anschluss lassen sich [FIDO2](https://fidoalliance.org/)-Keys als zusätzliche Authensierungsmöglichkeit hinzufügen. Ab diesem Moment kann die Anmeldung im Shop (Frontend und Backend) entweder mit FIDO2 oder mit Passwort erfolgen. Beides funktioniert unabhängig voneinander.

Die Anmeldung mit Passwort unterscheidet sich nicht vom Shopstandard und bleibt als Rückfalloption weiterhin bestehen.

Zur Verwendung der registrierten FIDO2-Keys wird bei der Anmeldung einfach das Passwortfeld leer gelassen. Sobald in das Passwortfeld mindestens ein Zeichen eingegeben wurde, wird von einer Anmeldung mit Passwort ausgegangen. Bei leerer Übergabe des Passwortfeldes wird auf die Existenz einer Keyregistrierung geprüft und im Erfolgsfall das entsprechende Gerät angefordert. Liegt keine Registrierung vor, wird ebenfalls von einer Anmeldung mit Passwort ausgegangen.

Die Keys können einfach im Mein-Konto-Bereich des Frontends und auch im Kundenkonto im Backend verwaltet werden. Die Verwaltung umfasst das Registrieren neuer Keys (mehrfache Keys pro Konto sind möglich und empfohlen). Jedem Key kann ein Freitextname zugeordnet werden. Weiterhin werden alle registrierten Keys mit ihrem Namen dargestellt. Ebenso sind registrierte Keys dort löschbar.

Zur Anmeldung ist jede FIDO2-zertifizierte Hardware nutzbar. Das können USB-Tokens (z.B. Solokey oder YubiKey), NFC- oder Bluetoothsender, Smartphones (Android ab Version 7, iOS ab Version 14) oder Smartcards sein.

Da bei einer FIDO2-basierten Anmeldung kein Passwort mehr benötigt wird, kann das Kennwort auch komplexer als alltagstaugliche Passworte sein.

## Changelog

Siehe [CHANGELOG](CHANGELOG.md) für weitere Informationen.

## Beitragen

Wenn Sie eine Verbesserungsvorschlag haben, legen Sie einen Fork des Repositories an und erstellen Sie einen Pull Request. Alternativ können Sie einfach ein Issue erstellen. Fügen Sie das Projekt zu Ihren Favoriten hinzu. Vielen Dank.

- Erstellen Sie einen Fork des Projekts
- Erstellen Sie einen Feature Branch (git checkout -b feature/AmazingFeature)
- Fügen Sie Ihre Änderungen hinzu (git commit -m 'Add some AmazingFeature')
- Übertragen Sie den Branch (git push origin feature/AmazingFeature)
- Öffnen Sie einen Pull Request

## Lizenz
(Stand: 25.10.2022)

Vertrieben unter der GPLv3 Lizenz.

```
Copyright (c) D3 Data Development (Inh. Thomas Dartsch)

Diese Software wird unter der GNU GENERAL PUBLIC LICENSE Version 3 vertrieben.
```

Die vollständigen Copyright- und Lizenzinformationen entnehmen Sie bitte der [LICENSE](LICENSE.md)-Datei, die mit diesem Quellcode verteilt wurde.

## weitere Lizenzen und Nutzungsbedingungen
