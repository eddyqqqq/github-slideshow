# Aries Group — website

`index.html` is the **homepage built from the design** (`design/images/homepage-design.jpg`), played as a scroll sequence. The first concept from the brochure is kept in `v1/index.html`; its ideas and motion (below) carry over.

- `index.html` — the homepage (needs `img/*.webp`, `vendor/*.js` and `fonts/*.woff2` next to it).
- `v1/index.html` — the first concept (brochure draft 2).
- `design/` — the design file, its photos and the designer's ribbon SVGs.
- `img/` — the Aries mark (`mark.png`, an alpha mask recoloured with CSS) and the web-compressed photos.
- `tools/` — how the shapes are made, so they can be regenerated or extended.

## Homepage scroll sequence

1. **Hero → photo → blue** (one pinned scene). The two blue bands (`design/ribbons/Vector.svg`) draw in with the photo between them. On scroll the bands fly apart, the photo fills the screen and *The partner of choice…* rises in word by word; then the blue gradient fills the screen from the top-left corner.
2. **Group + companies** (one pinned scene). The blue scene slides up over the end of the hero while the gradient is still filling, so the Group text arrives with the fill, not after it. One ribbon pair then flows Group (`Group_32`) → Shipping Agency (`Group_11`) → Interlink (`Group_12`) → Logistics (`Group_13`) while the background colour, logo and text change. Every shape is scaled to the same width and the ribbon SVG covers the whole section, so shapes are only ever cut by the screen edge. The tabs (top right), the menu and the footer links jump to a state.
3. **Statements, figure tiles** (ribbons `Group_33` / `34` / `35`), **service cards**: the section pins and the row slides sideways with the scroll (progress bar underneath); the diagonal photo cut opens as each card comes in and swings open with an elastic bounce on hover. Then the **footer**.

**Navigation.** The full-screen menu is set aside for now (`tools/parts/menu.html`; build with `MENU=1 python3 tools/build_home.py` to put it back). When it returns: burger as in the design (three plain lines, top right), white over photo and colour, hides while scrolling down; the panel slides down without ever showing a scrollbar (a bar that appeared during the slide made the page jump), and the Group ribbon is fitted into the free band between the links and the slogan so it never covers text.

**Logos** are the design files (`design/logos/`), inlined: Group in colour on white (header, footer), the companies in white on their colours; 72 px wide at 1440.

**Images** keep the quality they were delivered at: the desktop hero and the card photos are the original WebP files; phones get a 900 px hero as AVIF (WebP fallback), chosen by the browser through a hidden `<picture>`.

**Hover.** Buttons and links behave the same: the text turns the header blue and a 2 px bar draws in from the left (and leaves to the right). The service cards' diagonal cut eases open a little. The figure tiles have no hover.

**Figure tiles** follow the design layout (label top left, figure low with its caption under it, the wide tile's second figure just past the middle). The figures count from 0 when a tile is well in view, one after the other, and reset when it leaves below, so the count plays every time you scroll down to it (tabular figures, so the digits don't jitter).

**Responsive.** Fluid type and content width (`--cw: max(1440px, 76vw)`), so 1920 and 2560 screens get the 1440 design scaled up instead of a narrow column; the hero composition keeps the same weight on phones and tablets; short landscape screens get a compact layout. Checked at 320, 390, 768, 1024, 1280, 1440, 1920, 2560 and 844×390.

**Type.** Area Normal: titles Bold 48 px and text Regular 20 px at 1440 (hero title 60, labels 14, links 16), fluid above and below. The fonts are self-hosted as subset WOFF2 (`fonts/area-regular.woff2`, `fonts/area-bold.woff2`, about 32 KB each, Latin + Romanian), preloaded, with metric-matched Arial fallbacks so nothing moves when they swap in. **They are not in this repository**: Area Normal is a commercial typeface whose embedding is restricted, so it needs a web licence; the files are git-ignored and go on the server.

**Libraries** are self-hosted in `vendor/` (GSAP 3.12.5, ScrollTrigger, Lenis 1.1.13): no third-party hosts, nothing render-blocking.

Colours (sampled from the design): Group `#145fad`, Shipping Agency `#3089a9`, Interlink `#2aa8da`, Logistics `#42979c`.

Build: `python3 tools/build_home.py` (reads `design/ribbons/*.svg` through `tools/design_svg.py`, writes `index.html`). To change a ribbon, replace its SVG and rebuild.

Lighthouse (local, simulated slow 4G for mobile): mobile performance 78–81 (the full-quality hero photo), desktop 99; accessibility, SEO and best practices 100.

# First concept (v1)

One-page animated concept built from the Aries Group brochure (`v1/index.html`, built with `python3 tools/build.py`).

## Ideas to keep

1. **Exact brochure shapes.** Shapes are not redrawn by hand. They are extracted from the PDF vectors and sampled as `y(x)` edges on a shared grid (A4 page width = 594.55 pt). This makes every shape morphable and resolution-independent.
2. **Identity ribbons (the white gradient bands).** Drawn from the designer's SVGs in `tools/ribbons/` (`group`, `agency`, `interlink`, `logistics`), with their exact gradients. The two bands of a ribbon share one edge exactly (no overlap, no seam). Two ribbons per company, continuous across each two-page spread:
   - Group: arc + peak (pages 3–4)
   - Shipping Agency: wave (pages 7–8)
   - Interlink: zigzag (pages 11–12)
   - Logistics: shallow peak (pages 17–18)
   Lower ribbon fades white → transparent left to right; upper ribbon the reverse.
3. **Ribbon motion.** Ribbons draw along their length and pan across the spread with scroll (the two bands never move apart). Between companies the old shape leaves to the right while the new one draws in behind it — no in-between morphs, so only real shapes are ever shown.
4. **Hero = brochure cover.** Exact cover band (top and bottom bands, three diagonal image stripes for road / sea / rail at the brochure's 20.75° angle). Opening sequence: bands draw, stripes slide in, title rises. On scroll the hero pins and the band opens to full screen.
5. **Identity section.** Pinned scene where one ribbon pair moves through the four company shapes while the background colour changes.
6. **Figures as a tile grid** (chosen over the horizontal strip). Company-coloured tiles; large tiles carry their company's ribbons. Numbers are static — no scroll counters.
7. **Images.** Rounded frame that opens to full width as it reaches the middle of the screen. No shape-cut images.
8. **Colour bands** for statements stay straight; shapes appear only as ribbons and the cover band.
9. **Motion rules.** Everything is scroll-scrubbed (nothing pops in), Lenis smooth scroll, layout re-measured after fonts load and after ScrollTrigger refresh.
10. **Layouts.** Full width and Contained (rounded panels in a 1440 px container, done with `clip-path` so pinned sections keep working). Switch with `#contained` in the link.

## Brand tokens

| Token | Value |
|---|---|
| Group | `#2c59b0` |
| Shipping Agency | `#4388ac` |
| Interlink | `#50abdc` |
| Logistics | `#49989a` |
| Type | Figtree (stand-in for the brochure face), IBM Plex Mono for labels |

## Regenerating the shapes

```bash
# 1. PDF pages to SVG (poppler)
for p in $(seq 1 20); do pdftocairo -svg -f $p -l $p Brosura.pdf svg/p$p.svg; done
# 2. sample every shape onto the shared grid
python3 tools/extract_shapes.py svg tools/shapes.json
# 3. simplify the data and build v1/index.html from tools/template.html
#    (identity ribbons are read from tools/ribbons/*.svg — replace those files to change them)
python3 tools/build.py
```

`extract_shapes.py` maps each brochure page to its clip paths (ribbons, cover bands, cut shapes). If a new brochure version moves shapes, update the page/clip ids in the `R` table and the `add(...)` calls.

## Performance (PageSpeed / Lighthouse)

Measured locally with Lighthouse 12 (libraries served locally, fonts unavailable in the test environment):

| | Before | After |
|---|---|---|
| Mobile performance | 67 | 88–97 |
| Mobile FCP / LCP | 4.1 s / 4.3 s | 1.4 s / 2.4 s |
| Desktop performance | 98 | 100 |
| Best practices / SEO | 96 / 90 | 100 / 100 |
| CLS | 0 | 0 |

What was done, and what to keep when porting:

- **Nothing blocks first paint.** GSAP, ScrollTrigger and Lenis load with `defer`; the page script runs on `DOMContentLoaded`. Google Fonts are injected by script (non-blocking) with only the weights in use (Figtree 300/400/600, IBM Plex Mono 500).
- **No font shift.** A metric-matched local fallback (`Figtree Fallback`, `size-adjust` + ascent/descent overrides) keeps text in place when the web font arrives.
- **Hero text paints at once.** It is the LCP element, so it is never hidden; only the shapes animate in.
- **Smaller, simpler shape data.** `tools/build.py` joins the spreads and simplifies every band with Ramer–Douglas–Peucker (0.06 pt tolerance): 4,776 → 1,740 ribbon points, shape data 67 → 35 KB.
- **Idle start-up.** Only the hero is set up immediately; every other section's animation is created in 12 ms slices with `requestIdleCallback`, then one sorted ScrollTrigger refresh.
- **Draw only what is seen.** Ribbons are measured on layout but redrawn only when on screen (IntersectionObserver gate), batched to one render per frame on the GSAP ticker.
- **Fewer triggers.** The nav colour uses an IntersectionObserver instead of a ScrollTrigger per section; numbers are static (no counters).
- **Cheaper paint.** No `will-change` on split words; lighter nav blur, none on phones; the logo mask is inlined as a data URI (no extra request).
- **Accessibility.** Darker ink shades (`--agency-ink`, `--interlink-ink`, `--logistics-ink`) for small coloured text on white; darker placeholder captions; meta description.

Still open, by design: white text on the Interlink sky blue (`#50abdc`) is 2.6:1, below WCAG AA. It is the brand colour from the brochure; fix it in the final design (darker band shade or navy text) if accessibility score matters.

For production (WordPress): self-host GSAP/ScrollTrigger/Lenis and the fonts (one less DNS/TLS each), serve with Brotli/gzip and long cache headers, and use `srcset` + `loading="lazy"` for the real photos (the hero stripes should get `fetchpriority="high"`).
