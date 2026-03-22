# Implementierungsplan: Benutzerverwaltung mit RBAC

## Übersicht

Schrittweise Implementierung des erweiterten, modulbasierten RBAC-Systems auf Basis von `spatie/laravel-permission`. Das System umfasst:

- Trennung zwischen Basismodul und Fachmodulen
- Superadministrator-Rolle mit uneingeschränktem Zugriff
- Modulverwaltung mit Aktivierung/Deaktivierung
- Feingranulare Zugriffssteuerung mit Scopes
- Flexible Berechtigungsvergabe pro Benutzer oder Rolle

Jeder Schritt baut auf dem vorherigen auf.

## Aufgaben

- [x] 1. PasswordGeneratorService implementieren
  - [x] 1.1 `app/Services/PasswordGeneratorService.php` erstellen
    - Zeichensätze als Konstanten definieren (UPPERCASE, LOWERCASE, DIGITS)
    - `generate(): string` implementiert den Algorithmus: 1× Großbuchstabe + 1× Kleinbuchstabe + 1× Ziffer + 5× zufällig aus Gesamtzeichensatz, dann shuffle
    - _Anforderungen: 1.1, 1.2, 1.3, 1.4_
  - [ ]* 1.2 Property-Tests für PasswordGeneratorService schreiben
    - **Property 1: Passwort-Länge** – 100× generate(), strlen() === 8
    - **Property 2: Passwort-Zeichensatz** – 100× generate(), preg_match('/^[A-Za-z0-9]+$/')
    - **Property 3: Passwort-Mindestanforderungen** – 100× generate(), mind. 1 Groß, 1 Klein, 1 Ziffer
    - **Property 4: Passwort-Einzigartigkeit** – 1000× generate(), Kollisionsrate < 1%
    - **Validates: Requirements 1.1, 1.2, 1.3, 1.4**

- [x] 2. Datenbankstruktur anpassen und RBAC-Seeder erstellen
  - [x] 2.1 Migration erstellen: `role`-Spalte aus `users`-Tabelle entfernen
    - `php artisan make:migration remove_role_column_from_users_table`
    - `$table->dropColumn('role')` in `up()`
    - _Anforderungen: 3.6_
  - [x] 2.2 `database/seeders/RbacSeeder.php` erstellen
    - Alle 11 Berechtigungen im Format `module.{modul}.{aktion}` anlegen (announcements.view/create/edit/delete, users.view/create/edit/delete, audit.view, network.view/edit)
    - 4 Rollen anlegen: Admin, Redaktion, Netzwerk-Editor, Viewer
    - Berechtigungen den Rollen zuweisen gemäß Design-Tabelle
    - _Anforderungen: 3.1–3.5, 4.1–4.4_
  - [x] 2.3 `DatabaseSeeder` um `RbacSeeder` erweitern
    - `RbacSeeder::class` in `$this->call([...])` eintragen
    - _Anforderungen: 4.1–4.4_
  - [ ]* 2.4 Unit-Tests für RbacSeeder schreiben
    - Nach `RbacSeeder::run()` prüfen: alle 11 Berechtigungen vorhanden, alle 4 Rollen vorhanden, Rollen-Berechtigungs-Zuordnungen korrekt
    - **Property 7: Rollen-Berechtigungs-Konsistenz**
    - **Validates: Requirements 3.3, 4.1–4.4**

- [x] 3. User-Model und Middleware aktualisieren
  - [x] 3.1 `app/Models/User.php` bereinigen
    - `role`-Feld aus `$fillable` entfernen
    - `role`-Cast entfernen
    - `isSuperAdmin()` und `isAdmin()` auf Spatie-Rollen umstellen (`$this->hasRole('Admin')`)
    - `scopeByRole()` auf Spatie-Abfrage umstellen
    - _Anforderungen: 3.5, 5.2_
  - [x] 3.2 `app/Http/Middleware/EnsureUserIsActive.php` erstellen
    - Prüft `auth()->user()->is_active`, leitet bei `false` auf Login mit Fehlermeldung um
    - Middleware in `bootstrap/app.php` als `web`-Middleware registrieren
    - _Anforderungen: 5.6_
  - [ ]* 3.3 Property-Test für EnsureUserIsActive schreiben
    - **Property 6: Inaktive Benutzer werden abgelehnt** – Benutzer mit is_active=false, GET auf geschützte Route → 302 auf Login
    - **Validates: Requirements 5.6**

- [x] 4. Checkpoint – Datenbankstruktur und Basislogik validieren
  - Sicherstellen, dass alle Tests aus Schritt 1–3 grün sind. Bei Fragen den Benutzer ansprechen.

- [x] 5. WelcomeMail und UserMailService implementieren
  - [x] 5.1 `app/Mail/WelcomeMail.php` erstellen
    - `readonly`-Properties: `User $user`, `string $plaintextPassword`
    - `build()`: Betreff „Zugangsdaten für IT-Cockpit", View `emails.welcome`
    - `to($user->email)` setzen
    - _Anforderungen: 6.1–6.6_
  - [x] 5.2 Blade-Template `resources/views/emails/welcome.blade.php` erstellen
    - Persönliche Begrüßung mit `$user->name`
    - Login-URL (`config('app.url') . '/login'`)
    - Benutzername (`$user->email`)
    - Klartext-Passwort (`$plaintextPassword`)
    - Hinweis zur Passwortänderung nach erstem Login
    - _Anforderungen: 6.2–6.6_
  - [x] 5.3 `app/Services/UserMailService.php` erstellen
    - `sendWelcomeMail(User $user, string $plaintextPassword): void`
    - Instanziiert `WelcomeMail` und versendet via `Mail::to()->send()`
    - _Anforderungen: 2.4, 6.1_
  - [ ]* 5.4 Property-Test für WelcomeMail schreiben
    - **Property 5 (E-Mail-Inhalt):** Für beliebige User-Daten und Passwörter enthält das gerenderte Template Begrüßung, Login-URL, Benutzernamen und Passwort
    - **Validates: Requirements 6.2–6.5**
  - [ ]* 5.5 Unit-Tests für UserMailService schreiben
    - `Mail::fake()` – Checkbox aktiviert → WelcomeMail wird versendet
    - `Mail::fake()` – Checkbox nicht aktiviert → keine Mail versendet
    - **Validates: Requirements 2.4, 2.5**

- [x] 6. UserController erweitern
  - [x] 6.1 `store()`-Methode in `UserController` überarbeiten
    - Validierung: `password` optional (nullable), `send_credentials` boolean, `roles` Array
    - Passwort-Generierung: wenn `$request->password` leer → `PasswordGeneratorService::generate()`
    - Klartext-Passwort in lokaler Variable halten, nicht in Session/Response
    - Benutzer anlegen (Passwort wird durch Laravel-Cast automatisch gehasht)
    - Rollen zuweisen: `$user->syncRoles($request->roles)`
    - E-Mail versenden wenn `$request->boolean('send_credentials')` → `UserMailService::sendWelcomeMail()`
    - Audit-Log erweitern
    - _Anforderungen: 2.1–2.6_
  - [x] 6.2 `create()`-View `resources/views/users/create.blade.php` erweitern
    - Checkbox `send_credentials` hinzufügen: `<input type="checkbox" name="send_credentials">`
    - Rollen-Mehrfachauswahl hinzufügen (alle verfügbaren Rollen aus DB)
    - Passwortfeld als optional kennzeichnen (Hinweis: „Leer lassen für automatische Generierung")
    - _Anforderungen: 2.1_
  - [x] 6.3 `edit()`-View und `update()`-Methode für Rollenverwaltung erweitern
    - Aktuelle Rollen des Benutzers anzeigen und änderbar machen
    - `update()`: `$user->syncRoles($request->roles)`
    - _Anforderungen: 5.4_
  - [ ]* 6.4 Unit-Tests für UserController@store schreiben
    - Test: Passwort leer → Benutzer wird mit gültigem Hash angelegt
    - Test: Checkbox aktiviert → WelcomeMail versendet (Mail::fake)
    - Test: Checkbox nicht aktiviert → keine Mail
    - **Property 8: Passwort wird nur gehasht gespeichert**
    - **Validates: Requirements 2.2, 2.3, 2.4, 2.5**

- [x] 7. Berechtigungsprüfung in bestehenden Controllern absichern
  - [x] 7.1 `AnnouncementController` mit Berechtigungsprüfungen versehen
    - `index()`: `$this->authorize('module.announcements.view')` oder Middleware
    - `store()`: `module.announcements.create`
    - `update()`: `module.announcements.edit`
    - `destroy()`: `module.announcements.delete`
    - _Anforderungen: 4.1, 5.2, 5.3_
  - [x] 7.2 `UserController` mit Berechtigungsprüfungen versehen
    - `index()`: `module.users.view`
    - `store()`: `module.users.create`
    - `update()`: `module.users.edit`
    - `destroy()`: `module.users.delete`
    - _Anforderungen: 4.2, 5.2, 5.3_
  - [x] 7.3 `AuditLogController` mit Berechtigungsprüfung versehen
    - `index()`: `module.audit.view`
    - _Anforderungen: 4.3, 5.2, 5.3_
  - [ ]* 7.4 Property-Tests für Berechtigungsprüfung schreiben
    - **Property 5: Berechtigungsprüfung – Zugriffsverweigerung** – Benutzer ohne Rolle → hasPermissionTo() gibt false zurück
    - Für jede geschützte Route: Benutzer ohne Berechtigung → 403
    - **Validates: Requirements 5.2, 5.3**

- [x] 8. Checkpoint – Alle Tests grün, Integration validieren
  - Sicherstellen, dass alle Tests aus Schritt 1–7 grün sind. Bei Fragen den Benutzer ansprechen.

- [x] 9. HookManager-Integration für Benutzerverwaltungs-Modul
  - [x] 9.1 Sidebar-Eintrag und Berechtigungen für Benutzerverwaltung im `AppServiceProvider` oder einem neuen `UserManagementServiceProvider` registrieren
    - `$hookManager->registerSidebarItem('users', [...])`
    - `$hookManager->registerPermission('users', 'view', '...')` etc.
    - _Anforderungen: 4.2, 5.5_

- [x] 10. Abschluss-Checkpoint – Vollständige Integration
  - Sicherstellen, dass alle Tests grün sind und die Anwendung korrekt funktioniert. Bei Fragen den Benutzer ansprechen.

- [x] 11. Modulbasierte Architektur implementieren
  - [x] 11.1 Migration für `modules`-Tabelle erstellen
    - `php artisan make:migration create_modules_table`
    - Spalten: id, name (unique), display_name, description, is_active (default true), timestamps
    - _Anforderungen: 7.2_
  
  - [x] 11.2 Migration für `permission_scopes`-Tabelle erstellen
    - `php artisan make:migration create_permission_scopes_table`
    - Spalten: id, user_id (FK), permission_id (FK), scope_type, scope_id, timestamps
    - Unique constraint auf (user_id, permission_id, scope_type, scope_id)
    - _Anforderungen: 10.1_
  
  - [x] 11.3 Migration für `permissions`-Tabelle erweitern
    - `php artisan make:migration add_module_id_to_permissions_table`
    - Spalte: module_id (nullable, FK zu modules, cascade on delete)
    - _Anforderungen: 9.2_
  
  - [x] 11.4 `Module`-Model erstellen
    - `app/Models/Module.php` mit Fillable, Casts, Relationships
    - `permissions()` HasMany-Beziehung
    - `isActive()` und `scopeActive()` Methoden
    - _Anforderungen: 7.1, 7.2_
  
  - [x] 11.5 `PermissionScope`-Model erstellen
    - `app/Models/PermissionScope.php` mit Fillable, Relationships
    - `user()`, `permission()`, `scopable()` Beziehungen
    - _Anforderungen: 10.1_
  
  - [ ]* 11.6 Unit-Tests für Module- und PermissionScope-Models schreiben
    - Testen: CRUD-Operationen, Beziehungen, Scopes
    - **Validates: Requirements 7.2, 10.1**

- [x] 12. ModuleService und Superadministrator-Gate implementieren
  - [x] 12.1 `app/Services/ModuleService.php` erstellen
    - `activateModule(Module $module): void`
    - `deactivateModule(Module $module): void` mit Basismodul-Schutz
    - `registerModule(string $name, string $displayName, string $description): Module`
    - `getAvailableModulesForUser(User $user): Collection`
    - _Anforderungen: 7.4, 11.2, 11.3, 11.5_
  
  - [x] 12.2 SuperAdminGate in `AuthServiceProvider` registrieren
    - `Gate::before()` mit Prüfung auf Rolle `Superadministrator`
    - Gibt `true` zurück für alle Berechtigungen wenn Superadmin
    - _Anforderungen: 8.2_
  
  - [ ]* 12.3 Property-Tests für ModuleService schreiben
    - **Property 10: Modul-Registrierung zur Laufzeit** – Zufällige Module registrieren, Abrufbarkeit prüfen
    - **Property 21: Metadaten-Änderung beeinflusst Berechtigungen nicht** – Modul-Metadaten ändern, Berechtigungen unverändert
    - **Validates: Requirements 7.4, 11.4**
  
  - [ ]* 12.4 Property-Tests für SuperAdminGate schreiben
    - **Property 11: Superadministrator hat alle Rechte** – Superadmin-Benutzer, zufällige Berechtigungen prüfen
    - **Validates: Requirements 8.2, 8.3, 8.4, 8.5, 8.6**

- [x] 13. RbacSeeder erweitern für modulbasierte Berechtigungen
  - [x] 13.1 `RbacSeeder` aktualisieren
    - Basismodul und Fachmodule (announcements, audit, network, hh) anlegen
    - Alle Berechtigungen mit `module_id` verknüpfen
    - Berechtigungsformat auf `{modul}.{aktion}` umstellen (ohne "module." Präfix)
    - Superadministrator-Rolle anlegen (ohne explizite Berechtigungen)
    - Neue Rollen: Abteilungsleiter HH, Mitarbeiter HH
    - Bestehende Rollen aktualisieren mit neuen Berechtigungen
    - _Anforderungen: 7.5, 8.1, 9.1, 4.2_
  
  - [ ]* 13.2 Property-Tests für erweiterten RbacSeeder schreiben
    - **Property 7: Rollen-Berechtigungs-Konsistenz** – Seeder ausführen, Rollen-Berechtigungen abgleichen
    - **Property 12: Berechtigungsformat-Konsistenz** – Alle Berechtigungen, Format-Regex prüfen
    - **Validates: Requirements 3.3, 4.1–4.4, 7.5, 9.1**

- [x] 14. Checkpoint – Datenbankstruktur und Seeder validieren
  - Sicherstellen, dass alle Migrationen und Seeder korrekt funktionieren. Bei Fragen den Benutzer ansprechen.

- [x] 15. ModuleController und Verwaltungsoberfläche implementieren
  - [x] 15.1 `app/Http/Controllers/ModuleController.php` erstellen
    - `index()`: Zeigt alle Module (nur für Superadmin)
    - `activate(Module $module)`: Aktiviert Modul
    - `deactivate(Module $module)`: Deaktiviert Modul (mit Basismodul-Schutz)
    - `update(Request $request, Module $module)`: Aktualisiert Metadaten
    - Middleware: `can:base.modules.manage`
    - _Anforderungen: 11.1, 11.2, 11.3, 11.4, 11.5_
  
  - [x] 15.2 Routes für ModuleController registrieren
    - `routes/web.php`: Resource-Route für Module
    - Middleware: `auth`, `can:base.modules.manage`
    - _Anforderungen: 11.1_
  
  - [x] 15.3 Views für Modulverwaltung erstellen
    - `resources/views/modules/index.blade.php`: Liste aller Module mit Aktivierungs-Buttons
    - `resources/views/modules/edit.blade.php`: Formular für Metadaten-Bearbeitung
    - _Anforderungen: 11.1_
  
  - [ ]* 15.4 Unit-Tests für ModuleController schreiben
    - Test: Nicht-Superadmin kann nicht auf Modulverwaltung zugreifen (403)
    - Test: Basismodul kann nicht deaktiviert werden
    - Test: Modul aktivieren/deaktivieren funktioniert
    - **Validates: Requirements 11.1, 11.5**

- [x] 16. Erweiterte Berechtigungsprüfung mit Modulstatus und Scopes
  - [x] 16.1 `User`-Model um Scope-Prüfung erweitern
    - `hasPermissionToScope(string $permission, string $scopeType, int $scopeId): bool`
    - `hasPermissionWithoutScope(string $permission): bool`
    - Prüft `PermissionScope`-Einträge
    - _Anforderungen: 10.2, 10.3_
  
  - [x] 16.2 Middleware `CheckModuleAccess` erstellen
    - Prüft, ob Modul aktiv ist (außer für Superadmin)
    - Leitet auf 403-Seite um wenn Modul deaktiviert
    - _Anforderungen: 7.3, 11.3_
  
  - [x] 16.3 Bestehende Controller um Modul-Checks erweitern
    - `AnnouncementController`, `AuditLogController`: Middleware `CheckModuleAccess`
    - _Anforderungen: 7.3_
  
  - [ ]* 16.4 Property-Tests für erweiterte Berechtigungsprüfung schreiben
    - **Property 9: Deaktivierte Module für Nicht-Superadmins** – Modul deaktivieren, Nicht-Superadmin-Zugriff prüfen
    - **Property 13: Modul-Existenz bei Berechtigungserstellung** – Berechtigung mit nicht-existierendem Modul erstellen
    - **Property 14: Berechtigungen via Rolle und direkt** – Benutzer mit Rollen und direkten Berechtigungen
    - **Property 15: Cascade-Delete bei Modul-Löschung** – Modul mit Berechtigungen löschen, Cascade prüfen
    - **Property 16: Scope-basierte Zugriffsprüfung** – Benutzer mit Scope, Zugriff auf andere Untereinheiten prüfen
    - **Property 17: Berechtigung ohne Scope gewährt vollen Zugriff** – Benutzer ohne Scope, Zugriff auf alle Untereinheiten
    - **Property 18: Multiple Scopes für eine Berechtigung** – Benutzer mit mehreren Scopes, Zugriff prüfen
    - **Property 19: Scope-Validierung** – Scope mit nicht-existierender Kostenstelle erstellen
    - **Property 20: Modul-Aktivierung macht Berechtigungen verfügbar** – Modul aktivieren, Berechtigungen verfügbar prüfen
    - **Validates: Requirements 7.3, 9.3, 9.4, 9.5, 10.2, 10.3, 10.4, 10.5, 11.2, 11.3**

- [x] 17. Navigation und UI-Integration
  - [x] 17.1 Sidebar-Logik um Modulprüfung erweitern
    - `HookManager` oder `AppServiceProvider`: Nur Module anzeigen, für die Benutzer Berechtigungen hat
    - Modul-Status prüfen (is_active)
    - _Anforderungen: 12.3_
  
  - [x] 17.2 UserController um Modul-Zuweisungen erweitern
    - `create()` und `edit()` Views: Modul-Auswahl für Benutzer
    - `store()` und `update()`: Modul-spezifische Rollen zuweisen
    - _Anforderungen: 12.2_
  
  - [ ]* 17.3 Property-Tests für Navigation schreiben
    - **Property 22: Navigation zeigt nur verfügbare Module** – Navigation für Benutzer, nur verfügbare Module
    - **Property 23: Modulübergreifende Rollen** – Rolle mit Berechtigungen aus mehreren Modulen
    - **Validates: Requirements 12.3, 12.4**

- [x] 18. Abschluss-Checkpoint – Vollständige Integration testen
  - Sicherstellen, dass alle Tests grün sind und die erweiterte Funktionalität korrekt funktioniert. Bei Fragen den Benutzer ansprechen.

## Hinweise

- Aufgaben mit `*` sind optional und können für ein schnelles MVP übersprungen werden
- Jede Aufgabe referenziert spezifische Anforderungen für Rückverfolgbarkeit
- Property-Tests laufen mit mindestens 100 Iterationen
- Das Klartext-Passwort darf niemals in der Datenbank, im Session-State oder in Logs gespeichert werden
- Basismodul (name='base') kann nicht deaktiviert werden (Application-Level-Check)
- Superadministrator-Rolle erhält Zugriff über Gate::before, nicht über explizite Berechtigungen
