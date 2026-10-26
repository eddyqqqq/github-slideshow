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
| Flex | Row or column of blocks: direction, justify, align, gap, wrap, per breakpoint. |
| Grid / Grid item | CSS grid: columns, column and row gap, alignment per breakpoint; items span columns and rows per breakpoint. |
| Box | Card or panel: background, text tone, padding, direction, gap, radius, minimum height, whole-box link. |
| Logo | Group or company logo, in colour or white. |
| Ribbon | Decorative Aries ribbon behind the content of a Section or Box: shape, size, position, opacity, optional drift. |
| Aries hero, company scene, figures, service cards | The designed scenes: texts and photos edited in place, lists (companies, tiles, figures, cards) added, removed and reordered; the motion is built in. |

**Breakpoints:** desktop, tablet (≤ 1024 px), mobile (≤ 640 px). Desktop values apply everywhere; tablet and mobile only override what they change (the settings show a "Use desktop" link where they do).

**Animation on any block:** select it → *Animation (Imagient)*: fade up, words rising one by one (headings), numbers counting up, scale in. In the editor, animated blocks carry a small ✦ badge.

**Link styles:** Button → style *Aries link* (the underline that draws in on hover); List → style *Aries links* (for link lists).

**Patterns** (inserter → *Patterns* → *Aries (Imagient)*): the whole homepage, each of its sections, a blue title-and-text section and three company boxes; starting points for new sections.

**Links to the company scene:** `#group` (Group), `#aries-shipping-agency`, `#aries-interlink`, `#aries-logistics` (the company names), or `#group-1`, `#group-2`… scroll to that state of the scene.

## Checked

- All templates, parts and patterns parse as valid blocks with the current Gutenberg packages (119 blocks, 0 invalid); every Imagient block renders in the editor with its settings, breakpoint tabs included, without errors.
- Front end on WordPress 6.6 (local): the homepage matches the static prototype at 1440 × 900 and 390 × 844, no horizontal scroll.
