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
 * German language strings for LernHive Onboarding.
 *
 * @package    local_lernhive_onboarding
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'LernHive Onboarding';

// Privacy.
$string['privacy:null_reason'] = 'Das Plugin speichert keine Benutzerdaten. Die Touren-Abschlüsse werden von Moodle Core in den Benutzereinstellungen verfolgt.';

// Capabilities.
$string['lernhive_onboarding:viewtours'] = 'Onboarding-Touren anzeigen';
$string['lernhive_onboarding:receivelearningpath'] = 'Trainer-Lernpfad-Banner auf dem Dashboard sehen';

// Trainer role (created by install.php / upgrade.php).
$string['trainer_role_name'] = 'LernHive Trainer/in';
$string['trainer_role_description'] = 'Kennzeichnet einen User als LernHive-Trainer/in — erhält den geführten Lernpfad auf dem Dashboard sowie die Onboarding-Touren.';

// Dashboard banner.
$string['banner_heading'] = 'Dein Trainer-Lernpfad';
$string['banner_intro_start'] = 'Starte dein Schritt-für-Schritt-Onboarding — geführte Touren zeigen dir jede Level-1-Fähigkeit.';
$string['banner_intro_resume'] = 'Mach da weiter, wo du aufgehört hast — noch ein paar Touren bis Level 1 abgeschlossen ist.';
$string['banner_cta_start'] = 'Lernpfad starten';
$string['banner_cta_resume'] = 'Lernpfad fortsetzen';
$string['banner_progress_label'] = '{$a->done} von {$a->total} Touren';

// Tour overview (Onboarding).
$string['tours_pagetitle'] = 'Dein Onboarding';
$string['tours_heading'] = 'Dein Onboarding';
$string['tours_intro'] = 'Schritt für Schritt durch LernHive — interaktive Touren führen dich durch alle wichtigen Funktionen.';

// --- Plugin Shell (0.2.2) ---
// Zone A (sticky header) + Zone B (info bar) Strings für tours.php.
$string['shell_name'] = 'Onboarding';
$string['shell_tagline'] = 'Lernpfad';
$string['shell_subtitle'] = 'Schritt für Schritt durch LernHive — interaktive Touren führen dich durch alle wichtigen Trainer-Skills.';
$string['shell_hint'] = 'Schließe eine Kategorie ab, um die nächste Tour-Stufe freizuschalten.';
$string['shell_tag_level'] = 'Level {$a}';

$string['tours_level_badge'] = 'Level {$a->level}: {$a->name}';
$string['tours_overall_progress'] = 'Gesamtfortschritt';
$string['tours_x_of_y'] = '{$a->done} von {$a->total} Touren';
$string['tours_status_not_started'] = 'Nicht gestartet';
$string['tours_status_in_progress'] = 'In Arbeit';
$string['tours_status_completed'] = 'Erledigt';
$string['tours_x_of_y_tours'] = '{$a->done} von {$a->total} Touren';
$string['tours_x_of_y_tour'] = '{$a->done} von {$a->total} Tour';
$string['tours_start'] = 'Tour starten';
$string['tours_restart'] = 'Wiederholen';
$string['tours_unlock_title'] = 'Bereit für das nächste Level?';
$string['tours_unlock_text'] = 'Schließe alle {$a->cats} Themen ab, um Level {$a->nextlevel} ({$a->nextname}) freizuschalten. Noch {$a->remaining} Touren offen.';
$string['tours_unlock_btn'] = 'Level {$a->level}: {$a->name} freischalten';
$string['tours_all_done'] = 'Du hast alle Touren auf diesem Level abgeschlossen!';
$string['tours_nav_link'] = 'Lernpfad';
$string['tour_completion_overlay_title'] = 'Tour abgeschlossen';
$string['tour_completion_overlay_body'] = 'Sehr gut. Möchtest du zur Onboarding-Übersicht zurück oder auf dieser Seite bleiben?';
$string['tour_completion_overlay_overview'] = 'Zur Onboarding-Übersicht';
$string['tour_completion_overlay_stay'] = 'Hier bleiben';

// Tour categories (Level 1: Explorer).
$string['tourcat_create_users'] = 'Nutzer/innen anlegen';
$string['tourcat_create_users_desc'] = 'Lerne, wie du Nutzer/innen-Accounts erstellst — einzeln oder per CSV-Upload.';
$string['tourcat_enrol_users'] = 'Nutzer/innen einschreiben';
$string['tourcat_enrol_users_desc'] = 'Nutzer/innen in Kurse einschreiben — manuell oder per Selbsteinschreibung.';
$string['tourcat_create_courses'] = 'Kurse anlegen';
$string['tourcat_create_courses_desc'] = 'Erstelle deinen ersten Kurs mit der vereinfachten LernHive-Oberfläche.';
$string['tourcat_course_settings'] = 'Kurseinstellungen';
$string['tourcat_course_settings_desc'] = 'Verstehe die wichtigsten Einstellungen — Format, Sichtbarkeit, Abschlussverfolgung.';
$string['tourcat_create_activities'] = 'Aktivitäten anlegen';
$string['tourcat_create_activities_desc'] = 'Erstelle Aufgaben, Foren und lade Dateien hoch — die Basis jedes Kurses.';
$string['tourcat_communication'] = 'Kommunikation';
$string['tourcat_communication_desc'] = 'Ankündigungen posten und Nachrichten an Nutzer/innen senden.';

// Tour names (Level 1: Explorer).
$string['tour_create_user_single'] = 'Einzelne/n Nutzer/in anlegen';
$string['tour_create_user_single_desc'] = 'Schritt für Schritt einen neuen Account erstellen.';
$string['tour_create_user_csv'] = 'CSV-Upload';
$string['tour_create_user_csv_desc'] = 'Mehrere Nutzer/innen auf einmal per CSV-Datei anlegen.';
$string['tour_enrol_manual'] = 'Manuelle Einschreibung';
$string['tour_enrol_manual_desc'] = 'Nutzer/innen einzeln in einen Kurs einschreiben.';
$string['tour_enrol_self'] = 'Selbsteinschreibung einrichten';
$string['tour_enrol_self_desc'] = 'Nutzer/innen erlauben, sich selbst in den Kurs einzuschreiben.';
$string['tour_create_course'] = 'Kurs erstellen';
$string['tour_create_course_desc'] = 'Einen neuen Kurs mit der vereinfachten Oberfläche erstellen.';
$string['tour_course_format'] = 'Kursformat & Sichtbarkeit';
$string['tour_course_format_desc'] = 'Format, Sichtbarkeit und Darstellung deines Kurses anpassen.';
$string['tour_course_completion'] = 'Abschlussverfolgung';
$string['tour_course_completion_desc'] = 'Kursabschluss-Bedingungen für Nutzer/innen einrichten.';
$string['tour_activity_assignment'] = 'Aufgabe erstellen';
$string['tour_activity_assignment_desc'] = 'Eine Abgabe-Aufgabe für deine Klasse erstellen.';
$string['tour_activity_forum'] = 'Forum erstellen';
$string['tour_activity_forum_desc'] = 'Ein Diskussionsforum für deine Klasse einrichten.';
$string['tour_activity_file'] = 'Datei hochladen';
$string['tour_activity_file_desc'] = 'PDFs, Bilder oder andere Dateien im Kurs bereitstellen.';
$string['tour_communication_announcements'] = 'Ankündigungen posten';
$string['tour_communication_announcements_desc'] = 'Neuigkeiten über das Ankündigungsforum an alle senden.';
$string['tour_communication_messaging'] = 'Nachrichten senden';
$string['tour_communication_messaging_desc'] = 'Direktnachrichten an einzelne Nutzer/innen senden.';

// Onboarding-Sandbox-Kurs — versteckter Kurs, auf den {DEMOCOURSEID} aufgelöst wird.
$string['sandbox_course_fullname'] = 'LernHive Onboarding-Sandbox';
$string['sandbox_course_summary'] = '<p>Versteckter Sandbox-Kurs für die LernHive-Trainer-Onboardingtouren. Dient als sicheres Ziel für Touren, die einen Kurskontext brauchen. Nicht löschen, ohne vorher Ersatz zu schaffen — Touren mit <code>{DEMOCOURSEID}</code>-Platzhalter landen sonst auf einem ungültigen Kurs.</p>';

// Admin-Einstellungen.
$string['setting_trainercoursecategoryid'] = 'Kurskategorie für Trainer/innen';
$string['setting_trainercoursecategoryid_desc'] = 'Die Kurskategorie, in der die „Kurs erstellen"-Onboarding-Tour neue Trainer/innen landen lässt. Admins sollten diese Einstellung auf die Kategorie setzen, in der eure Trainer/innen ihre Kurse anlegen sollen — besonders auf mandantenfähigen Installationen, auf denen die Standard-Kategorie <em>Verschiedenes</em> für Trainer/innen oft ausgeblendet ist.';
