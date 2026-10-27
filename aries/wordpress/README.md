# Aries — WordPress theme and blocks (Imagient)

Two packages, built by `python3 tools/build_wp.py` into `aries/dist/`:

| Package | What it is |
|---|---|
| `imagient-blocks.zip` | Plugin **Imagient Blocks**: the blocks (layout + Aries scenes), the scroll animations for every block, the motion runtime (GSAP, ScrollTrigger, Lenis), the design shapes and logos. |
| `aries.zip` | Block theme **Aries** (by Imagient): design system in `theme.json` (colours, Area Normal, fluid type and spacing), header and footer template parts, templates (front page, page, blank page, post, news, 404), pattern library, Imagient credits in the admin. |

## Install (staging / production)

1. **Plugins → Add New → Upload Plugin** → `imagient-blocks.zip` → Activate.
2. **Appearance → Themes → Add New → Upload Theme** → `aries.zip` → Activate.
   On activation, if the site still shows the latest posts on its front page, the theme creates a **Home** page from the homepage pattern and sets it as the front page. A site that already has a front page is left as it is (then insert the *Aries homepage* pattern into a page yourself).
3. **Settings → Permalinks**: "Post name".
4. Replace the photos: edit the Home page, select the hero / service cards, *Choose photo* in the sidebar. Upload the hero large (3000 px wide or more): WordPress makes the smaller sizes and the browser picks the one each screen needs.

The theme ships with the Area Normal font files (`assets/fonts`). Area Normal is a commercial typeface: use it on the site only with a web licence. The font files are not in the Git repository.

## Editing

**Blocks** (inserter → *Imagient*):

| Block | Use |
|---|---|
| Section | A band of the page: background colour, text tone, inner width (page margins / 1440 / 960 / edge to edge), space above and below per breakpoint, full-screen height, rounded corners. |
| Flex | Row or column of blocks, with icon controls: direction arrows, a 3 × 3 alignment matrix, justify / align / wrap icons, children sizing (Equal widths, Fit content, Fill, Fixed) and gap presets or any value, per breakpoint. |
| Grid / Grid item | CSS grid: columns, column and row gap, alignment per breakpoint; items span columns and rows per breakpoint. |
| Box | Card or panel: background, text tone, padding, direction, gap, radius, minimum height, whole-box link. |
| Logo | Group or company logo, in colour or white. |
| Ribbon | Decorative Aries ribbon behind the content of a Section or Box: shape, size, position, opacity, optional drift. |
| Aries hero | The Aries frame (two bands closed by a bar at each end, the designer's vector `design/hero-frame.svg`, traced by `tools/hero_frame.py`) with the photo inside, framed as in the design (railway, ship, road with the truck). Up to 1920 px the frame spans the screen and its bars sit just outside it; wider screens keep the 1920 size, centred, with the bars in view. On scroll the frame grows from the centre of the screen, the bands open and the photo fills the screen; the title and logo stay in place and fade. |
| Aries hero, company scene, figures, service cards | The designed scenes: texts and photos edited in place, lists (companies, tiles, figures, cards) added, removed and reordered; the motion is built in. |

**Structure** (as in Webflow or Elementor containers): **Section** (the band: background, width, space above and below) → **Flex** or **Grid** (how the children sit: side by side, stacked, in columns) → **Box** (each column or card; it is also a small flex container) → content (headings, text, images, buttons, logos). A Flex in a row puts its children side by side as equal columns by default; set *Direction* to the down arrow on Mobile to stack them there. Select the parent with the *Select parent* arrow in the block toolbar, or in *List view* (top left).

**Breakpoints:** desktop, tablet (≤ 1024 px), mobile (≤ 640 px). Desktop values apply everywhere; tablet and mobile only override what they change (the settings show a "Use desktop" link where they do).

**Style tab on every block (Imagient Style, like Elementor's Style tab):** select any block → sidebar → *Styles* tab → *Imagient Style*. Pick the device (Desktop / Tablet / Mobile) or *Hover*, then type any value (`24px`, `3rem`, `50%`, `clamp(28px, 3.3vw, 72px)`, a brand colour or `#hex`):
Layout (display, direction, justify, align, wrap, gap, grid columns, order), Spacing (margin and padding per side, linkable), Size (width, height, min/max, overflow, aspect ratio, object fit), Position (position, offsets, z-index), Typography (font, size, weight, line height, letter spacing, transform, decoration, align, colour), Background (colour, image, gradient, size, position), Border (style, width per side, colour, radius per corner), Effects (opacity, shadow, transform, transition, filters, cursor) and Custom CSS (`selector` = this block). A bare number means px. Tablet and Mobile show the inherited value in grey; set fields are marked blue, and the device buttons show a dot when they hold values. The editor previews the result live; on the site the rules go into the page head, once per distinct style.

**Animation on any block:** select it → *Animation (Imagient)*: fade up, words rising one by one (headings), numbers counting up, scale in. In the editor, animated blocks carry a small ✦ badge.

**Link styles:** Button → style *Aries link* (the underline that draws in on hover); List → style *Aries links* (for link lists).

**Patterns** (inserter → *Patterns* → *Aries (Imagient)*): the whole homepage, each of its sections, a blue title-and-text section and three company boxes; starting points for new sections.

**Links to the company scene:** `#group` (Group), `#aries-shipping-agency`, `#aries-interlink`, `#aries-logistics` (the company names), or `#group-1`, `#group-2`… scroll to that state of the scene.

## Checked

- All templates, parts and patterns parse as valid blocks with the current Gutenberg packages (119 blocks, 0 invalid); every Imagient block renders in the editor with its settings, breakpoint tabs included, without errors.
- Front end on WordPress 6.6 (local): the homepage matches the static prototype at 1440 × 900 and 390 × 844, no horizontal scroll.
