# Submission — Plugins Directory Publishing Playbook

This file covers the end-to-end process of publishing a Moodle plugin
to the **Moodle Plugins Directory** (`https://moodle.org/plugins`). It
assumes the plugin is already production-quality in terms of code and
tests; this document is about getting it **past the reviewers** and
into the directory.

Target audience: a maintainer who has never submitted before, or one
who has shipped plugins before but wants a repeatable checklist.
Reference project: LeitnerFlow (`mod_eledialeitnerflow`).

---

## 1. Before you submit — hard gates

A submission that fails any of these bounces back instantly:

- [ ] **Privacy API implemented.** `classes/privacy/provider.php` is
      present and correct, even if it's just a `null_provider`.
      Reviewers always check this.
- [ ] **`$plugin->component` matches directory.** `local_example` in
      `version.php` → the zip must extract to `local/example/`, not
      `local_example/`.
- [ ] **`$plugin->requires` is honest.** Do not claim to support a
      version you didn't actually test. Minimum accepted at time of
      writing: Moodle 4.5 (2024100700).
- [ ] **`$plugin->maturity = MATURITY_STABLE;`** for a real release.
      Use `MATURITY_BETA` for preview, but most reviewers want STABLE
      for directory listings.
- [ ] **`$plugin->release`** is a human-readable version like
      `'1.0.0'` — not a date, not a `v` prefix.
- [ ] **GPL v3+ license header** in every PHP file. Moodle is GPLv3+,
      and the reviewers **will** grep for the header. Use the
      canonical block:

```php
// This file is part of the Moodle plugin local_example.
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * @package    local_example
 * @copyright  2026 Your Name <you@example.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
```

- [ ] **No external binaries, no bundled third-party JS/CSS without
      provenance.** If you ship a `amd/build/` file, it must be the
      `grunt amd` output of a `amd/src/` file you ship alongside.
      If you ship a vendored library, include the unmodified license
      file and note provenance in a `thirdpartylibs.xml` file.
- [ ] **English language strings exist.** `lang/en/<frankenstyle>.php`
      is non-empty and all `get_string()` calls have matching entries.
      Reviewers test with English set as the site language.
- [ ] **Capabilities defined in `db/access.php`** if the plugin adds
      any. If you gate a page with `require_capability` but never
      declared the capability, that's an instant bounce.
- [ ] **No `.git`, `.github`, `node_modules`, `vendor/`, or IDE files
      in the ZIP.** Use `git archive` (see §4) to build the clean
      release.

---

## 2. Local QA prechecks

### 2a. Moodle QA suite (`moodle-plugin-ci`)

The Moodle QA bot runs [`moodle-plugin-ci`](https://moodlehq.github.io/moodle-plugin-ci/)
against every submission. Run the same checks locally first:

```bash
# One-time setup
composer global require moodlehq/moodle-plugin-ci
export PATH="$PATH:$HOME/.composer/vendor/bin"

# Inside the plugin directory
moodle-plugin-ci phplint
moodle-plugin-ci phpmd
moodle-plugin-ci phpcs
moodle-plugin-ci validate
moodle-plugin-ci savepoints
moodle-plugin-ci mustache
moodle-plugin-ci grunt
moodle-plugin-ci phpunit
moodle-plugin-ci behat
```

Any red output here **will** be red in the reviewer pipeline. Fix
before submitting.

### 2b. PHPCS with Moodle standard

Install the Moodle coding style:

```bash
composer global require moodlehq/moodle-cs
```

Run against your plugin:

```bash
~/.composer/vendor/bin/phpcs --standard=moodle path/to/local/example
```

Common PHPCS complaints:
- Missing `defined('MOODLE_INTERNAL') || die();` at the top of lib
  files (but not at the top of class files — class files use the
  namespace declaration instead).
- Missing PHPDoc on classes, methods, and functions.
- Missing `@package` / `@copyright` / `@license` tags.
- Lines over 132 characters.
- Space-vs-tab indentation drift.
- Snake_case vs camelCase in places Moodle cares about.

### 2c. PHPDoc

Every class, method, function, and property must have a docblock.
Public API needs `@param` / `@return` tags. Moodle is strict about this
— it's the single biggest source of QA bounces.

Minimum method docblock:

```php
/**
 * Retrieve items belonging to the given user.
 *
 * @param int $userid The user ID to filter by.
 * @param int $limit  Maximum number of items to return.
 * @return \stdClass[] Records from local_example_items.
 */
public function get_items_for_user(int $userid, int $limit = 20): array {
    // ...
}
```

### 2d. Savepoints match versions

`moodle-plugin-ci savepoints` grep-checks that every `if ($oldversion <
…)` block in `db/upgrade.php` ends with `upgrade_plugin_savepoint(true,
…, 'local', 'example')` and that the version number is present in
`$plugin->version`. Run it, fix any mismatches.

### 2e. Mustache lint

```bash
moodle-plugin-ci mustache
```

Common issues:
- Triple-mustache (`{{{ }}}`) in places where double-mustache (`{{ }}`)
  would be XSS-safe.
- Missing whitespace around `{{` `}}`.
- Partials that reference a block not defined in the parent.

### 2f. Grunt build (AMD, YUI, SCSS)

If you ship AMD, YUI, or theme SCSS:

```bash
cd <plugin-dir>
npm install
grunt
```

Commit both `amd/src/` and `amd/build/`. The reviewers check that
`amd/build/*.min.js` is the actual grunt output of
`amd/src/*.js` — if you modify `src/` without rebuilding, it's an
instant bounce.

---

## 3. Directory metadata — the fields you'll be asked for

When you open the "Add plugin" form on moodle.org/plugins, have these
ready:

### Short description (≤ 200 chars)

One sentence answering "what does this do?" for a new reader.

Good: _"Spaced-repetition Leitner-box activity module that reuses the
Moodle question bank and pushes progress into the gradebook."_

Bad: _"LeitnerFlow is a comprehensive spaced repetition learning tool
for Moodle that helps students..."_ — fluff, no info density.

### Long description (Markdown)

4–8 paragraphs covering:
1. What the plugin does and who it's for
2. Key features (bullet list)
3. How to use it (link to docs or describe the main flow)
4. Optional: screenshots or GIFs
5. Known limitations / roadmap

### Tags

Pick 3–6 existing tags. Prefer popular ones (quiz, content, activity,
course, assessment, communication) over niche ones. Directory search
surfaces plugins by tag.

### Supported Moodle versions

Honest range. `2024100700 – 2025050200` for "Moodle 4.5 through 5.0".
Do **not** claim to support a version you haven't at least smoke-tested.

### GitHub / source URL

Public repo. Must contain the tag matching the release.

### License

Always "GNU GPL v3 or later". Moodle does not accept anything else.

### Bug tracker URL

GitHub Issues is fine. Some maintainers use Moodle Tracker — your call.

### Documentation URL

Either a Moodle Docs wiki page, a GitHub Pages site, or (minimum) a
README in the repo. Not optional — "it's self-explanatory" is not an
accepted answer.

---

## 4. Building the release ZIP

The reviewers expect a clean archive. Never `zip -r` from a working
checkout — you'll include `.git`, `.github`, `node_modules`, editor
swap files, and whatever else is dirty.

**Use `git archive`:**

```bash
cd <plugin-repo>
VERSION=v1.0.0       # the git tag you're releasing
PLUGIN=local_example

git tag -a "$VERSION" -m "$PLUGIN $VERSION"
git push origin "$VERSION"

git archive --format=zip --prefix="$PLUGIN/" "$VERSION" \
    -o "../${PLUGIN}_${VERSION}.zip"

# Verify the contents
unzip -l "../${PLUGIN}_${VERSION}.zip" | head -20
```

**Rules:**
- The top-level directory inside the ZIP must match the plugin name
  portion of the frankenstyle, **without** the plugin-type prefix. So
  `local_example` extracts to `example/` at the top, because the
  target is `local/example/`. Similarly `mod_forum` extracts to
  `forum/`.
- Never include `_claude-skills-update/`, `playbooks/`, `docs/`
  (if this is just workflow docs, not user docs), or any multi-plugin
  scaffolding from a workspace repo. The plugin repo should be **its
  own repo** with only the plugin in it.
- If you develop plugins in a monorepo (like LernHive's `plugins/`
  workspace), build the ZIP from a subtree-split or a dedicated
  single-plugin mirror repo, not from the monorepo itself.

---

## 5. Upload and first review cycle

### 5a. Upload

1. Log in at `https://moodle.org` with your account.
2. Go to `moodle.org/plugins/` → **Register a new plugin**.
3. Fill in metadata (see §3), attach the ZIP.
4. Submit.

### 5b. The QA bot pass

Within ~30 minutes, the `moodle-plugin-ci` bot runs the same checks
you (hopefully) ran locally. You'll get one of:

- **Green** → queued for human review.
- **Amber** → warnings, will still go to human review but with flags.
- **Red** → rejected. Fix, bump `$plugin->version`, rebuild, reupload.

If the bot rejects, it posts a log fragment in the plugin forum. Read
every line of it; the first red line is usually the cause, the rest
are symptoms.

### 5c. Human review

A Moodle HQ reviewer (volunteer or staff) will look at the code,
typically within 1–3 weeks. They check:

- Does it do what the description says?
- Is it maintained by someone reachable?
- Privacy, security, capabilities — no surprises?
- Does it follow Moodle conventions (hooks over core hacks, Mustache
  over raw HTML, `$DB` over direct SQL)?
- Is the code reasonably clean and commented?

They may ask questions or request changes in the plugin forum. Respond
within a reasonable time (days, not weeks) — reviewers on Moodle HQ
are volunteers, and your responsiveness matters.

### 5d. Revise and resubmit

When a reviewer asks for changes:

1. Make the changes in the source repo.
2. Bump `$plugin->version` — every new ZIP needs a new version number,
   monotonically increasing.
3. Bump `$plugin->release` if the changes are user-visible.
4. Tag, build, upload the new ZIP to the same plugin page (look for
   "Upload new version").
5. Reply in the plugin forum saying what you changed.

**Never** respond with "it works on my machine" or "that's how Moodle
does it elsewhere". The reviewers have seen every dodge; they know
the core patterns and they're usually right about the fix.

---

## 6. Post-approval: the first release tag

Once approved, your plugin appears in the directory and users can
install it via site admin → Install plugin from ZIP.

Take care of the loose ends:

- [ ] Push the git tag to GitHub (`git push --tags`).
- [ ] Create a GitHub Release with the ZIP attached (optional but
      nice for non-moodle.org users).
- [ ] Update the README's "Installation" section with the directory
      link.
- [ ] Announce in the plugin forum (brief post).
- [ ] Subscribe to the plugin forum — that's where future bug reports
      and update requests land.

---

## 7. Ongoing releases

Every subsequent release follows the same cycle, minus the first-time
metadata:

1. Bug report or feature arrives.
2. Implement + test locally.
3. Update `$plugin->version` and `$plugin->release`.
4. Run prechecks.
5. Tag, archive, upload to the existing plugin page.
6. Wait for QA bot, respond to human reviewer if asked.
7. Announce in the plugin forum when approved.

The reviewers remember your plugin on subsequent rounds; after a few
successful releases they tend to push things through faster.

---

## 8. Common bounce reasons (and how to pre-empt them)

| Bounce reason | Fix |
|---|---|
| Missing privacy provider | Implement `null_provider` at minimum. |
| PHPDoc missing on public API | Add docblocks everywhere. |
| Savepoint version mismatch | Run `moodle-plugin-ci savepoints` locally. |
| AMD build drift | Run `grunt amd`, commit `amd/build/` alongside `amd/src/`. |
| Top-level dir in ZIP is wrong | Use `git archive --prefix=<name>/`. |
| `strings` missing for capabilities | Every capability needs a `<plugin>:<cap>` string in `lang/en/`. |
| Raw `<script>` or `<style>` in templates | Move JS to AMD, CSS to the plugin's `styles.css` or theme SCSS. |
| Direct DB access bypassing `$DB` | Always use `$DB->get_records`, never PDO. |
| `mkdir` in `make_upload_directory` instead of `make_temp_directory` | Use the right helper for the right lifetime. |
| Event class missing `init()` or `get_description()` | Always implement all three abstract methods. |
| `require_login()` missing on a view script | Every user-facing page. |
| Stale copyright year | Not a blocker but reviewers notice. |

---

## 9. Checklist before hitting "Submit"

- [ ] `moodle-plugin-ci` all-green locally
- [ ] PHPUnit all-green locally (see `03-testing.md`)
- [ ] Behat all-green locally (happy paths at minimum)
- [ ] Privacy provider implemented
- [ ] All lang strings in `lang/en/<frankenstyle>.php`
- [ ] GPL v3+ header on every PHP file
- [ ] `$plugin->version` bumped
- [ ] `$plugin->release` human-readable
- [ ] `$plugin->requires` matches the oldest tested Moodle
- [ ] Git tag pushed
- [ ] ZIP built via `git archive` with correct `--prefix`
- [ ] ZIP contents verified (no `.git`, no editor files)
- [ ] Metadata (short desc, long desc, tags) drafted
- [ ] Screenshots ready (optional but helps approval)

If all checked: submit with confidence. First reviewer cycle usually
lands within 2 weeks.
