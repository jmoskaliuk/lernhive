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
