# Aries Group — concept site (brochure draft 2)

One-page animated concept built from the Aries Group brochure. This is a reference for the final design: the pieces below were approved and should be reused as they are.

- `index.html` — the finished page (self-contained; GSAP, ScrollTrigger and Lenis from jsDelivr).
- `img/mark.png` — the Aries mark as an alpha mask, recoloured with CSS (`.mark`).
- `tools/` — how the shapes were made, so they can be regenerated or extended.

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
# 3. simplify the data and build index.html from tools/template.html
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
