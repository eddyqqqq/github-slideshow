/*
 * Imagient Blocks — front-end runtime (Imagient · https://imagient.ro)
 *
 * Starts whatever is on the page: the Aries hero, the company scene, figure tiles, service cards, decorative
 * ribbons and the per-block animations (data-anim). GSAP + ScrollTrigger drive every scroll effect, Lenis
 * smooths the scroll. Setup below the first screen runs in idle slices so the page stays responsive while it
 * loads; ribbons redraw only while visible, and all drawing is batched on the GSAP ticker.
 */
document.addEventListener('DOMContentLoaded', () => {
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const clamp01 = v => v < 0 ? 0 : v > 1 ? 1 : v;
  const f1 = v => (Math.round(v * 10) / 10).toString();
  const root = document.documentElement;
  const DATA = window.ARIES_SHAPES;
  if (!DATA) { root.classList.remove('pre'); return; }
  const RIB = DATA.ribbons;

  // polygon for a band between two edges, visible between fractions q0..q1 of its length (left to right)
  function bandD(xs, t, b, q1, dyT, dyB, q0 = 0) {
    const n = xs.length;
    q0 = clamp01(q0); q1 = clamp01(q1);
    if (q1 - q0 <= 0.0005) return 'M0 0Z';
    const span = xs[n - 1] - xs[0], xa = xs[0] + span * q0, xb = xs[0] + span * q1;
    const T = [], B = [];
    const at = (x, k, arr) => arr[k - 1] + (arr[k] - arr[k - 1]) * (x - xs[k - 1]) / (xs[k] - xs[k - 1]);
    let k = 0;
    while (k < n && xs[k] < xa) k++;
    if (k > 0 && k < n && xs[k] > xa) { T.push(f1(xa) + ' ' + f1(at(xa, k, t) + dyT)); B.push(f1(xa) + ' ' + f1(at(xa, k, b) + dyB)); }
    for (; k < n && xs[k] <= xb; k++) { T.push(f1(xs[k]) + ' ' + f1(t[k] + dyT)); B.push(f1(xs[k]) + ' ' + f1(b[k] + dyB)); }
    if (k < n && k > 0) { T.push(f1(xb) + ' ' + f1(at(xb, k, t) + dyT)); B.push(f1(xb) + ' ' + f1(at(xb, k, b) + dyB)); }
    if (T.length < 2) return 'M0 0Z';
    return 'M' + T.join('L') + 'L' + B.reverse().join('L') + 'Z';
  }

  /* ---------------- ribbons ----------------
     Every shape is scaled to the same width (the design files differ), so one ribbon can flow through several
     shapes. The SVG covers its whole box: shapes are never cut by a box, only by the screen. */
  const NS = 'http://www.w3.org/2000/svg';
  let uid = 0;
  class Ribbons {
    constructor(svg, keys, o = {}) {
      this.svg = svg; this.keys = keys.filter(k => RIB[k]);
      if (!this.keys.length) this.keys = ['group'];
      this.fill = o.fill || 0; this.over = o.over || 1; this.ay = o.ay || 0.5; this.place = o.place || null;
      this.SW = Math.min(...this.keys.map(k => RIB[k].w));
      this.f = {}; let span = 0;
      this.keys.forEach(k => { const s = RIB[k], f = this.SW / s.w; this.f[k] = f; span = Math.max(span, (Math.max(...s.lo.b) - Math.min(...s.up.t)) * f); });
      this.span = span + 12;
      this.st = { q: 1, pan: 0.5, m: 0, mx: 0 };
      const id = 'rb' + (uid++);
      const mk = (tag, attrs, parent) => { const e = document.createElementNS(NS, tag); for (const a in attrs) e.setAttribute(a, attrs[a]); parent.appendChild(e); return e; };
      const defs = mk('defs', {}, svg);
      this.defs = defs;
      const grad = (gid, g) => { const e = mk('linearGradient', { id: gid, gradientUnits: 'userSpaceOnUse', x1: g.c[0], y1: g.c[1], x2: g.c[2], y2: g.c[3] }, defs); g.s.forEach(([off, a]) => mk('stop', { offset: off, 'stop-color': '#fff', 'stop-opacity': a }, e)); };
      this.gid = id;
      [...new Set(this.keys)].forEach(k => { grad(id + k + 'u', RIB[k].gu); grad(id + k + 'l', RIB[k].gl); });
      this.ga = mk('g', {}, svg); this.gb = mk('g', {}, svg);
      this.pu = mk('path', { d: 'M0 0Z' }, this.ga); this.pl = mk('path', { d: 'M0 0Z' }, this.ga);
      this.pu2 = mk('path', { d: 'M0 0Z' }, this.gb); this.pl2 = mk('path', { d: 'M0 0Z' }, this.gb);
      [this.pu, this.pl, this.pu2, this.pl2].forEach(p => this.fader(p, mk));
      svg.setAttribute('preserveAspectRatio', 'xMidYMid slice');
    }
    // a band that is being drawn in or leaving has soft ends instead of a straight cut: a mask whose gradient runs
    // along the band, clear where the band is cut and opaque a little further in
    fader(path, mk) {
      const id = 'rf' + (uid++), big = { x: -1e4, y: -1e4, width: 3e4, height: 3e4 };
      const g = mk('linearGradient', { id: id + 'g', gradientUnits: 'userSpaceOnUse', x1: 0, y1: 0, x2: 1, y2: 0 }, this.defs);
      path._fs = [0, 0, 1, 1, 0, 0].map(a => mk('stop', { offset: 0, 'stop-color': '#fff', 'stop-opacity': a }, g));
      path._fg = g;
      const m = mk('mask', Object.assign({ id, maskUnits: 'userSpaceOnUse' }, big), this.defs);
      mk('rect', Object.assign({ fill: `url(#${id}g)` }, big), m);
      path.setAttribute('mask', `url(#${id})`);
    }
    fade(path, xs, a, b) {
      // a, b: where the band starts and ends, as fractions of its length; the soft end grows to 20 % of it
      const F = 0.2, x0 = xs[0], x1 = xs[xs.length - 1];
      let tb = a + Math.min(F, a), tc = b - Math.min(F, 1 - b);
      if (tb > tc) tb = tc = (tb + tc) / 2;
      const off = [0, a, tb, tc, b, 1];
      path._fg.setAttribute('x1', f1(x0)); path._fg.setAttribute('x2', f1(x1));
      path._fs.forEach((st, i) => st.setAttribute('offset', off[i].toFixed(4)));
    }
    layout() {
      const r = this.svg.getBoundingClientRect();
      if (!r.width || !r.height) return;
      this.W = r.width; this.H = r.height;
      this.s = this.place ? this.place.w * this.W / this.SW : Math.max(this.W * this.over / this.SW, (this.H * this.fill) / this.span);
      this.vbW = this.W / this.s; this.vbH = this.H / this.s;
      request(this);
    }
    pair(key, q0, q1, g, pl, pu) {
      const sp = RIB[key];
      if (g._k !== key) { g._k = key; g.setAttribute('transform', `scale(${this.f[key].toFixed(5)})`); pl.setAttribute('fill', `url(#${this.gid}${key}l)`); pu.setAttribute('fill', `url(#${this.gid}${key}u)`); }
      const lo0 = q0 <= 0 ? 0 : clamp01(q0 * 1.08), up0 = q0 <= 0 ? 0 : clamp01(q0 * 1.12 - 0.12);
      const lo1 = clamp01(q1 * 1.08), up1 = clamp01(q1 * 1.12 - 0.12);
      pl.setAttribute('d', bandD(sp.lo.x, sp.lo.t, sp.lo.b, lo1, 0, 0, lo0));
      pu.setAttribute('d', bandD(sp.up.x, sp.up.t, sp.up.b, up1, 0, 0, up0));
      this.fade(pl, sp.lo.x, lo0, lo1); this.fade(pu, sp.up.x, up0, up1);
    }
    render() {
      if (!this.s) return;
      if (!this.vis) { this.stale = true; return; }
      this.stale = false;
      const st = this.st, last = this.keys.length - 1, P = this.place;
      // ays: one vertical position per shape (the company scene), blended as the ribbon flows between them
      let ay = this.ay;
      if (this.ays) { const k = Math.max(0, Math.min(st.m, this.ays.length - 1)), i0 = Math.floor(k), i1 = Math.min(i0 + 1, this.ays.length - 1); ay = this.ays[i0] + (this.ays[i1] - this.ays[i0]) * (k - i0); }
      const vbX = P ? -P.x * this.vbW : (this.SW - this.vbW) * clamp01(st.pan + st.mx), vbY = P ? -P.y * this.vbH : -this.vbH * ay;
      this.svg.setAttribute('viewBox', `${f1(vbX)} ${f1(vbY)} ${f1(this.vbW)} ${f1(this.vbH)}`);
      const m = Math.max(0, Math.min(st.m, last));
      const i = Math.min(Math.floor(m), last), t = m - i;
      const ease = v => { v = clamp01(v); return v * v * (3 - 2 * v); };
      if (t < 0.001 || i >= last) {
        this.pair(this.keys[i], 0, st.q, this.ga, this.pl, this.pu);
        this.pl2.setAttribute('d', 'M0 0Z'); this.pu2.setAttribute('d', 'M0 0Z');
      } else {
        // the current shape leaves to the right while the next one is drawn in behind it
        this.pair(this.keys[i], ease(t / 0.86), 1, this.ga, this.pl, this.pu);
        this.pair(this.keys[i + 1], 0, ease((t - 0.14) / 0.86), this.gb, this.pl2, this.pu2);
      }
    }
  }

  let request = c => c.render();

  /* ---------------- hero: the frame (two bands closed by a bar at each end) with the photo inside ---------------- */
  // Drawn in artboard units (FR.w × FR.h). The space between the bars (FR.bars) is the 1920 px screen: up to 1920 the
  // frame spans the screen and the bars sit just outside it; wider screens keep the 1920 size, centred, bars in view.
  const FR = DATA.frame;
  const heroEl = FR ? $('#hero.hero') : null;
  const hero = heroEl ? {
    el: heroEl, stage: $('.hero-stage', heroEl), svg: $('#heroSvg'), slot: $('#heroSlot'), top: $('#hTop'), bot: $('#hBot'),
    topG: $('#hTopG'), botG: $('#hBotG'), brL: $('#hBridgeL'), brR: $('#hBridgeR'), cutT: $('#hCutT'), cutB: $('#hCutB'), region: $('#hRegion'), zoom: $('#hZoom'), photo: $('#hPhoto'), wrap: $('#hImgWrap'),
    img: $('#hImg'), src: $('#heroSrc'),
    st: { dT: 1, dB: 1, io: 1, zi: 1, eT: 0, eB: 0, z: 1, p: 0, ld: 0 }, IMG: { w: 1500, h: 1020 }
  } : null;
  const yAt = (xs, ys, x) => { let k = 1; while (k < xs.length - 1 && xs[k] < x) k++; return ys[k - 1] + (ys[k] - ys[k - 1]) * (x - xs[k - 1]) / (xs[k] - xs[k - 1]); };
  const isLand = () => matchMedia('(min-aspect-ratio: 1/1) and (min-width: 760px)').matches;
  // the photo's subjects, in its pixels (1500 × 1020): the truck, the ship and the railway (a train along most of it)
  const SUBJ = { truck: [930, 330, 1050, 372], ship: [640, 505, 862, 560] }, RAIL = [380, 1490, 742, 790];
  // where the scrolled photo settles: the point P of the photo at the screen's centre and the scale s (units per pixel)
  // that cover the screen (hw × hh, half sizes) at rotation r (degrees); the smallest s that keeps every subject in view
  // with a margin, or, where none can, the placement that comes closest
  function endCamera(hw, hh, r, Hp) {
    const R = r * Math.PI / 180, c = Math.cos(R), n = Math.sin(R), sy = Hp / 1020;
    const toScreen = (P, k, x, y) => { const dx = x - P[0], dy = y * sy - P[1]; return [(dx * c - dy * n) * k, (dx * n + dy * c) * k]; };
    let best = null;
    for (let px = 420; px <= 1080; px += 12) for (let py = Hp * 0.3; py <= Hp * 0.7; py += Hp / 100) {
      let k = 0;
      [[-1, -1], [1, -1], [-1, 1], [1, 1]].forEach(([a, b]) => {
        const vx = (a * hw * c + b * hh * n), vy = (-a * hw * n + b * hh * c);   // the screen corner in photo directions
        k = Math.max(k, vx > 0 ? vx / (1500 - px) : -vx / px, vy > 0 ? vy / (Hp - py) : -vy / py);
      });
      k *= 1.03;
      const P = [px, py], rx = Math.min(Math.max(px, RAIL[0] + 90), RAIL[1] - 90);
      const boxes = [SUBJ.truck, SUBJ.ship, [rx - 90, RAIL[2], rx + 90, RAIL[3]]];
      let slack = Infinity;
      boxes.forEach(([x0, y0, x1, y1]) => [[x0, y0], [x1, y0], [x0, y1], [x1, y1]].forEach(([x, y]) => {
        const [X, Y] = toScreen(P, k, x, y); slack = Math.min(slack, (hw - Math.abs(X)) / hw, (hh - Math.abs(Y)) / hh); }));
      const score = slack >= 0.06 ? 1000 - k : slack;
      if (!best || score > best.score) best = { score, P, s: k };
    }
    return { P: best.P, s: best.s };
  }
  // how much the photo must grow, along the scroll (k: 0 → 1), to keep covering all that shows between the parting
  // bands on the screen: sampled once per layout, then made to only ever grow, and smoothed, so the photo eases
  // larger instead of jumping when the bands uncover more of it
  function heroCoverage(Hp) {
    const N = 48, out = [], A = FR.a, B = FR.b, c0 = hero.cam0, c1 = hero.cam1, [sx, sy] = hero.S0;
    for (let j = 0; j <= N; j++) {
      const k = j / N, L = (a, b) => a + (b - a) * k, z = 1 + (hero.zEnd - 1) * k, eT = hero.eTmax * k, eB = hero.eBmax * k;
      const C = [L(c0.C[0], c1.C[0]), L(c0.C[1], c1.C[1])], P = [L(c0.P[0], c1.P[0]), L(c0.P[1], c1.P[1])], s0 = L(c0.s, c1.s);
      const R = L(c0.r, c1.r) * Math.PI / 180, co = Math.cos(R), si = Math.sin(R);
      const hw = hero.scrH[0] / z + 6, hh = hero.scrH[1] / z + 6;
      let need = s0;
      for (let i = 0; i <= 64; i++) {
        const x = sx - hw + 2 * hw * i / 64, y0 = Math.max(sy - hh, yAt(A.x, A.b, x) + eT - 5), y1 = Math.min(sy + hh, yAt(B.x, B.t, x) + eB + 5);
        if (y1 <= y0) continue;
        [y0, y1].forEach(y => { const dx = x - C[0], dy = y - C[1], u = dx * co + dy * si, v = -dx * si + dy * co;
          need = Math.max(need, u > 0 ? u / (1500 - P[0]) : -u / P[0], v > 0 ? v / (Hp - P[1]) : -v / P[1]); });
      }
      out.push(need > s0 * 1.0001 ? need * 1.03 / s0 : 1);   // at rest (nothing to cover beyond the frame): as designed
    }
    for (let j = 1; j <= N; j++) out[j] = Math.max(out[j], out[j - 1]);                       // only ever grows
    // smoothed with a window looking ahead: on a curve that only grows that stays at or above the need, without steps
    return out.map((v, j) => { let a = 0; for (let d = 0; d <= 6; d++) a += out[Math.min(N, j + d)]; return a / 7; });
  }
  function heroLayout() {
    if (!hero) return;
    const H = hero.stage.getBoundingClientRect(), S = hero.slot.getBoundingClientRect();
    if (!H.width || !S.height) return;
    const land = isLand(), A = FR.a, B = FR.b;
    // portrait: the photo a little smaller and further right, so the train, the ship and the truck all show in the part
    // of the window a phone sees (fitted on the frame; the designed placement is for landscape)
    const ph = land ? FR.photo : Object.assign({}, FR.photo, { k: 1.27, cx: 895, cy: 460 });
    // landscape: the slot is FR.h units tall (CSS), the middle between the bars at the screen's centre.
    // portrait: PORTRAIT_W units across the screen from PORTRAIT_X (the whole curve, the peak and the start of the
    // diagonal with the truck), centred in the slot's height
    const PORTRAIT_W = 1220, PORTRAIT_X = 300;   // width: same as the slot's height in CSS (portrait)
    const u = land ? S.height / FR.h : Math.min(S.height / FR.h, S.width / PORTRAIT_W);
    const vbX = land ? (FR.bars[0] + FR.bars[1]) / 2 - (S.left - H.left + S.width / 2) / u : PORTRAIT_X - (S.left - H.left) / u;
    const vbY = -(S.top - H.top + (land ? 0 : (S.height - FR.h * u) / 2)) / u;
    const vbW = H.width / u, vbH = H.height / u;
    hero.vb = [vbX, vbY, vbW, vbH];
    hero.svg.setAttribute('viewBox', `${f1(vbX)} ${f1(vbY)} ${f1(vbW)} ${f1(vbH)}`);
    // the screen while the hero is pinned and its centre (the zoom origin): a landscape hero taller than the screen pins
    // at its bottom; a phone's stage fills the large viewport (toolbars hidden), all of which can show
    const tallPin = land && H.height > innerHeight + 4;
    const sh = (tallPin ? innerHeight : H.height) / u, sy = tallPin ? vbY + vbH - sh : vbY;
    const scr = [vbX, sy, vbW, sh];
    hero.S0 = [vbX + vbW / 2, sy + sh / 2];
    // the photo in its own pixels (1500 wide): at rest at its designed place and size (k: units per pixel), rotated with
    // the bands; larger only if a different photo would leave a corner of the frame uncovered
    const Hp = 1500 * hero.IMG.h / hero.IMG.w;
    const rot = ph.rot * Math.PI / 180, RC = Math.cos(rot), RS = Math.sin(rot);
    const uv = (x, y) => { const dx = x - ph.cx, dy = y - ph.cy; return [Math.abs(dx * RC + dy * RS), Math.abs(-dx * RS + dy * RC)]; };
    let iw = 1500 * ph.k, ih = iw * Hp / 1500, grow = 1;
    const edgePts = [];
    A.x.forEach((x, i) => edgePts.push([x, A.b[i]])); B.x.forEach((x, i) => edgePts.push([x, B.t[i]]));
    // only the part of the window on screen has to be covered
    edgePts.filter(([x]) => x >= vbX - 20 && x <= vbX + vbW + 20).forEach(([x, y]) => { const [p, q] = uv(x, y); grow = Math.max(grow, 2.02 * p / iw, 2.02 * q / ih); });
    hero.img.setAttribute('x', 0); hero.img.setAttribute('y', 0);
    hero.img.setAttribute('width', 1500); hero.img.setAttribute('height', f1(Hp));
    hero.cam0 = { C: [ph.cx, ph.cy], r: ph.rot, s: ph.k * grow, P: [750, Hp / 2] };
    hero.Hp = Hp;
    // scrolled: the frame grows from the screen's centre, the bands leave it at the top and bottom
    hero.zEnd = Math.max(1.12, scr[2] / (FR.bars[1] - FR.bars[0]) * 1.04);
    hero.scrH = [scr[2] / 2, scr[3] / 2];
    const [cx, cy] = hero.S0, hw = scr[2] / 2 / hero.zEnd, hh = scr[3] / 2 / hero.zEnd;
    const xsIn = Array.from({ length: 41 }, (_, i) => cx - hw + 2 * hw * i / 40);
    hero.eTmax = (cy - hh - 40) - Math.max(...xsIn.map(x => yAt(A.x, A.b, x)));
    hero.eBmax = (cy + hh + 40) - Math.min(...xsIn.map(x => yAt(B.x, B.t, x)));
    // ... and the photo settles. On phones it is straightened a little, so the road with the truck, the river with the
    // ship and the railway all show (the meaning of the cover: every mode of transport), as little enlarged as can be
    // wide screens: it keeps its angle and grows from the screen's centre (the render makes it cover the screen);
    // phones: it settles straightened, framed so all three show
    if (land) {
      const c0 = hero.cam0, R0 = c0.r * Math.PI / 180, dx = cx - c0.C[0], dy = cy - c0.C[1];
      const P = [c0.P[0] + (dx * Math.cos(R0) + dy * Math.sin(R0)) / c0.s, c0.P[1] + (-dx * Math.sin(R0) + dy * Math.cos(R0)) / c0.s];
      hero.cam1 = { C: [cx, cy], r: c0.r, s: c0.s, P };
    } else hero.cam1 = Object.assign({ C: [cx, cy], r: ph.rot * 0.38 }, endCamera(hw, hh, ph.rot * 0.38, Hp));
    hero.cov = heroCoverage(Hp);
    heroRender();
  }
  // the designer's outline, exactly as drawn, split along the middle of its window into an upper and a lower half
  // (the halves overlap by a unit, so no seam shows where they meet); q: how much of each half is drawn, left to right
  const MX = FR ? FR.mid.map(p => p[0]) : [], MY = FR ? FR.mid.map(p => p[1]) : [];
  function halfCut(q, up) {
    const xr = MX[0] + (MX[MX.length - 1] - MX[0]) * clamp01(q);
    if (q <= 0.0005) return 'M0 0Z';
    const off = up ? 0.6 : -0.6, far = up ? -4000 : 5000, pts = [];
    pts.push(`${f1(xr)} ${f1(yAt(MX, MY, xr) + off)}`);
    for (let i = MX.length - 1; i >= 0; i--) if (MX[i] < xr) pts.push(`${f1(MX[i])} ${f1(MY[i] + off)}`);
    return `M${f1(MX[0])} ${far}L${f1(xr)} ${far}L` + pts.join('L') + 'Z';
  }
  function heroRender() {
    if (!hero || !hero.vb) return;
    const st = hero.st, A = FR.a, B = FR.b, e = hero;
    if (!e.top._d) { e.top.setAttribute('d', FR.path); e.bot.setAttribute('d', FR.path); e.top._d = 1; }
    e.cutT.setAttribute('d', halfCut(st.dT, true)); e.cutB.setAttribute('d', halfCut(st.dB, false));
    e.topG.setAttribute('transform', `translate(0 ${f1(st.eT)})`); e.botG.setAttribute('transform', `translate(0 ${f1(st.eB)})`);
    // as the halves part, each bar stretches between them (the bars are upright, so this is exact): the frame stays
    // one piece instead of breaking at the bars
    const yl = yAt(MX, MY, FR.bars[0] / 2), yr = yAt(MX, MY, (FR.bars[1] + FR.w) / 2), open = st.eB - st.eT > 0.5;
    // exactly on the bars' edges in the designer's path (left 0–44, right 1964–2008): a bridge any wider shows a step
    e.brL.setAttribute('d', open && st.dT > 0.02 ? `M0 ${f1(yl + st.eT - 1)}H44V${f1(yl + st.eB + 1)}H0Z` : 'M0 0Z');
    e.brR.setAttribute('d', open && st.dB > 0.999 ? `M1964 ${f1(yr + st.eT - 1)}H2008V${f1(yr + st.eB + 1)}H1964Z` : 'M0 0Z');
    // the photo shows inside the frame: under band a, over band b, between the bars
    // the window between the bands (they part on scroll), reaching a few units under them and the bars so no hairline
    // of page shows at its edges
    const G = 4, n = A.x.length - 1, m = B.x.length - 1;
    const top = A.x.map((x, k) => f1(x) + ' ' + f1(A.b[k] - G + st.eT));
    const bot = B.x.map((x, k) => f1(x) + ' ' + f1(B.t[k] + G + st.eB)).reverse();
    e.region.setAttribute('d', `M${f1(A.x[0] - G)} ${f1(A.b[0] - G + st.eT)}L` + top.join('L') + `L${f1(A.x[n] + G)} ${f1(A.b[n] - G + st.eT)}L${f1(B.x[m] + G)} ${f1(B.t[m] + G + st.eB)}L` + bot.join('L') + `L${f1(B.x[0] - G)} ${f1(B.t[0] + G + st.eB)}Z`);
    const [cx, cy] = e.S0, about = k => `translate(${f1(cx)} ${f1(cy)}) scale(${k.toFixed(4)}) translate(${f1(-cx)} ${f1(-cy)})`;
    e.zoom.setAttribute('transform', about(st.z));
    e.photo.setAttribute('transform', about(st.zi));
    // the photo moves from its place in the frame to where it settles on the screen (st.p: 0 → 1)
    const c0 = e.cam0, c1 = e.cam1 || c0, t = st.p, L = (a, b) => a + (b - a) * t;
    const C = [L(c0.C[0], c1.C[0]), L(c0.C[1], c1.C[1])], r = L(c0.r, c1.r), P = [L(c0.P[0], c1.P[0]), L(c0.P[1], c1.P[1])];
    let sc = L(c0.s, c1.s);
    // on the way it grows as the precomputed, smooth coverage curve says (heroCoverage)
    if (e.cov) { const q = clamp01(t) * (e.cov.length - 1), i = Math.min(Math.floor(q), e.cov.length - 2); sc *= e.cov[i] + (e.cov[i + 1] - e.cov[i]) * (q - i); }
    e.img.setAttribute('transform', `translate(${f1(C[0])} ${f1(C[1])}) rotate(${r.toFixed(3)}) scale(${sc.toFixed(5)}) translate(${f1(-P[0])} ${f1(-P[1])})`);
    e.wrap.style.opacity = st.io * st.ld;
  }

  if (hero && hero.src) {
    // the photo the browser chose from the srcset (format and size), already loading
    // once, when it has loaded (before that the <picture> may not have picked its source yet: setting the WebP first
    // and the AVIF later downloaded the photo twice and swapped it in view); then it fades in
    const useSrc = () => {
      if (hero.ready || !hero.src.naturalWidth) return;
      hero.ready = true;
      hero.img.setAttribute('href', hero.src.currentSrc || hero.src.src);
      hero.IMG = { w: hero.src.naturalWidth, h: hero.src.naturalHeight };
      heroLayout();
      gsap.to(hero.st, { ld: 1, duration: 0.6, ease: 'power1.out', onUpdate: () => touch(hero) });
    };
    if (hero.src.complete) useSrc(); else hero.src.addEventListener('load', useSrc, { once: true });
  }

  /* ---------------- build the ribbons on the page ---------------- */
  const narrow = innerWidth < 700;
  const idEl = $('section.identity[data-shapes]');
  // the company scene right after the hero slides over its end; marked now, before the hero's pin wraps it (after
  // that the two are no longer siblings and '.hero + .identity' stops matching)
  // (phones only: on wider screens the scene follows the hero's sequence, as designed)
  if (idEl && heroEl && heroEl.nextElementSibling === idEl && !isLand()) idEl.classList.add('after-hero');
  const ribs = [];
  const add = r => { ribs.push(r); return r; };
  let identity = null;
  if (idEl) {
    let shapes = ['group', 'agency', 'interlink', 'logistics'];
    try { shapes = JSON.parse(idEl.dataset.shapes); } catch (e) {}
    // phones: each shape sized by the width, so the whole of it shows (sized by the height it was cut to a fragment)
    identity = add(new Ribbons($('[data-rib="identity"]', idEl), shapes, { over: narrow ? 1.2 : 1.08, fill: narrow ? 0 : 0.2, ay: narrow ? 0.54 : 0.56 }));
  }
  // tiles: each ribbon placed exactly as in the design, measured on the design file (fit of the SVG shape to the
  // tile, IoU 0.99): w = ribbon width / tile width, x = left edge / tile width, y = ribbon centre / tile height
  const TILE_PLACE = { agencyT: { w: 1.545, x: -0.272, y: 0.508 }, interlinkT: { w: 2.966, x: -1.092, y: 0.433 }, logisticsT: { w: 3.217, x: -1.106, y: 0.722 } };
  const tileRibs = $$('[data-rib="tile"]').map(svg => { const k = svg.dataset.shape; return add(new Ribbons(svg, [k], { place: TILE_PLACE[k] || { w: 1.4, x: -0.2, y: 0.5 } })); });
  const decorRibs = $$('[data-rib="decor"]').map(svg => {
    let place = { w: 1.1, x: -0.05, y: 0.55 };
    try { place = JSON.parse(svg.dataset.place); } catch (e) {}
    const r = add(new Ribbons(svg, [svg.dataset.shape || 'group'], { place }));
    r.panning = svg.dataset.pan === '1';
    return r;
  });

  const io = 'IntersectionObserver' in window ? new IntersectionObserver(es => es.forEach(e => {
    const r = e.target._rib; r.vis = e.isIntersecting; if (r.vis && r.stale) request(r);
  }), { rootMargin: '25% 0px' }) : null;
  ribs.forEach(r => { r.svg._rib = r; r.vis = !io; if (io) io.observe(r.svg); });

  // company scene: for each state, place the ribbon vertically so that, along the width of the texts, its edges
  // stay clear of the top block (logo or Group text) and of the bottom text, using the real shape at the pan
  // position where that state rests
  const N = identity ? identity.keys.length : 0;
  const TR = Array.from({ length: Math.max(0, N - 1) }, (_, k) => 0.5 + k * 1.1);   // transition k → k+1
  const AT = [0.08, ...TR.map(t => t + 0.65)];                                       // where each state rests
  const ID_T = N > 1 ? TR[N - 2] + 0.7 : 1;
  const ID_PAN = AT.map(t => 0.04 + 0.92 * t / ID_T);
  const interp = (xs, ys, x) => { let k = 1; while (k < xs.length - 1 && xs[k] < x) k++; const t = (x - xs[k - 1]) / (xs[k] - xs[k - 1]); return ys[k - 1] + (ys[k] - ys[k - 1]) * Math.min(1, Math.max(0, t)); };
  const topSt = idEl ? $$('.id-top .id-states > *', idEl) : [], footSt = idEl ? $$('.id-foot .id-states > *', idEl) : [];
  function identityBands() {
    if (!identity) return;
    const r = identity, sec = idEl.getBoundingClientRect(), H = sec.height, x0 = r.svg.getBoundingClientRect().left;
    if (!r.s || !H) return;
    const m = 18;
    r.ays = r.keys.map((key, k) => {
      if (!topSt[k] || !footSt[k]) return r.ay;
      const sp = RIB[key], f = r.f[key], s = r.s, vbX = (r.SW - r.vbW) * ID_PAN[k];
      const ed = (xs, ys, a, b, pick) => { let v = pick === 'max' ? -1e9 : 1e9;
        for (let sx = a; sx <= b; sx += 6) { const y = interp(xs, ys, (sx / s + vbX) / f) * f * s; v = pick === 'max' ? Math.max(v, y) : Math.min(v, y); }
        return v; };
      const tb = topSt[k].getBoundingClientRect(), fb = footSt[k].getBoundingClientRect();
      const lowB = ed(sp.lo.x, sp.lo.b, Math.max(0, fb.left - x0 - m), fb.right - x0 + m, 'max');
      const topT = ed(sp.up.x, sp.up.t, Math.max(0, tb.left - x0 - m), tb.right - x0 + m, 'min');
      const hi = (fb.top - sec.top - m - lowB) / H, lo = (tb.bottom - sec.top + m - topT) / H;
      // phones: the ribbon centred between the logo and the text below; wider screens: as designed (at least 0.55)
      const ay = lo > hi || narrow ? (lo + hi) / 2 : Math.min(Math.max(0.55, lo), hi);
      return Math.min(0.85, Math.max(0.15, ay));
    });
    request(r);
  }
  const layoutAll = () => { heroLayout(); ribs.forEach(r => r.layout()); identityBands(); };
  layoutAll();
  let rz; addEventListener('resize', () => { clearTimeout(rz); rz = setTimeout(layoutAll, 120); });

  /* ---------------- company scene states ---------------- */
  let COLS = ['#145fad', '#3089a9', '#2aa8da', '#42979c'];
  if (idEl) { try { COLS = JSON.parse(idEl.dataset.colors); } catch (e) {} }
  const tabs = idEl ? $$('.co-tabs button', idEl) : [];
  const markTab = k => tabs.forEach((t, i) => t.setAttribute('aria-current', String(i + 1 === k)));
  function showState(k) {
    [topSt, footSt].forEach(list => list.forEach((e, i) => { e.style.opacity = i === k ? 1 : 0; e.style.visibility = i === k ? 'visible' : 'hidden'; }));
    $('#idBg', idEl).style.background = COLS[k] || COLS[0];
    identity.st.m = k; identity.render(); markTab(k);
  }
  // which scene state a link points to: the tabs (data-co) and #anchors rendered by the scene
  const stateOf = a => {
    if (a.dataset.co !== undefined) return +a.dataset.co;
    const h = a.getAttribute('href') || '';
    if (!idEl || h.charAt(0) !== '#' || h.length < 2) return null;
    const t = document.getElementById(decodeURIComponent(h.slice(1)));
    if (t === idEl) return 0;
    return t && t.classList.contains('ig-anchor') ? +t.dataset.state : null;
  };

  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!window.gsap || !window.ScrollTrigger || reduce) {
    root.classList.remove('pre');
    if (idEl) $$('a[href^="#"], [data-co]').forEach(a => { const k = stateOf(a); if (k === null) return; a.addEventListener('click', ev => { ev.preventDefault(); showState(k); idEl.scrollIntoView(); }); });
    return;
  }

  /* =====================================================================
     MOTION
     ===================================================================== */
  gsap.registerPlugin(ScrollTrigger);
  ScrollTrigger.config({ ignoreMobileResize: true, limitCallbacks: true });
  root.classList.add('anim');

  let lenis = null;
  if (window.Lenis) {
    lenis = new Lenis({ lerp: 0.09 });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add(t => lenis.raf(t * 1000));
    gsap.ticker.lagSmoothing(0);
  }
  const goTo = y => lenis ? lenis.scrollTo(y, { duration: 1.6 }) : scrollTo({ top: y, behavior: 'smooth' });

  const dirty = new Set();
  const touch = c => dirty.add(c);
  request = touch;
  gsap.ticker.add(() => { dirty.forEach(c => c === hero ? heroRender() : c.render()); dirty.clear(); });
  ScrollTrigger.addEventListener('refresh', layoutAll);

  function splitWords(el) {
    if (el._w) return el._w;
    const words = el.textContent.trim().split(/\s+/);
    const sr = document.createElement('span'); sr.className = 'sr'; sr.textContent = el.textContent.trim();
    el.textContent = ''; el.appendChild(sr);
    words.forEach((w, i) => {
      const o = document.createElement('span'); o.className = 'w'; o.setAttribute('aria-hidden', 'true');
      const inner = document.createElement('span'); inner.className = 'wi'; inner.textContent = w;
      o.appendChild(inner); el.appendChild(o); if (i < words.length - 1) el.appendChild(document.createTextNode(' '));
    });
    return (el._w = $$('.wi', el));
  }
  // "split" on a container splits its headings; on a text block, the block itself
  const splitTargets = el => /^(H[1-6]|P)$/.test(el.tagName) ? [el] : $$('h1, h2, h3, h4, h5, h6', el);

  // split every headline now, before the first layout: later trigger setup then reads a clean layout
  $$('[data-anim="split"]').forEach(el => splitTargets(el).forEach(splitWords));
  if (hero) splitWords($('#sceneTwo h2'));
  if (idEl) splitWords($('.g-intro h2', idEl));

  /* ---- hero · opening: the bands draw in, the photo settles between them ---- */
  const H = () => touch(hero);
  if (hero) {
    const hs = hero.st;
    Object.assign(hs, { dT: 0, dB: 0, io: 0, zi: 1.14 });
    heroRender();
    gsap.timeline({ defaults: { onUpdate: H } })
      .to(hs, { dT: 1, duration: 1.5, ease: 'expo.inOut' }, 0.1)
      .to(hs, { dB: 1, duration: 1.5, ease: 'expo.inOut' }, 0.25)
      .to(hs, { io: 1, duration: 1.1, ease: 'power2.out' }, 0.55)
      .to(hs, { zi: 1, duration: 2.2, ease: 'expo.out' }, 0.55);

    /* ---- hero · scroll: the bands fly apart → the photo with its text → the gradient fills the screen ---- */
    const shade = $('#shade'), twoWords = splitWords($('#sceneTwo h2')), twoP = $('#sceneTwo p');
    const hsc = { e: 0 };
    const heroLand = isLand();
    // pinned from the top; on a landscape screen shorter than the hero, pinned at its bottom (the title in view)
    gsap.timeline({ scrollTrigger: { trigger: hero.el, start: () => isLand() && hero.el.offsetHeight > innerHeight + 4 ? 'bottom bottom' : 'top top', end: heroLand ? '+=320%' : '+=270%', pin: true, scrub: 1.2, invalidateOnRefresh: true } })
      // smooth: the zoom and the parting move together on one gentle curve
      .to(hsc, { e: 1, duration: 0.8, ease: 'none', onUpdate: () => {
        const k = gsap.parseEase('sine.inOut')(hsc.e);
        hs.eT = hero.eTmax * k; hs.eB = hero.eBmax * k; hs.z = 1 + (hero.zEnd - 1) * k; hs.p = k; H(); } }, 0)
      .to('#heroTitle', { y: -40, opacity: 0, duration: 0.2, ease: 'power1.in' }, 0)   // gone before the lower band reaches it
      .to('.ig-top', { opacity: 0, duration: 0.3 }, 0)
      .to(shade, { opacity: 1, duration: 0.45, ease: 'none' }, 0.5)
      .fromTo(twoWords, { yPercent: 115 }, { yPercent: 0, duration: 0.4, stagger: 0.025, ease: 'power3.out' }, 0.62)
      .fromTo(twoP, { y: 24, opacity: 0 }, { y: 0, opacity: 1, duration: 0.35, ease: 'power2.out' }, 0.85)
      .to(twoWords, { yPercent: -115, duration: 0.3, stagger: 0.015, ease: 'power2.in' }, 1.42)
      .to(twoP, { y: -20, opacity: 0, duration: 0.25, ease: 'power2.in' }, 1.42)
      // the blue fills the screen as the text leaves; on phones the hero lets go as soon as it has (a long scroll on an
      // empty blue screen there), on wider screens the gradient takes its time, as designed
      .to(shade, { '--a': '100%', '--b': '160%', duration: heroLand ? 0.7 : 0.42, ease: 'power1.inOut' }, heroLand ? 1.5 : 1.46)
      .to({}, { duration: 0.01 }, heroLand ? 2.25 : 1.88);
  }
  root.classList.remove('pre');

  /* ---- everything below is set up in idle slices ---- */
  const tasks = [];
  let tasksDone = false;
  const later = fn => tasks.push(fn);
  const idle = cb => ('requestIdleCallback' in window) ? requestIdleCallback(cb, { timeout: 250 }) : setTimeout(cb, 16);
  function runLater(done) {
    const slice = () => {
      const t0 = performance.now();
      while (tasks.length && performance.now() - t0 < 12) tasks.shift()();
      if (tasks.length) idle(slice); else { tasksDone = true; done(); }
    };
    idle(slice);
  }

  /* ---- company scene: arrives with the scroll, then pins; one ribbon pair flows through every shape while
         colour, logo and text change ---- */
  let idST = null;
  if (identity) later(() => {
    const r = identity, R = () => touch(r), bg = $('#idBg', idEl);
    r.st.q = 0; r.st.m = 0; r.st.pan = 0; r.render();
    gsap.to(r.st, { q: 1, pan: 0.04, ease: 'none', onUpdate: R, scrollTrigger: { trigger: idEl, start: 'top bottom', end: 'top top', scrub: 0.8 } });
    if (idEl.classList.contains('after-hero')) gsap.to(bg, { opacity: 1, ease: 'none', scrollTrigger: { trigger: idEl, start: 'top 32%', end: 'top 21%', scrub: true } });
    gsap.from(splitWords($('.g-intro h2', idEl)), { yPercent: 115, ease: 'none', stagger: 0.05, scrollTrigger: { trigger: idEl, start: 'top 98%', end: 'top 40%', scrub: 0.6 } });
    gsap.from([$('.g-intro p', idEl), $('.g-foot p', idEl)], { y: 36, opacity: 0, ease: 'none', stagger: 0.3, scrollTrigger: { trigger: idEl, start: 'top 75%', end: 'top 10%', scrub: 0.6 } });

    let on = -1;
    const tl = gsap.timeline({ scrollTrigger: { trigger: idEl, start: 'top top', end: '+=' + Math.round(85 * N) + '%', pin: true, scrub: 0.8,
      onUpdate: () => { const t = tl.time(); let k = 0; TR.forEach((x, i) => { if (t >= x + 0.3) k = i + 1; }); if (k !== on) { on = k; markTab(k); } } } });
    idST = tl.scrollTrigger;
    tl.fromTo(r.st, { pan: 0.04 }, { pan: 0.96, duration: ID_T, ease: 'none', onUpdate: R }, 0);
    for (let k = 1; k < N; k++) {
      const at = TR[k - 1];
      tl.to(r.st, { m: k, duration: 0.75, ease: 'none', onUpdate: R }, at)
        .to(bg, { backgroundColor: COLS[k] || COLS[0], duration: 0.55, ease: 'power1.inOut' }, at + 0.1)
        .to([topSt[k - 1], footSt[k - 1]], { autoAlpha: 0, y: -30, duration: 0.28, ease: 'power2.in' }, at)
        .set([topSt[k], footSt[k]], { autoAlpha: 1 }, at + 0.3)
        .fromTo([...topSt[k].children, ...footSt[k].children], { y: 40, opacity: 0 }, { y: 0, opacity: 1, duration: 0.45, stagger: 0.05, ease: 'power3.out' }, at + 0.3);
    }
    if (TR.length) tl.fromTo($('.co-tabs', idEl), { autoAlpha: 0, y: -12 }, { autoAlpha: 1, y: 0, duration: 0.35, ease: 'power2.out' }, TR[0] + 0.3);
    tl.to({}, { duration: 0.01 }, ID_T);
  });
  // links into the scene jump to a state; other #links scroll smoothly
  $$('a[href^="#"], [data-co]').forEach(a => a.addEventListener('click', ev => {
    const k = stateOf(a);
    if (k !== null) { ev.preventDefault(); if (idST) goTo(idST.start + (idST.end - idST.start) * AT[k] / ID_T); return; }
    const h = a.getAttribute('href');
    const t = h && h.length > 1 ? document.getElementById(decodeURIComponent(h.slice(1))) : null;
    if (t) { ev.preventDefault(); lenis ? lenis.scrollTo(t, { duration: 1.6 }) : t.scrollIntoView({ behavior: 'smooth' }); }
  }));

  /* ---- block animations chosen in the editor (all scrubbed with the scroll, nothing pops in) ---- */
  const atEnd = el => el.getBoundingClientRect().bottom + scrollY > document.documentElement.scrollHeight - innerHeight * 0.4;
  $$('[data-anim="split"]').forEach(el => later(() => {
    const last = atEnd(el);   // near the very end of the page: finish by the time the page ends
    splitTargets(el).forEach(h => gsap.from(splitWords(h), { yPercent: 115, ease: 'none', stagger: 0.04,
      scrollTrigger: { trigger: h, start: last ? 'top bottom' : 'top 94%', end: last ? 'bottom bottom' : 'top 62%', scrub: 0.6 } }));
  }));
  $$('[data-anim="fade-up"]').forEach(el => later(() => gsap.from(el, { y: 32, opacity: 0, ease: 'none',
    scrollTrigger: { trigger: el, start: 'top 98%', end: 'top 78%', scrub: 0.6 } })));
  $$('[data-anim="scale-in"]').forEach(el => later(() => gsap.from(el, { scale: 0.94, opacity: 0, ease: 'none',
    scrollTrigger: { trigger: el, start: 'top 98%', end: 'top 70%', scrub: 0.6 } })));
  // count: every number in the block counts from 0 when it comes into view, and again on the next visit
  const fmt = (v, like) => { const r = Math.round(v); return like.indexOf(',') > -1 ? r.toLocaleString('en-US') : like.indexOf('.') > -1 && like.split('.')[1].length === 3 ? r.toLocaleString('de-DE') : String(r); };
  function counter(trigger, nodes) {
    const figs = nodes.map((node, j) => {
      const m = node.textContent.match(/\d[\d.,]*/); if (!m) return null;
      return { node, raw: node.textContent, num: m[0], to: parseInt(m[0].replace(/\D/g, ''), 10), o: { v: 0 }, late: j * 0.15 };
    }).filter(Boolean);
    const set = (f, v) => { f.node.textContent = f.raw.replace(f.num, fmt(v, f.num)); };
    figs.forEach(f => set(f, 0));
    ScrollTrigger.create({ trigger, start: 'top 85%',
      onEnter: () => figs.forEach(f => { gsap.killTweensOf(f.o); f.o.v = 0; gsap.to(f.o, { v: f.to, duration: 1.1, delay: f.late, ease: 'power2.out', onUpdate: () => set(f, f.o.v) }); }),
      onLeaveBack: () => figs.forEach(f => { gsap.killTweensOf(f.o); f.o.v = 0; set(f, 0); }) });
  }
  const textNodes = el => { const w = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, { acceptNode: n => /\d/.test(n.textContent) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_SKIP }); const out = []; while (w.nextNode()) out.push(w.currentNode); return out; };
  $$('[data-anim="count"]').forEach(el => later(() => counter(el, textNodes(el))));

  /* ---- ribbons in tiles and decorative ribbons: they draw in along their length with the scroll ---- */
  [...tileRibs, ...decorRibs].forEach(r => later(() => {
    const box = r.svg.parentElement;
    r.st.q = 0; r.render();
    gsap.to(r.st, { q: 1, ease: 'none', onUpdate: () => touch(r), scrollTrigger: { trigger: box, start: 'top 92%', end: 'top 45%', scrub: 0.8 } });
    if (r.panning) gsap.fromTo(r.place, { x: r.place.x + 0.08 }, { x: r.place.x - 0.08, ease: 'none', onUpdate: () => touch(r), scrollTrigger: { trigger: box, start: 'top bottom', end: 'bottom top', scrub: 1 } });
  }));

  /* ---- figure tiles: rise in, label and caption fade up, the figures count when the tile comes into view ---- */
  $$('[data-tiles] .tile').forEach((t, i) => later(() => {
    gsap.from(t, { y: 70, opacity: 0, ease: 'none', scrollTrigger: { trigger: t, start: `top ${100 - (i % 2) * 3}%`, end: `top ${76 - (i % 2) * 3}%`, scrub: 0.6 } });
    gsap.from($$('small, .lbl', t), { y: 24, opacity: 0, ease: 'none', stagger: 0.12, scrollTrigger: { trigger: t, start: 'top 85%', end: 'top 55%', scrub: 0.6 } });
    counter(t, $$('strong', t).map(s => s.firstChild).filter(n => n && n.nodeType === 3));
  }));

  /* ---- service cards: the section pins and the row slides sideways with the scroll ---- */
  $$('section.cards').forEach(sec => later(() => {
    const track = $('.cards-track', sec), cards = $$('.card', track);
    const dist = () => Math.max(0, track.offsetWidth - root.clientWidth);
    const hz = gsap.to(track, { x: () => -dist(), ease: 'none',
      scrollTrigger: { trigger: sec, start: 'center center', end: () => '+=' + dist(), pin: true, scrub: 0.8, invalidateOnRefresh: true } });
    const vw = root.clientWidth;
    cards.forEach((c, i) => {
      const pi = $('.pi', c), h3 = $('h3', c);
      // the blue shape opens once as the card arrives and then stays as designed; the photo under it never moves
      const first = c.getBoundingClientRect().left < vw * 0.85;
      gsap.set(c, { '--o': 0 });
      const tl = gsap.timeline({ scrollTrigger: first ? { trigger: c, start: 'top 88%', once: true } : { trigger: c, containerAnimation: hz, start: 'left 80%', once: true } });
      if (first) tl.from(c, { y: 50, opacity: 0, duration: 0.9, ease: 'power3.out' }, i * 0.1);
      tl.to(c, { '--o': 1, duration: 1.3, ease: 'expo.inOut' }, first ? 0.2 + i * 0.1 : 0);
      // hover: the cut eases open a little, the photo and title follow
      c.addEventListener('pointerenter', e => {
        if (e.pointerType !== 'mouse') return;
        gsap.to(c, { '--h': 1, duration: 1.1, ease: 'power3.out', overwrite: 'auto' });
        gsap.to(pi, { scale: 1.04, duration: 1.4, ease: 'power2.out', overwrite: 'auto' });
        gsap.to(h3, { x: 6, duration: 1.1, ease: 'power3.out', overwrite: 'auto' });
      });
      c.addEventListener('pointerleave', e => {
        if (e.pointerType !== 'mouse') return;
        gsap.to(c, { '--h': 0, duration: 1.1, ease: 'power3.out', overwrite: 'auto' });
        gsap.to(pi, { scale: 1, duration: 1.2, ease: 'power2.out', overwrite: 'auto' });
        gsap.to(h3, { x: 0, duration: 1, ease: 'power3.out', overwrite: 'auto' });
      });
    });
  }));

  // one sorted refresh once everything exists, and again (debounced) when fonts or late resources settle
  let rf = 0, lastH = 0;
  const again = force => { clearTimeout(rf); rf = setTimeout(() => {
    const h = document.body.scrollHeight;
    if (!force && h === lastH) return;
    ScrollTrigger.sort(); ScrollTrigger.refresh(); lastH = document.body.scrollHeight;
  }, 120); };
  runLater(() => again(true));
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(() => tasksDone && again());
  addEventListener('load', () => tasksDone && again());
});
