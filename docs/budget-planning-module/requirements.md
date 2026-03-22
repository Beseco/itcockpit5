# Anforderungsdokument – Haushaltsplanung (HH)

## Einleitung

Das Modul „Haushaltsplanung (HH)" ist Bestandteil des IT-Cockpits und dient der strukturierten, transparenten und revisionssicheren Planung aller IT-bezogenen Haushaltsmittel im öffentlichen Dienst. Es unterstützt die Planung von Projekten und laufenden Kosten, Mehrjahresplanung, Versionierung für Haushaltsgespräche, Priorisierung von Maßnahmen sowie die Budgetüberwachung als Grundlage für das Bestellwesen. Das Modul ist für mehrere Organisationseinheiten (z. B. IT, IP) ausgelegt und setzt ein kostenstellenbezogenes Rechtekonzept um.

---

## Glossar

- **HH_Modul**: Das Modul „Haushaltsplanung (HH)" im IT-Cockpit
- **Haushaltsjahr**: Ein Kalenderjahr, für das Haushaltsmittel geplant werden; besitzt einen Status und kann mehrere Versionen haben
- **Haushaltsposition**: Ein einzelner Planungseintrag innerhalb eines Haushaltsjahres, bestehend aus Kostenstelle, Sachkonto, Betrag und weiteren Attributen
- **Kostenstelle**: Eine organisatorische Einheit (z. B. 143011 – IUK), der Haushaltspositionen zugeordnet werden
- **Sachkonto**: Ein Finanzkonto zur Kategorisierung von Ausgaben (z. B. 0121002 – Software); besitzt einen Kontotyp (investiv/konsumtiv)
- **Version**: Eine Momentaufnahme aller Haushaltspositionen eines Haushaltsjahres zu einem bestimmten Zeitpunkt
- **Audit_Trail**: Das revisionssichere Änderungsprotokoll aller relevanten Datenänderungen
- **Leiter**: Rolle mit Vollzugriff auf alle Kostenstellen und administrative Funktionen
- **Teamleiter**: Rolle mit Zugriff auf zugewiesene Kostenstellen; kann Positionen in Draft-Haushaltsjahren anlegen und bearbeiten
- **Mitarbeiter**: Rolle mit eingeschränktem Schreibzugriff; kann Positionen vorschlagen, aber nicht löschen
- **Audit_Zugang**: Lesezugriff auf alle Daten ohne Bearbeitungsrechte (z. B. Abteilungsleiter, Finanzbuchhaltung)
- **Bestellwesen**: Das externe Modul im IT-Cockpit, das lesend auf Budgetdaten zugreift

---

## Anforderungen

### Anforderung 1: Verwaltung von Haushaltsjahren

**User Story:** Als Leiter möchte ich Haushaltsjahre anlegen und deren Status verwalten, damit die Planung strukturiert und revisionssicher durchgeführt werden kann.

#### Akzeptanzkriterien

1. THE HH_Modul SHALL für jedes Kalenderjahr genau ein Haushaltsjahr mit den Status-Werten `draft`, `preliminary` und `approved` verwalten.
2. WHEN ein Leiter ein neues Haushaltsjahr anlegt, THE HH_Modul SHALL das Haushaltsjahr mit dem Status `draft` initialisieren.
3. WHEN ein Leiter den Status eines Haushaltsjahres von `draft` auf `preliminary` ändert, THE HH_Modul SHALL alle zugehörigen Haushaltspositionen für Teamleiter und Mitarbeiter eingeschränkt bearbeitbar setzen.
4. WHEN ein Leiter den Status eines Haushaltsjahres auf `approved` setzt, THE HH_Modul SHALL alle zugehörigen Haushaltspositionen dauerhaft sperren und keine weiteren Änderungen zulassen.
5. IF ein Benutzer versucht, ein Haushaltsjahr mit Status `approved` zu bearbeiten, THEN THE HH_Modul SHALL die Änderung ablehnen und eine Fehlermeldung zurückgeben.
6. THE HH_Modul SHALL sicherstellen, dass der Status eines Haushaltsjahres nur in der Reihenfolge `draft` → `preliminary` → `approved` geändert werden kann.

---

### Anforderung 2: Verwaltung von Kostenstellen

**User Story:** Als Leiter möchte ich Kostenstellen anlegen und verwalten, damit Haushaltspositionen organisatorischen Einheiten zugeordnet werden können.

#### Akzeptanzkriterien

1. THE HH_Modul SHALL Kostenstellen mit den Attributen Nummer, Bezeichnung und Status (aktiv/inaktiv) verwalten.
2. WHEN ein Leiter eine neue Kostenstelle anlegt, THE HH_Modul SHALL die Kostenstelle mit Status aktiv initialisieren.
3. WHEN ein Leiter eine Kostenstelle auf inaktiv setzt, THE HH_Modul SHALL verhindern, dass neue Haushaltspositionen dieser Kostenstelle zugeordnet werden.
4. IF eine Kostenstelle inaktiv ist und bereits Haushaltspositionen besitzt, THEN THE HH_Modul SHALL die bestehenden Positionen unverändert erhalten.
5. THE HH_Modul SHALL pro Benutzer und pro Kostenstelle individuelle Zugriffsrechte (Rolle) verwalten.

---

### Anforderung 3: Verwaltung von Sachkonten

**User Story:** Als Leiter möchte ich Sachkonten anlegen und verwalten, damit Ausgaben finanziell korrekt kategorisiert werden können.

#### Akzeptanzkriterien

1. THE HH_Modul SHALL Sachkonten mit den Attributen Nummer, Bezeichnung, Kontotyp (`investiv`/`konsumtiv`) und Status (aktiv/inaktiv) verwalten.
2. WHEN ein Leiter ein neues Sachkonto anlegt, THE HH_Modul SHALL das Sachkonto mit Status aktiv initialisieren.
3. WHEN ein Leiter ein Sachkonto auf inaktiv setzt, THE HH_Modul SHALL verhindern, dass neue Haushaltspositionen diesem Sachkonto zugeordnet werden.
4. IF ein Sachkonto inaktiv ist und bereits Haushaltspositionen besitzt, THEN THE HH_Modul SHALL die bestehenden Positionen unverändert erhalten.

---

### Anforderung 4: Verwaltung von Haushaltspositionen

**User Story:** Als Teamleiter oder Mitarbeiter möchte ich Haushaltspositionen anlegen und bearbeiten, damit Projekte und laufende Kosten geplant werden können.

#### Akzeptanzkriterien

1. THE HH_Modul SHALL Haushaltspositionen mit folgenden Pflichtattributen verwalten: Haushaltsjahr, Kostenstelle, Sachkonto, Projektname, Betrag (brutto, positiver Wert in Euro), Priorität (`hoch`/`mittel`/`niedrig`), Kategorie (`Pflichtaufgabe`/`gesetzlich gebunden`/`freiwillige Leistung`), Status (`geplant`/`angepasst`/`gestrichen`).
2. THE HH_Modul SHALL Haushaltspositionen mit folgenden optionalen Attributen verwalten: Beschreibung, Startjahr, Endjahr, Wiederkehrend (ja/nein).
3. WHEN ein Benutzer eine Haushaltsposition anlegt, THE HH_Modul SHALL sicherstellen, dass Kostenstelle und Sachkonto aktiv sind und der Benutzer Schreibrechte auf die Kostenstelle besitzt.
4. WHEN ein Haushaltsjahr den Status `draft` hat, THE HH_Modul SHALL Teamleitern und Mitarbeitern das Anlegen und Bearbeiten von Haushaltspositionen erlauben.
5. WHILE ein Haushaltsjahr den Status `preliminary` hat, THE HH_Modul SHALL nur dem Leiter das Bearbeiten von Haushaltspositionen erlauben.
6. IF ein Benutzer versucht, eine Haushaltsposition in einem Haushaltsjahr mit Status `approved` zu löschen, THEN THE HH_Modul SHALL die Aktion ablehnen und eine Fehlermeldung zurückgeben.
7. IF ein Mitarbeiter versucht, eine genehmigte Haushaltsposition zu löschen, THEN THE HH_Modul SHALL die Aktion ablehnen.
8. WHEN ein Benutzer den Betrag einer Haushaltsposition ändert, THE HH_Modul SHALL den alten und neuen Wert im Audit_Trail protokollieren.

---

### Anforderung 5: Mehrjahresplanung

**User Story:** Als Teamleiter möchte ich wiederkehrende und laufzeitbasierte Haushaltspositionen definieren, damit Mehrjahresplanungen automatisch erzeugt werden.

#### Akzeptanzkriterien

1. WHEN eine Haushaltsposition als wiederkehrend markiert wird, THE HH_Modul SHALL diese Position beim Abschluss des Haushaltsjahres automatisch in das folgende Haushaltsjahr übernehmen.
2. WHEN eine Haushaltsposition ein Startjahr und ein Endjahr besitzt, THE HH_Modul SHALL für jedes Kalenderjahr innerhalb der Laufzeit automatisch eine entsprechende Haushaltsposition anlegen.
3. IF das Endjahr einer laufzeitbasierten Haushaltsposition kleiner als das Startjahr ist, THEN THE HH_Modul SHALL die Eingabe ablehnen und eine Fehlermeldung zurückgeben.
4. THE HH_Modul SHALL sicherstellen, dass automatisch erzeugte Mehrjahrespositionen dieselben Attribute wie die Ursprungsposition erhalten, mit Ausnahme des Haushaltsjahres.

---

### Anforderung 6: Versionierung von Haushaltsjahren

**User Story:** Als Leiter möchte ich Versionen eines Haushaltsjahres erstellen, damit verschiedene Planungsstände für Haushaltsgespräche festgehalten werden können.

#### Akzeptanzkriterien

1. THE HH_Modul SHALL pro Haushaltsjahr mehrere Versionen verwalten, wobei jede Version eine Momentaufnahme aller Haushaltspositionen darstellt.
2. WHEN ein Leiter eine neue Version eines Haushaltsjahres erstellt, THE HH_Modul SHALL alle bestehenden Haushaltspositionen der aktuellen Version in die neue Version kopieren.
3. WHEN eine neue Version erstellt wird, THE HH_Modul SHALL die vorherige Version als unveränderlich markieren.
4. THE HH_Modul SHALL sicherstellen, dass immer genau eine Version eines Haushaltsjahres als aktiv (bearbeitbar) gilt.

---

### Anforderung 7: Dashboard und Budgetübersicht

**User Story:** Als Leiter oder Teamleiter möchte ich eine Übersicht der Budgetdaten eines Haushaltsjahres einsehen, damit ich den Planungsstand auf einen Blick erfassen kann.

#### Akzeptanzkriterien

1. WHEN ein Benutzer das Dashboard eines Haushaltsjahres aufruft, THE HH_Modul SHALL folgende Kennzahlen anzeigen: Gesamtbudget, Investiv gesamt, Konsumtiv gesamt, Anteil Investiv in Prozent.
2. WHEN ein Benutzer das Dashboard aufruft, THE HH_Modul SHALL Budgetsummen gruppiert nach Kostenstelle, nach Sachkonto und nach Priorität anzeigen.
3. WHERE die Ampelfunktion aktiviert ist, THE HH_Modul SHALL Budgetüberschreitungen visuell hervorheben.
4. THE HH_Modul SHALL den Anteil Investiv in Prozent als `(Investiv gesamt / Gesamtbudget) * 100` berechnen, sofern das Gesamtbudget größer als 0 ist.
5. IF das Gesamtbudget eines Haushaltsjahres gleich 0 ist, THEN THE HH_Modul SHALL den Anteil Investiv als 0 Prozent anzeigen.

---

### Anforderung 8: Budgetberechnung und Integration mit dem Bestellwesen

**User Story:** Als Leiter möchte ich das verfügbare Budget je Kostenstelle und Sachkonto einsehen, damit Bestellungen auf Basis verbindlicher Haushaltsdaten geprüft werden können.

#### Akzeptanzkriterien

1. THE HH_Modul SHALL für jede Kombination aus Haushaltsjahr, Kostenstelle und Sachkonto das verfügbare Budget als `Geplantes Budget − Summe der Bestellungen` berechnen.
2. WHEN das Bestellwesen eine Budgetabfrage stellt, THE HH_Modul SHALL die aktuellen Budgetdaten des genehmigten Haushaltsjahres lesend bereitstellen.
3. IF eine Bestellung das verfügbare Budget überschreitet, THEN THE HH_Modul SHALL einen Warnhinweis an das Bestellwesen zurückgeben.
4. WHILE ein Haushaltsjahr den Status `approved` hat, THE HH_Modul SHALL die Budgetdaten als verbindliche Grundlage für das Bestellwesen bereitstellen.

---

### Anforderung 9: Rechte- und Rollenkonzept

**User Story:** Als Leiter möchte ich Benutzern kostenstellenbezogene Rollen zuweisen, damit der Zugriff auf Haushaltsdaten kontrolliert und sicher ist.

#### Akzeptanzkriterien

1. THE HH_Modul SHALL vier Rollen unterstützen: `Leiter`, `Teamleiter`, `Mitarbeiter`, `Audit_Zugang`.
2. THE HH_Modul SHALL Rollen pro Benutzer und pro Kostenstelle (nicht global) vergeben.
3. WHEN ein Benutzer mit der Rolle `Leiter` agiert, THE HH_Modul SHALL Vollzugriff auf alle Kostenstellen, Haushaltsjahre und administrative Funktionen gewähren.
4. WHEN ein Benutzer mit der Rolle `Teamleiter` auf eine zugewiesene Kostenstelle zugreift, THE HH_Modul SHALL das Anlegen und Bearbeiten von Haushaltspositionen in Haushaltsjahren mit Status `draft` erlauben.
5. WHEN ein Benutzer mit der Rolle `Mitarbeiter` auf eine zugewiesene Kostenstelle zugreift, THE HH_Modul SHALL das Vorschlagen und Anlegen von Haushaltspositionen in Haushaltsjahren mit Status `draft` erlauben, jedoch kein Löschen genehmigter Positionen.
6. WHEN ein Benutzer mit der Rolle `Audit_Zugang` auf das Modul zugreift, THE HH_Modul SHALL ausschließlich Lesezugriff auf alle Daten gewähren.
7. IF ein Benutzer versucht, auf eine Kostenstelle zuzugreifen, für die er keine Rechte besitzt, THEN THE HH_Modul SHALL den Zugriff verweigern und eine Fehlermeldung zurückgeben.

---

### Anforderung 10: Änderungsprotokoll (Audit Trail)

**User Story:** Als Leiter oder Audit_Zugang möchte ich alle relevanten Änderungen an Haushaltsdaten nachvollziehen können, damit die Revisionssicherheit gewährleistet ist.

#### Akzeptanzkriterien

1. THE HH_Modul SHALL jeden Schreibvorgang auf Haushaltspositionen im Audit_Trail mit Benutzer, Datum/Uhrzeit, geändertem Feld, altem Wert und neuem Wert protokollieren.
2. THE HH_Modul SHALL Statusänderungen von Haushaltsjahren im Audit_Trail protokollieren.
3. THE HH_Modul SHALL Prioritätsänderungen und Betragsänderungen von Haushaltspositionen im Audit_Trail protokollieren.
4. THE HH_Modul SHALL Genehmigungsaktionen (Status → `approved`) im Audit_Trail protokollieren.
5. THE HH_Modul SHALL sicherstellen, dass Einträge im Audit_Trail nicht verändert oder gelöscht werden können.
6. WHEN ein Benutzer mit der Rolle `Audit_Zugang` oder `Leiter` das Änderungsprotokoll abruft, THE HH_Modul SHALL alle Einträge gefiltert nach Haushaltsjahr, Kostenstelle oder Zeitraum anzeigen.

---

### Anforderung 11: Exportfunktionen

**User Story:** Als Leiter oder Teamleiter möchte ich Haushaltsdaten exportieren, damit diese für externe Berichte und Haushaltsgespräche genutzt werden können.

#### Akzeptanzkriterien

1. THE HH_Modul SHALL den Export von Haushaltsdaten im Format Excel (XLSX) und PDF unterstützen.
2. WHEN ein Benutzer einen Export anfordert, THE HH_Modul SHALL folgende Felder je Haushaltsposition enthalten: Kostenstelle, Sachkonto, Kontotyp (investiv/konsumtiv), Projektname, Beschreibung, Betrag, Priorität, Kategorie, Wiederkehrend.
3. WHEN ein Benutzer einen Export anfordert, THE HH_Modul SHALL folgende Summen enthalten: Summe pro Kostenstelle, Summe pro Sachkonto, Investiv gesamt, Konsumtiv gesamt, Gesamtbudget.
4. IF ein Benutzer keinen Lesezugriff auf eine Kostenstelle hat, THEN THE HH_Modul SHALL die Positionen dieser Kostenstelle aus dem Export ausschließen.
