# Aries Group — concept site (brochure draft 2)

One-page animated concept built from the Aries Group brochure. This is a reference for the final design: the pieces below were approved and should be reused as they are.

- `index.html` — the finished page (self-contained; GSAP, ScrollTrigger and Lenis from jsDelivr).
- `img/mark.png` — the Aries mark as an alpha mask, recoloured with CSS (`.mark`).
- `tools/` — how the shapes were made, so they can be regenerated or extended.

## Ideas to keep

1. **Exact brochure shapes.** Shapes are not redrawn by hand. They are extracted from the PDF vectors and sampled as `y(x)` edges on a shared grid (A4 page width = 594.55 pt). This makes every shape morphable and resolution-independent.
2. **Identity ribbons (the white gradient bands).** Two ribbons per company, continuous across each two-page spread:
   - Group: arc + peak (pages 3–4)
   - Shipping Agency: wave (pages 7–8)
   - Interlink: zigzag (pages 11–12)
   - Logistics: shallow peak (pages 17–18)
   Lower ribbon fades white → transparent left to right; upper ribbon the reverse.
3. **Ribbon motion.** Ribbons draw along their length, pan across the spread and drift apart slightly with scroll. Between companies the old shape leaves to the right while the new one draws in behind it — no in-between morphs, so only real shapes are ever shown.
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
# 3. inject the data into the page
python3 -c "s=open('tools/template.html').read(); open('index.html','w').write(s.replace('/*SHAPES*/', open('tools/shapes.json').read()))"
```

`extract_shapes.py` maps each brochure page to its clip paths (ribbons, cover bands, cut shapes). If a new brochure version moves shapes, update the page/clip ids in the `R` table and the `add(...)` calls.
