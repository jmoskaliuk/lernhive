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
 * German strings for local_lernhive_flavour.
 *
 * @package    local_lernhive_flavour
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'LernHive Flavour';

// Admin page.
$string['page_title'] = 'Flavour-Setup';
$string['page_intro'] = 'Wähle das Flavour, das am besten zu deiner Organisation passt. Ein Flavour ist ein Startpunkt: es setzt sinnvolle Standardwerte für alle LernHive-Plugins, du kannst jede Einstellung aber nachträglich überschreiben.';

// Flavour labels and descriptions.
$string['flavour_school'] = 'School';
$string['flavour_school_desc'] = 'Klassisches LMS für Schulen und Bildungsträger. Trainer/innen besitzen ihre Kurse, verwalten ihre Lernenden, und der Level-Bar begleitet das Onboarding.';
$string['flavour_lxp'] = 'LXP';
$string['flavour_lxp_desc'] = 'Learning Experience Platform. Explore ersetzt das Dashboard, Discovery steht im Vordergrund, und Trainer/innen konzentrieren sich auf Snacks statt vollständiger Kurse.';
$string['flavour_highered'] = 'Higher Education';
$string['flavour_highered_desc'] = 'Hochschulen und Universitäten. Experimenteller Startpunkt — erbt aktuell die School-Defaults, bis Higher-Ed-Spezifika definiert sind.';
$string['flavour_corporate'] = 'Corporate Academy';
$string['flavour_corporate_desc'] = 'Firmenakademien und betriebliche Weiterbildung. Experimenteller Startpunkt — erbt aktuell die School-Defaults, bis Corporate-Academy-Spezifika definiert sind.';

// Card badges and buttons.
$string['badge_active'] = 'Aktiv';
$string['badge_experimental'] = 'Experimentell';
$string['btn_apply'] = 'Flavour anwenden';
$string['btn_current'] = 'Aktuelles Flavour';
$string['btn_confirm_apply'] = '„{$a}" trotzdem anwenden';
$string['btn_cancel'] = 'Abbrechen';

// Confirm diff dialog.
$string['diff_heading'] = 'Flavour-Wechsel bestätigen';
$string['diff_intro'] = 'Das Anwenden von „{$a}" ändert die folgenden Einstellungen. Prüfe den Diff unten und bestätige, wenn du fortfahren möchtest. Bestehende Werte werden überschrieben.';
$string['diff_col_component'] = 'Komponente';
$string['diff_col_setting'] = 'Einstellung';
$string['diff_col_current'] = 'Aktueller Wert';
$string['diff_col_target'] = 'Neuer Wert';
$string['value_unset'] = '(nicht gesetzt)';

// Result notifications.
$string['flavour_applied'] = 'Flavour „{$a}" wurde erfolgreich angewendet.';
$string['flavour_applied_with_overrides'] = 'Flavour „{$a}" wurde angewendet. Zuvor angepasste Einstellungen wurden überschrieben — Details im Audit-Log.';
$string['current_flavour'] = 'Aktuelles Flavour: {$a}';

// Errors.
$string['err_unknown_flavour'] = 'Unbekannter Flavour-Schlüssel.';

// Events.
$string['event_flavour_applied'] = 'Flavour angewendet';

// Privacy.
$string['privacy:metadata'] = 'LernHive Flavour speichert einen Audit-Verlauf der Flavour-Anwendungen. Der Verlauf enthält die Nutzer-ID des Admins, der das Apply ausgelöst hat, aber keine weiteren persönlichen Daten.';
$string['privacy:metadata:local_lernhive_flavour_apps'] = 'Audit-Verlauf der Flavour-Anwendungen.';
$string['privacy:metadata:local_lernhive_flavour_apps:applied_by'] = 'Die Nutzer/in, die das Flavour angewendet hat.';
$string['privacy:metadata:local_lernhive_flavour_apps:timeapplied'] = 'Zeitpunkt der Flavour-Anwendung.';
$string['privacy:metadata:local_lernhive_flavour_apps:flavour'] = 'Das angewendete Flavour.';
