# Changelog

Änderungen an CIS Requests, für Benutzer verständlich zusammengefasst. Jede Version wird
beim Deploy hier ergänzt; Benutzer sehen die Punkte seit ihrer zuletzt gesehenen Version
automatisch als Hinweis beim nächsten Seitenaufruf.

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
