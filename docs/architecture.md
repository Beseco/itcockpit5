# IT Cockpit v5.0 - Systemarchitektur & Spezifikation

Dieses Dokument dient als zentrale Wissensbasis für die Entwicklung des IT Cockpits. Es beschreibt die technische Struktur, die Datenmodelle und die Logik der Kernanwendung.

## 1. Technischer Stack
* **Framework:** Laravel 11 (PHP 8.2+)
* **Frontend:** Tailwind CSS (Styling), Blade (Templating), Alpine.js (Interaktivität)
* **Datenbank:** MySQL 8.0+
* **E-Mail:** SMTP-basiert (Laravel Mail-System)

## 2. Benutzer- & Rechtesystem (IAM)
Das System unterstützt mehrere Administratoren und eine feingranulare Rechtevergabe.

### 2.1 Rollen
1.  **Super-Admin:** Hat globalen Zugriff auf alle Einstellungen, System-Logs und die Modulverwaltung.
2.  **Admin:** Kann Benutzer innerhalb seines Zuständigkeitsbereichs verwalten und hat Zugriff auf administrative Funktionen freigeschalteter Module.
3.  **User:** Standard-Anwender mit Zugriff auf die für ihn freigegebenen Module.

### 2.2 Berechtigungs-Logik (RBAC)
Berechtigungen werden pro Modul vergeben:
- `module.{slug}.view`: Erlaubt den Zugriff auf das Modul und die Anzeige der Dashboard-Kachel.
- `module.{slug}.edit`: Erlaubt Schreibrechte/Konfiguration innerhalb des Moduls.

## 3. Datenbank-Schema (Core)

### Tabelle: `users`
| Spalte | Typ | Beschreibung |
| :--- | :--- | :--- |
| `id` | BigInt (PK) | Eindeutige ID |
| `role` | Enum | super-admin, admin, user |
| `name` | String | Vollständiger Name |
| `email` | String (Unique) | Login-E-Mail |
| `password` | String | Gehashtes Passwort |
| `is_active` | Boolean | Account-Status |
| `last_login_at` | Datetime | Zeitstempel des letzten Logins |

### Tabelle: `announcements` (Dashboard-Ticker)
| Spalte | Typ | Beschreibung |
| :--- | :--- | :--- |
| `type` | Enum | info (neutral), maintenance (gelb), critical (rot) |
| `message` | Text | Der Inhalt der Meldung |
| `starts_at` | Datetime | Beginn (besonders für Wartung) |
| `ends_at` | Datetime | Geplantes Ende |
| `is_fixed` | Boolean | True, wenn Störung behoben |
| `fixed_at` | Datetime | Zeitstempel der Behebung (für 8h-Logik) |

### Tabelle: `audit_logs` (System-Protokoll)
| Spalte | Typ | Beschreibung |
| :--- | :--- | :--- |
| `user_id` | FK | Wer hat die Aktion ausgeführt? |
| `module` | String | Kontext (Core oder Modul-Name) |
| `action` | String | z.B. "User created", "Module disabled" |
| `payload` | JSON | Details zur Änderung (Vorher/Nachher) |

## 4. Dashboard-Logik ("Traffic Light")
Das Dashboard ist das Herzstück der App.
- **Kritische Störungen (Rot):** Werden oben fixiert. Button "Störung behoben" setzt `is_fixed = true`.
- **Wartungsarbeiten (Gelb):** Erscheinen automatisch im definierten Zeitraum.
- **Behobene Meldungen (Grün):** Sobald `is_fixed` aktiv ist, wechselt die Farbe zu Grün. Nach exakt **8 Stunden** wird die Meldung über einen Query-Filter vom Dashboard entfernt.
- **Modul-Kacheln:** Jedes Modul kann eine Info-Kachel (Widget) injizieren, sofern der User das `view`-Recht besitzt.

## 5. Modul-Integrität
Die Basis-Anwendung scannt das Verzeichnis `/app/Modules`. Ein Modul wird über einen `Service-Provider` registriert. Die Basis stellt Hooks für:
1.  **Sidebar-Navigation**
2.  **Dashboard-Grid**
3.  **Rechte-Verwaltung**
zur Verfügung.