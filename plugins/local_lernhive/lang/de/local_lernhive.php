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
 * German language strings for LernHive.
 *
 * @package    local_lernhive
 * @copyright  2026 LernHive.de
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'LernHive';
$string['admin_dashboard'] = 'LernHive Dashboard';
$string['settings'] = 'LernHive Einstellungen';
$string['setting_level_configuration'] = 'Level-Konfiguration';

// Level names (English-first — canonical LernHive level names, identical in all languages).
$string['level_explorer'] = 'Explorer';
$string['level_creator'] = 'Creator';
$string['level_pro'] = 'Pro';
$string['level_expert'] = 'Expert';
$string['level_master'] = 'Master';

// Level descriptions.
$string['level_explorer_desc'] = 'Erste Schritte — Dateien, Textseiten und Links';
$string['level_creator_desc'] = 'Aufgaben & Feedback — Aufgaben stellen und bewerten';
$string['level_pro_desc'] = 'Tests & Interaktion — Quiz, H5P und Lernpfade';
$string['level_expert_desc'] = 'Kollaboration — Wiki, Glossar und Peer-Bewertung';
$string['level_master_desc'] = 'Voller Zugriff — Alle Moodle-Funktionen';

// Dashboard.
$string['current_level'] = 'Stufe {$a->level} — {$a->name}';
$string['current_level_short'] = 'LernHive-Stufe';
$string['total_teachers'] = 'Trainer/innen gesamt';
$string['save_level'] = 'Speichern';
$string['search_placeholder'] = 'Name oder E-Mail suchen...';
$string['no_teachers_found'] = 'Keine Trainer/innen gefunden. Stellen Sie sicher, dass Nutzer/innen die Rolle "Trainer/in" zugewiesen ist.';
$string['level_changed_success'] = 'Stufe von {$a->user} wurde auf {$a->level} — {$a->name} geändert.';

// Level bar.
$string['next_level_hint'] = 'Nächste Stufe ({$a->levelname}): {$a->modules}';

// Settings.
$string['setting_default_level'] = 'Standard-Stufe für neue Trainer/innen';
$string['setting_default_level_desc'] = 'Welche Stufe sollen neue Trainer/innen standardmäßig erhalten?';
$string['setting_show_levelbar'] = 'Level-Leiste anzeigen';
$string['setting_show_levelbar_desc'] = 'Zeigt Trainer/innen ihre aktuelle LernHive-Stufe als Leiste oben auf Kursseiten an.';
$string['setting_heading_level_configuration'] = 'Feature-Level konfigurieren';
$string['setting_heading_level_configuration_desc'] = 'Setzt pro Feature eine Override-Stufe. "Standard" nutzt Registry-Level oder Flavor-Preset, "Deaktiviert" blendet das Feature auf allen Stufen aus.';
$string['setting_feature_group'] = 'Kategorie: {$a->category}';
$string['setting_feature_override_default'] = 'Standard';
$string['setting_feature_override_disabled'] = 'Deaktiviert';
$string['setting_feature_override_desc'] = 'Feature-ID: {$a->featureid}<br>Standard-Level: {$a->defaultlevel}<br>Erforderliche Capability: <code>{$a->capability}</code>';

// Events.
$string['event_level_changed'] = 'LernHive-Stufe geändert';
$string['event_feature_override_changed'] = 'LernHive-Feature-Override geändert';

// Capabilities.
$string['lernhive:managelevel'] = 'LernHive-Stufen verwalten';
$string['lernhive:viewownlevel'] = 'Eigene LernHive-Stufe anzeigen';

// Settings — headings.
$string['setting_heading_levels'] = 'Level-Einstellungen';
$string['setting_heading_course_creation'] = 'Kurs-Erstellung';
$string['setting_heading_user_creation'] = 'Nutzer/innen-Erstellung';

// Settings — course creation.
$string['setting_allow_course_creation'] = 'Trainer/innen dürfen Kurse anlegen';
$string['setting_allow_course_creation_desc'] = 'Wenn aktiviert, erhält jede/r Trainer/in einen eigenen Kursbereich und kann selbst Kurse erstellen. Ein Button "Kurs anlegen" wird auf dem Dashboard angezeigt.';
$string['setting_parent_category'] = 'Oberkategorie für Trainer-Kursbereiche';
$string['setting_parent_category_desc'] = 'Unter welcher Kategorie sollen die persönlichen Trainer-Kursbereiche erstellt werden?';
$string['setting_parent_category_top'] = 'Oberste Ebene';

// Settings — user creation.
$string['setting_allow_user_creation'] = 'Trainer/innen dürfen Nutzer/innen anlegen';
$string['setting_allow_user_creation_desc'] = 'Wenn aktiviert, können Trainer/innen neue Nutzer/innen-Accounts erstellen. Das Formular wird vereinfacht dargestellt. Ein Button "Nutzer/in anlegen" wird auf dem Dashboard angezeigt.';

// Dashboard buttons.
$string['btn_create_course'] = 'Kurs anlegen';
$string['btn_create_user'] = 'Nutzer/in anlegen';

// Course manager.
$string['teacher_category_desc'] = 'Persönlicher Kursbereich von {$a}';

// Onboarding link (points to local_lernhive_start).
$string['onboarding_nav_link'] = 'Onboarding';

// Settings — user browsing.
$string['setting_heading_user_browse'] = 'Nutzer/innen-Übersicht';
$string['setting_allow_user_browse'] = 'Trainer/innen dürfen Nutzer/innen-Liste sehen';
$string['setting_allow_user_browse_desc'] = 'Wenn aktiviert, können Trainer/innen die vollständige Liste aller Nutzer/innen in der Installation einsehen. Die Ansicht wird für Explorer-Level vereinfacht dargestellt.';

// User list page.
$string['user_list_title'] = 'Nutzer/innen';
$string['user_search_placeholder'] = 'Name oder E-Mail suchen...';
$string['user_list_count'] = '{$a} Nutzer/innen';
$string['user_col_name'] = 'Name';
$string['user_col_lastaccess'] = 'Letzter Zugriff';
$string['user_col_actions'] = 'Aktionen';
$string['user_none_found'] = 'Keine Nutzer/innen gefunden.';
$string['user_suspend'] = 'Sperren';
$string['user_unsuspend'] = 'Entsperren';
$string['user_suspended_badge'] = 'Gesperrt';
$string['user_delete_confirm'] = 'Möchten Sie den Account von "{$a}" wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.';
$string['user_deleted'] = 'Nutzer/in "{$a}" wurde gelöscht.';

// Capabilities.
$string['lernhive:browseusers'] = 'Nutzer/innen-Liste einsehen';

// Sidebar.
$string['nav_userlist'] = 'Nutzer/innen';

// Course enrolment page.
$string['enrol_title'] = 'Einschreibung: {$a}';
$string['enrol_back_to_course'] = 'Zurück zum Kurs';
$string['enrol_search_title'] = 'Nutzer/innen einschreiben';
$string['enrol_search_placeholder'] = 'Name oder E-Mail suchen...';
$string['enrol_no_results'] = 'Keine passenden Nutzer/innen gefunden.';
$string['enrol_results_count'] = '{$a} Nutzer/innen gefunden';
$string['enrol_btn'] = 'Einschreiben';
$string['enrol_enrolled_title'] = 'Eingeschriebene Nutzer/innen';
$string['enrol_enrolled_count'] = '{$a} eingeschrieben';
$string['enrol_none_enrolled'] = 'Noch keine Nutzer/innen eingeschrieben.';
$string['enrol_remove_btn'] = 'Entfernen';
$string['enrol_confirm_remove'] = 'Nutzer/in wirklich aus dem Kurs entfernen?';
$string['enrol_added'] = 'Nutzer/in wurde eingeschrieben.';
$string['enrol_removed'] = 'Nutzer/in wurde aus dem Kurs entfernt.';

// Support page.
$string['support_title'] = 'Support zur Nutzung';
$string['support_onboarding_title'] = 'Onboarding-Touren';
$string['support_onboarding_desc'] = 'Schritt-für-Schritt-Anleitungen helfen Ihnen, alle Funktionen Ihres aktuellen Levels kennenzulernen.';
$string['support_courses_title'] = 'Kurse verwalten';
$string['support_courses_desc'] = 'Erfahren Sie, wie Sie Kurse anlegen, Inhalte hinzufügen und Einstellungen anpassen.';
$string['support_users_title'] = 'Nutzer/innen verwalten';
$string['support_users_desc'] = 'Lernen Sie, wie Sie Nutzer/innen anlegen, einschreiben und Rollen zuweisen.';
$string['support_contact_title'] = 'Weitere Hilfe benötigt?';
$string['support_contact_desc'] = 'Wenn Sie weitere Unterstützung benötigen, wenden Sie sich bitte an Ihre/n Administrator/in.';
$string['nav_support'] = 'Support';

// Privacy.
$string['privacy:metadata:local_lernhive_levels'] = 'Speichert die LernHive-Stufe jeder/jedes Trainer/in.';
$string['privacy:metadata:local_lernhive_levels:userid'] = 'Die Nutzer-ID der/des Trainer/in.';
$string['privacy:metadata:local_lernhive_levels:level'] = 'Die aktuelle Stufe (1-5).';
$string['privacy:metadata:local_lernhive_levels:updated_by'] = 'Die Nutzer-ID des Admins, der die Stufe geändert hat.';
