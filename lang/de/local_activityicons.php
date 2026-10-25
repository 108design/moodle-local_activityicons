<?php
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

$string['pluginname'] = 'Aktivitätsicons';
$string['activityicons:manage'] = 'Icons einzelner Aktivitäten und Kursregeln verwalten';
$string['activityicons:managepool'] = 'Gemeinsamen Icon-Pool und H5P-Zuordnungen verwalten';
$string['managepool'] = 'Icon-Pool und H5P-Zuordnung';
$string['uploadicon'] = 'Icon hinzufügen';
$string['iconlabel'] = 'Bezeichnung';
$string['iconfile'] = 'Icon-Datei';
$string['poolnotice'] = 'Der gemeinsame Pool steht allen Kursen zur Verfügung. Hochgeladene Icons sind öffentlich abrufbare Gestaltungsdateien. Verwenden Sie SVG mit viewBox oder PNG/WebP, höchstens 1 MB und bei Rasterbildern höchstens 2048 × 2048 Pixel. SVG muss aus statischen Formen bestehen; Skripte, eingebettete Bilder und externe Inhalte sind nicht erlaubt. Rasterbilder werden als PNG gespeichert. Bereits verwendete Icons bleiben bei weiteren Uploads erhalten.';
$string['invalidimage'] = 'Die Datei ist kein unterstütztes, sicheres Icon. Verwenden Sie ein statisches SVG mit viewBox oder PNG/WebP (maximal 1 MB, 2048 × 2048 Pixel).';
$string['invalidlabel'] = 'Geben Sie eine Bezeichnung mit 1 bis 100 Zeichen ein.';
$string['pooliconsaved'] = 'Das Icon ist im gemeinsamen Pool verfügbar.';
$string['h5ptypes'] = 'H5P-Inhaltstypen';
$string['h5ptypes_help'] = 'Wählen Sie installierte Inhaltstypen oder geben Sie zusätzliche Maschinennamen ein, z. B. H5P.InteractiveVideo. Die Zuordnung gilt für alle Versionen dieses Typs. Vorhandene Zuordnungen der ausgewählten Typen werden ersetzt. Individuelle Aktivitätsicons bleiben vorrangig.';
$string['usefallback'] = 'Zuordnung entfernen – Ersatzicon verwenden';
$string['savemapping'] = 'Typen zuordnen';
$string['mappingsaved'] = 'Die H5P-Typzuordnung wurde gespeichert.';
$string['invalidmachine'] = 'Verwenden Sie einen H5P-Maschinennamen ohne Versionsnummer, z. B. H5P.InteractiveVideo.';
$string['coursemanage'] = 'Aktivitätsicons';
$string['coursemanage_desc'] = 'Die Kursregel gilt für bestehende und neue H5P-Aktivitäten ohne individuelle Auswahl. Einzelne Aktivitäten können weiterhin ein eigenes Icon verwenden.';
$string['courseautoh5p'] = 'H5P-Automatik';
$string['courseautoh5p_help'] = 'Die Automatik verwendet die globale H5P-Typzuordnung. Individuelle Icon-Auswahlen haben Vorrang. Nach einem Komplett-Reset müssen einzelne Aktivitäten zuerst wieder auf „Kursregel übernehmen“ gesetzt werden, bevor die Automatik für sie gilt.';
$string['courseautoinherit'] = 'Website-Einstellung übernehmen';
$string['courseautoenabled'] = 'Für diesen Kurs aktivieren';
$string['courseautodisabled'] = 'Für diesen Kurs deaktivieren';
$string['coursepolicysaved'] = 'Die H5P-Kursregel wurde gespeichert.';
$string['invalidcourseautomode'] = 'Die ausgewählte H5P-Kursregel ist ungültig.';
$string['resetall'] = 'Ursprüngliche Moodle-Icons wiederherstellen';
$string['resetall_desc'] = 'Setzt alle Aktivitäten dieses Kurses auf das ursprüngliche Modulicon zurück und deaktiviert die H5P-Automatik im Kurs.';
$string['resetconfirm'] = 'Wirklich alle Icon-Zuweisungen dieses Kurses durch die ursprünglichen Moodle-Modulicons ersetzen? Ihre individuellen Zuordnungen gehen dabei verloren. Das Wiederherstellen kann viel manuelle Arbeit verursachen und ist nur durch erneute Zuordnung oder eine Kurssicherung möglich. Auch die H5P-Automatik wird für diesen Kurs deaktiviert.';
$string['resetdone'] = 'Die ursprünglichen Moodle-Modulicons wurden wiederhergestellt. Die H5P-Kursautomatik ist deaktiviert.';
$string['activityicon'] = 'Aktivitätsicon';
$string['activityicon_help'] = 'Wählen Sie das ursprüngliche Icon des Aktivitätstyps oder ein Icon aus dem gemeinsamen Pool. Für H5P können Sie außerdem die Kursregel übernehmen oder den H5P-Typ dieser Aktivität unabhängig davon erkennen lassen.';
$string['autoh5p'] = 'Nicht zugewiesene H5P-Aktivitäten automatisch klassifizieren';
$string['autoh5p_desc'] = 'Prüfe die Typzuordnung, bevor Du die Automatik einschaltest.';
$string['h5pmapping'] = 'Manuelle H5P-Typzuordnung';
$string['h5pmapping_desc'] = 'Eine Zuordnung pro Zeile, z. B. H5P.InteractiveVideo=video. Verwenden Sie den H5P-Maschinennamen ohne Versionsnummer. Groß-/Kleinschreibung spielt keine Rolle. Leerzeilen und Kommentare mit # sind erlaubt. Eine leere Liste verwendet für alle Typen das Ersatzicon. Verfügbare Icon-Schlüssel:';
$string['mappinginvalidline'] = 'Ungültige oder doppelte Zuordnung {$a}. Wählen Sie einen gültigen H5P-Typ und ein Icon. Jeder Typ darf nur einmal zugeordnet werden.';
$string['h5pfallback'] = 'Icon für nicht zugeordnete H5P-Typen';
$string['h5pfallback_desc'] = 'Dieses Pool-Icon wird verwendet, wenn der Maschinenname nicht in der manuellen Zuordnung steht. Es werden keine Icons aus dem Internet oder aus dem H5P-Paket geladen.';
$string['h5pfallbackdefault'] = 'Originales Moodle-H5P-Icon';
$string['dynamicfallback'] = 'Dynamisch geladene Aktivitätslisten ergänzen';
$string['dynamicfallback_desc'] = 'Konfigurierte Icons auch in Moodle-Standardblöcken wie Zeitleiste und Zuletzt aufgerufen auflösen, die per AJAX geladen werden.';
$string['selectioninherit'] = 'Kursregel übernehmen';
$string['selectioninherit_desc'] = 'Verwendet die H5P-Automatik des Kurses; ohne Kursregel gilt die globale Automatik.';
$string['selectiondefault'] = 'Originalicon verwenden';
$string['selectiondefault_desc'] = 'Verwendet das ursprüngliche Icon dieses Aktivitätstyps.';
$string['selectionauto'] = 'H5P-Typ erkennen';
$string['selectionauto_desc'] = 'Ordnet das Icon dieser Aktivität anhand ihres H5P-Typs zu, unabhängig von der Kursregel.';
$string['bundledicons'] = 'Icon-Pool (mitgelieferte und eigene Icons)';
$string['chooseicon'] = 'Aktivitätsicon auswählen';
$string['closepicker'] = 'Schließen';
$string['editicon'] = 'Aktivitätsicon für {$a} ändern';
$string['searchicons'] = 'Icons durchsuchen';
$string['iconsaved'] = 'Aktivitätsicon gespeichert.';
$string['invalidselection'] = 'Das ausgewählte Aktivitätsicon ist ungültig.';
$string['unknowncoursemodule'] = 'Die ausgewählte Aktivität gehört nicht zu diesem Kurs.';
$string['privacy:metadata'] = 'Aktivitätsicons speichert Darstellungsoptionen für Kursaktivitäten, aber keine personenbezogenen Daten.';
$string['iconvideo'] = 'Video';
$string['iconquiz'] = 'Test';
$string['iconpresentation'] = 'Präsentation';
$string['iconbranching'] = 'Verzweigungsszenario';
$string['iconaudio'] = 'Audio';
$string['iconimage'] = 'Bild';
$string['icongame'] = 'Spiel';
$string['iconh5p'] = 'H5P (sonstige Typen)';
$string['adminintro'] = 'Verwalte hier den gemeinsamen Icon-Pool und die automatische H5P-Zuordnung.';
$string['batchupload'] = 'Icons hinzufügen';
$string['iconfiles'] = 'Icon-Dateien';
$string['batchhelp'] = 'Füge eigene Icons für Deine Kurse hinzu.';
$string['batchrequired'] = 'Wählen Sie 1 bis 50 Icon-Dateien aus.';
$string['batchinvalid'] = 'Die Datei „{$a}“ ist kein unterstütztes, sicheres Icon. Es wurde kein Icon aus diesem Stapel übernommen.';
$string['batchsaved'] = '{$a} unterschiedliche Icons aus dem Upload stehen im Pool zur Verfügung. Sie können ihre Bezeichnungen jetzt bearbeiten.';
$string['adminsaved'] = 'Die Einstellungen und Bezeichnungen wurden gespeichert.';
$string['poollabels'] = 'Icon-Pool und Bezeichnungen';
$string['labelshelp'] = 'Benenne eigene Icons um oder entferne unbenutzte Icons.';
$string['iconkey'] = 'Schlüssel für die Zuordnung';
$string['mappinghelp'] = 'Ordne jedem H5P-Typ das passende Icon zu.';
$string['sitebehaviour'] = 'Automatik und Darstellung';
$string['dynamichelp'] = 'Ergänzt Icon-Zuordnungen in dynamisch geladenen Standardlisten, beispielsweise Zeitleiste und zuletzt aufgerufene Aktivitäten.';
$string['adminlink'] = 'Icon-Pool, H5P-Zuordnung und Website-Automatik verwalten';
$string['mappingrow'] = 'Zuordnung {no}';
$string['addmapping'] = 'Zuordnung hinzufügen';
$string['removemapping'] = 'Zuordnung entfernen';
$string['undoremovemapping'] = 'Entfernen rückgängig machen';
$string['chooseh5ptype'] = 'H5P-Inhaltstyp auswählen';
$string['iconinuse_short'] = 'In Verwendung – nicht löschbar';
$string['iconinuse'] = 'Das Icon wird verwendet oder ist einem H5P-Typ bzw. als Ersatzicon zugeordnet und kann nicht gelöscht werden.';
$string['deleteicon'] = 'Eigenes Icon löschen';
$string['deleteiconconfirm'] = 'Das eigene Icon „{$a}“ wirklich aus dem gemeinsamen Pool löschen? Es wird derzeit nicht verwendet. Die Datei und der Pool-Eintrag werden entfernt; dies kann hier nicht rückgängig gemacht werden.';
$string['icondeleted'] = 'Das eigene Icon wurde aus dem gemeinsamen Pool gelöscht.';
$string['deleteiconsselected'] = 'Ausgewählte Icons löschen';
$string['selecticondelete'] = '„{$a}“ zum Löschen auswählen';
$string['iconinuse_batch'] = 'In Verwendung – nicht auswählbar';
$string['deleteicons'] = 'Eigene Icons löschen';
$string['deleteiconsconfirm'] = '{$a->selected} eigene Icons wurden ausgewählt. Nicht verwendete Icons werden endgültig gelöscht. {$a->locked} derzeit verwendete oder zugeordnete Icons bleiben erhalten. Vor dem Löschen wird die Verwendung erneut geprüft.';
$string['iconsdeletedbatch'] = '{$a->deleted} Icons wurden gelöscht. {$a->kept} verwendete oder zugeordnete Icons wurden beibehalten.';
$string['selecticonsfirst'] = 'Wählen Sie mindestens ein eigenes Icon aus.';
$string['invalidiconselection'] = 'Die Icon-Auswahl ist ungültig.';

$string['settingshelp'] = 'Hilfe zu Aktivitätsicons';

$string['contexthelptopics'] = 'Hilfethemen';

$string['settingshelpmore'] = 'Mehr dazu im Hilfepanel';

$string['settingshelp_overview'] = 'Aktivitätsicons verwalten';

$string['overview_help'] = 'Uploads haben eine eigene Aktion. Sichere Änderungen an Bezeichnungen und Einstellungen vorher mit „Änderungen speichern“.';

$string['settingshelp_overview_article'] = '<p>Erweitere den gemeinsamen Pool über <strong>Icons hinzufügen</strong>. Unter <strong>Icon-Pool und Bezeichnungen</strong> bearbeitest Du die Namen; unter <strong>Manuelle H5P-Typzuordnung</strong> wählst Du passende Icons für H5P-Typen.</p><p>Uploads haben eine eigene Aktion. Sichere Bezeichnungen, Zuordnungen und Einstellungen mit <strong>Änderungen speichern</strong>, bevor Du einen Upload beginnst. Das Aufklappen eines Bereichs oder Öffnen der Hilfe speichert nichts.</p><p>Kursverantwortliche können die Automatik für ihren Kurs gesondert einstellen. Eine individuelle Auswahl an einer Aktivität hat Vorrang vor automatischen Zuordnungen.</p>';

$string['settingshelp_pool'] = 'Icons hinzufügen und verwalten';

$string['pool_help'] = 'Icons stehen kursübergreifend bereit und sind öffentlich abrufbar. Verwendete Icons sind vor dem Löschen geschützt.';

$string['settingshelp_pool_article'] = '<p>Wähle unter <strong>Icons hinzufügen</strong> bis zu 50 SVG-, PNG- oder WebP-Dateien mit jeweils höchstens 1 MB. Rasterbilder dürfen maximal 2048 × 2048 Pixel groß sein. SVG-Dateien müssen statisch sein und eine viewBox besitzen.</p><p>Verwende ausschließlich Gestaltungsdateien, die öffentlich abrufbar sein dürfen. Hochgeladene Icons stehen kursübergreifend bereit. Vermeide private Angaben und Motive, die Du nicht weitergeben darfst.</p><p>Unter <strong>Icon-Pool und Bezeichnungen</strong> kannst Du eigene Icons umbenennen und die Änderungen speichern. Bestehende Zuordnungen bleiben erhalten. Wähle unbenutzte eigene Icons und <strong>Ausgewählte Icons löschen</strong>, um die Löschung zu prüfen. Verwendete Icons sowie Zuordnungen zu H5P-Typen oder zum Ersatzicon sind geschützt; mitgelieferte Icons lassen sich hier nicht entfernen.</p>';

$string['settingshelp_mapping'] = 'H5P-Typen zuordnen';

$string['mapping_help'] = 'Wähle je H5P-Typ ein Icon. Zum Entfernen markierte Zeilen werden erst beim Speichern gelöscht; vorher kannst Du die Markierung zurücknehmen.';

$string['settingshelp_mapping_article'] = '<p>Wähle unter <strong>Manuelle H5P-Typzuordnung</strong> die Aktion <strong>Zuordnung hinzufügen</strong>, anschließend den H5P-Inhaltstyp und sein Icon. Jeder Typ hat eine Zuordnung, die für seine verschiedenen Versionen gilt.</p><p>Mit × markierst Du eine Zuordnung zum Entfernen; ↶ nimmt die Markierung zurück. Erst <strong>Änderungen speichern</strong> übernimmt die Bearbeitung. Wähle für nicht zugeordnete Typen ein <strong>Icon für nicht zugeordnete H5P-Typen</strong> oder behalte das originale Moodle-H5P-Icon.</p><p>Die Zuordnung greift, wenn die H5P-Typerkennung verwendet wird. Eine ausdrücklich an der Aktivität gewählte Darstellung hat weiterhin Vorrang.</p>';

$string['settingshelp_behaviour'] = 'Automatik und Darstellung';

$string['behaviour_help'] = 'Die Website-Automatik gilt für H5P-Aktivitäten ohne individuelle Auswahl, deren Kurs die Website-Vorgabe verwendet. Kurse können davon abweichen.';

$string['settingshelp_behaviour_article'] = '<p><strong>Nicht zugewiesene H5P-Aktivitäten automatisch klassifizieren</strong> legt die Website-Vorgabe fest. Diese Option ist nach der Installation ausgeschaltet, damit bestehende Kurse ihre Darstellung behalten. Prüfe vor dem Einschalten die Typzuordnungen und das Ersatzicon.</p><p>Ein Kurs kann die automatische H5P-Zuordnung unabhängig davon ein- oder ausschalten. An einer Aktivität behält <strong>Originalicon verwenden</strong> das Moodle-Icon bei; ein gewähltes Pool-Icon legt die Darstellung fest. <strong>H5P-Typ erkennen</strong> verwendet die Typerkennung unabhängig von der Kursvorgabe.</p><p><strong>Dynamisch geladene Aktivitätslisten ergänzen</strong> zeigt konfigurierte Icons auch in Standardlisten wie Zeitleiste und Zuletzt besuchte Elemente. Die Inhalte der Aktivitäten ändern sich dadurch nicht.</p><p>Die websiteweiten Schalter werden nur Personen angezeigt, die die Website-Konfiguration ändern dürfen.</p>';

$string['overview'] = $string['settingshelp_overview'];

$string['pool'] = $string['settingshelp_pool'];

$string['mapping'] = $string['settingshelp_mapping'];

$string['behaviour'] = $string['settingshelp_behaviour'];

$string['cachedef_h5ptypes'] = 'H5P-Inhaltstypen für Aktivitätssymbole';

$string['iconfiles_help'] = 'Lade dekorative Icons hoch, die öffentlich zugänglich sein dürfen. Das Entfernen aus diesem Entwurf löscht kein vorhandenes Pool-Icon.';
$string['h5pfallback_help'] = 'Dieses Icon gilt für H5P-Typen ohne Zuordnung. Eine individuelle Aktivitätsauswahl hat Vorrang.';
$string['autoh5p_help'] = 'Die Website-Vorgabe gilt für Kurse, die sie übernehmen. Kurse können automatische H5P-Icons gesondert ein- oder ausschalten.';
$string['dynamicfallback_help'] = 'Zeige zugeordnete Icons auch in dynamisch geladenen Aktivitätslisten wie Zeitleiste und zuletzt besuchten Elementen.';
