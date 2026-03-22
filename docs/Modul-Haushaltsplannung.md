Modulbeschreibung – Haushaltsplanung (HH)
IT-Cockpit
1. Ziel und Zweck des Moduls

Das Modul „Haushaltsplanung (HH)“ dient der strukturierten, transparenten und revisionssicheren Planung aller IT-bezogenen Haushaltsmittel im öffentlichen Dienst.

Es ermöglicht:

Planung von Projekten und laufenden Kosten

Mehrjahresplanung

Unterscheidung zwischen investiven und konsumtiven Mitteln

Versionierung für Haushaltsgespräche

Priorisierung von Maßnahmen

Budgetüberwachung als Grundlage für das Bestellwesen

Revisionssichere Archivierung genehmigter Haushalte

Kostenstellenbezogene Rechtevergabe

Nutzung durch mehrere Organisationseinheiten (z. B. IT, IP, weitere Bereiche)

Das Modul bildet damit die vollständige interne Haushaltsvorbereitung ab und dient als verbindliche Grundlage für Haushaltsgespräche und Budgetüberwachung.

2. Grundstruktur des Moduls
2.1 Haushaltsjahre

Für jedes Kalenderjahr wird ein eigenes Haushaltsjahr im System angelegt.

Jedes Haushaltsjahr besitzt folgende Status:

draft (Planungsphase)

preliminary (vorläufig nach Haushaltsgespräch)

approved (genehmigt und gesperrt)

Statuslogik
Status	Bearbeitbar	Beschreibung
draft	ja	Planung offen
preliminary	eingeschränkt	Nach Haushaltsgespräch
approved	nein	Revisionssicher gesperrt

Nach dem Status „approved“ sind keine Änderungen mehr möglich.

3. Organisationseinheiten
3.1 Kostenstellen

Kostenstellen repräsentieren organisatorische Einheiten.

Beispiele:

143011 – IUK

14925 – Telefonanlage

79100 – IUK-Schulen

Funktionen:

Anlegen neuer Kostenstellen

Bearbeiten bestehender Kostenstellen

Aktiv/Inaktiv setzen

Rechtevergabe pro Kostenstelle

Das System muss so gestaltet sein, dass zukünftig auch andere Bereiche (z. B. IP oder weitere Abteilungen) ihre eigenen Haushalte planen können.

3.2 Sachkonten

Sachkonten repräsentieren die finanzielle Zuordnung einer Ausgabe.

Beispiele:

0121002 – Software

52910070 – Wartung

Jedes Sachkonto besitzt folgende Eigenschaften:

Nummer

Bezeichnung

Kontotyp:

investiv

konsumtiv

Aktiv/Inaktiv

Sachkonten können angelegt, bearbeitet und verwaltet werden.

4. Haushaltspositionen

Eine Haushaltsposition besteht aus:

Haushaltsjahr

Kostenstelle

Sachkonto

Projektname

Beschreibung

Betrag (brutto pro Jahr)

Startjahr

Endjahr (optional)

Wiederkehrend (ja/nein)

Priorität (hoch/mittel/niedrig)

Kategorie:

Pflichtaufgabe

gesetzlich gebunden

freiwillige Leistung

Status:

geplant

angepasst

gestrichen

5. Mehrjahresplanung

Das Modul unterstützt echte Mehrjahresprojekte.

Optionen:

Variante A – Wiederkehrend

Wenn „wiederkehrend“ aktiviert ist:

Die Position wird automatisch in das nächste Haushaltsjahr übernommen.

Variante B – Laufzeit

Wenn Start- und Endjahr gesetzt sind:

Das System erzeugt automatisch Positionen für alle Jahre innerhalb der Laufzeit.

Beispiel:
Start: 2027
Ende: 2029
→ automatische Einträge für 2027, 2028, 2029

6. Versionierung

Jedes Haushaltsjahr kann mehrere Versionen besitzen.

Beispiele:

Entwurf IT

Nach 1. Haushaltsrunde

Final vor Beschluss

Beim Erstellen einer neuen Version:

werden alle bestehenden Positionen kopiert

die neue Version ist bearbeitbar

die alte Version bleibt unverändert

Dadurch ist jede Änderung nachvollziehbar.

7. Dashboard

Für jedes Haushaltsjahr wird eine Übersichtsseite bereitgestellt.

Anzeigen:

Gesamtbudget

Investiv gesamt

Konsumtiv gesamt

Anteil Investiv in Prozent

Summen pro Kostenstelle

Summen pro Sachkonto

Summen nach Priorität

Optional:

Ampelsystem bei Budgetüberschreitungen

8. Budgetberechnung

Für jede Kombination aus:

Haushaltsjahr

Kostenstelle

Sachkonto

wird automatisch berechnet:

Geplantes Budget
minus
Bestellungen (aus Modul Bestellwesen)
= Verfügbares Budget

Diese Berechnung ist Grundlage für die Budgetüberwachung.

9. Rechte- und Rollenkonzept

Das Rechtekonzept ist kostenstellenbezogen.

Das bedeutet:
Rechte werden nicht global vergeben, sondern pro Kostenstelle.

9.1 Rollen
1. Leiter (IT-Leitung)

Rechte:

Vollzugriff auf alle Kostenstellen

Haushaltsjahre anlegen

Versionen erstellen

Status ändern

Haushalt genehmigen

Haushalt sperren

Kostenstellen anlegen und verwalten

Sachkonten anlegen und verwalten

Exportfunktionen

Dashboard-Zugriff

Mehrjahresplanung verwalten

2. Teamleiter

Rechte:

Zugriff nur auf zugewiesene Kostenstellen

Haushaltspositionen anlegen

Haushaltspositionen bearbeiten (nur in draft)

Prioritäten setzen

Versionen innerhalb ihrer Kostenstellen bearbeiten

Dashboard-Zugriff für eigene Kostenstellen

Keine Rechte:

Haushaltsjahr genehmigen

Kostenstellen anlegen

Sachkonten anlegen

Status auf approved setzen

3. Mitarbeiter

Rechte:

Zugriff nur auf zugewiesene Kostenstellen

Haushaltspositionen vorschlagen oder anlegen

Bearbeitung nur in draft

Kein Löschen genehmigter Positionen

Optional:

Positionen nur mit Freigabe durch Teamleiter aktivieren

4. Audit-Zugang (Abteilungsleiter / Finanzbuchhaltung)

Rechte:

Lesender Zugriff auf alle Kostenstellen

Zugriff auf alle Haushaltsjahre

Zugriff auf Versionen

Zugriff auf Dashboard

Zugriff auf Exportfunktionen

Zugriff auf Änderungsprotokolle

Keine Bearbeitungsrechte.

10. Kostenstellenbezogene Rechte

Rechte können pro Benutzer pro Kostenstelle vergeben werden.

Beispiel:

Benutzer	Kostenstelle	Rolle
Max Mustermann	143011	Teamleiter
Anna Beispiel	79100	Mitarbeiter

So kann das System von mehreren Bereichen parallel genutzt werden.

11. Änderungsprotokoll (Audit Trail)

Jede Änderung wird protokolliert:

Benutzer

Datum/Uhrzeit

Feld

Alter Wert

Neuer Wert

Das gilt insbesondere für:

Betragsänderungen

Statusänderungen

Prioritätsänderungen

Genehmigungen

12. Exportfunktionen

Das System muss für Haushaltsgespräche eine strukturierte Liste erzeugen können mit:

Kostenstelle

Sachkonto

Investiv/Konsumtiv

Projekt

Beschreibung

Betrag

Priorität

Kategorie

Wiederkehrend

Mit Summen:

Pro Kostenstelle

Pro Sachkonto

Investiv gesamt

Konsumtiv gesamt

Gesamtbudget

Exportformate:

Excel

PDF

13. Integration mit Modul „Bestellwesen“

Das Haushaltsmodul ist die Planungsbasis.

Das Bestellwesen:

dokumentiert Bestellungen

greift lesend auf Budgetdaten zu

berechnet verbleibende Mittel

Bei Überschreitung:

Warnhinweis

optional Genehmigungsworkflow

14. Sperrlogik

Wenn ein Haushaltsjahr den Status „approved“ erhält:

alle Positionen werden gesperrt

keine Bearbeitung möglich

nur Lesemodus

Budget dient als verbindliche Grundlage

15. Zielarchitektur

Das Modul ist:

modular

rollenbasiert

kostenstellenbezogen

revisionssicher

mehrjahresfähig

skalierbar für weitere Organisationseinheiten

Es bildet eine vollständige interne Haushaltssteuerung für den öffentlichen Dienst.

Wenn du möchtest, kann ich dir als nächsten Schritt:

eine technische Datenbankstruktur vollständig ausformulieren

oder eine Umsetzungs-Roadmap für die Entwicklung erstellen

oder eine zweite MD für das Modul „Bestellwesen“ erstellen, passend zu diesem Konzept.