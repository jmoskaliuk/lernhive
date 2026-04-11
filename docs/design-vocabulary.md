# LernHive Design Vocabulary

Gemeinsames Vokabular für UI-Elemente. Alle Begriffe sind Englisch (matching den CSS-Klassen). 
Ziel: ein Begriff = eine Sache, kein "dieses Ding da oben rechts".

---

## App Shell

Die grundlegende Seitenstruktur — gilt für alle Seiten außer Login.

| Begriff | Beschreibung | CSS-Klasse |
|---------|-------------|------------|
| **App Shell** | Das gesamte Seiten-Frame (Sidebar + Page Area) | `.lernhive-app-shell` |
| **Sidebar** | Dunkelblaue linke Navigationsspalte | `.lernhive-sidebar` |
| **Page Area** | Rechter Inhaltsbereich (Header + Content + Footer) | `.lernhive-page` |
| **Page Header** | Oberste Leiste (Seitentitel + User Block) | `.lernhive-page-header` |

---

## Page Header (rechts oben)

| Begriff | Beschreibung | CSS-Klasse |
|---------|-------------|------------|
| **User Block** | Avatar + Gear-Icon + Logout-Icon | `.lernhive-user-block` |
| **Avatar** | Profilbild, klickbar → Profil-Seite | `.lernhive-user-block__avatar` |
| **Preferences Button** | Zahnrad-Icon → Einstellungen | `.lernhive-user-block__action` |
| **Logout Button** | Sign-out-Icon → Abmelden | `.lernhive-user-block__action--danger` |
| **Launcher** | Grid-Icon → App-Übersicht | `.lernhive-page-header__launcher` |
| **Lang Menu** | Globus-Icon + Sprachauswahl | `.lernhive-lang-menu` |

---

## Sidebar Navigation

| Begriff | Beschreibung |
|---------|-------------|
| **Primary Nav** | Hauptnavigation in der Sidebar (Home / Dashboard / My Courses / Site Admin) |
| **Sidebar Nav Item** | Einzelner Eintrag in der Sidebar |
| **Sidebar Label** | Optionaler Infotext-Block unterhalb der Nav-Items |

---

## Context Dock

Floating Action Buttons unten links/rechts — erscheinen kontextabhängig.

| Begriff | Beschreibung | CSS-Klasse |
|---------|-------------|------------|
| **Context Dock** | Floating-Strip mit Aktions-Buttons | `.lh-dock` |
| **Dock Item** | Einzelner runder Button im Dock | `.lh-dock__item` |
| **Dock Tooltip** | Label, das beim Hover erscheint | `.lh-dock__tooltip` |

---

## Admin Navigation

Nur auf Admin-Seiten. Zwei horizontale Tab-Leisten übereinander.

| Begriff | Beschreibung | CSS-Klasse |
|---------|-------------|------------|
| **L1 Nav** | Oberste Tab-Leiste: General · Plugins · Development · Appearance | `.lernhive-admin-topnav` |
| **L2 Nav** | Zweite Tab-Leiste: Subkategorien des aktiven L1-Tabs | `.lernhive-admin-topnav--secondary` |
| **Nav Tab** | Einzelner Tab in L1 oder L2 | `.lernhive-admin-topnav__link` |
| **Active Tab** | Aktuell aktiver Tab (orange Unterstrich) | `.lernhive-admin-topnav__link--active` |

---

## Plugin Shell

2-Zonen-Sticky-Header, der in allen LernHive Local-Plugins verwendet wird.

```
┌─────────────────────────────────────────────────────────┐
│  ← Back   PluginName | Tagline            ? Help       │  ← Zone A (Shell Header)
│           Subtitle                                       │
│           [Tag] [Tag]                                    │
├─────────────────────────────────────────────────────────┤
│  ⓘ Info text / stats / progress bar        [CTA btn]  │  ← Zone B (Info Bar)
└─────────────────────────────────────────────────────────┘
  [Card]  [Card]  [Card]                                    ← Card Grid
```

| Begriff | Beschreibung | CSS-Klasse |
|---------|-------------|------------|
| **Shell** | Das gesamte 2-Zonen-Header-Pattern | — |
| **Zone A** / **Shell Header** | Weißes Sticky-Panel oben: Back, Name, Tags | `.lh-plugin-header` |
| **Zone B** / **Info Bar** | Warmer Streifen darunter: Stats + CTA | `.lh-plugin-infobar` |
| **Back Button** | ← Navigation zurück (z.B. ← Dashboard) | `.lh-plugin-header__nav-btn` |
| **Help Button** | ? Navigation zu Hilfe/Docs | `.lh-plugin-header__nav-btn` |
| **Name Block** | "PluginName | Tagline" + Subtitle + Tags | `.lh-plugin-header__title-block` |
| **Plugin Name** | Fetter Produktname (z.B. "ContentHub") | `.lh-plugin-header__name` |
| **Tagline** | Kurzbeschreibung nach dem Pipe-Trenner | `.lh-plugin-header__tagline` |
| **Subtitle** | Ein-Satz-Beschreibung darunter | `.lh-plugin-header__subtitle` |
| **Tag Strip** | Zeile mit Tag-Pills | `.lh-plugin-header__tags` |

### Tags (Zone A)

| Begriff | Farbe | Beispiel | CSS-Klasse |
|---------|-------|---------|------------|
| **Level Tag** | Blau | "Starter", "Advanced" | `.lh-plugin-tag--level` |
| **Type Tag** | Lila | "Course", "Snack", "Community" | `.lh-plugin-tag--type` |
| **Active Tag** | Orange | "2 active", "In progress" | `.lh-plugin-tag--active` |
| **Done Tag** | Grün | "Completed", "3 done" | `.lh-plugin-tag--done` |
| **Locked Tag** | Grau | "Locked", "Coming soon" | `.lh-plugin-tag--locked` |

### Info Bar (Zone B)

| Begriff | Beschreibung | CSS-Klasse |
|---------|-------------|------------|
| **Stat Item** | Zahl + Label (z.B. "12 Courses") | `.lh-plugin-infobar__stat` |
| **Progress Bar** | Fortschrittsbalken + Prozent | `.lh-plugin-infobar__progress` |
| **Bar CTA** | Oranger Action-Button rechts in Zone B | `.lh-plugin-infobar__cta` |

---

## Cards

Karten zeigen immer Inhalte (Kurse, Snacks, Pfade). Nie als Layout-Container.

```
┌──────────────────────────────┐
│  [Icon]  Kicker              │  ← Card Top
│          Title               │
├──────────────────────────────┤
│  Description text            │  ← Card Body
│  that can span multiple rows │
├──────────────────────────────┤
│  [Start]  [Ghost]   [Info→]  │  ← Card Actions
└──────────────────────────────┘
```

| Begriff | Beschreibung | CSS-Klasse |
|---------|-------------|------------|
| **Card** | Basis-Karte | `.lh-plugin-card` |
| **Card Top** | Oberer Bereich: Icon + Meta | `.lh-plugin-card__top` |
| **Card Icon** | Farbiges Icon-Quadrat (38×38px) | `.lh-plugin-card__icon` |
| **Card Meta** | Kicker + Titel | `.lh-plugin-card__meta` |
| **Kicker** | Kleiner Label-Text über dem Titel (ALL CAPS) | `.lh-plugin-card__kicker` |
| **Card Title** | Haupttitel der Karte | `.lh-plugin-card__title` |
| **Card Sub** | Kleintext unter dem Titel | `.lh-plugin-card__sub` |
| **Card Body** | Beschreibungstext | `.lh-plugin-card__body` |
| **Card Actions** | Unterer Streifen mit Buttons | `.lh-plugin-card__actions` |

### Card States

| Begriff | Aussehen | Wann |
|---------|---------|------|
| **Default** | Grauer Ring, leichter Hover-Schatten | normaler Inhalt |
| **Current** | Oranger Ring (2px) | aktiver / laufender Inhalt |
| **Done** | 82% Deckkraft | abgeschlossen |
| **Locked** | 52% Deckkraft | nicht verfügbar / coming soon |

### Card Icon Colors

| Begriff | Farbe | Wann |
|---------|-------|------|
| **Course** | Blau | Kurs-Inhalt |
| **Snack** | Orange | Snack-Inhalt |
| **Community** | Lila | Community-Inhalt |
| **Tour** | Grün | Tour-Schritt |
| **Lock** | Grau | gesperrter Inhalt |

---

## Action Buttons (in Cards)

| Begriff | Farbe | Funktion | CSS-Klasse |
|---------|-------|---------|------------|
| **Start Button** | Orange | Primäre Aktion starten | `.lh-plugin-btn--start` |
| **Open Button** | Navy | Zu Detail-Ansicht navigieren | `.lh-plugin-btn--open` |
| **Ghost Button** | Outline | Sekundäre Aktion | `.lh-plugin-btn--ghost` |
| **Danger Button** | Rot | Destruktive Aktion (Löschen) | `.lh-plugin-btn--danger` |
| **Icon Button** | — | Kompakter Button (nur Icon) | `.lh-plugin-btn--icon` |
| **Disabled** | Ausgegraut | Nicht verfügbar | `.lh-plugin-btn--disabled` |

---

## CTA Strip (Contextual Action Notice)

Kontextbezogene Hinweise, die den Nutzer zu einer Aktion auffordern. Kein Card-Element — ein flacher Streifen.

```
┌──────────────────────────────────────────────────────────────────┐  ← 3px Akzentlinie (oben)
│ [■]  Heading                                    [Aktion →]      │
│      Subtitle / Intro-Text                                       │
│      ████████░░░░  4 of 12 tours               (optional)       │
└──────────────────────────────────────────────────────────────────┘  ← 1px Border (unten)
```

| Eigenschaft | Regel |
|-------------|-------|
| `border-radius` | **0** — scharfe Ecken, keine Rundung |
| Top-Border | 3px farbige Akzentlinie (Typ-Signal) |
| Position | Immer in der Content Column — nie über die Sidebar hinaus |
| Max. Anzahl | Eine pro Seite |
| Icon | Artifact Icon Style (farbiges Quadrat, kein Radius) |
| CTA Button | Action Button Style (Pill, orange) |
| CSS-Klasse | `.lh-cta-strip` |

### CTA Strip Varianten

| Modifier | Akzentfarbe | Verwendung |
|----------|-------------|-----------|
| `--trainer` | Navy `$lh-primary` | Onboarding, Trainer-Aufgaben |
| `--warning` | Orange `$lh-accent` | Ausstehende Aktionen, Deadlines |
| `--success` | Grün `$lh-green` | Erfolge, Abschlüsse |

### CTA Strip vs. andere Elemente

| Element | border-radius | Position | Zweck |
|---------|-------------|---------|-------|
| **CTA Strip** | 0 | Content Column (pre-header) | Aufforderung zu Aktion |
| **Zone B / Info Bar** | 0 | Plugin Shell (Zone B) | Kontext-Info + CTA im Plugin |
| **Card** | 12px | Content Grid | Inhalts-Objekt (Kurs, Snack) |
| **Tag / Badge** | Pill | Inline | Metadata-Label |

**Technische Besonderheit:** CTA Strips werden über den Moodle-Hook `before_standard_top_of_body_html_generation` injiziert — sie erscheinen im DOM VOR der `.lernhive-app-shell`. Das Theme setzt `margin-left: $lh-sidebar-width` auf Desktop, damit der Strip nur in der Content Column erscheint. Die Sidebar ist `position: fixed` (nicht sticky), damit sie unabhängig davon immer sichtbar bleibt.

---

## Icon Taxonomy

Jedes Icon im LernHive UI gehört zu genau einer der drei Kategorien. Die Form kodiert die Absicht — kein Raten nötig.

```
Navigation    Artifact      Action
  [  fa  ]   [■ fa ■]     ( fa )
 transparent  colored box  circle
 hover: tint  no hover     hover: grows
```

### Typ 1: Navigation Icon

Zeigt ein **Ziel** an — der Nutzer navigiert irgendwohin.

| Eigenschaft | Regel |
|-------------|-------|
| Hintergrund | Transparent (kein Hintergrund) |
| Hover | Leichter Farbtint (hell: `rgba(primary, 0.08)` / dunkel: `rgba(white, 0.10)`) |
| Form | Abgerundetes Rechteck (kein Kreis) |
| CSS-Klasse | `.lh-icon-nav` |
| Modifier | `.lh-icon-nav--on-dark` (Sidebar), `.lh-icon-nav--active` |

Beispiele: Home, Dashboard, My Courses, Notifications Bell, Launcher-Grid, Sprach-Menü

### Typ 2: Artifact Icon

Repräsentiert ein **Inhaltsobjekt mit Typ** — der Nutzer sieht, was das Objekt ist.

| Eigenschaft | Regel |
|-------------|-------|
| Hintergrund | Farbiger Kasten (Farbe = Inhaltstyp) |
| Hover | Kein eigener Hover (Eltern-Element übernimmt Hover) |
| Form | Abgerundetes Quadrat (border-radius 9px) |
| CSS-Klasse | `.lh-icon-artifact` |
| Modifier | `--course` `--snack` `--community` `--tour` `--generic` `--lock` |
| Größen | `--sm` (24px) · Standard (38px) · `--lg` (48px) |

Beispiele: Card Icon im Plugin Shell, Kurs-Icon in Kurslisten, Template, Library, User Tour

#### Artifact Icon Farben

| Modifier | Hintergrund | Farbe | Bedeutung |
|----------|-------------|-------|-----------|
| `--course` | Blau hell `#dbeafe` | Navy `#1e4d8c` | Kurs-Inhalt |
| `--snack` | Orange hell | Orange dunkel | Snack-Inhalt |
| `--community` | Lila hell `#ede9fb` | Lila `#5b3fa6` | Community-Inhalt |
| `--tour` | Grün hell `#dcfce7` | Grün `#166534` | Tour-Schritt |
| `--generic` | Blau-Grau `#e2edf2` | Blau-Grau `#3d6b80` | Kein Typ zugewiesen |
| `--lock` | Grau | Grau | Gesperrter Inhalt |

### Typ 3: Action Icon

**Löst eine Funktion aus** — der Nutzer tut etwas (oft nicht umkehrbar).

| Eigenschaft | Regel |
|-------------|-------|
| Hintergrund | Kreis |
| Hover | Kreis wächst (`scale(1.10)`) + leichter Schatten |
| Form | Vollkreis (`border-radius: 50%`) |
| CSS-Klasse | `.lh-icon-action` |
| Modifier | `--primary` (orange) `--nav` (navy) `--danger` (lila) `--on-dark` `--active` |
| Größen | Standard (36px) · `--lg` (44px, WCAG 2.5.5 Touch Target) |

Beispiele: Edit (Pencil), Delete, Start, Logout, Turn Editing On, Cookie Shield, Context Dock Items

### Grenzfälle & Entscheidungsregeln

| Frage | Antwort |
|-------|---------|
| Settings-Gear im User Block? | **Action** — öffnet Einstellungen für das aktuelle Objekt |
| Logout-Button? | **Action** — löst eine Funktion aus (Abmelden) |
| Back-Button in Zone A? | **Navigation** — navigiert zurück (`.lh-plugin-header__nav-btn`, kein `.lh-icon-nav` da eigene Stile) |
| Benachrichtigungs-Bell? | **Navigation** — öffnet eine Übersicht (kein Action, es wird nichts ausgelöst) |
| Kurs-Icon in der Sidebar-Nav? | **Navigation** — zeigt Ziel "My Courses" |
| Card Icon (Kurs/Snack/…)? | **Artifact** — zeigt den Inhaltstyp der Karte |

---

## Login Page

| Begriff | Beschreibung |
|---------|-------------|
| **Login Card** | Das weiße, zentrierte Karten-Panel mit dem Formular |
| **Login Card Header** | Oranger Akzentbalken oben an der Login Card |
| **Cookie Shield** | Das floating Kreis-Icon (fa-shield) rechts unten |
| **Guest Button** | "Access as a guest" Pill-Button unterhalb der Login Card |

---

## Kurzreferenz: Kommunikation

Statt... → besser...

| ❌ Unpräzise | ✅ Mit Vokabular |
|-------------|-----------------|
| "das Ding oben rechts" | "der User Block" oder "das Avatar-Icon" |
| "die blaue Navigation links" | "die Sidebar" / "Primary Nav" |
| "das Menü im Admin" | "L1 Nav" oder "L2 Nav" |
| "der Header im Plugin" | "Zone A" oder "Shell Header" |
| "der orangene Streifen" | "Zone B" / "Info Bar" |
| "die kleine Karte" | "Card" (+ Card State: Current/Done/Locked) |
| "der grüne Button" | "Start Button" |
| "das Cookie-Ding" | "Cookie Shield" |
