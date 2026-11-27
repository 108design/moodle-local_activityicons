<?php
// This file is part of Moodle - https://moodle.org/
/**
 * This file is part of a 108design source-available software product.
 *
 * Use and modification are permitted only under the Software License included
 * with this distribution. See LICENSE.md for the full terms.
 *
 * File documentation for lang/de/local_activityicons.php.
 *
 * @package   local_activityicons
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   See LICENSE.md for the full terms.
 */
defined('MOODLE_INTERNAL') || die();

$string['poolnotice'] = 'Der gemeinsame Pool steht allen Kursen zur Verfügung. Hochgeladene Icons sind öffentlich abrufbare Gestaltungsdateien. Verwende SVG mit viewBox oder PNG/WebP, höchstens 1 MB und bei Rasterbildern höchstens 2048 × 2048 Pixel. SVG muss aus statischen Formen bestehen; Skripte, eingebettete Bilder und externe Inhalte sind nicht erlaubt. Rasterbilder werden als PNG gespeichert. Bereits verwendete Icons bleiben bei weiteren Uploads erhalten.';
$string['invalidimage'] = 'Die Datei ist kein unterstütztes, sicheres Icon. Verwende ein statisches SVG mit viewBox oder PNG/WebP (maximal 1 MB, 2048 × 2048 Pixel).';
$string['invalidlabel'] = 'Gib eine Bezeichnung mit 1 bis 100 Zeichen ein.';
$string['h5ptypes_help'] = 'Wähle installierte Inhaltstypen oder gib zusätzliche Maschinennamen ein, z. B. H5P.InteractiveVideo. Die Zuordnung gilt für alle Versionen dieses Typs. Vorhandene Zuordnungen der ausgewählten Typen werden ersetzt. Individuelle Aktivitätsicons bleiben vorrangig.';
$string['invalidmachine'] = 'Verwende einen H5P-Maschinennamen ohne Versionsnummer, z. B. H5P.InteractiveVideo.';
$string['resetconfirm'] = 'Wirklich alle Icon-Zuweisungen dieses Kurses durch die ursprünglichen Moodle-Modulicons ersetzen? Deine individuellen Zuordnungen gehen dabei verloren. Das Wiederherstellen kann viel manuelle Arbeit verursachen und ist nur durch erneute Zuordnung oder eine Kurssicherung möglich. Auch die H5P-Automatik wird für diesen Kurs deaktiviert.';
$string['activityicon_help'] = 'Wähle das ursprüngliche Icon des Aktivitätstyps oder ein Icon aus einem der Pools. Uploads stehen unter „Eigene Icons“. Für H5P kannst du außerdem die Kursregel übernehmen oder den H5P-Typ dieser Aktivität unabhängig davon erkennen lassen.';
$string['autoh5p_desc'] = 'Prüfe die Typzuordnung, bevor du die Automatik einschaltest.';
$string['h5pmapping_desc'] = 'Eine Zuordnung pro Zeile, z. B. H5P.InteractiveVideo=video. Verwende den H5P-Maschinennamen ohne Versionsnummer. Groß-/Kleinschreibung spielt keine Rolle. Leerzeilen und Kommentare mit # sind erlaubt. Eine leere Liste verwendet für alle Typen das Ersatzicon. Verfügbare Icon-Schlüssel:';
$string['mappinginvalidline'] = 'Ungültige oder doppelte Zuordnung {$a}. Wähle einen gültigen H5P-Typ und ein Icon. Jeder Typ darf nur einmal zugeordnet werden.';
$string['adminintro'] = 'Verwalte hier den gemeinsamen Icon-Pool und die automatische H5P-Zuordnung.';
$string['batchhelp'] = 'Füge eigene Icons für deine Kurse hinzu.';
$string['batchrequired'] = 'Wähle 1 bis 50 Icon-Dateien aus.';
$string['batchsaved'] = '{$a} unterschiedliche Icons aus dem Upload stehen im Pool zur Verfügung. Du kannst ihre Bezeichnungen jetzt bearbeiten.';
$string['labelshelp'] = 'Benenne eigene Icons um oder entferne unbenutzte Icons.';
$string['mappinghelp'] = 'Ordne jedem H5P-Typ ein Icon zu. Mit „Pool“ und der Suche grenzt du die Auswahl ein.';
$string['selecticonsfirst'] = 'Wähle mindestens ein eigenes Icon aus.';
$string['overview_help'] = 'Klicke auf den Namen oder das Bild einer Poolkarte, um ihre Icons zu öffnen.';
$string['settingshelp_overview_article'] = '<p>Unter <strong>Icon-Pools</strong> lädst du Dateien hoch und öffnest eigene oder mitgelieferte Pools. <strong>H5P-Mapping</strong> enthält die Typzuordnungen und das Ersatzicon. Unter <strong>Einstellungen</strong> finden Website-Administrator/innen die H5P-Automatik, dynamische Aktivitätslisten und den Entwicklermodus.</p>';
$string['settingshelp_pool_article'] = '<p>Du kannst bis zu 50 öffentlich nutzbare SVG-, PNG- oder WebP-Dateien mit jeweils höchstens 1 MB hochladen. Rasterbilder dürfen höchstens 2048 × 2048 Pixel groß sein. SVGs müssen statisch sein und eine viewBox enthalten. Öffne einen eigenen Pool, lädt das Uploadfeld direkt dorthin.</p><p>Markiere Icons und wähle eine Aktion neben <strong>Ausführen</strong>. Verschieben ändert den Hauptpool eines Uploads. Kopieren zeigt dasselbe Icon zusätzlich im Zielpool. <strong>Aus diesem Pool entfernen</strong> blendet die Kopie in diesem Pool aus. Bestehende Zuweisungen behalten das Icon.</p><p>Für eine Namensänderung bearbeitest du das Textfeld, markieren den Upload und wählen <strong>Bezeichnungen speichern</strong>. <strong>Ausgewählte Icons löschen</strong> öffnet die Bestätigung. Verwendete Uploads lassen sich über den Entwicklermodus löschen.</p>';
$string['mapping_help'] = 'Wähle je H5P-Typ ein Icon. Zum Entfernen markierte Zeilen werden erst beim Speichern gelöscht; vorher kannst du die Markierung zurücknehmen.';
$string['settingshelp_mapping_article'] = '<p>Wähle unter <strong>Manuelle H5P-Typzuordnung</strong> die Aktion <strong>Zuordnung hinzufügen</strong>, anschließend den H5P-Inhaltstyp und sein Icon. Jeder Typ hat eine Zuordnung, die für seine verschiedenen Versionen gilt. Du kannst je Typ auch das Moodle-Originalicon wählen.</p><p>Mit × markierst du eine Zuordnung zum Entfernen; ↶ nimmt die Markierung zurück. Erst <strong>Änderungen speichern</strong> übernimmt die Bearbeitung. Wähle für nicht zugeordnete Typen ein <strong>Icon für nicht zugeordnete H5P-Typen</strong> oder behalte das originale Moodle-H5P-Icon. Die Suche in <strong>Alle Pools</strong> umfasst auch eigene Icons. Wähle einen Pool, sucht sie nur darin.</p><p>Die Zuordnung greift, wenn die H5P-Typerkennung verwendet wird. Eine ausdrücklich an der Aktivität gewählte Darstellung hat weiterhin Vorrang.</p>';
$string['settingshelp_behaviour_article'] = '<p><strong>Nicht zugewiesene H5P-Aktivitäten automatisch klassifizieren</strong> legt die Website-Vorgabe fest. Diese Option ist nach der Installation ausgeschaltet, damit bestehende Kurse ihre Darstellung behalten. Prüfe vor dem Einschalten die Typzuordnungen und das Ersatzicon.</p><p>Ein Kurs kann die automatische H5P-Zuordnung unabhängig davon ein- oder ausschalten. An einer Aktivität behält <strong>Originalicon verwenden</strong> das Moodle-Icon bei; ein gewähltes Pool-Icon legt die Darstellung fest. <strong>H5P-Typ erkennen</strong> verwendet die Typerkennung unabhängig von der Kursvorgabe.</p><p><strong>Dynamisch geladene Aktivitätslisten ergänzen</strong> zeigt konfigurierte Icons auch in Standardlisten wie Zeitleiste und Zuletzt besuchte Elemente. Die Inhalte der Aktivitäten ändern sich dadurch nicht.</p><p>Die websiteweiten Schalter werden nur Personen angezeigt, die die Website-Konfiguration ändern dürfen.</p>';
$string['iconfiles_help'] = 'Lade dekorative Icons hoch, die öffentlich zugänglich sein dürfen. Das Entfernen aus diesem Entwurf löscht kein vorhandenes Pool-Icon.';
$string['dynamicfallback_help'] = 'Zeige zugeordnete Icons auch in dynamisch geladenen Aktivitätslisten wie Zeitleiste und zuletzt besuchten Elementen.';
$string['updateguard_hint'] = 'Verwende ein freigegebenes Paket für die installierte Ausgabe. Du kannst den Vorgang erneut prüfen.';
$string['updateguard_dependency'] = 'Eine benötigte Plugin-Version fehlt. Aktualisiere die Abhängigkeit gemeinsam mit diesem Plugin oder vorher.';
$string['updateguard_approval'] = 'Die Pakete oder der installierte Stand haben sich seit der Vorschau geändert. Prüfe den Vorgang erneut.';
$string['updateguard_busy'] = 'Eine andere Installation läuft. Versuche es nach deren Abschluss erneut.';
$string['updateguard_installed'] = 'Der installierte Updateschutz ist unvollständig. Stelle die Dateien der zuletzt installierten freigegebenen Version wieder her.';
$string['updateguard_operation'] = 'Diese Kombination von Installationsaktionen wird nicht unterstützt. Starte einen einzelnen Updatevorgang erneut.';
$string['invalidgroupname'] = 'Gib einen Namen mit 1 bis 100 Zeichen ein.';
$string['duplicategroupname'] = 'Wähle einen anderen Namen. Dieser Poolname wird bereits verwendet.';
$string['groupsintro'] = 'Lege hier Pools an und ordne sie. Inhalte bearbeitest du über die Aktion am jeweiligen Pool.';
$string['groupbundled_help'] = 'Mitgelieferte Icons erscheinen zusätzlich in diesem Pool. Deine mitgelieferten Pools bleiben verfügbar.';
$string['groups_help'] = 'Klicke auf den Namen oder das Bild einer Poolkarte. Bei eigenen benannten Pools öffnet der Stift die Metadaten.';
$string['settingshelp_groups_article'] = '<p>Unter <strong>Icon-Pools</strong> findest du zuerst deine eigenen Pools. <strong>Eigene Uploads</strong> enthält Dateien ohne eigenen Zielpool. Weitere Pools legst du über <strong>Pool hinzufügen</strong> an.</p><p>Kopf und Mitte einer Karte öffnen den Pool. Wähle <strong>Metadaten ändern</strong>, um den Namen oder das Referenzicon eines eigenen Pools zu ändern. Ohne Auswahl zeigt die Karte das erste verfügbare Icon.</p><p>Öffne einen mitgelieferten Themenpool, um seine Motive in einen eigenen Pool zu kopieren. Wenn du einen eigenen Pool löschst, kommen seine Uploads zurück zu <strong>Eigene Uploads</strong>. Die Zuweisungen in Kursen und H5P bleiben erhalten.</p>';
$string['deleteusageintro'] = 'Prüfe vor der Bestätigung, wo die ausgewählten Icons verwendet werden.';
$string['confirmoriginalrequired'] = 'Bestätige erst das Zurücksetzen der aufgeführten Verwendungen.';
$string['deleteusagechanged'] = 'Die Verwendung hat sich geändert. Prüfe die aktualisierte Liste und bestätige erneut.';
$string['developer_help'] = 'Beim Löschen eines verwendeten Icons siehst du vorher die betroffenen Zuweisungen, H5P-Typen und die Verwendung als Ersatzicon.';
$string['settingshelp_developer_article'] = '<p>Aktiviere den <strong>Entwicklermodus</strong> unter <strong>Einstellungen</strong>. Öffne unter <strong>Icon-Pools</strong> den Hauptpool des Uploads. Markiere ihn, wähle <strong>Ausgewählte Icons löschen</strong> und klicke auf <strong>Ausführen</strong>.</p><p>Die Bestätigung zeigt direkte Zuweisungen, H5P-Typregeln und die Verwendung als Ersatzicon. Beim Löschen greifen diese Stellen auf das Moodle-Originalicon zurück. Ändert sich eine Verwendung, prüfe und bestätige die aktualisierte Übersicht.</p>';
$string['groupcontentsintro'] = 'Wähle hier Icons aus dem Katalog aus, die im Picker auch in diesem Pool erscheinen sollen.';
$string['groupcatalogue_help'] = 'Öffne die Vorschläge oder suche nach einem Motiv, etwa „Textmarker“. Die gewählten Icons findest du im Picker in diesem und in ihrem ursprünglichen Themenpool.';
$string['groupcopieduploads_help'] = 'Entferne einen Eintrag mit ×, um ihn aus diesem Pool zu nehmen. Der Upload bleibt in seinem Hauptpool und bestehende Zuweisungen behalten das Icon.';
$string['poolbulkintro'] = 'Markiere Icons und wähle eine Aktion. Wenn du eine Bezeichnung geändert hast, markierst du den Upload und wähle „Bezeichnungen speichern“.';
$string['poolemptyuploads'] = 'Hier erscheinen deine hochgeladenen Icons.';
$string['movetransferintro'] = 'Wähle den neuen Hauptpool für diese Icons.';
$string['copytransferintro'] = 'Wähle einen zusätzlichen Pool. Die Icons bleiben auch in ihrem bisherigen Hauptpool verfügbar.';
$string['transfernodestination'] = 'Lege unter Gruppen einen weiteren Zielpool an.';
$string['catalogueinstructions'] = 'Markiere die Icons, die du in einem eigenen Pool anbieten möchtest.';
$string['creategroupforcopy'] = 'Lege unter Icon-Pools einen eigenen Pool an, um Icons dorthin zu kopieren.';
$string['poolisempty'] = 'Dieser Pool ist leer. Du kannst hier Dateien hochladen oder Icons aus einem anderen Pool hierher kopieren.';
$string['poolreferenceinvalid'] = 'Wähle ein Referenzicon aus diesem Pool.';
$string['settingshelp_course_article'] = '<p>Mit <strong>Website-Einstellung übernehmen</strong> gilt die Website-Vorgabe. Du kannst die H5P-Automatik auch für diesen Kurs ein- oder ausschalten. Individuelle Einstellungen an einer Aktivität haben Vorrang.</p><p>Wenn eine Aktivität nach der Zurücksetzung wieder der Kursregel folgen soll, wählst du an ihr <strong>Kursregel übernehmen</strong>.</p>';
$string['courseadmin_help'] = 'Beim Zurücksetzen gehen individuelle Icon-Zuweisungen dieses Kurses verloren. Die nächste Seite fragt dich nach einer Bestätigung.';
$string['settingshelp_courseadmin_article'] = '<p>Unter <strong>Administrationsaktionen</strong> öffnest du die websiteweite Verwaltung oder stellst alle ursprünglichen Moodle-Icons dieses Kurses wieder her.</p><p>Die Zurücksetzung deaktiviert die H5P-Automatik im Kurs. Du kannst individuelle Icons danach erneut zuweisen oder aus einer passenden Kurssicherung wiederherstellen.</p>';
$string['updateinfo_choice'] = 'Kontrolliere vor dem Fortfahren die Paketquelle und Moodles Prüfergebnisse. Mit „{$a}“ unten kannst du abbrechen.';
