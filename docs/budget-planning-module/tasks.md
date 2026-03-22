# Implementierungsplan – Haushaltsplanung (HH)

## Übersicht

Das Modul wird schrittweise als Laravel-Modul unter `app/Modules/HH/` aufgebaut. Jede Aufgabe baut auf der vorherigen auf und endet mit der vollständigen Integration aller Komponenten. Die Implementierung folgt dem Design-Dokument und referenziert die Anforderungen aus `requirements.md`.

---

## Aufgaben

- [x] 1. Modulgerüst und Datenbankstruktur anlegen
  - `app/Modules/HH/` mit Unterverzeichnissen anlegen (Console, Database/Migrations, Http/Controllers, Http/Requests, Models, Providers, Routes, Services, Views)
  - `module.json` für das HH-Modul erstellen
  - `HHServiceProvider` anlegen und in `config/app.php` registrieren
  - Migrations für alle 7 Tabellen erstellen: `hh_budget_years`, `hh_budget_year_versions`, `hh_cost_centers`, `hh_accounts`, `hh_budget_positions`, `hh_user_cost_center_roles`, `hh_audit_entries`
  - Enum-Werte, Unique-Constraints und Foreign Keys gemäß Design-Dokument setzen
  - _Anforderungen: 1.1, 2.1, 3.1, 4.1, 6.1, 9.2, 10.1_

- [x] 2. Eloquent-Models und Beziehungen implementieren
  - [x] 2.1 Models `BudgetYear`, `BudgetYearVersion`, `CostCenter`, `Account` erstellen
    - `$fillable`, `$casts`, Beziehungen (`hasMany`, `belongsTo`) gemäß ER-Diagramm
    - _Anforderungen: 1.1, 2.1, 3.1, 6.1_
  - [x] 2.2 Model `BudgetPosition` erstellen
    - Alle Pflicht- und optionalen Felder, Self-Referenz `origin_position_id`
    - Beziehungen zu `BudgetYearVersion`, `CostCenter`, `Account`
    - _Anforderungen: 4.1, 4.2, 5.4_
  - [x] 2.3 Models `UserCostCenterRole` und `AuditEntry` erstellen
    - `AuditEntry` ohne `updated_at` (immutable), kein `update()`-Aufruf erlaubt
    - _Anforderungen: 9.2, 10.1, 10.5_
  - [ ]* 2.4 Property-Test: Initialstatus beim Anlegen
    - **Eigenschaft 2: Initialstatus beim Anlegen**
    - **Validates: Anforderungen 1.2, 2.2, 3.2**

- [x] 3. AuthorizationService implementieren
  - [x] 3.1 `AuthorizationService` mit Methoden `canAccessCostCenter`, `getUserRoleForCostCenter`, `isLeiter` implementieren
    - Kostenstellenbezogene Rechtsprüfung; `Leiter` hat globalen Vollzugriff
    - _Anforderungen: 9.1, 9.2, 9.3, 9.7_
  - [ ]* 3.2 Property-Test: Kostenstellenbezogene Rollenzuweisung Round-Trip
    - **Eigenschaft 18: Kostenstellenbezogene Rollenzuweisung – Round-Trip**
    - **Validates: Anforderungen 2.5, 9.2**
  - [ ]* 3.3 Property-Test: Benutzer ohne Recht wird abgewiesen
    - **Eigenschaft 6: Berechtigungsvalidierung beim Anlegen von Positionen**
    - **Validates: Anforderungen 4.3, 9.7**
  - [ ]* 3.4 Property-Test: Audit_Zugang hat keinen Schreibzugriff
    - **Eigenschaft 17: Audit_Zugang – Kein Schreibzugriff**
    - **Validates: Anforderungen 9.6**

- [x] 4. BudgetYearService und Statuslogik implementieren
  - [x] 4.1 `BudgetYearService` mit `create` und `transitionStatus` implementieren
    - Statusübergangs-Validierung: nur `draft → preliminary` und `preliminary → approved` erlaubt
    - Sperrlogik bei `approved`: alle Schreibversuche ablehnen
    - _Anforderungen: 1.2, 1.3, 1.4, 1.5, 1.6_
  - [ ]* 4.2 Property-Test: Eindeutigkeit von Haushaltsjahren
    - **Eigenschaft 1: Eindeutigkeit von Haushaltsjahren**
    - **Validates: Anforderungen 1.1**
  - [ ]* 4.3 Property-Test: Statusübergangs-Reihenfolge
    - **Eigenschaft 3: Statusübergangs-Reihenfolge**
    - **Validates: Anforderungen 1.6**
  - [ ]* 4.4 Property-Test: Sperrung nach Genehmigung
    - **Eigenschaft 4: Sperrung nach Genehmigung**
    - **Validates: Anforderungen 1.4, 1.5, 4.6**

- [x] 5. AuditService implementieren
  - [x] 5.1 `AuditService` mit `log` und `getEntries` implementieren
    - `log` schreibt immutable Einträge in `hh_audit_entries`
    - `getEntries` unterstützt Filter nach Haushaltsjahr, Kostenstelle, Zeitraum
    - _Anforderungen: 10.1, 10.2, 10.3, 10.4, 10.5, 10.6_
  - [ ]* 5.2 Property-Test: Audit-Trail-Vollständigkeit
    - **Eigenschaft 8: Audit-Trail-Vollständigkeit**
    - **Validates: Anforderungen 4.8, 10.1, 10.2, 10.3, 10.4**
  - [ ]* 5.3 Property-Test: Immutabilität des Audit Trails
    - **Eigenschaft 9: Immutabilität des Audit Trails**
    - **Validates: Anforderungen 10.5**
  - [ ]* 5.4 Property-Test: Audit-Trail-Filterung
    - **Eigenschaft 19: Audit-Trail-Filterung**
    - **Validates: Anforderungen 10.6**

- [x] 6. Checkpoint – Alle bisherigen Tests müssen bestehen
  - Alle Tests ausführen, offene Fragen mit dem Benutzer klären.

- [x] 7. PositionService implementieren
  - [x] 7.1 `PositionService` mit `create`, `update`, `delete` implementieren
    - Validierung: Kostenstelle und Sachkonto aktiv, Benutzer hat Schreibrecht, Haushaltsjahr nicht `approved`
    - Betragsänderungen über `AuditService` protokollieren
    - _Anforderungen: 4.3, 4.4, 4.5, 4.6, 4.7, 4.8_
  - [ ]* 7.2 Property-Test: Inaktive Stammdaten blockieren neue Positionen
    - **Eigenschaft 5: Inaktive Stammdaten blockieren neue Positionen**
    - **Validates: Anforderungen 2.3, 2.4, 3.3, 3.4**
  - [ ]* 7.3 Property-Test: Schreibzugriff nach Haushaltsjahr-Status
    - **Eigenschaft 7: Schreibzugriff nach Haushaltsjahr-Status**
    - **Validates: Anforderungen 4.4, 4.5**

- [x] 8. Versionierungslogik implementieren
  - [x] 8.1 `createVersion` im `BudgetYearService` implementieren
    - Alle Positionen der aktiven Version in neue Version kopieren
    - Vorherige Version auf `is_active = false` setzen, neue auf `is_active = true`
    - _Anforderungen: 6.2, 6.3, 6.4_
  - [ ]* 8.2 Property-Test: Versionierung – Positionskopie
    - **Eigenschaft 13: Versionierung – Positionskopie**
    - **Validates: Anforderungen 6.2**
  - [ ]* 8.3 Property-Test: Genau eine aktive Version
    - **Eigenschaft 14: Genau eine aktive Version**
    - **Validates: Anforderungen 6.3, 6.4**

- [x] 9. MultiYearPlanningService implementieren
  - [x] 9.1 `propagateRecurring` implementieren
    - Alle wiederkehrenden Positionen des Haushaltsjahres ins Folgejahr kopieren
    - _Anforderungen: 5.1, 5.4_
  - [x] 9.2 `generateForDateRange` implementieren
    - Für jedes Jahr zwischen `start_year` und `end_year` eine Position anlegen
    - Validierung: `end_year >= start_year`
    - _Anforderungen: 5.2, 5.3, 5.4_
  - [ ]* 9.3 Property-Test: Wiederkehrende Positionen – Propagierung
    - **Eigenschaft 12: Wiederkehrende Positionen – Propagierung**
    - **Validates: Anforderungen 5.1, 5.4**
  - [ ]* 9.4 Property-Test: Laufzeitbasierte Positionsanzahl
    - **Eigenschaft 10: Mehrjahresplanung – Laufzeitbasierte Positionsanzahl**
    - **Validates: Anforderungen 5.2, 5.4**
  - [ ]* 9.5 Property-Test: Validierung ungültiger Laufzeiten
    - **Eigenschaft 11: Validierung ungültiger Laufzeiten**
    - **Validates: Anforderungen 5.3**

- [x] 10. BudgetCalculationService implementieren
  - [x] 10.1 `getAvailableBudget`, `getTotals`, `getInvestiveShare` implementieren
    - `getAvailableBudget`: geplantes Budget minus Bestellsumme (negativer Wert = Überschreitung)
    - `getTotals`: Gesamtbudget, Investiv gesamt, Konsumtiv gesamt
    - `getInvestiveShare`: `(investiv / gesamt) * 100`, bei gesamt = 0 → 0
    - _Anforderungen: 7.1, 7.2, 7.4, 7.5, 8.1, 8.3_
  - [ ]* 10.2 Property-Test: Budget-Kennzahlen-Konsistenz
    - **Eigenschaft 15: Budget-Kennzahlen-Konsistenz**
    - **Validates: Anforderungen 7.1, 7.2, 7.4, 7.5**
  - [ ]* 10.3 Property-Test: Verfügbares Budget
    - **Eigenschaft 16: Verfügbares Budget**
    - **Validates: Anforderungen 8.1, 8.3**

- [x] 11. Checkpoint – Alle bisherigen Tests müssen bestehen
  - Alle Tests ausführen, offene Fragen mit dem Benutzer klären.

- [x] 12. HTTP-Controller und Form Requests implementieren
  - [x] 12.1 `CostCenterController` und `AccountController` mit CRUD-Endpunkten implementieren
    - Form Requests für Validierung (Nummer eindeutig, Pflichtfelder)
    - _Anforderungen: 2.1, 2.2, 2.3, 3.1, 3.2, 3.3_
  - [x] 12.2 `BudgetYearController` und `BudgetYearVersionController` implementieren
    - Statusübergangs-Endpunkte (`POST /budget-years/{id}/transition`)
    - Versions-Endpunkt (`POST /budget-years/{id}/versions`)
    - _Anforderungen: 1.1, 1.2, 1.3, 1.4, 1.6, 6.1, 6.2, 6.3, 6.4_
  - [x] 12.3 `BudgetPositionController` implementieren
    - CRUD-Endpunkte mit Berechtigungsprüfung über `AuthorizationService`
    - _Anforderungen: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6_
  - [x] 12.4 `DashboardController` implementieren
    - Aggregierte Kennzahlen über `BudgetCalculationService`
    - _Anforderungen: 7.1, 7.2, 7.3, 7.4, 7.5_
  - [x] 12.5 `AuditController` implementieren
    - Gefilterte Anzeige des Änderungsprotokolls
    - Zugriff nur für `Leiter` und `Audit_Zugang`
    - _Anforderungen: 10.6_
  - [ ]* 12.6 Feature-Tests für alle Controller-Endpunkte
    - HTTP-Status-Codes, Validierungsfehler, Berechtigungsfehler testen
    - _Anforderungen: 1.5, 4.6, 9.7_

- [x] 13. ExportService und ExportController implementieren
  - [x] 13.1 `ExportService` mit `exportExcel` und `exportPdf` implementieren
    - Excel-Export mit `maatwebsite/excel`, PDF mit `barryvdh/laravel-dompdf`
    - Alle Pflichtfelder und Summen gemäß Anforderung 11.2 und 11.3
    - Kostenstellen-Zugriffsfilterung über `AuthorizationService`
    - _Anforderungen: 11.1, 11.2, 11.3, 11.4_
  - [x] 13.2 `ExportController` mit Endpunkten für Excel und PDF implementieren
    - _Anforderungen: 11.1_
  - [ ]* 13.3 Property-Test: Export-Vollständigkeit und Zugriffsschutz
    - **Eigenschaft 20: Export-Vollständigkeit und Zugriffsschutz**
    - **Validates: Anforderungen 11.2, 11.3, 11.4**

- [x] 14. Blade-Views und Routing verdrahten
  - [x] 14.1 Routes in `Routes/web.php` und `Routes/api.php` registrieren
    - Middleware für Authentifizierung und Modulzugriff
    - _Anforderungen: 9.1, 9.7_
  - [x] 14.2 Blade-Templates für Dashboard, Positionen-Liste, Kostenstellen, Sachkonten, Audit-Log und Export-Seite erstellen
    - Dashboard mit Kennzahlen-Karten und optionalem Ampelsystem (Anforderung 7.3)
    - _Anforderungen: 7.1, 7.2, 7.3, 11.1_

- [x] 15. Abschluss-Checkpoint – Alle Tests müssen bestehen
  - Alle Unit-, Feature- und Property-Tests ausführen.
  - Sicherstellen, dass alle 20 Korrektheitseigenschaften durch Tests abgedeckt sind.
  - Offene Fragen mit dem Benutzer klären.

---

## Hinweise

- Aufgaben mit `*` sind optional und können für ein schnelleres MVP übersprungen werden
- Jede Aufgabe referenziert spezifische Anforderungen aus `requirements.md`
- Property-Tests werden mit `// Feature: budget-planning-module, Property N: <Text>` annotiert
- Checkpoints stellen sicher, dass keine hängenden oder nicht integrierten Code-Teile entstehen
