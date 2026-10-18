# Changelog

Änderungen an CIS Requests, für Benutzer verständlich zusammengefasst. Jede Version wird
beim Deploy hier ergänzt; Benutzer sehen die Punkte seit ihrer zuletzt gesehenen Version
automatisch als Hinweis beim nächsten Seitenaufruf.

## 0.15.1.0 — 2026-10-18
- Workflow-Maßnahme „E-Mail senden“: Empfänger können jetzt auch Rollen sein (alle Mitglieder der Rolle erhalten die E-Mail), zusätzlich zu Benutzern, Gruppen und externen Adressen. Mehrere Empfänger je Maßnahme sind möglich, doppelte Adressen werden zusammengefasst

## 0.15.0.0 — 2026-10-18
- Workflow: neue Option „Tabs verbergen“ je Schritt – hier legst du fest, welche Tabs der Projektansicht (Produkte, Ausschreibung, Angebote, Bestellung, Wareneingang, Lager) auf diesem Schritt zu sehen sind. Tabs, die logisch noch nicht sichtbar sein können (Meilenstein noch nicht erreicht, Modul inaktiv, Fahrzeugausschreibung ohne Ausschreibungs-Tab), sind ausgegraut und nennen den Grund. Mindestens ein Tab bleibt immer sichtbar; verborgene Tabs lassen sich nicht als Standard-Tab wählen

## 0.14.2.0 — 2026-10-18
- Fixierte Projekte sind ausnahmslos gesperrt: Produkte, Ausschreibungstext und Fahrzeug-Konfiguration lassen sich nach der Fixierung von niemandem mehr ändern, auch nicht von Administratoren. Bearbeiten ist erst wieder möglich, wenn die Fixierung durch Zurücksetzen des Status aufgehoben wird (das dürfen weiterhin nur die im Workflow unter „Fixierung aufheben“ hinterlegten Personen)

## 0.14.1.0 — 2026-10-18
- Export-Import → Vorlagen: die Vorlagen sind jetzt in eigene Tabs gegliedert – „Vor der Ausschreibung“, „Nach der Ausschreibung / Auswertung“ und „Systemdaten“ (mit Anzahl je Tab)

## 0.14.0.0 — 2026-10-18
- Import / Export steht jetzt als Tab „Import / Export“ im Menüpunkt „Export-Import“ (neben „Vorlagen“) statt als eigener Menüeintrag
- Neue Datenkategorien für Import/Export mit umfangreichen Feldern und je einer Standardvorlage „… – alle Felder“: Benutzer (inkl. Rollen/Gruppen), Rollen (inkl. Mitgliedern und Berechtigungen), Gruppen, Projekte (inkl. Ausschreibungszeitraum, Verantwortliche, Kostenschätzung), Lagerorte und Lagerbestand (nur Export); Produkte wurden um weitere Felder ergänzt (Unterprodukte, Beschreibungstext, Preisverlauf u.a.). Jede Zeile wird über den Import-Key zugeordnet, ein Import zeigt zuerst alle Änderungen und Fehler und schreibt erst nach „Übernehmen“. Neue Benutzer lassen sich nicht importieren, der Projektstatus nur exportieren
- Die Feldauswahl gruppiert nach Herkunft (Benutzer, Rollen, Gruppen, Projekte, Lager, Produkte, Kategorien, Quellen, Preise)
- Bugfix Trigger: Ausschreibungsbeginn/-ende wurden als UTC statt als Ortszeit gewertet – ein Beginn „20:22“ galt erst um 22:22 Uhr. Eingabe und Anzeige erfolgen jetzt in Ortszeit (Europa/Berlin, einstellbar über APP_DISPLAY_TIMEZONE)
- Trigger: Maßnahmen wie „Springen zu Schritt“, die ohne weitere Angaben gültig sind, sind nach dem Ablegen sofort aktiv (bisher erst nach Speichern im Editor); ein Trigger ohne aktive Maßnahme gilt nicht mehr als „erledigt“
- Entwicklung: E-Mails lassen sich lokal mit Mailpit/Mailhog prüfen (SMTP 127.0.0.1:1025, Oberfläche http://localhost:8025)
- Sicherheit: Zellen, die mit „=“ oder „@“ beginnen, werden im Excel-/CSV-Export als Text gesichert (keine Formel-Injektion über Namen)

## 0.13.0.0 — 2026-10-17
- Neu: Projekte haben eine „Ausschreibungsdauer“ mit Beginn und Ende (Datum und Uhrzeit), pflegbar beim Anlegen/Bearbeiten, sichtbar im Projektkopf und als Variablen für E-Mails (Ausschreibungsbeginn/-ende). So lässt sich eine Ausschreibung vorbereiten, die sich selbst startet und beendet
- Workflow: neben Maßnahmen gibt es jetzt Trigger. Ein Trigger wird per Drag&Drop an einen Schritt gehängt und löst eigene Maßnahmen aus, sobald das Ereignis eintritt, während ein Projekt auf diesem Schritt steht. Verfügbar: „Ausschreibungszeitraum hat begonnen“ und „Ausschreibungszeitraum ist geendet“. Weitere Ereignisse lassen sich künftig über einen zentralen Event-Manager ergänzen
- Neue Maßnahme „Springen zu Schritt“ (nächster, vorheriger oder bestimmter Schritt) – der Wechsel erfolgt automatisch ohne Freigabe und erscheint im Verlauf als „automatisch“. Die Maßnahme „E-Mail senden“ funktioniert auch an Triggern
- Die Checkbox „Automatisch vorrücken, sobald die Fälligkeit überschritten ist“ entfällt; bestehende Einstellungen wurden in einen Trigger „Ausschreibungszeitraum ist geendet → Springen zum nächsten Schritt“ umgewandelt, das bisherige Fälligkeitsdatum als Ausschreibungsende (23:59 Uhr) übernommen. Der Projektverlauf zeigt anstehende automatische Wechsel mit Uhrzeit
- Einstellungen → Planer (Cron): Statusanzeige (läuft regelmäßig/verzögert/läuft nicht), letzte Ausführungen, einzurichtender Cron-Eintrag und „Jetzt ausführen“. Nur in der Entwicklungsumgebung gibt es zusätzlich eine Cron-Trigger-URL. Für zeitgenaue Trigger muss der Server jede Minute `php artisan schedule:run` ausführen; ohne Cron werden fällige Trigger beim Öffnen von Projektliste/Projekt nachgeholt
- Neue Berechtigung „Planer (Cron) einsehen“ (Administratoren haben sie automatisch)

## 0.12.0.2 — 2026-10-17
- Bugfix: Workflow – „Eigener Schritt“ ließ sich nicht hinzufügen („Der letzte Schritt muss Abgeschlossen sein“). Eigene Schritte werden jetzt direkt hinter dem gewählten Schritt (sonst vor „Abgeschlossen“) eingefügt und lassen sich anschließend frei verschieben, z.B. als reiner Auslöser für eine E-Mail-Maßnahme

## 0.12.0.1 — 2026-10-17
- Bugfix: Angebote vergleichen zeigte „Diesem Projekt sind noch keine Produkte zugeordnet“ und versteckte damit vorhandene Angebote, wenn ein Projekt nur Set-Produkte (bzw. nicht ausschreibungsrelevante Positionen neben Sets) enthielt – deren Mitgliedsprodukte werden jetzt korrekt verglichen

## 0.12.0.0 — 2026-10-17
- Neu: Import / Export (Menü „Module“) – Systemdaten als Excel-/CSV-Tabelle exportieren und geänderte Tabellen wieder einlesen. Erste Datengrundlage: Produkte (Name, Set, Kategorie, feste Quelle, Preis inkl. Preisquelle). Ein Import prüft zuerst alle Zeilen (Vorschau mit Änderungen und Fehlern) und schreibt erst nach „Übernehmen“; Preisänderungen werden als neuer Preis mit Historie angelegt, neue Produkte werden nur auf Wunsch angelegt
- Vorlagen unter „Export-Import“ haben jetzt eine Datengrundlage: Projekt-Positionen (wie bisher, mit Filtern/Sortierung, Anzeige im Dokumentenmanager) oder Systemdaten wie „Produkte“ (für das Import-/Export-Werkzeug). Die Feldauswahl zeigt je Datengrundlage die passenden Felder mit Herkunft (Produkte, Kategorien, Quellen, Preise)
- Neue Berechtigung „Import-/Export-Werkzeug nutzen“ (Administratoren haben sie automatisch)

## 0.11.0.0 — 2026-10-17
- Export-Import: Bugfix – beim Anlegen einer Vorlage spiegelten sich die Namensfelder der beiden Bereiche („Vor der Ausschreibung“ / „Nach der Ausschreibung“) gegenseitig
- Der Import-Key (technische Spalte „_ImportKey“) wird jetzt je Vorlage unter „Bearbeiten“ → „Import“ festgelegt; die bisherige „Standard“-Markierung entfällt. Bestehende Vorlagen mit Händler-Preisfeld behalten ihren Import-Key; der Preis-Import im Angebotsvergleich bietet Vorlagen mit Händler-Preisfeld und Import-Key an
- Neu: je Vorlage lässt sich unter „Anzeigen in“ festlegen, wo sie angeboten wird (aktuell: Projekt → Dokumentenmanager). Vorlagen ohne Freigabe erscheinen nirgends; bestehende bleiben im Dokumentenmanager
- Feldauswahl beim Hinzufügen einer Spalte ist jetzt ein Dropdown mit Suche und Filter nach Herkunft (z.B. „Projekte (Materialausschreibung)“, „Allgemein“); die Herkunft wird auch in der Spaltenliste angezeigt

## 0.10.0.4 — 2026-10-17
- Ausschreibung: die visuelle Vorschau hat jetzt oben und unten einen Seitenrand wie ein echtes Blatt (Logo-Kopf und Fußzeile kleben nicht mehr am Rand)

## 0.10.0.3 — 2026-10-17
- Bugfix: im Projektkopf überlappten bei schmalen Fenstern die Status-Badges (z.B. „Fixiert“) die Angaben rechts daneben; der Kopf bricht jetzt sauber um

## 0.10.0.2 — 2026-10-17
- Bugfix: die Schrittanzeige im „Projektverlauf“ war oben und seitlich abgeschnitten (Fixierungs-Symbol, Markierung des aktuellen Schritts, lange Schrittnamen wie „Ausschreibungsentwurf“)

## 0.10.0.1 — 2026-09-27
- Bugfix: im Workflow-Bildschirm ließ sich die Maßnahme „E-Mail senden“ nicht per Drag&Drop auf einen Schritt ziehen (fehlerhafte Anführungszeichen verhinderten die Initialisierung)
- Die Maßnahmen eines Schritts werden jetzt direkt auf dessen Karte in der Zeitleiste angezeigt und dorthin gezogen, statt im weiter entfernten Detail-Bereich

## 0.10.0.0 — 2026-09-27
- Workflow-Maßnahmen (Phase 2): im Workflow-Bildschirm lässt sich jedem Schritt per Drag&Drop die Maßnahme "E-Mail senden" hinzufügen – Empfänger frei aus Systembenutzern, Gruppen oder externen E-Mail-Adressen, Betreff und Text mit einfügbaren Projektvariablen (Projektname, Auftraggeber, neuer/vorheriger Status, Fälligkeitsdatum, Kostenschätzung, u.a.)
- Eine Maßnahme feuert automatisch, sobald ein Projekt den zugehörigen Schritt per Vorrücken erreicht; ein fehlgeschlagener E-Mail-Versand blockiert dabei nie die eigentliche Statusänderung
- Die Bestätigung beim Vorrücken zeigt jetzt an, welche Maßnahmen dabei ausgeführt werden

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
