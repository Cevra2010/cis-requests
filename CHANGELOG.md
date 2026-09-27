# Changelog

Änderungen an CIS Requests, für Benutzer verständlich zusammengefasst. Jede Version wird
beim Deploy hier ergänzt; Benutzer sehen die Punkte seit ihrer zuletzt gesehenen Version
automatisch als Hinweis beim nächsten Seitenaufruf.

## 0.9.0.0 — 2026-09-27
- Neuer Bereich „Workflow“ (im Menü direkt unter „Dashboard“): der Projektverlauf ist jetzt je Projekttyp (Produkt-/Fahrzeugausschreibung) frei konfigurierbar – Schritte hinzufügen, umbenennen, deaktivieren oder entfernen (z.B. „Bereit zur Ausschreibung“ überspringen), dabei sind nur logisch sinnvolle Reihenfolgen erlaubt
- Wer einen Projektschritt freigeben darf, wird nicht mehr über die klassische Rechteverwaltung geregelt, sondern direkt im Workflow-Bildschirm je Schritt festgelegt (Benutzer, Gruppen oder Rollen) – bestehende Freigabe-Rechte wurden beim Update automatisch übernommen
- Der Projektverlauf im Projekt selbst ist jetzt ein zusammenhängender Wizard statt einzelner Buttons, inkl. Verlaufsanzeige (wer hat wann welchen Schritt gesetzt)
- Bugfix: Projekte ließen sich bislang über das Bearbeiten-Formular auf einen beliebigen Status setzen, ohne dass die dafür nötige Freigabe geprüft wurde

## 0.8.0.0 — 2026-09-26
- Wareneingang: neuer Buchungsablauf – erfasste Positionen sind zunächst nur vorgemerkt: vollständig erfasste rutschen in „Offen“ mit grünem Rahmen nach unten, erst „Jetzt buchen“ finalisiert die Buchung (inkl. Einlagerung in den zuvor gewählten Ziel-Lagerort, der je Position beim Erfassen gemerkt wird) und verschiebt sie nach „Abgeschlossen“
- Die Gesamtübersicht zeigt alle Positionen mit aktuellem Stand, vorgemerkte Positionen sind als „noch nicht gebucht“ gekennzeichnet

## 0.7.0.0 — 2026-09-26
- Lager: Lagerorte haben jetzt einen Typ (Name + Kürzel, z.B. Halle „H“, Regal „Reg“), pflegbar unter „Ordnung“ → „Lagerort-Typen“; der Standardtyp „Standard“ (S) existiert immer, mindestens ein Typ bleibt erhalten
- Jeder Lagerort erhält eine ID aus der Hierarchie (z.B. H1R2SC3-F4), sichtbar im Lagerort-Baum, im Projekt-Tab und auf dem QR-Etikett
- Projekt-Tab „Lager“: Zuteilung erfolgt rekursiv (nur unterste Elemente, z.B. Gitterboxen), Anzeige als Tabelle mit QR-Code-Ansicht; der Tab steht jetzt ganz vorne und ist von Projektbeginn an verfügbar

## 0.6.0.0 — 2026-09-15
- Lager-Warenübersicht (Hauptmenü "Lager" und neu auch im Projekt-Tab "Lager") nutzt jetzt dieselbe Tabelle mit Spalten-Filtern, Sortierung und Pagination wie Benutzer/Produkte – inkl. Filtern nach Lagerort
- Projekt-Tab "Lager" zeigt jetzt direkt die Produkte mit aktuellem Lagerort in dieser Tabelle; der Tab steht in der Reihenfolge jetzt vor "Wareneingang"
- Im Wareneingang sind als Ziel-Lagerort (Auswahl wie auch QR-Scan) nur noch die dem Projekt zugeteilten Lagerorte wählbar; wird per QR-Code ein Lagerort gescannt, der dem Projekt nicht zugeteilt ist, erscheint der Hinweis "Lagerort nicht dem Projekt zugeteilt" statt ihn stillschweigend zu übernehmen
- Neu: Lagerort direkt per QR-Scan in der Warenübersicht nachschlagen ("Lagerort scannen") – zeigt sofort, welche Produkte dort aktuell eingelagert sind
- "X im Lager eingebucht" heißt jetzt "X in <Lagerort> eingebucht"
- QR-Etikett (PDF) bietet jetzt drei Größen (groß/mittel/klein, klein max. 4 cm) zur Auswahl auf einem Blatt, jede mit eigenem Rahmen zum Ausschneiden und dem Lagerort-Namen im Klartext

## 0.5.1.0 — 2026-09-14
- Wareneingang: neuer Ziel-Lagerort-Bereich oben in der Checkliste (Auswahl oder QR-Scan) – solange gesetzt, wird jede erfasste Menge automatisch dorthin eingebucht, statt es für jede Position einzeln bestätigen zu müssen; das Ziel lässt sich während der Erfassung jederzeit wechseln (z.B. anderen QR-Code scannen), falls eine Lieferung woanders hin soll

## 0.5.0.0 — 2026-09-14
- Lagerorte können jetzt QR-Etiketten ausdrucken (Baum unter Ordnung → Lagerort → QR-Symbol) und per Handy-Kamera gescannt werden – sowohl beim Einbuchen im Wareneingang als auch bei "Ware buchen" und beim Verschieben in der Warenübersicht
- Neu: beim Hinzufügen eines Produkts zu einem Projekt wird angezeigt, wenn bereits nicht zugeordneter Lagerbestand vorhanden ist ("X auf Lager") – Wahl zwischen "Ausschreiben" und "Aus Lager beziehen"; aus Lager bezogene Positionen werden regulär geplant, aber nicht ausgeschrieben, nicht in die Kostenschätzung, den Angebotsvergleich, die Bestellzuordnung oder Tabellenexporte übernommen

## 0.4.0.0 — 2026-09-14
- Neues, optionales Modul "Lager": eigener Menüpunkt, Lagerort-Baum (beliebig tief, z.B. Lager → Raum → Regal → Fach) unter "Ordnung" verwaltbar
- Im Wareneingang kann eine erfasste Menge jetzt direkt einem Lagerort zugewiesen werden ("Im Lager einbuchen")
- Neue Seite "Ware buchen": erst Lagerort wählen, dann alle noch nicht eingelagerten Wareneingangspositionen einbuchen
- Neue Warenübersicht: Bestand je Produkt/Lagerort/Projekt, filterbar, Verschieben zwischen Lagerorten (einzeln oder im Block), Freigeben (Projektzuordnung aufheben)
- Projekten lassen sich jetzt Lagerorte zuweisen (neuer Projekt-Tab "Lager"), inkl. Übersicht des für das Projekt reservierten Bestands
- "Interne Beschaffung" heißt jetzt "Nicht ausschreibungsrelevante Produkte" und ist in der Bestellung ausklappbar (standardmäßig eingeklappt)

## 0.3.2.0 — 2026-09-14
- Dokumentennamen im Dokumentenmanager deutlich kürzer: der Projektname steht nicht mehr im Dateinamen (unnötig, man befindet sich ja bereits im jeweiligen Projekt)
- Neu: über der Dateiliste im Dokumentenmanager nach Dateiformat filtern (z.B. nur PDF, oder PDF und CSV zusammen) – kombinierbar mit der Verzeichnisauswahl

## 0.3.1.0 — 2026-09-14
- Bestelllisten je Anbieter stehen jetzt zusätzlich zur PDF auch als Excel-/CSV-Tabelle zur Verfügung (im Dokumentenmanager und direkt in der Bestellung)

## 0.3.0.0 — 2026-09-14
- Export-Vorlagen jetzt klar in "Vor der Ausschreibung" und "Nach der Ausschreibung/Auswertung" eingeteilt, mit frei kombinierbaren Filtern (Produktkategorie, feste Produktquelle, Ausschreibungsrelevanz, Set-Produkt) und Sortierung je Vorlage
- Export-Tab im Projekt entfernt – alle Exporte (PDF + Tabellen) stehen nur noch im Dokumentenmanager, dafür übersichtlich nach Verzeichnissen "Allgemein", "Ausschreibung" und "Bestellung" sortiert
- Bestelllisten (je Anbieter) und Materialanforderungen (je interner Quelle) stehen jetzt einzeln je Anbieter/Quelle als eigenes Dokument im Dokumentenmanager zur Verfügung, statt zusammengefasst
- Dokumente heißen im Dokumentenmanager jetzt einheitlich "Projektname-Dokumentenname"; beim Herunterladen wird zusätzlich Datum und Uhrzeit vorangestellt
- Produktquellen-Übersicht zeigt jetzt auch, ob eine Quelle ausschreibungsrelevant ist
- Bugfix: Ein leeres Suchfeld beim Anlegen eines verknüpften Produkts führte zu einem Fehler statt einer Meldung
- Bugfix: Die Update-Notes konnten höher als der Bildschirm werden und ließen sich dann nicht mehr vollständig lesen

## 0.2.0.0 — 2026-09-06
- Kategorie-Auswahl (bei Projekt/Produkt anlegen & bearbeiten) ist jetzt durchsuchbar statt eines klassischen Dropdowns
- Set-Produkte sind jetzt auch bei der Produktauswahl im Projekt als "Set" gekennzeichnet
- "Hausintern" abgelöst durch neue, quellenbasierte Regel: Produkte können fest einer Produktquelle zugeordnet werden; Quellen können als "nicht ausschreibungsrelevant" markiert werden (z.B. eine eigene Werkstatt) – deren Produkte werden weiterhin geplant, erscheinen aber nicht auf der Ausschreibung, im Angebotsvergleich oder in der Bestellzuordnung
- Neu: Materialanforderungs-PDF für nicht-ausschreibungsrelevante Positionen, automatisch im Dokumentenmanager verfügbar
- Neu: eigener Wareneingang-Bereich für interne Quellen
- Export-Vorlagen können jetzt gezielt auch nicht-ausschreibungsrelevante Positionen einschließen (z.B. für eine interne Materialliste)

## 0.1.3.1 — 2026-09-05
- Bugfix: Hochgeladene Dokumente erscheinen jetzt sowohl unter "Uploads" als auch im passenden Dateiformat-Verzeichnis (z.B. PDF-Dateien) statt nur in einem davon

## 0.1.3.0 — 2026-09-05
- Dokumentenmanager: Ausschreibungs-PDF, alle Tabellen-Exporte und eine neue Projektübersicht als PDF stehen jetzt automatisch als Dokument zur Verfügung (werden bei Bedarf frisch erzeugt)
- Neue Projektübersicht (PDF) mit Stammdaten, Produktliste und Kostenschätzung

## 0.1.2.0 — 2026-09-04
- Dokumentenmanager überarbeitet: Verzeichnisse links (Alle, Tabellen, PDF-Dateien, Uploads), Dateiliste rechts
- Dateien lassen sich jetzt auch per Drag & Drop hochladen (mehrere gleichzeitig möglich)
- Versionsnummer im Sidebar-Footer bei "Mein Konto" – anklickbar für die komplette Update-Historie

## 0.1.1.0 — 2026-09-02
- Angebote importieren: Preise aus einer ausgefüllten Export-Liste automatisch einlesen (Export-Modul heißt jetzt "Export-Import")
- Neuer Dokumentenmanager je Projekt (Kopfbereich → "Dokumentenmanager")
- Setprodukte: mehrfach verwendete Bestandteile werden in der Ausschreibung jetzt korrekt zusammengezählt statt doppelt zu erscheinen
- Neues Filter- und Sortiersystem für Projekte, Produktdatensätze, Produktquellen, Benutzer und Gruppen (Filter-Symbol je Spalte, Einstellungen werden gemerkt)
- "Kategorien" heißt jetzt "Ordnung" und lässt sich per Ziehen neu anordnen oder verschieben (statt einer freien Nummer)
- Die grobe Kostenschätzung ist jetzt auch im Produkte-Tab eines Projekts sichtbar und aktualisiert sich sofort
