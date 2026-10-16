"""Build aries/index.html from tools/template.html and tools/shapes.json.

The shape data is compacted for the page:
- identity ribbons are read from the designer's SVGs in tools/ribbons/ (see ribbons_svg.py);
- the hero cover bands come from the brochure PDF (shapes.json, see extract_shapes.py);
- every band is simplified with Ramer-Douglas-Peucker (straight runs collapse to their end points,
  curves keep enough points to stay within the tolerance), so the page ships and redraws fewer points.

Usage: python3 tools/build.py
"""
import json
import os
import sys
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

HERE = os.path.dirname(os.path.abspath(__file__))
PW = 594.551
TOL = 0.06  # pt; at the largest on-screen scale (~2.5 px/pt) this is under 0.2 px


def rdp_keep(xs, ys, tol):
    keep = {0, len(xs) - 1}
    stack = [(0, len(xs) - 1)]
    while stack:
        a, b = stack.pop()
        if b <= a + 1:
            continue
        x0, y0, x1, y1 = xs[a], ys[a], xs[b], ys[b]
        dx, dy = x1 - x0, y1 - y0
        norm = (dx * dx + dy * dy) ** 0.5 or 1.0
        far, idx = -1.0, -1
        for i in range(a + 1, b):
            d = abs(dy * (xs[i] - x0) - dx * (ys[i] - y0)) / norm
            if d > far:
                far, idx = d, i
        if far > tol:
            keep.add(idx)
            stack += [(a, idx), (idx, b)]
    return keep


def band(xs, t, b):
    keep = sorted(rdp_keep(xs, t, TOL) | rdp_keep(xs, b, TOL))
    r = lambda v: round(v, 1)
    return {'x': [r(xs[i]) for i in keep], 't': [r(t[i]) for i in keep], 'b': [r(b[i]) for i in keep]}


def main():
    data = json.load(open(os.path.join(HERE, 'shapes.json')))
    G, S = data['grid'], data['shapes']
    # identity ribbons come from the designer's SVGs (tools/ribbons/*.svg)
    import ribbons_svg
    w, spreads = ribbons_svg.spreads()
    out = {'w': w, 'spreads': spreads}
    for k in ('tb', 'bb'):
        e = band(G, S[k]['t'], S[k]['b'])
        e.update({s: S[k][s] for s in ('tsl', 'tsr', 'bsl', 'bsr')})
        out[k] = e
    payload = json.dumps(out, separators=(',', ':'))
    tpl = open(os.path.join(HERE, 'template.html')).read()
    mark = open(os.path.join(HERE, '..', 'img', 'mark.png'), 'rb').read()
    import base64
    html = tpl.replace('/*SHAPES*/', payload).replace('/*MARK*/', 'data:image/png;base64,' + base64.b64encode(mark).decode())
    open(os.path.join(HERE, '..', 'index.html'), 'w').write(html)
    pts = sum(len(v['lo']['x']) * 2 for v in spreads.values())
    print(f'shape data {len(payload) // 1024} KB, ribbon points {pts}')


if __name__ == '__main__':
    main()
