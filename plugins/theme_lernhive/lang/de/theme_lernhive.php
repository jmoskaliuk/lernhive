<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Deutsche Sprachdatei für das LernHive-Theme.
 *
 * @package    theme_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin-Metadaten.
$string['pluginname'] = 'LernHive';
$string['choosereadme'] = 'LernHive ist ein modernes Moodle-Theme, aufgebaut als Boost Union Child-Theme. Es bietet eine vereinfachte, übersichtliche Oberfläche, die speziell für das LernHive-Plugin-Ökosystem entwickelt wurde.';

// Einstellungen.
$string['configtitle'] = 'LernHive-Einstellungen';
$string['customcss'] = 'Eigenes CSS';
$string['customcss_desc'] = 'Eigene CSS-Regeln, die nach allen anderen Styles angewendet werden. Nutze dies für schnelle Anpassungen.';

// Layout-Optionen.
// Block-Regionen (seit 0.9.3 — der frühere rechte Block-Drawer 'side-pre' wurde entfernt).
$string['region-content-top'] = 'Inhalt oben';
$string['region-content-bottom'] = 'Inhalt unten';
$string['region-sidebar-bottom'] = 'Seitenleiste unten';
$string['region-footer-left'] = 'Fußzeile links';
$string['region-footer-center'] = 'Fußzeile Mitte';
$string['region-footer-right'] = 'Fußzeile rechts';
$string['region-footer'] = 'Fußzeile';
$string['layoutoption_limitedwidth'] = 'Begrenzte Breite';

// Datenschutz.
$string['privacy:metadata'] = 'Das LernHive-Theme speichert keine persönlichen Daten.';

// Launcher.
$string['launcher'] = 'Launcher';
$string['launcherdesc'] = 'Schnellaktionen zum Erstellen und Verwalten von Inhalten.';
$string['launcherstyle'] = 'Launcher-Stil';
$string['launcherstyledesc'] = 'Wähle den Grundstil des Launchers. Das kompakte Flyout ist die bevorzugte LernHive-Richtung.';
$string['launcherstylebase'] = 'Kompaktes Flyout';
$string['launcherstyledock'] = 'Dock-ähnliche Erweiterung';
$string['launchernoactions'] = 'In diesem Kontext sind derzeit keine Launcher-Aktionen verfügbar.';
$string['launcherpending'] = 'Launcher-Ziele werden interaktiv, sobald die zugehörigen Plugin-Aktionen angebunden sind.';

// Explore.
$string['exploreoptional'] = 'Explore ersetzt das Dashboard nur im optionalen LXP-Flavour.';
$string['primarynavigation'] = 'Hauptnavigation';
$string['coursenavigation'] = 'Kursnavigation';
$string['flavourpriority'] = 'Priorisierte Flavours';
$string['flavourprioritydesc'] = 'School und das optionale LXP-Flavour sind aktuell die wichtigsten Startpunkte.';
$string['exploreranking'] = 'Explore-Ranking-Prinzipien';
$string['exploreheroeyebrow'] = 'Optionales LXP-Flavour';
$string['exploreherotitle'] = 'Explore';
$string['exploreherosummary'] = 'Explore bleibt ruhig und gut scannbar. Es hebt Kurse, Snacks und Communities in wenigen klaren Feed-Blöcken hervor.';
$string['exploreheronotetitle'] = 'Warum Inhalte erscheinen';
$string['exploreheronote'] = 'Das Release-1-Ranking bleibt erklärbar: Community- und Audience-Relevanz kommen zuerst, Snacks werden bevorzugt, und die letzten 7 Tage zählen am meisten.';
$string['exploresearch'] = 'Kurse, Snacks, Communities und Themen durchsuchen';
$string['exploretools'] = 'Explore-Werkzeuge';
$string['exploredownload'] = 'Explore-Liste herunterladen';
$string['exploremore'] = 'Weitere Explore-Aktionen';

// ContentHub.
$string['contenthubeyebrow'] = 'Orchestrierungsebene';
$string['contenthubtitle'] = 'ContentHub';
$string['contenthubsummary'] = 'ContentHub hilft Nutzenden beim Einstieg. Die Logik für Copy, Template oder Library liegt nicht im ContentHub selbst.';
$string['contenthubtools'] = 'ContentHub-Werkzeuge';
$string['contenthubsearch'] = 'Inhaltspfade durchsuchen';
$string['contenthubfilter'] = 'Einträge filtern';
$string['contenthubguide'] = 'Import-Leitfaden für die Library herunterladen';
$string['contenthubnotetitle'] = 'Release-1-Hinweis';
$string['contenthubnote'] = 'Template und Library bleiben im Launcher und im ContentHub getrennt. Managed .mbz-Bereitstellung reicht für Release 1, während reichere Versionierungs- und Lifecycle-Themen später kommen.';

// Course surfaces.
$string['courserowlabel'] = 'Kursseite';
$string['coursenotetitle'] = 'Ruhige Kursstruktur';
$string['coursenotecopy'] = 'Kursseiten sollen lesbar und geführt bleiben. Snacks bleiben leichter und sollen nicht in volle Kurskomplexität hineinwachsen.';
$string['coursehelpers'] = 'Kurs-Helfer';
$string['coursehelpertitle'] = 'Hilfsbereich';
$string['coursehelpercopy'] = 'Context Helper und andere kursbezogene Blöcke können hier erscheinen, ohne den Hauptinhalt zu übernehmen.';
$string['courseheadertools'] = 'Werkzeuge der Kursseite';
$string['coursedownload'] = 'Kursübersicht herunterladen';
$string['courseprint'] = 'Kursseite drucken';
$string['coursemore'] = 'Weitere Kursaktionen';
$string['sectiondownload'] = 'Abschnittsliste herunterladen';
$string['sectionmore'] = 'Weitere Abschnittsaktionen';

// Snack surfaces.
$string['snacktools'] = 'Snack-Werkzeuge';
$string['snackdownload'] = 'Snack-Übersicht herunterladen';
$string['snackprint'] = 'Snack-Seite drucken';
$string['snackmore'] = 'Weitere Snack-Aktionen';
$string['snackpromise'] = 'Snack-Versprechen';
$string['snackreleasenote'] = 'Release-1-Hinweis';
$string['snackhelperarea'] = 'Snack-Hilfsbereich';

// Action labels.
$string['actioncreatecourse'] = 'Kurs erstellen';
$string['actioncreatecoursedesc'] = 'Den zentralen Kurs-Erstellungsfluss starten.';
$string['actioncontenthub'] = 'ContentHub';
$string['actioncontenthubdesc'] = 'Copy-, Template- und Library-Einstiege öffnen.';
$string['actioncreatesnack'] = 'Snack erstellen';
$string['actioncreatesnackdesc'] = 'Ein leichtgewichtiges 10-30-Minuten-Format erstellen.';
$string['actionopencopy'] = 'Copy-Flow öffnen';
$string['actionopentemplate'] = 'Template wählen';
$string['actionopenlibrary'] = 'Aus der Library importieren';

// Relationship actions.
$string['follow'] = 'Folgen';
$string['bookmark'] = 'Merken';

// --- Header Dock + Side Panel (0.9.36) --------------------------------------
$string['dock'] = 'Schnellzugriff';
$string['close'] = 'Schließen';

$string['messages'] = 'Nachrichten';
$string['messages_sub'] = 'Chats und Unterhaltungen';
$string['messages_empty'] = 'Deine Unterhaltungen erscheinen hier. Ungelesen-Zähler und Vorschauen kommen bald — bis dahin öffnest du die vollständige Nachrichtenansicht.';
$string['messages_openfull'] = 'Nachrichten öffnen';

$string['notifications'] = 'Benachrichtigungen';
$string['notifications_sub'] = 'Hinweise und Aktivitäts-Updates';
$string['notifications_empty'] = 'Deine Benachrichtigungen erscheinen hier. Gruppiert nach Tag, mit „Alle als gelesen markieren" — kommt in der nächsten Version. Bis dahin öffnest du die vollständige Ansicht.';
$string['notifications_openfull'] = 'Benachrichtigungen öffnen';
$string['notifications_prefs'] = 'Einstellungen';

$string['aiassistant'] = 'KI-Assistent';
$string['aiassistant_sub'] = 'Stell eine Frage zu deinem Lernstoff';
$string['aiassistant_empty'] = 'Der LernHive-KI-Assistent kommt bald. Er hilft dir mit Erklärungen, Übungsaufgaben und Lernvorschlägen — passend zu dem, wo du in deinen Kursen stehst.';

$string['help'] = 'Hilfe';
$string['help_sub'] = 'Anleitungen und Links für diese Seite';
$string['help_onthispage'] = 'Zu dieser Seite';
$string['help_startguide'] = 'Erste Schritte mit LernHive';
$string['help_startguide_desc'] = 'Kurzer Einstieg · 2 Min';
$string['help_dashboard'] = 'Dein Dashboard';
$string['help_dashboard_desc'] = 'Deinen Lernpfad ansehen';
$string['help_preferences'] = 'Benutzereinstellungen';
$string['help_preferences_desc'] = 'Sprache, Benachrichtigungen, Profil';

// --- Plugin Shell für Moodle-Kernseiten (0.9.40) ----------------------------
// Zone A / Zone B Strings, die von templates/plugin_shell_header.mustache
// via theme_lernhive_get_plugin_shell_context() gerendert werden. Ein Set pro
// freigeschalteter Pagetype: dashboard (my-index), mycourses (my-courses),
// profile (user-profile), preferences (user-preferences).
$string['shell_name_dashboard'] = 'Dashboard';
$string['shell_tagline_dashboard'] = 'Übersicht';
$string['shell_subtitle_dashboard'] = 'Dein Lernfortschritt, deine Kurse und schnelle Aktionen auf einen Blick.';
$string['shell_hint_dashboard'] = 'Mach da weiter, wo du aufgehört hast, oder entdecke unten neue Inhalte.';

$string['shell_name_mycourses'] = 'Meine Kurse';
$string['shell_tagline_mycourses'] = 'Einschreibungen';
$string['shell_subtitle_mycourses'] = 'Alles, wo du gerade eingeschrieben bist — laufende, kommende und abgeschlossene Kurse.';
$string['shell_hint_mycourses'] = 'Filtere nach Status oder suche einen Kurs direkt über seinen Namen.';

$string['shell_name_profile'] = 'Profil';
$string['shell_tagline_profile'] = 'Über dich';
$string['shell_subtitle_profile'] = 'Dein öffentliches LernHive-Profil — Name, Avatar, Interessen und aktuelle Aktivität.';
$string['shell_hint_profile'] = 'Andere Lernende sehen diese Seite. Halte sie aufgeräumt und zu dir passend.';

$string['shell_name_preferences'] = 'Einstellungen';
$string['shell_tagline_preferences'] = 'Kontoeinstellungen';
$string['shell_subtitle_preferences'] = 'Sprache, Benachrichtigungen, Sicherheit und alles andere zu deinem LernHive-Konto.';
$string['shell_hint_preferences'] = 'Änderungen werden sofort gespeichert. Einige Einstellungen greifen erst nach dem nächsten Login.';

// Tag-Pillen — eine kontextuelle Pille pro Seite, kombiniert mit einem FontAwesome-Icon.
$string['shell_tag_overview'] = 'Übersicht';
$string['shell_tag_courses'] = 'Kurse';
$string['shell_tag_account'] = 'Konto';
$string['shell_tag_settings'] = 'Einstellungen';
