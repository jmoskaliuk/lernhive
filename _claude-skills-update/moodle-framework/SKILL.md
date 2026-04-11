---
name: moodle-framework
description: >
  Umfassendes Framework für die Claude-gestützte Entwicklung von Moodle-Plugins.
  Ersetzt die bisherigen Skills moodle-dev, moodle-deploy, moodle-plugin-submit
  und eledia-moodle-ux. Verwende diesen Skill für JEDE Moodle-bezogene Aufgabe —
  Plugin-Entwicklung, Testing, Deployment, Submission, Architektur-Fragen,
  UX-Design, Fehlersuche. Trigger bei: "Moodle", "Plugin", "Behat", "PHPUnit",
  "deploy", "submit", "precheck", "Orb", "Docker Moodle", oder jeder Erwähnung
  von Moodle-Dateien wie version.php, lib.php, install.xml, mod_form.php.
  Auch bei projektspezifischen Namen wie "LeitnerFlow", "LernHive", "eLeDia".
---

# Moodle Plugin Development Framework

Dieses Framework konsolidiert das gesamte Wissen für die Entwicklung von Moodle-Plugins —
Architektur, Workflow, Testing, Submission und die Fehler-Datenbank aus bisheriger Arbeit.
Es ist **generisch** für jedes Moodle-Plugin einsetzbar. Codebeispiele verwenden
`mod_example` als Platzhalter; das Referenzprojekt LeitnerFlow zeigt die Patterns
in der Praxis.

**Ziel-Moodle-Versionen:** 4.3+ für Hooks API, 4.5+ als Minimum für Plugin-Submission,
5.0/5.1 als primäres Entwicklungsziel. Context-Klassen: immer `\core\context\*` (seit 4.2).

## Referenz-Dateien

Lade die relevante(n) Datei(en) basierend auf der Aufgabe:

| Aufgabe | Datei |
|---------|-------|
| Lokales Setup, Deployment, GitHub, Orb/Docker Pipeline | `references/01-workflow.md` |
| Moodle-Architektur, APIs, Plugin-Struktur, DB, Hooks | `references/02-architecture.md` |
| PHPUnit, Behat, Testdaten-Generatoren, CI | `references/03-testing.md` |
| Plugin-Submission, Prechecks, Directory-Upload, Approval | `references/04-submission.md` |
| Fehler-Datenbank, Lessons Learned, Prävention | `references/05-errors.md` |

**Regel:** Bei jeder Moodle-Aufgabe IMMER zuerst `references/05-errors.md` laden,
um bekannte Fehler zu vermeiden. Dann die aufgabenspezifische Referenz.

## Kern-Prinzipien

1. **Fehler nicht wiederholen** — Die Fehler-Datenbank (05) ist das wichtigste Dokument
2. **Moodle-nativ arbeiten** — Bootstrap, $DB, get_string(), Mustache, AMD — keine Workarounds
3. **Prechecks vor jedem Commit** — PHPCS, PHPDoc, Savepoint, Grunt, Lint
4. **Privacy & Backup immer** — Beide APIs von Anfang an implementieren
5. **Tests schreiben** — Behat für UI-Flows, PHPUnit für Logik

## Schnellstart: Neues Plugin

```bash
# 1. Scaffold generieren (Typ kann mod, local, block, tool etc. sein)
~/moodle-scaffold-plugin.sh <plugintype> <shortname> "Human Name"

# 2. Grundstruktur prüfen
cd ~/demo/site/moodle/public/<plugintype-ordner>/<shortname>
cat version.php
# Ordner je nach Typ: mod/, local/, blocks/, admin/tool/, theme/ etc.

# 3. Erste Prechecks
bash bin/precheck.sh

# 4. Deploy zu lokalem Moodle
# (via moodle-deploy Script oder manuell per Docker)

# 5. Tests schreiben & ausführen
# PHPUnit: vendor/bin/phpunit <plugintype-ordner>/<shortname>/tests/
# Behat: vendor/bin/behat --tags @<frankenstyle>
```

## Referenzprojekt: LeitnerFlow

LeitnerFlow (`mod_eledialeitnerflow`) ist das Referenzprojekt für alle Patterns.
- GitHub: `jmoskaliuk/mod_eledialeitnerflow`
- Typ: Activity Module (mod_*)
- Features: Leitner-Box Spaced Repetition, Question Bank Integration, Gradebook, Backup/Restore
- Status: Submitted to Moodle Plugins Directory
