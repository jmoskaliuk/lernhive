# Level × Feature × Tour Matrix — Review Draft v2

**Status:** Draft v2 nach Feedback-Runde 1 (2026-04-11)
**Quellen:** `local_lernhive/classes/capability_mapper.php` (Ist-Zustand), vorhandene Tour-JSONs in `tours/level1/`, vorgeschlagene Tour-Pakete für Level 2–5.

Die Tabellen beschreiben, welche Moodle-Features (Aktivitätsmodule + Kern-Capabilities) auf jeder LernHive-Stufe freigeschaltet sind und welche Onboarding-Touren daraus abgeleitet werden.

**Änderungen gegenüber v1 (aus Review eingearbeitet):**
- Assignment-Tour von Level 1 → Level 2 verschoben.
- BigBlueButton ersetzt Chat; kommt neu auf **Level 2** (nicht Level 5).
- Gruppen: Level 2 → **Level 3**.
- Gradebook (Setup + Verwaltung): Level 3 → **Level 4**.
- Reports: Level 2 → **Level 4**.
- Level-Tiefe: schlank, **5–7 Touren pro Level**.
- Tour-Kategorien haben Icons und Farben (auch für Level 2–5).
- Konzeptioneller Zusatz: konfigurierbare Rechte-Level und Flavor-Verknüpfung (siehe eigener Abschnitt unten).

---

## Übersicht — Welche Features sind pro Level „erlaubt"?

| Level | Name | Neue Aktivitätsmodule | Neue Kern-Rechte | Tour-Kategorien | Touren |
|---:|---|---|---|---|---:|
| 1 | **Explorer** | Datei, Textseite, Textfeld, Link, Verzeichnis, Forum (nur Ankündigungen) | Basis: Kurs anlegen, Nutzer/innen einschreiben. **Optional** (per Admin aktivierbar): Nutzer/innen neu anlegen | Nutzer/innen anlegen ⚙ · Einschreiben · Kurs anlegen · Kurseinstellungen · Aktivitäten anlegen · Kommunikation | 10 (+1 optional) |
| 2 | **Creator** | + Aufgabe, Forum (alle Typen), BigBlueButton | + `grade:view`/`grade:viewall` (Einreichungen ansehen & bewerten — ohne Gradebook-Setup) | Aufgaben-Workflow · Forum vertieft · BigBlueButton | 6 |
| 3 | **Pro** | + Test (Quiz), + H5P, + Lektion | + Gruppenverwaltung (`course:managegroups`) | Fragensammlung · Tests bauen · H5P · Lektion · Gruppen | 7 |
| 4 | **Expert** | + Wiki, + Glossar, + Datenbank, + Workshop (Peer-Review) | + Gradebook-Setup (`grade:manage`, `grade:edit`), + Reports (`site:viewreports`), + Einschreibe-Methoden konfigurieren (`course:enrolconfig`) | Kollaborative Formate · Peer-Bewertung · Gradebook · Reports · Einschreibe-Methoden | 7 |
| 5 | **Master** | + SCORM, + LTI, + Feedback, + Abstimmung, + Umfrage, + Buch, + IMS CP, + Subsection | + Kurssicherung, Kurswiederherstellung, Kursimport | Standards & externe Tools · Evaluation & Feedback · Struktur & Gliederung · Kurs-Lifecycle | 7 |

> **Invariant:** Level-Freischaltungen sind **kumulativ** — ab Level 2 hat der Trainer/die Trainerin weiterhin alles aus Level 1. Das Touren-Dashboard zeigt deshalb zusätzlich die Kategorien aller niedrigeren Level mit ihrem Abschluss-Status.

> ⚙ = per Admin-Einstellung konfigurierbar (siehe nächster Abschnitt).

---

## Konzeptioneller Zusatz: konfigurierbare Level-Rechte und Flavor-Bindung

Das Onboarding-Plugin geht aktuell davon aus, dass die Zuordnung „Feature → Level" **fest codiert** ist (`capability_mapper::get_level_modules()` + `get_level_capabilities()`). Das passt nicht zu allen LernHive-Flavors — darum muss das Konzept erweitert werden. Drei neue Bausteine:

### 1. Admin-konfigurierbare Level-Zuordnung

Jedes Feature (Modul oder Kern-Capability) bekommt eine **Standard-Level-Zuweisung**, die der Admin pro Site überschreiben kann. Beispiele:

- **Flavor „Schule":** Der Admin hebt `create_users` dauerhaft auf Level 1, weil Lehrkräfte von Beginn an eigene Klassenlisten pflegen müssen.
- **Flavor „Academy":** Der Admin verschiebt `grade:manage` von Level 4 auf Level 1, weil dort die Trainer/innen sofort ins Gradebook müssen.
- **Flavor „LXP":** Der Admin senkt Level-Anforderungen für Kurs-Erstellung und Einladung drastisch, weil auch Teilnehmer/innen Kurse anlegen.

**Implikation für die Tour-Zuordnung:** Wird ein Feature auf ein anderes Level verschoben, wandert die passende Tour automatisch mit. Die Kategorie-/Tour-Zuordnung darf also nicht mehr über „Tour liegt im Ordner `tours/levelN/`" laufen, sondern muss über ein **Feature-Identifier pro Tour** auflösen:

```
tour → feature_id (z. B. "mod_assign.create")
feature_id → effective_level (via admin config)
category → effective_level (kleinster effective_level aller gemappten features)
```

Im Dashboard zeigt `tour_manager::get_categories($userlevel)` dann nicht mehr starre Level-Kategorien, sondern alle Kategorien mit `effective_level <= $userlevel`.

### 2. Rechts-abhängige Tour-Sichtbarkeit

**Regel:** Wenn ein Trainer/eine Trainerin ein Recht gerade **nicht** hat — sei es, weil das Feature noch auf einem höheren Level liegt, sei es, weil der Admin es explizit abgeschaltet hat — muss die zugehörige Tour **unsichtbar** sein. Das gilt pro User, nicht global.

Das heißt: `tour_manager::get_category_tours()` darf nicht einfach aus der DB lesen, sondern muss jede Tour durch einen `feature_gate`-Check leiten, der `has_capability()` bzw. die Moodle-Core-Capability auf dem relevanten Kontext prüft.

**Folgerung für die Architektur:** Wir brauchen eine neue Klasse `feature_registry` (oder ähnlich) in `local_lernhive`, die
- Features registriert (Name, Default-Level, required Capability, beschreibende Strings),
- die Admin-Override-Tabelle kapselt,
- `effective_level(feature_id)` und `is_available_for_user(feature_id, userid)` liefert.

Die bisherige `capability_mapper` wird dann ein Konsument dieser Registry statt deren Ersatz.

### 3. Flavor-abhängige Zielgruppe: Trainer/innen *vs.* Teilnehmer/innen

Der Onboarding-Lernpfad ist heute exklusiv für die Rolle `lernhive_trainer` (Capability `local/lernhive_onboarding:receivelearningpath`). Im **LXP-Flavor** können jedoch auch Teilnehmer/innen Kurse anlegen und Mitglieder einladen — sie brauchen die gleichen Basics.

**Vorschlag:**

- Capability bleibt bestehen, aber die *Zuweisung* wird flavor-abhängig. Im Flavor „LXP" wird die Capability auch an `authenticated user` (oder eine passende Teilnehmer-Rolle) vererbt.
- Das Banner-Gate (`banner_gate::should_show()`) verändert sich nicht — es prüft weiterhin nur die Capability, egal wem sie zugewiesen wurde.
- Der **Inhalt** der Touren muss neutral formuliert sein, wo Rolle und Kontext divergieren. Konkret: in LXP-Flavor-Touren nicht von „deinen Schüler/innen" sprechen, sondern von „Teilnehmer/innen deines Kurses". Wir brauchen entweder pro Flavor eigene Tour-Strings oder eine Platzhalter-Ersetzung über `get_string()`.
- Die Touren-Auswahl pro Level kann bleiben — ein Teilnehmer im LXP-Flavor bekommt exakt dieselben Level-1-Basics wie ein Trainer im Schul-Flavor, solange der Admin die Capability freigeschaltet hat.

**Offen:** Braucht es pro Flavor eigene Default-Level-Overrides? Oder reicht ein Flavor-Wizard, der beim Aktivieren die richtigen Overrides vorschlägt? → Diskussion in eigenem ADR.

---

## Level 1 — Explorer *(Update zu v1)*

**Freigeschaltet:** mod_resource, mod_page, mod_label, mod_url, mod_folder, mod_forum *(nur Ankündigungsforum, gefiltert per Typ)*, Kurs anlegen, Nutzer/innen einschreiben.

**Optional (per Admin-Setting):** mod_user:create — Nutzer/innen neu anlegen *(Default: **an**, weil Schul-Flavor das braucht; kann abgeschaltet werden)*.

**Gesperrt:** Aufgabe, BigBlueButton, volles Forum (Level 2); Gruppen, Quiz, H5P, Lektion (Level 3); Wiki, Glossar, Datenbank, Workshop, Gradebook-Setup, Reports, Enrolment-Config (Level 4); SCORM, LTI, Feedback, Choice, Survey, Book, IMS CP, Subsection, Backup/Restore/Import (Level 5).

### Kategorien & Touren

| # | Kategorie | Shortname | Icon | Farbe | Tour | Datei | Anmerkung |
|---:|---|---|---|---|---|---|---|
| 1 | Nutzer/innen anlegen ⚙ | `create_users` | `user-plus` | `#2563eb` | Nutzer/in einzeln anlegen | `01_single.json` | nur wenn Admin `create_users` auf L1 freigeschaltet hat |
| 2 | Nutzer/innen anlegen ⚙ | `create_users` | `user-plus` | `#2563eb` | Nutzer/innen per CSV importieren | `02_csv.json` | wie oben |
| 3 | Einschreiben | `enrol_users` | `users` | `#16a34a` | Manuell einschreiben | `01_manual.json` | — |
| 4 | Einschreiben | `enrol_users` | `users` | `#16a34a` | Selbsteinschreibung aktivieren | `02_self.json` | — |
| 5 | Kurs anlegen | `create_courses` | `book-plus` | `#7c3aed` | Kurs neu anlegen | `01_create.json` | — |
| 6 | Kurseinstellungen | `course_settings` | `settings` | `#d97706` | Kursformat wählen | `01_format.json` | — |
| 7 | Kurseinstellungen | `course_settings` | `settings` | `#d97706` | Abschlussverfolgung einrichten | `02_completion.json` | — |
| 8 | Aktivitäten anlegen | `create_activities` | `plus-square` | `#0d9488` | Forum anlegen *(nur Ankündigung)* | `02_forum.json` | umbenennen/überarbeiten: Ankündigungsforum-Fokus |
| 9 | Aktivitäten anlegen | `create_activities` | `plus-square` | `#0d9488` | Datei bereitstellen | `03_file.json` | — |
| 10 | Kommunikation | `communication` | `message-circle` | `#dc2626` | Ankündigung schreiben | `01_announcements.json` | — |
| 11 | Kommunikation | `communication` | `message-circle` | `#dc2626` | Messaging benutzen | `02_messaging.json` | — |

> **Migration:** `tours/level1/create_activities/01_assignment.json` wird nach `tours/level2/assignments/01_create.json` verschoben und umbenannt. Der bestehende Tour-Content kann übernommen werden.

**Gesamt:** 9 Touren fix + 2 optional = max. 11 Touren, min. 9.

---

## Level 2 — Creator *(neu, v2)*

**Neu freigeschaltet:** `mod_assign`, volles `mod_forum` (alle Typen), `mod_bigbluebuttonbn`, plus Kern-Capabilities `moodle/grade:view`, `moodle/grade:viewall` *(nur so viel Grade-Recht wie nötig, um Aufgaben zu bewerten — kein Gradebook-Setup!)*.

**Narrativ:** Level 1 war „Inhalte bereitstellen". Level 2 ist „echte Lernaktivität": Aufgaben einsammeln und bewerten, echte Diskussionen moderieren, synchron über BigBlueButton unterrichten.

### Kategorien & Touren

| # | Kategorie | Shortname | Icon | Farbe | Tour |
|---:|---|---|---|---|---|
| 1 | Aufgaben-Workflow | `assignments` | `clipboard-check` | `#ea580c` | Aufgabe erstellen *(aus Level 1 übernommen)* |
| 2 | Aufgaben-Workflow | `assignments` | `clipboard-check` | `#ea580c` | Abgaben ansehen und bewerten |
| 3 | Forum vertieft | `forum_advanced` | `messages-square` | `#dc2626` | Diskussionsforum vs. Ankündigung — welcher Typ wann |
| 4 | Forum vertieft | `forum_advanced` | `messages-square` | `#dc2626` | Abos, Pflichteinträge, Bewertung im Forum |
| 5 | BigBlueButton | `bigbluebutton` | `video` | `#0891b2` | BBB-Raum im Kurs anlegen |
| 6 | BigBlueButton | `bigbluebutton` | `video` | `#0891b2` | Aufzeichnung aktivieren und teilen |

**Gesamt:** 6 Touren in 3 Kategorien.

> **Abhängigkeit:** BigBlueButton ist kein Core-Modul. Entweder `mod_bigbluebuttonbn` wird als Plugin-Abhängigkeit im Onboarding definiert, oder die Kategorie wird nur eingeblendet, wenn das Modul auf der Site tatsächlich installiert ist. Empfehlung: **bedingte Einblendung** per `\core_plugin_manager::instance()->get_plugin_info('mod_bigbluebuttonbn')`.

---

## Level 3 — Pro *(neu, v2)*

**Neu freigeschaltet:** `mod_quiz`, `mod_h5pactivity`, `mod_lesson`, plus Kern-Capability `moodle/course:managegroups`.

**Narrativ:** Jetzt geht es um *skalierbare, automatisch bewertbare* Formate und um *Gruppen-basiertes Lernen*.

### Kategorien & Touren

| # | Kategorie | Shortname | Icon | Farbe | Tour |
|---:|---|---|---|---|---|
| 1 | Fragensammlung | `question_bank` | `library` | `#7c3aed` | Kategorien anlegen und eine Multiple-Choice-Frage erstellen |
| 2 | Tests bauen | `quizzes` | `clipboard-list` | `#2563eb` | Quiz anlegen und Fragen hinzufügen |
| 3 | Tests bauen | `quizzes` | `clipboard-list` | `#2563eb` | Versuchs- und Bewertungs-Optionen |
| 4 | H5P-Inhalte | `h5p_content` | `puzzle` | `#16a34a` | H5P-Aktivität anlegen und Content-Type wählen |
| 5 | Lektionen | `lessons` | `book-open` | `#d97706` | Lektion mit Verzweigungen aufbauen |
| 6 | Gruppen | `groups` | `users-round` | `#0f766e` | Gruppen anlegen und Mitglieder zuweisen |
| 7 | Gruppen | `groups` | `users-round` | `#0f766e` | Gruppenmodus auf Kurs-/Aktivitätsebene |

**Gesamt:** 7 Touren in 5 Kategorien.

---

## Level 4 — Expert *(neu, v2)*

**Neu freigeschaltet:** `mod_wiki`, `mod_glossary`, `mod_data`, `mod_workshop`, plus Kern-Capabilities `moodle/grade:manage`, `moodle/grade:edit` *(Gradebook-Setup)*, `moodle/site:viewreports` *(Reports)*, `moodle/course:enrolconfig` *(Einschreibe-Methoden konfigurieren)*.

**Narrativ:** Level 4 ist der „Administrations-Schritt": Gradebook-Struktur selbst aufsetzen, Reports lesen und interpretieren, Einschreibe-Methoden konfigurieren — plus kollaborative und Peer-Review-Formate.

### Kategorien & Touren

| # | Kategorie | Shortname | Icon | Farbe | Tour |
|---:|---|---|---|---|---|
| 1 | Kollaborative Formate | `collaborative` | `users` | `#7c3aed` | Wiki und Glossar als gemeinsame Wissensspeicher |
| 2 | Strukturierte Daten | `databases` | `database` | `#2563eb` | Datenbank-Aktivität mit Feldtypen und Templates |
| 3 | Peer-Bewertung | `peer_review` | `git-compare` | `#16a34a` | Workshop-Phasen aufsetzen und abschließen |
| 4 | Gradebook | `gradebook` | `book-marked` | `#d97706` | Kategorien, Gewichtungen und Skalen |
| 5 | Reports | `reports` | `bar-chart-3` | `#dc2626` | Kurs-Reports verstehen und interpretieren |
| 6 | Einschreibe-Methoden | `enrolment_methods` | `key` | `#0891b2` | Selbsteinschreibung und Einschreibeschlüssel |
| 7 | Einschreibe-Methoden | `enrolment_methods` | `key` | `#0891b2` | Kohorten-Sync und Meta-Link-Einschreibung |

**Gesamt:** 7 Touren in 6 Kategorien.

---

## Level 5 — Master *(Update zu v1)*

**Neu freigeschaltet:** `mod_scorm`, `mod_lti`, `mod_feedback`, `mod_choice`, `mod_survey`, `mod_book`, `mod_imscp`, `mod_subsection`, plus `moodle/backup:backupcourse`, `moodle/restore:restorecourse`, `moodle/course:import`.

**Gestrichen gegenüber v1:** `mod_chat` (deprecated; BigBlueButton übernimmt die synchrone Kommunikations-Rolle bereits auf Level 2).

**Narrativ:** Volle Moodle-Breite — Standards (SCORM/LTI), Evaluations-Instrumente, Strukturierungs-Formate, Lifecycle-Verwaltung.

### Kategorien & Touren

| # | Kategorie | Shortname | Icon | Farbe | Tour |
|---:|---|---|---|---|---|
| 1 | Standards & externe Tools | `standards_lti` | `package` | `#7c3aed` | SCORM-/IMS-CP-Paket hochladen |
| 2 | Standards & externe Tools | `standards_lti` | `package` | `#7c3aed` | LTI-Tool verbinden (externes System) |
| 3 | Evaluation & Feedback | `eval_feedback` | `message-square-heart` | `#ea580c` | Feedback-Aktivität für Kursfeedback |
| 4 | Evaluation & Feedback | `eval_feedback` | `message-square-heart` | `#ea580c` | Abstimmung (Choice) und Umfrage (Survey) |
| 5 | Struktur & Gliederung | `structure` | `layout-list` | `#2563eb` | Buch-Aktivität als Skript |
| 6 | Struktur & Gliederung | `structure` | `layout-list` | `#2563eb` | Subsections im Kurs verwenden |
| 7 | Kurs-Lifecycle | `course_lifecycle` | `archive` | `#16a34a` | Kurs sichern, wiederherstellen, importieren |

**Gesamt:** 7 Touren in 4 Kategorien.

---

## Getroffene Entscheidungen (Review-Runde 1)

| # | Frage | Entscheidung |
|---:|---|---|
| 1 | Assignment-Inkonsistenz | **Tour nach Level 2** (Creator) |
| 2 | Level-2-Upgrade-Trigger | **Auto nach Level-1-Abschluss ODER früher durch Admin** (manueller Admin-Override erlaubt, auch wenn Touren unvollständig) |
| 3 | Tour-Naming | **Englische Shortnames** — Übersetzung kommt separat über `lang/de/`-Strings |
| 4 | Chat vs. BigBlueButton | **BBB statt Chat**, und BBB kommt bereits auf **Level 2** (nicht Level 5) |
| 5 | Tour-Tiefe Level 3–5 | **Schlank, 5–7 Touren pro Level** |
| 6 | Kategorien-Icons/Farben | **Jetzt festlegen** — siehe Tabellen oben |
| 7 | *(neu aus Level-1-Feedback)* Nutzer/innen anlegen auf Level 1 | **Ja, aber konfigurierbar** — Default: an, Admin kann ausschalten; zugehörige Tour ist dann unsichtbar |
| 8 | Gruppen | **Level 3 (Pro)** — nicht mehr Level 2 |
| 9 | Gradebook-Setup | **Level 4 (Expert)** — auf Level 2 bleibt nur `grade:view` fürs Bewerten von Aufgaben |
| 10 | Reports | **Level 4 (Expert)** — nicht mehr Level 2 |

---

## Neue offene Punkte (für Runde 2)

1. **Feature-Registry als Architektur-Fundament.** Brauchen wir in `local_lernhive` eine neue `feature_registry`, die Default-Level, Admin-Override und `has_capability`-Mapping pro Feature kapselt? → Ich schlage vor: **ja, eigener ADR** (z. B. `ADR-P02: Feature Registry & konfigurierbare Level-Rechte`).
2. **Admin-UI für Level-Overrides.** Wo lebt die Einstellung? Vorschlag: neues Settings-Panel unter `Site administration → LernHive → Level configuration`, tabellarische Übersicht „Feature → Default-Level → Override". Braucht eigenes `settings.php` in `local_lernhive` oder `local_lernhive_onboarding`?
3. **Tour → Feature-Mapping.** Tours müssen ein `feature_id`-Attribut bekommen (JSON-Metadaten oder Spalte in `local_lhonb_map`). Ohne das kann die „Tour folgt dem Recht automatisch"-Logik nicht greifen.
4. **Flavor-Konzept schärfen.**
   - Im LXP-Flavor ist die Zielgruppe auch „Teilnehmer/in mit Kurs-Erstellen-Recht". Wer weist die Capability zu — Flavor-Installer oder Admin-Klick?
   - Braucht es pro Flavor eigene Tour-String-Varianten (Trainer/in vs. Teilnehmer/in)? Oder reicht eine neutrale Formulierung „im eigenen Kurs"?
   - Konkreter Vorschlag: **Flavor-spezifische Level-Override-Presets** als Teil der Flavor-Installation (`local_lernhive_flavour` liefert das Preset, `local_lernhive` wendet es an).
5. **Migration der existierenden Level-1-Assignment-Tour.** Datei verschieben, Katalog-Eintrag in `tour_importer::seed_categories()` für `assignments` anlegen, `db/upgrade.php`-Schritt für bestehende Installationen (Tour vom alten Mapping trennen + neu zuordnen).
6. **BigBlueButton als Soft-Dependency.** In `version.php` nur `recommends`, und in `tour_importer::import_level(2)` die Kategorie `bigbluebutton` überspringen, wenn `mod_bigbluebuttonbn` nicht installiert ist. Alternative: harte Dependency und Hinweis bei Installation.
7. **Default-Overrides für Flavor „Schule".** `create_users` auf L1 ist bereits der Default — aber sollten weitere Schul-spezifische Freigaben mit dazu? (z. B. `course:enrolconfig` auf L2 für Klassenraum-Admin-Szenarien)
