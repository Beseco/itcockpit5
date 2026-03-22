# Anforderungsdokument: Benutzerverwaltung mit RBAC

## Einleitung

Dieses Dokument beschreibt die Anforderungen für ein flexibles, modulbasiertes Benutzerverwaltungssystem im IT-Cockpit (Laravel). Das System umfasst:

- Automatische Passwort-Generierung und optionalen E-Mail-Versand von Zugangsdaten
- Ein rollenbasiertes Rechte- und Berechtigungssystem (RBAC)
- Trennung zwischen Basismodul (globale Funktionen) und Fachmodulen (spezifische Funktionen)
- Superadministrator-Rolle mit uneingeschränktem Zugriff
- Modulspezifische Berechtigungen mit optionaler feingranularer Zugriffssteuerung auf Untereinheiten
- Flexible Modulverwaltung zur Laufzeit

Das System ist für zukünftige Module erweiterbar und ermöglicht es, Module unabhängig voneinander zu aktivieren, deaktivieren und zu verwalten.

## Glossar

- **Benutzer (User)**: Eine Person mit einem Konto im IT-Cockpit.
- **Rolle (Role)**: Eine benannte Gruppe von Berechtigungen, die einem Benutzer zugewiesen werden kann (z. B. Admin, Redaktion, Viewer).
- **Berechtigung (Permission)**: Eine atomare Zugriffsregel, bestehend aus Modul und Aktion (z. B. `announcements.view`, `hh.edit`).
- **RBAC**: Role-Based Access Control – rollenbasierte Zugriffskontrolle.
- **Passwort-Generator**: Komponente, die sichere Zufallspasswörter erzeugt.
- **Mailer**: Komponente, die E-Mails über das Laravel-Mail-System versendet.
- **Passwort-Hash**: Bcrypt-verschlüsselte Version des Klartextpassworts.
- **IT-Cockpit**: Das Laravel-basierte Verwaltungssystem, für das dieses Feature entwickelt wird.
- **Basismodul**: Das Kernmodul des IT-Cockpits, das globale Funktionen wie Benutzerverwaltung, Rollenverwaltung und Modulverwaltung bereitstellt.
- **Fachmodul (Module)**: Ein eigenständiges Modul mit spezifischen Funktionen (z. B. Haushaltsplanung, Netzwerkverwaltung), das aktiviert oder deaktiviert werden kann.
- **Superadministrator**: Eine spezielle Rolle mit uneingeschränktem Zugriff auf alle Module und das Basismodul.
- **Scope**: Eine optionale Einschränkung einer Berechtigung auf eine spezifische Untereinheit (z. B. Kostenstelle, Abteilung).
- **Untereinheit**: Eine fachliche Einheit innerhalb eines Moduls, auf die Berechtigungen beschränkt werden können (z. B. Kostenstelle im HH-Modul).

---

## Anforderungen

### Anforderung 1: Automatische Passwort-Generierung

**User Story:** Als Administrator möchte ich, dass das System automatisch ein sicheres Passwort generiert, wenn ich beim Anlegen eines Benutzers kein Passwort eingebe, damit ich keine unsicheren oder leeren Passwörter vergebe.

#### Akzeptanzkriterien

1. WHEN ein Benutzer ohne Passwort gespeichert wird, THE Passwort-Generator SHALL ein Passwort mit genau 8 Zeichen erzeugen.
2. THE Passwort-Generator SHALL ausschließlich Zeichen aus dem Zeichensatz A–Z, a–z und 0–9 verwenden.
3. THE Passwort-Generator SHALL sicherstellen, dass das generierte Passwort mindestens einen Großbuchstaben, mindestens einen Kleinbuchstaben und mindestens eine Ziffer enthält.
4. WHEN das Passwort zusammengestellt wurde, THE Passwort-Generator SHALL die Zeichen zufällig mischen (shuffle), bevor das Passwort zurückgegeben wird.
5. IF das Passwortfeld beim Speichern leer ist, THEN THE System SHALL automatisch den Passwort-Generator aufrufen.

---

### Anforderung 2: Benutzer anlegen mit optionalem E-Mail-Versand

**User Story:** Als Administrator möchte ich beim Anlegen eines Benutzers optional dessen Zugangsdaten per E-Mail versenden können, damit der neue Benutzer sofort Zugang zum IT-Cockpit erhält.

#### Akzeptanzkriterien

1. THE Benutzerverwaltung SHALL im Formular zum Anlegen eines Benutzers eine Checkbox „Zugangsdaten per E-Mail senden" anzeigen.
2. WHEN ein Benutzer gespeichert wird und das Passwortfeld leer ist, THE System SHALL den Passwort-Generator aufrufen und das generierte Passwort verwenden.
3. WHEN ein Benutzer gespeichert wird, THE System SHALL das Passwort ausschließlich als Bcrypt-Hash in der Datenbank speichern.
4. WHEN ein Benutzer gespeichert wird und die Checkbox „Zugangsdaten per E-Mail senden" aktiviert ist, THE Mailer SHALL eine E-Mail mit der Login-URL, dem Benutzernamen und dem Klartext-Passwort an die E-Mail-Adresse des Benutzers versenden.
5. IF die Checkbox „Zugangsdaten per E-Mail senden" nicht aktiviert ist, THEN THE Mailer SHALL keine E-Mail versenden.
6. THE System SHALL das Klartext-Passwort ausschließlich im Moment der Erstellung verwenden und danach nicht mehr speichern oder weitergeben.

---

### Anforderung 3: Datenbankstruktur für RBAC

**User Story:** Als Systemarchitekt möchte ich eine normalisierte Datenbankstruktur für Rollen und Berechtigungen, damit das System modular erweiterbar ist und keine hartcodierten Berechtigungsprüfungen benötigt.

#### Akzeptanzkriterien

1. THE System SHALL eine Tabelle `roles` mit den Spalten `id` und `name` bereitstellen.
2. THE System SHALL eine Tabelle `permissions` mit den Spalten `id`, `module` und `action` bereitstellen.
3. THE System SHALL eine Pivot-Tabelle `role_permissions` mit den Spalten `role_id` und `permission_id` bereitstellen, die Rollen mit Berechtigungen verknüpft.
4. THE System SHALL eine Pivot-Tabelle `user_roles` mit den Spalten `user_id` und `role_id` bereitstellen, die Benutzer mit Rollen verknüpft.
5. THE System SHALL die Tabelle `users` mit den Spalten `id`, `username`, `email`, `password_hash` und `active` bereitstellen.
6. THE System SHALL keine booleschen Berechtigungsspalten direkt in der `users`-Tabelle verwenden.

---

### Anforderung 4: Vordefinierte Berechtigungen für IT-Cockpit-Module

**User Story:** Als Administrator möchte ich vordefinierte Berechtigungen für alle bestehenden Module des IT-Cockpits haben, damit ich Rollen gezielt mit den notwendigen Zugriffsrechten ausstatten kann.

#### Akzeptanzkriterien

1. THE System SHALL die Berechtigungen `announcements.view`, `announcements.create`, `announcements.edit` und `announcements.delete` für das Ankündigungs-Modul bereitstellen.
2. THE System SHALL die Berechtigungen `base.users.view`, `base.users.create`, `base.users.edit` und `base.users.delete` für die Benutzerverwaltung im Basismodul bereitstellen.
3. THE System SHALL die Berechtigung `audit.view` für das Auditprotokoll bereitstellen.
4. THE System SHALL die Berechtigungen `network.view` und `network.edit` für das Netzwerk-Modul bereitstellen.
5. WHEN ein neues Modul zum IT-Cockpit hinzugefügt wird, THE System SHALL das Hinzufügen neuer Berechtigungen ohne Änderung bestehender Datenbankstrukturen ermöglichen.

---

### Anforderung 5: Rollenbasierte Zugriffskontrolle (RBAC)

**User Story:** Als Administrator möchte ich Benutzern Rollen zuweisen und Rollen mit Berechtigungen verknüpfen, damit der Zugriff auf Module und Aktionen zentral gesteuert wird.

#### Akzeptanzkriterien

1. THE System SHALL vordefinierte Rollen Admin, Redaktion, Netzwerk-Editor und Viewer bereitstellen.
2. WHEN ein Benutzer eine Aktion ausführen möchte, THE System SHALL prüfen, ob der Benutzer über eine Rolle verfügt, die die entsprechende Berechtigung enthält.
3. IF ein Benutzer nicht über die erforderliche Berechtigung verfügt, THEN THE System SHALL den Zugriff verweigern und eine entsprechende Fehlermeldung zurückgeben.
4. THE System SHALL es ermöglichen, einem Benutzer mehrere Rollen zuzuweisen.
5. THE System SHALL es ermöglichen, einer Rolle mehrere Berechtigungen zuzuweisen.
6. WHILE ein Benutzer inaktiv ist (`active = false`), THE System SHALL alle Zugriffsanfragen dieses Benutzers ablehnen.

---

### Anforderung 6: E-Mail-Template für Zugangsdaten

**User Story:** Als neuer Benutzer möchte ich eine übersichtliche E-Mail mit meinen Zugangsdaten erhalten, damit ich mich sofort im IT-Cockpit anmelden kann.

#### Akzeptanzkriterien

1. THE Mailer SHALL E-Mails mit dem Betreff „Zugangsdaten für IT-Cockpit" versenden.
2. THE Mailer SHALL im E-Mail-Body eine persönliche Begrüßung mit dem Namen des Benutzers enthalten.
3. THE Mailer SHALL im E-Mail-Body die Login-URL des IT-Cockpits enthalten.
4. THE Mailer SHALL im E-Mail-Body den Benutzernamen enthalten.
5. THE Mailer SHALL im E-Mail-Body das Klartext-Passwort enthalten.
6. THE Mailer SHALL im E-Mail-Body einen Hinweis enthalten, dass das Passwort nach dem ersten Login geändert werden soll.
7. WHERE ein Corporate-Design konfiguriert ist, THE Mailer SHALL das E-Mail-Template im Corporate Design rendern.

---

### Anforderung 7: Modulbasierte Architektur mit Basismodul

**User Story:** Als Systemarchitekt möchte ich eine klare Trennung zwischen Basismodul und einzelnen Fachmodulen, damit Module unabhängig voneinander aktiviert, deaktiviert und verwaltet werden können.

#### Akzeptanzkriterien

1. THE System SHALL ein Basismodul bereitstellen, das globale Funktionen wie Benutzerverwaltung, Rollenverwaltung und Modulverwaltung enthält.
2. THE System SHALL eine Tabelle `modules` mit den Spalten `id`, `name`, `display_name`, `description`, `is_active` und `created_at` bereitstellen.
3. WHEN ein Modul deaktiviert ist (`is_active = false`), THE System SHALL alle Berechtigungen dieses Moduls für Nicht-Superadministratoren ignorieren.
4. THE System SHALL es ermöglichen, neue Module zur Laufzeit zu registrieren, ohne bestehende Datenbankstrukturen zu ändern.
5. THE Basismodul SHALL die Berechtigungen `base.users.view`, `base.users.create`, `base.users.edit`, `base.users.delete`, `base.roles.view`, `base.roles.create`, `base.roles.edit`, `base.roles.delete`, `base.modules.view`, `base.modules.manage` bereitstellen.

---

### Anforderung 8: Superadministrator-Rolle

**User Story:** Als Superadministrator möchte ich uneingeschränkten Zugriff auf alle Module und das Basismodul haben, damit ich das gesamte System verwalten kann.

#### Akzeptanzkriterien

1. THE System SHALL eine vordefinierte Rolle `Superadministrator` bereitstellen.
2. WHEN ein Benutzer die Rolle `Superadministrator` besitzt, THE System SHALL alle Berechtigungsprüfungen mit `true` beantworten.
3. THE Superadministrator SHALL Module aktivieren und deaktivieren können.
4. THE Superadministrator SHALL Benutzer global verwalten und allen Modulen zuweisen können.
5. THE Superadministrator SHALL Rollen erstellen, bearbeiten und löschen können.
6. WHEN ein Modul deaktiviert ist, THE Superadministrator SHALL trotzdem Zugriff auf alle Funktionen dieses Moduls haben.

---

### Anforderung 9: Modulspezifische Berechtigungen

**User Story:** Als Administrator möchte ich für jedes Modul eigene Berechtigungen definieren können, damit ich den Zugriff feingranular steuern kann.

#### Akzeptanzkriterien

1. THE System SHALL Berechtigungen im Format `{modul}.{aktion}` speichern (z. B. `hh.view`, `hh.edit`, `network.view`, `network.edit`).
2. THE System SHALL die Tabelle `permissions` um die Spalte `module_id` erweitern, die auf die `modules`-Tabelle referenziert.
3. WHEN eine Berechtigung erstellt wird, THE System SHALL sicherstellen, dass das zugehörige Modul existiert.
4. THE System SHALL es ermöglichen, Berechtigungen pro Benutzer oder pro Rolle zu vergeben.
5. WHEN ein Modul gelöscht wird, THE System SHALL alle zugehörigen Berechtigungen automatisch entfernen.

---

### Anforderung 10: Feingranulare Zugriffssteuerung mit Untereinheiten

**User Story:** Als Administrator möchte ich Berechtigungen optional auf Untereinheiten beschränken können, damit Benutzer nur auf ihre Verantwortungsbereiche zugreifen können.

#### Akzeptanzkriterien

1. THE System SHALL eine Tabelle `permission_scopes` mit den Spalten `id`, `user_id`, `permission_id`, `scope_type`, `scope_id` bereitstellen.
2. WHERE eine Berechtigung mit Scope vergeben wird, THE System SHALL prüfen, ob der Benutzer Zugriff auf die spezifische Untereinheit hat.
3. WHEN ein Benutzer eine Berechtigung ohne Scope besitzt, THE System SHALL Zugriff auf alle Untereinheiten gewähren.
4. THE System SHALL es ermöglichen, mehrere Scopes für dieselbe Berechtigung zu vergeben (z. B. Zugriff auf Kostenstelle A und B).
5. WHERE ein Scope-Typ `cost_center` verwendet wird, THE System SHALL prüfen, ob die referenzierte Kostenstelle existiert.

---

### Anforderung 11: Flexible Modulverwaltung

**User Story:** Als Superadministrator möchte ich Module aktivieren, deaktivieren und deren Metadaten verwalten können, damit ich das System flexibel an die Anforderungen anpassen kann.

#### Akzeptanzkriterien

1. THE System SHALL eine Verwaltungsoberfläche für Module bereitstellen, die nur für Superadministratoren zugänglich ist.
2. WHEN ein Modul aktiviert wird, THE System SHALL alle zugehörigen Berechtigungen für berechtigte Benutzer verfügbar machen.
3. WHEN ein Modul deaktiviert wird, THE System SHALL alle zugehörigen Berechtigungen für Nicht-Superadministratoren deaktivieren.
4. THE System SHALL die Metadaten eines Moduls (display_name, description) bearbeiten können, ohne die Berechtigungen zu beeinflussen.
5. THE System SHALL verhindern, dass das Basismodul deaktiviert wird.

---

### Anforderung 12: Vorgesetzte und rollenbasierte Modulzugriffe

**User Story:** Als Administrator möchte ich Benutzern unterschiedliche Modulzugriffe basierend auf ihrer Rolle zuweisen können, damit Abteilungsleiter nur ihre relevanten Module sehen.

#### Akzeptanzkriterien

1. WHEN einem Benutzer eine Rolle zugewiesen wird, THE System SHALL automatisch die modulspezifischen Berechtigungen dieser Rolle gewähren.
2. THE System SHALL es ermöglichen, einem Benutzer Zugriff auf mehrere Module gleichzeitig zu gewähren.
3. WHEN ein Benutzer keine Berechtigung für ein Modul besitzt, THE System SHALL das Modul in der Navigation ausblenden.
4. THE System SHALL es ermöglichen, Rollen modulübergreifend zu definieren (z. B. Rolle `Abteilungsleiter` mit Zugriff auf HH-Modul und Audit-Modul).
5. WHERE ein Benutzer direkte Berechtigungen und Rollen-Berechtigungen besitzt, THE System SHALL die Vereinigungsmenge aller Berechtigungen anwenden.
