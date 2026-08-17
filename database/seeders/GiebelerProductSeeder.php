<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GiebelerProductSeeder extends Seeder
{
    public function run(): void
    {
        // ── Quelle anlegen ────────────────────────────────────────────────────
        $sourceId = (string) Str::uuid();
        DB::table('product_sources')->insertOrIgnore([
            'cis_row_id'    => $sourceId,
            'name'          => 'Giebeler – Der Fachlieferant',
            'url'           => 'https://giebeler.gfd-katalog.de',
            'contact_name'  => null,
            'contact_email' => null,
            'contact_phone' => null,
            'notes'         => 'Import aus GFD-Katalog (Testdaten)',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // ── Produktliste ──────────────────────────────────────────────────────
        // Format: [name, beschreibung, preis_netto, unterprodukte[]]
        $products = [

            // ── Einsatzjacken ─────────────────────────────────────────────────
            ['TEXPORT® Fire Max 3 Einsatzjacke', 'Dreilagige Einsatzjacke nach EN 469:2020 mit verbesserter Atemaktivität. Außenmaterial: NOMEX® NXT, Membran: CROSSTECH® BLACK. Reflexstreifen nach EN ISO 20471.', 689.00],
            ['TEXPORT® Hurricane Einsatzjacke', 'Leichte zweilagige Einsatzjacke für den Innenangriff. Hoher Tragekomfort durch anatomischen Schnitt. Außenmaterial PBI® Matrix.', 549.00],
            ['TEXPORT® Top Protect Einsatzjacke', 'Robuste Einsatzjacke für technische Hilfeleistung und Brandbekämpfung. Material: GORE-TEX® Feuerwehr, Außenstoff: KEVLAR®/NOMEX®-Mischgewebe.', 749.00],
            ['ROSENBAUER VANCOUVER Einsatzjacke', 'Feuerwehr-Einsatzjacke nach EN 469 Leistungsstufe 2. Außenmaterial: NFPA-zertifiziertes Gewirke. Integrierte Belüftungsöffnungen.', 620.00],
            ['ROSENBAUER HAMBURG Einsatzjacke', 'Komfortable Einsatzjacke für den städtischen Brandeinsatz. Druckknopfverschlüsse aus Edelstahl, verstärkte Ellbogen und Schultern.', 598.00],
            ['WATEX Guard I Einsatzjacke', 'Modulare Einsatzjacke mit herausnehmbarem Wärmeschutzfutter. EN 469:2020 Leistungsstufe 2, EN ISO 11612.', 572.00],
            ['WATEX Guard II Einsatzjacke THL', 'Einsatzjacke für technische Hilfeleistung mit erhöhtem Schnittschutz. Verstärkte Knie- und Ellenbogenpartien, robuste YKK-Reißverschlüsse.', 612.00],
            ['NTI® FW2 Einsatzjacke', 'Leistungsstarke Schutzjacke nach EN 469, gefertigt aus hochwertigem GORE-TEX® Membrane-Material. Optimale Wärmeregulierung durch Ventilationsklappen.', 680.00],
            ['NTI® FW3 Plus Einsatzjacke', 'Premium-Einsatzjacke der Leistungsstufe 2 mit PBI®-Außenmaterial. Besonders hitzebeständig, für den extremen Innenangriff konzipiert.', 790.00],
            ['NOVOTEX-ISOMAT EJ3 Einsatzjacke', 'Dreilagige Einsatzjacke mit CROSSTECH®-Membran. Robuster Außenstoff NOMEX®/KEVLAR®, reflektierende 3M™ Scotchlite™ Streifen.', 665.00],

            // ── Einsatzhosen ─────────────────────────────────────────────────
            ['TEXPORT® Fire Max 3 Einsatzhose', 'Dreilagige Einsatzhose passend zur Fire Max 3 Jacke. EN 469:2020 Leistungsstufe 2. Hosenträger verstellbar, großzügige Knie- und Gesäßtaschen.', 589.00],
            ['TEXPORT® Hurricane Einsatzhose', 'Leichte Einsatzhose mit GORE-TEX®-Membran. Passform-optimiert für freie Beweglichkeit im Einsatz.', 499.00],
            ['ROSENBAUER VANCOUVER Einsatzhose', 'Feuerwehrhose zur VANCOUVER-Jacke. Elastischer Bund, Hosenträger, verstärkte Kniepartien mit Knieschutz-Taschen.', 540.00],
            ['WATEX Guard I Einsatzhose', 'Feuerwehreinsatzhose mit herausnehmbarem Wärmefutter. Komfortabler Schnitt, integrierte Hosenträger.', 510.00],
            ['NTI® FW2 Einsatzhose', 'Einsatzhose EN 469 Leistungsstufe 2. PBI®-Außenmaterial, CROSSTECH®-Membran, verstellbare Hosenträger.', 598.00],
            ['NOVOTEX-ISOMAT EH3 Einsatzhose', 'Feuerwehrhose EN 469:2020. Passend zur EJ3-Jacke. Seitliche Reißverschlusstaschen, Knieschütztaschen.', 575.00],

            // ── Feuerwehrhelme ───────────────────────────────────────────────
            ['SCHUBERTH F220 Feuerwehrhelm', 'Strukturbrandbekämpfungshelm nach EN 16471 und EN 16473. Glasfaser-Schale, integriertes Visier, Kinnriemen mit Schnellverschluss. Inklusive Nackenschutz.', 310.00],
            ['SCHUBERTH F270 Feuerwehrhelm', 'Premium-Feuerwehrhelm mit integriertem Gesichtsschutz. Aramid-Schale, Innenfutter waschbar, kompatibel mit Atemschutzmasken DRÄGER und MSA.', 420.00],
            ['DRÄGER HPS® 7000 Feuerwehrhelm', 'Feuerwehrhelm nach EN 443:2008. Hochfeste Kunststoffschale, integrierter Gehörschutz, Schnellentriegelungssystem. Gewicht ca. 1.450 g.', 395.00],
            ['DRÄGER HPS® 6200 Feuerwehrhelm', 'Leichter Strukturbrandhelm mit verbessertem Tragekomfort. Kompatibel mit HPS® Atemschutzmasken. Belüftungskanäle reduzieren Wärmestau.', 345.00],
            ['MSA GALLET F1 XF Feuerwehrhelm', 'Bewährter Feuerwehrhelm aus der GALLET-Familie. EN 443:2008, Thermoplastschale, stufenloser Nackenbügel, integriertes Visier.', 289.00],
            ['ROSENBAUER HEROS-xtreme Feuerwehrhelm', 'Hochleistungshelm für extremen Brandeinsatz. Aramidfaser-Schale, Memory-Schaum-Innenausstattung, klappbares Visier, Kinnriemen mit Schnellverschluss.', 430.00],
            ['ROSENBAUER HEROS-titan Feuerwehrhelm', 'Leichter Kompakthelm aus Titan-Verbundmaterial. Für technische Hilfeleistung und Brandbekämpfung. Integrierter Nackenschutz, Gewicht ca. 1.250 g.', 510.00],

            // ── Feuerwehrhandschuhe ──────────────────────────────────────────
            ['SEIZ Feuerwehr-Einsatzhandschuh Fire III', 'Feuerwehrhandschuh nach EN 659:2003. Außenmaterial: Leder, Membran: GORE-TEX®. Hervorragende Griffigkeit, verstärkter Handballen.', 89.00],
            ['SEIZ Feuerwehr-Einsatzhandschuh Fire Grip', 'Feuerwehrhandschuh mit optimierter Griffigkeit für die technische Hilfeleistung. EN 659, langer Stulpen-Stichschutz.', 79.00],
            ['DRÄGER DRÄGON Feuerwehrhandschuh', 'Robuster Feuerwehrhandschuh für den Innenangriff. Innenfutter aus GORE-TEX®, Außenmaterial Kernleder. Schnittsicher nach EN 388.', 95.00],
            ['HONEYWELL First Responder Handschuh', 'Handschuh für technische Hilfeleistung und Brandbekämpfung. Schnittschutz Klasse B, EN 388, EN 407. Beidseitig tragbar.', 72.00],
            ['MAPA Temp-Tec 950 Hitzeschutzhandschuh', 'Aluminisierter Hitzeschutzhandschuh für extreme Temperaturen bis 250 °C Kontakthitze. Strahlungshitze bis 1.000 °C. Fünffingrig.', 68.00],
            ['gfd® Feuerwehr-Einsatzhandschuh Standard', 'Preiswerter Feuerwehrhandschuh nach EN 659. Rindspaltleder außen, Baumwollinnenfutter, verstärkte Fingerkuppen.', 44.00],

            // ── Feuerwehrstiefel ─────────────────────────────────────────────
            ['HAIX® FIRE EAGLE Feuerwehrstiefel', 'Feuerwehrstiefel nach EN 15090:2012 Typ 2. Wasserdichte GORE-TEX®-Auskleidung, Stahlkappe, antistatisch. Schnürstiefel mit Reißverschluss.', 349.00],
            ['HAIX® FIRE HERO 2 Feuerwehrstiefel', 'Hochwertiger Feuerwehrstiefel mit Schafthöhe 30 cm. GORE-TEX® Performance Comfort Futter, Stiefelkalibrierung 46 EN 15090.', 389.00],
            ['HAIX® CONNEXIS® AIR 2.0 Halbschuh', 'Leichter Einsatzhalbschuh für technische Hilfeleistung. Atmungsaktiv, antistatisch, EN ISO 20345 S3. Gewicht ca. 440 g.', 249.00],
            ['ROSENBAUER MANHATTAN Feuerwehrstiefel', 'Feuerwehrstiefel EN 15090:2012, Lederschaft, SYMPATEX®-Membran, auswechselbares Fußbett, Gleitschutz-Profilsohle.', 319.00],
            ['JOLLY FIREFIGHTER G/03 Feuerwehrstiefel', 'Gummistiefel für den Feuerwehreinsatz. EN 15090 Typ 1, Stahlkappe, Stahl-Zwischensohleinlage, antistatisch.', 189.00],
            ['RANGER Top Dry Feuerwehrstiefel', 'Feuerwehrstiefel mit herausnehmbarer Wärmeisolierung. GORE-TEX® Lining, CE EN 15090, Schnellverschlusssystem.', 298.00],

            // ── Atemschutz ───────────────────────────────────────────────────
            ['DRÄGER PSS® 7000 Atemschutzgerät', 'Pressluftatmer nach EN 137:2006. 6,8-Liter-Kohlefaser-Druckbehälter (300 bar), digitales Manometer, Alarmgeberfunktion. Tragegewicht ca. 12,5 kg.', 3290.00],
            ['DRÄGER PSS® AirBoss Atemschutzgerät', 'Leichtbau-Pressluftatmer mit ergonomischem Tragegestell. Composit-Druckbehälter, elektronische Restdruckanzeige, kompatibel mit DRÄGER-Masken.', 2890.00],
            ['MSA AirHawk® II Atemschutzgerät', 'Pressluftatmer nach EN 137. Leichtes Tragegestell, 6,8-Liter-CFK-Flasche, digitale Druckanzeige, kompatibel mit MSA Ultra Elite® Maske.', 3150.00],
            ['DRÄGER Panorama Nova® Atemschutzmaske', 'Vollmaske nach EN 136. Panorama-Sichtscheibe für optimales Sichtfeld, Innenmaske aus Silikon, RD40-Anschluss, Stimmübertragungsmembran.', 289.00],
            ['MSA Ultra Elite® Atemschutzmaske', 'Atemschutz-Vollmaske nach EN 136. Einlinsenscheibe gehärtet, Silikon-Innenmaske, Stimme-Membran, leichtes Kopfgeschirr.', 249.00],
            ['DRÄGER X-plore® 6300 Halbmaske', 'Mehrweg-Halbmaske mit automatischer Anpassung. EN 140. Kompatibel mit DRÄGER-Filterpatronen. Waschbar bis 60 °C.', 89.00],
            ['Atemschutzmaske-Prüfgerät Portacount®', 'Dichtheitsprüfung für Atemschutzmasken nach DGUV. Quantitativer Fit-Test, Protokolldrucker, Software-Schnittstelle.', 4200.00],
            ['DRÄGER Pressluftflasche 6,8 l CFK 300 bar', 'Kohlefaser-Druckbehälter für Pressluftatmer. 6,8 Liter, 300 bar, TÜV-geprüft, kompatibel mit DRÄGER PSS® und MSA AirHawk®.', 590.00],

            // ── Hydraulische Rettungsgeräte ──────────────────────────────────
            ['LUKAS e³ CONNECT Rettungsschere E131', 'Elektrisch-hydraulische Rettungsschere. Schneidkraft 1.050 kN, Messeröffnung 220 mm, Gewicht 11,5 kg. Akku-betrieben, kabellos.', 8900.00],
            ['LUKAS e³ CONNECT Spreizer SP310', 'Elektrisch-hydraulischer Spreizer. Spreizlast 156 kN, Öffnung 800 mm, Gewicht 14,2 kg. Akku-System kompatibel mit LUKAS e³-Linie.', 9200.00],
            ['LUKAS e³ CONNECT Rettungszylinder RC200', 'Hydraulischer Rettungszylinder. Hub 200–680 mm, Druckkraft 135 kN, Gewicht 9,8 kg.', 5400.00],
            ['WEBER S-FORCE eSPREADER Spreizer', 'Batteriegespeister hydraulischer Spreizer. Spreizlast 167 kN, Hub 762 mm, Gewicht 14,8 kg. LED-Beleuchtung integriert.', 8750.00],
            ['WEBER S-FORCE eCUTTER Rettungsschere', 'Elektrischer Rettungsschneider mit integrierten Akkus. Schneidkraft 1.100 kN, 3 Schneidstufen. Kompatibel mit WEBER S-FORCE Akku-System.', 8500.00],
            ['HOLMATRO SR 3250 C Spreizer', 'Kombinationsspreizer (Spreizer + Schere). Spreizlast 250 kN, Schneidkraft 700 kN, Gewicht 19,5 kg. Betrieb mit Hydraulikaggregat.', 7800.00],
            ['VETTER Hebekissen 10 bar Typ H10x10', 'Pneumatisches Hebekissen, 10 bar, Tragkraft 14 t, Hubhöhe 200 mm. Geeignet für PKW- und LKW-Hebung. Stahlkordverstärkt.', 890.00],
            ['VETTER Hebekissen 10 bar Typ H20x20', 'Pneumatisches Hebekissen, 10 bar, Tragkraft 40 t, Hubhöhe 255 mm. Für schwere Lasten, LKW und Eisenbahnfahrzeuge.', 1250.00],
            ['VETTER Hebekissen 8 bar Kombinationsset', 'Set aus zwei Hochdruckhebekissen 8 bar, Steuergerät, Schläuche und Transportkoffer. Hubkraft gesamt 42 t.', 3800.00],
            ['PARATECH StreetSmart® Abstützsystem', 'Fahrzeug-Stabilisierungssystem. Set aus 4 Stützen, Lastverteilungsplatten, Transporttasche. Max. Last 50 kN pro Stütze.', 2400.00],
            ['Hi-Lift® Farmwagenheber HL-48', 'Allzweckheber für die technische Hilfeleistung. Hubhöhe 1.220 mm, Traglast 2 t, Gusseisenkonstruktion, inklusive Off-Road-Kit.', 149.00],

            // ── Pumpen & Aggregate ────────────────────────────────────────────
            ['ROSENBAUER Fox 3 Tragkraftspritze', 'Tragbare Feuerlöschpumpe nach EN 14466. Förderleistung 800 l/min bei 10 bar. 2-Takt-Motor, Gewicht 25 kg, Schnellkupplung.', 4200.00],
            ['ROSENBAUER Fox Plus Tragkraftspritze', 'Hochleistungstragkraftspritze, 1.000 l/min bei 10 bar. 4-Takt-Honda-Motor, elektrischer Anlasser, Vakuumzusatz.', 5100.00],
            ['SPECK Feuerlöschpumpe PFPN 10-1000', 'Tragbare Pumpe nach DIN 14410. Förderleistung 1.000 l/min, max. Druck 10 bar. Benzinmotor Honda GX390. Saugtiefe bis 7,5 m.', 3850.00],
            ['HONDA WB30 Wasserpumpe', 'Hochleistungs-Tauchpumpe für die Hochwasserbekämpfung. Fördermenge 1.100 l/min, Honda GX160-Motor, Ansaughöhe max. 8 m.', 980.00],
            ['MULTIVAC Hydraulikaggregat HA60', 'Hydraulikaggregat für hydraulische Rettungsgeräte. Förderleistung 5,0 l/min bei 700 bar. Benzin-Motor, Gewicht 42 kg, zwei Ausgangskreise.', 6800.00],
            ['HOLMATRO Hydraulikaggregat HA 6100', 'Hydraulikaggregat, zwei Kreisläufe, 1×700 bar/5 l/min + 1×700 bar/3 l/min. Benzin-Motor, Elektrostarter. Für Rettungsschere und Spreizer.', 7200.00],
            ['Tauchpumpe TSURUMI TE3-80HA', 'Elektrische Tauchpumpe für den Hochwassereinsatz. Fördermenge 1.200 l/min, Schmutzwassergeeignet bis 25 mm Partikelgröße, 400 V.', 1450.00],
            ['Motorsäge STIHL MS 661 C-M', 'Professionelle Motorsäge für die technische Hilfeleistung. Hubraum 91,1 cm³, Leistung 5,4 kW, Schwertlänge 37–75 cm. M-Tronic-Motormanagement.', 1380.00],
            ['Winkelschleifer METABO WEV 22-230 MVT', 'Winkelschleifer für Stahl- und Betonschnitt im Rettungseinsatz. 2.200 W, 230 mm Scheibe, Variable Drehzahl, Kickback-Stop.', 420.00],
            ['Trennschleifer HUSQVARNA K 970', 'Benzin-Trennschleifer für Beton, Stahl und Asphalt. Hubraum 93 cm³, Leistung 4,8 kW, Schnitttiefe 150 mm. X-Torq® Motor.', 1890.00],

            // ── Leitern & Absturzsicherung ────────────────────────────────────
            ['ZARGES Feuerwehr-Steckleiter Z600 4-teilig', 'Vierteliger Feuerwehr-Steckleitersatz nach DIN 14711. Länge zusammengesteckt 9 m, Holmbreite 44 cm, Aluminium, Gewicht 23 kg.', 980.00],
            ['ZARGES Schiebleiter Z600 2-teilig', 'Zweiteilige Schiebeleiter nach EN 1147. Länge ausgezogen 8 m, Aluminium-Holme, rutschsichere Sprossen, Gewicht 27 kg.', 1240.00],
            ['Hakenleiter ALCO 4 m', 'Stahlhakenleiter für die Feuerwehr. Länge 4 m, Tragfähigkeit 150 kg, feuerverzinkter Stahl, klappbarer Haken.', 680.00],
            ['SKYLOTEC IGNEX Rettungsleine 30 m', 'Feuerwehr-Rettungsleine nach EN 1891 Typ A. Länge 30 m, Durchmesser 10,5 mm, statische Belastung bis 22 kN, Rucksack-Aufbewahrung.', 220.00],
            ['KONG BACK Feuerwehr-Selbstretter', 'Abseilgerät für die Personenrettung. Max. Last 140 kg, automatische Geschwindigkeitsbegrenzung, kompatibel mit Rettungsleinen 10–11 mm.', 890.00],
            ['PETZL ROLLCLIP Rollenschnappkarabiner', 'Rollenschnappkarabiner für Rettungsoperationen. Aluminiumkarabiner, Kugellager-Rolle, MBL 23 kN. Reduziert Seilverschleiß.', 89.00],
            ['PETZL TRIACT-LOCK Karabiner', 'Automatisch verriegelnder Karabiner. Aluminium, dreifache Sicherung, MBL 25 kN, Öffnung 23 mm.', 42.00],

            // ── Warnschutz & Absperrung ──────────────────────────────────────
            ['Warnweste gfd® EN ISO 20471 Klasse 3', 'Signalweste für Einsatzkräfte. Klasse 3 nach EN ISO 20471, gelb-leuchtend, reflektierende Streifen 50 mm, Klettverschluss.', 22.00],
            ['Faltsignal MÜBA Leitkegel 500 mm', 'Faltbarer Verkehrs-Leitkegel nach TL-Leitkegel 99. Höhe 500 mm, leuchtrot, rollbar, Stapelbar. 4 kg Befüllring.', 28.00],
            ['Faltsignal MÜBA Leitkegel 750 mm', 'Faltbarer Leitkegel 750 mm nach TL-Leitkegel 99. Für Autobahn-Einsätze, Schwergewichtsring 8 kg, UV-beständig.', 42.00],
            ['Absperrband gelb/schwarz 500 m', 'Kunststoff-Absperrband für Gefahrenstellen. 500 m auf Spule, 80 mm breit, reißfest, gelb/schwarz gestreift.', 18.00],
            ['Blitzleuchte BELI-BECO BL2000 LED', 'Gelbe Blitzleuchte für Absperrungen. LED-Technologie, 120 Blitze/min, IP 67, Betriebsdauer >200 h, Magnethaftung.', 65.00],

            // ── Beleuchtung & Strom ───────────────────────────────────────────
            ['PELI 9415 LED-Arbeitsleuchte', 'LED-Flutlicht für den Einsatzeinsatz. 3.200 Lumen, aufklappbarer Teleskopfuß, 8 h Leuchtdauer (2× D-Zellen), IP 67, schlagfest.', 520.00],
            ['BRENNENSTUHL Stativ-Scheinwerfer LED 50 W', 'LED-Stativ-Baustrahler für Einsatzbeleuchtung. 50 W, 4.250 Lumen, Neigungswinkel ±90°, Kaltlicht 6.500 K, IP 65.', 180.00],
            ['ZIEGLER Lichtmast Z-LM 4000', 'Hydraulischer Lichtmast für Feuerwehrfahrzeuge. 4.000 W, pneumatischer Teleskopmast 4,5 m, 360°-Drehung, 230/400 V.', 8900.00],
            ['HONDA EU22i Inverter-Generator', 'Stromerzeuger für den Einsatz. Leistung 2.200 W, Gewicht 21 kg, Benzinmotor, stabile Sinuswelle für empfindliche Elektronik.', 1190.00],
            ['SDMO INTES 2000 Stromerzeuger', 'Tragbarer Stromerzeuger. 2.000 W Nennleistung, 5,5-PS-Motor, 230 V / 50 Hz, AVR-Spannungsregler, Gewicht 28 kg.', 780.00],
            ['Verlängerungskabel H07RN-F 50 m', 'Gummischlauchabgesichertes Verlängerungskabel für den Außeneinsatz. 50 m, 3×2,5 mm², IP 44, 16 A CEE-Stecker.', 145.00],

            // ── Erste Hilfe & Medizin ─────────────────────────────────────────
            ['SÖHNGEN Notfallrucksack Emergengy L', 'Rettungsrucksack mit kompletter Erstversorgungsausstattung. Volumen 38 l, wasserresistentes Nylon, farbkodierte Module für schnellen Zugriff.', 420.00],
            ['WEINMANN MEDUMAT Standard Beatmungsgerät', 'Transportbeatmungsgerät für Rettungseinsätze. Volumetrisch gesteuert, PEEP-Ventil, Notfall-Modus. Batterieautonomie > 4 h.', 3800.00],
            ['Schaufelbahre FERNO 65', 'Zusammenklappbare Schaufelbahre aus Aluminium. Länge 185 cm, Tragfähigkeit 225 kg, 4 Handgriffe, inkl. Gurtsystem. Röntgendurchlässig.', 380.00],
            ['LAERDAL Little Anne Übungspuppe', 'Trainingsmodell für HLW-Schulungen. Wechselbares Gesichtsschild, Atemwegssimulator, realistische Anatomie. Inkl. Tragetasche.', 290.00],
            ['AED ZOLL AED Plus Defibrillator', 'Halbautomatischer Defibrillator für Laienhelfer. Real CPR Help®-Sensor, Sprachanleitungen, robustes Gehäuse IP 55. 10 Jahre Garantie.', 1850.00],
            ['Stifneck® ExTRICATOR Zervikalstütze', 'Zervikalstütze für Rettungseinsätze. 6 Größen mit einem Modell einstellbar, röntgendurchlässig, abwischbare Oberfläche.', 65.00],

            // ── Löschmittel & Armaturen ───────────────────────────────────────
            ['ROSENBAUER Schnellangriffsverteiler', 'Druckabgangsverteiler für Schnellangriffsleitungen. 2× B-Abgänge, 1× C-Abgang, Aluminium-Druckguss, 16 bar.', 380.00],
            ['TOTAL WALTHER Druckbegrenzungsventil DB 4', 'Druckbegrenzungsventil für Feuerwehr-Druckschläuche. Ansprechdruck einstellbar 4–16 bar, Nennweite 75 mm (B-Kupplung).', 290.00],
            ['Strahlrohr LAUTERBACH UNI-Kombirohr TFH', 'Mehrzweck-Strahlrohr (Voll-, Sprüh-, Sprühschutz). Durchfluss 100–400 l/min, Betriebsdruck 2–8 bar, B-Kupplung.', 420.00],
            ['Schaummitteltank IBC 1.000 l AFFF 3%', 'Stationärer Schaummittelbehälter. 1.000 Liter AFFF-Schaummittel, 3% Zumischung, IBC-Container, UN-geprüft, Zulassung nach EN 1568.', 2900.00],
            ['TYCO Sprinkleranlage Freiflächenleitung', 'Freileitungs-Sprinkleranlage für Außenanlage. Druckknopfauslösung, Freiflächensystem, Nennweite DN 50 bis DN 150.', 6500.00],
            ['MINIMAX Handfeuerlöscher PG6 ABC', 'Dauerdruck-Pulverlöscher 6 kg, ABC-Löschpulver, Löschleistung 34A 233B C, CE-Kennzeichnung, 10-Jahres-Garantie.', 78.00],
            ['MINIMAX CO2-Feuerlöscher CO2-5', 'Kohlendioxid-Löscher 5 kg für Elektroanlagen. Auslösedruck 58 bar, Leitungslänge 0,5 m, CE EN 3. Kein Löschmittelrückstand.', 185.00],

            // ── Schlauchmaterial ─────────────────────────────────────────────
            ['Druckschlauch B75 15 m Polyester', 'Feuerwehr-Druckschlauch B 75, 15 m, Polyester-Baumwolle Mischgewebe. Storz-Kupplung B, Betriebsdruck 8 bar, EN 14540.', 95.00],
            ['Druckschlauch C42 15 m Polyester', 'Feuerwehr-Druckschlauch C 42, 15 m, leicht, Polyester-Gewebe. Storz-Kupplung C, Betriebsdruck 8 bar, EN 14540.', 68.00],
            ['Saugschlauch A 110 2,5 m Hart-PVC', 'Feuerwehr-Saugschlauch A 110, 2,5 m, Hart-PVC. Storz-Kupplung A, Betriebsdruck -0,9 bar, DIN 14811.', 155.00],
            ['Schlauchtragekorb für B-Schläuche', 'Tragkorb für 2× B75-Schläuche. Verstärkter Kunststoff, Schultergurt und Traggurt, max. Zuladung 25 kg.', 48.00],

            // ── Kommunikation & Navigation ────────────────────────────────────
            ['MOTOROLA DP4801e TETRA-Handfunkgerät', 'Digitalfunkgerät TETRA nach BOS-Standard. 4 W Sendeleistung, Bluetooth, GPS, MAN DOWN, integriertes WLAN, IP 68.', 1250.00],
            ['MOTOROLA DP4400e TETRA-Handfunkgerät', 'Kompaktes BOS-Digitalfunkgerät. 4 W, 1.000 Kanäle, LED-Taschenlampe, IMPRES-Akkutechnologie, IP 67.', 990.00],
            ['SEPURA STP9000 TETRA-Funkgerät', 'Robustes Digitalfunkgerät für Behörden und Organisationen. TETRA, Verschlüsselung TEA2, GPS, 1.500 mAh-Akku, IP67.', 1080.00],
            ['Lautsprecher-Mikrofon RSM100', 'Fernbedienungs-Lautsprechermikrofon für TETRA-Geräte. IP 67, 1,5 m Spiralkabel, Notruftaste, Klippmontage.', 175.00],

            // ── Fahrzeugausrüstung ────────────────────────────────────────────
            ['ROSENBAUER Schnellangriffseinrichtung D26', 'Löschwasser-Schnellangriff für Löschfahrzeuge. D26-Schlauch 30 m, automatischer Druckbegrenzer, Wandhydrantenhalter.', 1800.00],
            ['Heckwarnsystem LED 1.500 mm', 'LED-Heckwarntafel für Einsatzfahrzeuge. 8 Warn-LED, 1.500 mm Breite, Steuergerät, Blitzfolge programmierbar. 12/24 V.', 1350.00],
            ['Blaulichtanlage FG KRONSBERG Modul 6', 'Dachbalken-Blaulichtalage mit Signalhorn. 6 Module, LED, 100 W Horn, 12/24 V, nach StVZO zugelassen. Inklusive Steuergerät.', 2900.00],
            ['Fahrzeughalterung für TETRA-Funkgeräte', 'Fahrzeughalterung für MOTOROLA DP4-Serie. Ladefunktion, Lautsprecherausgang, Kipphebelverriegelung, 12/24 V.', 450.00],
            ['Fahrzeugschrank Aluminium 600×400×400 mm', 'Fahrzeuggerätekasten für Feuerwehr-Aufbauten. Aluminium eloxiert, Druckgussecken, Zylinderschloss, IP 54.', 620.00],

            // ── Jugendfeuerwehr ───────────────────────────────────────────────
            ['Jugendfeuerwehr-Übungsjacke TEXPORT® Junior', 'Leichte Übungsjacke für die Jugendfeuerwehr. Polyester-Außenstoff, Polyamid-Futter, reflektierende Streifen, Größen 128–XL.', 189.00],
            ['Jugendfeuerwehr-Helm SCHUBERTH F220 Junior', 'Verkleinerter Feuerwehrhelm für Jugendliche. Gleiche Schutzklasse wie Erwachsenenhelm, einstellbare Kopfgrößen 50–58 cm.', 245.00],
            ['Jugendfeuerwehr-Ausbildungsset Basis', 'Ausrüstungsset für Jugendfeuerwehr. Enthält: Einstiegsjacke, Hose, Handschuhe, Helm. Für Übungen ohne Brandeinsatz.', 380.00],

            // ── Chemikalienschutz ─────────────────────────────────────────────
            ['DuPont™ Tychem® 6000F Schutzanzug', 'Vollschutzanzug gegen Flüssigkeiten und Gase. EN 943-1 Typ 1a, integrierte Handschuhe und Stiefelüberzieh. Einmalgebrauch.', 320.00],
            ['DuPont™ Tychem® 2000 Einwegschutzanzug', 'Einweg-Schutzanzug gegen Chemikalienspritzer. EN 13982 Typ 5, EN 13034 Typ 6. Reißverschluss mit Klebestreifen, Kapuze integriert.', 28.00],
            ['MSA Millennium® CBRN-Maske', 'Schutzmaske für CBRN-Einsätze. EN 136, NIOSH-zertifiziert. CBRN-Filter-Anschluss, breites Sichtfeld, kompatibel mit Atemschutzgeräten.', 480.00],
            ['Chemikalien-Schutzhandschuh Ansell AlphaTec 87-900', 'Butyl-Schutzhandschuh gegen Chemikalien. 0,4 mm Wandstärke, Länge 400 mm, EN ISO 374-1 Typ A. Für Säuren, Basen, Lösemittel.', 85.00],

            // ── Wasserrettung ─────────────────────────────────────────────────
            ['VIKING Pro Trockentauchanzug', 'Trockentauchanzug für Feuerwehr-Wasserrettung. Trilaminat-Material, integrierte Neopren-Socken, Silex-Unterzieher. Größe angepasst.', 2900.00],
            ['CREWSAVER Rettungsweste 150 N', 'Selbstaufblasbare Rettungsweste für den Wassereinsatz. 150 N, automatische Auslösung, CE EN ISO 12402-3, Sicherungslicht.', 290.00],
            ['OCEAN SIGNAL Rettungsboje Typ III', 'Wurfleine mit Rettungsring. 30 m Leine, Kunststoffring Ø 710 mm, leuchtgelb, Reflektorband rundum. Nach DIN EN 1564.', 185.00],
            ['HIKO Schwimmweste 50N Paddling', 'Schwimmweste für Strömungsrettung und Paddeleinsatz. 50 N, EN ISO 12402-5, Schnellverschlüsse, Messerhalter, Pfeifen-Halterung.', 145.00],

            // ── Schulung & Dokumentation ──────────────────────────────────────
            ['Gerätebuch Feuerwehr DIN A5', 'Gebundenes Gerätebuch zur Prüfprotokollierung. 200 Seiten, Tabellen für Prüfung, Reparatur, Wartung. Ausfallsicher, wetterfest.', 18.00],
            ['Ausbildungsunterlagen Atemschutz-Grundlehrgang', 'Offizielles Lehrmaterial für den Atemschutz-Grundlehrgang. Skript + Übungsblätter + Prüfungsfragen. Aktualisierte Auflage.', 35.00],
        ];

        foreach ($products as [$name, $beschreibung, $preis]) {
            $productId = (string) Str::uuid();
            $descId    = (string) Str::uuid();
            $priceId   = (string) Str::uuid();

            DB::table('products')->insert([
                'cis_row_id' => $productId,
                'name'       => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('product_descriptions')->insert([
                'cis_row_id'         => $descId,
                'cis_row_id_product' => $productId,
                'text'               => $beschreibung,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            DB::table('prices')->insert([
                'cis_row_id'         => $priceId,
                'cis_row_id_product' => $productId,
                'cis_row_id_source'  => $sourceId,
                'amount'             => $preis,
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);
        }

        $this->command->info('✓ ' . count($products) . ' Produkte aus dem Giebeler-Katalog importiert.');
    }
}
