"""Read the homepage design SVGs (aries/design/ribbons/*.svg) into band data for the page.

- Ribbon files hold two bands sharing an edge (lower = bright on the left, upper = bright on the right).
  They are scanned into top/bottom edges, the shared edge is snapped, the pair is centred on y = 0
  and simplified with RDP. Gradients are taken from the file (fill="url(#...)").
- Vector.svg is the single solid band of the hero (used twice: top and bottom band).
"""
import json
import os
import re
import xml.etree.ElementTree as ET

from ribbons_svg import flatten_path, scan, rdp_keep

NS = '{http://www.w3.org/2000/svg}'
HERE = os.path.dirname(os.path.abspath(__file__))
SRC = os.path.join(HERE, '..', 'design', 'ribbons')
TOL = 0.15
r1 = lambda v: round(v, 1)


def _bands(path):
    root = ET.parse(path).getroot()
    w = float(root.get('viewBox').split()[2])
    grads = {}
    for g in root.iter(NS + 'linearGradient'):
        grads[g.get('id')] = {
            'c': [round(float(g.get(k, 0)), 2) for k in ('x1', 'y1', 'x2', 'y2')],
            's': [[float(s.get('offset', 0)), float(s.get('stop-opacity', 1))] for s in g.iter(NS + 'stop')],
        }
    out = []
    for el in root.iter():
        tag = el.tag.replace(NS, '')
        if tag == 'path':
            pts = flatten_path(el.get('d'))
        elif tag == 'polygon':
            nums = [float(v) for v in re.findall(r'-?\d+\.?\d*', el.get('points'))]
            pts = list(zip(nums[0::2], nums[1::2])); pts.append(pts[0])
        else:
            continue
        m = re.match(r'url\(#([\w-]+)\)', el.get('fill', ''))
        out.append((pts, grads.get(m.group(1)) if m else el.get('fill')))
    return w, out


def _grid(w, bands):
    # the drawn extent can be a hair narrower than the viewBox; scan only where the bands exist
    x0 = max(min(x for x, _ in pts) for pts, _ in bands)
    x1 = min(max(x for x, _ in pts) for pts, _ in bands)
    vx = {round(x, 3) for pts, _ in bands for x, _ in pts if x0 + 0.05 < x < x1 - 0.05}
    grid = sorted(set(float(k) for k in range(int(x0) + 1, int(x1) + 1)) | vx | {x0, x1})
    return grid, [min(max(x, x0 + 0.02), x1 - 0.02) for x in grid]


def ribbon(name):
    w, bands = _bands(os.path.join(SRC, name + '.svg'))
    grid, gx = _grid(w, bands)
    edges = []
    for pts, grad in bands:
        tb = [scan(pts, x) for x in gx]
        t, b = [a for a, _ in tb], [c for _, c in tb]
        edges.append(({'t': t, 'b': b}, grad, sum(t) / len(t)))
    edges.sort(key=lambda e: -e[2])
    (lo, gl, _), (up, gu, _) = edges
    up['b'] = list(lo['t'])  # one shared edge: no seam, no overlap
    yc = (min(up['t']) + max(lo['b'])) / 2
    keep = sorted(set().union(*(rdp_keep(grid, e[k], TOL) for e in (lo, up) for k in ('t', 'b'))))
    pick = lambda e: {'x': [r1(grid[k]) for k in keep], 't': [r1(e['t'][k] - yc) for k in keep], 'b': [r1(e['b'][k] - yc) for k in keep]}
    shift = lambda g: {'c': [g['c'][0], round(g['c'][1] - yc, 2), g['c'][2], round(g['c'][3] - yc, 2)], 's': g['s']}
    return {'w': round(grid[-1], 2), 'lo': pick(lo), 'up': pick(up), 'gl': shift(gl), 'gu': shift(gu)}


def band(name):
    """Single solid band, kept in its own coordinates, with end slopes for extending it past the edges."""
    w, bands = _bands(os.path.join(SRC, name + '.svg'))
    grid, gx = _grid(w, bands)
    pts, fill = bands[0]
    tb = [scan(pts, x) for x in gx]
    t, b = [a for a, _ in tb], [c for _, c in tb]
    keep = sorted(rdp_keep(grid, t, TOL) | rdp_keep(grid, b, TOL))
    slope = lambda arr, i, j: (arr[j] - arr[i]) / (grid[j] - grid[i])
    sl = (slope(t, 0, 3) + slope(b, 0, 3)) / 2
    sr = (slope(t, -4, -1) + slope(b, -4, -1)) / 2
    return {'w': round(grid[-1], 2), 'fill': fill, 'x': [r1(grid[k]) for k in keep], 't': [r1(t[k]) for k in keep],
            'b': [r1(b[k]) for k in keep], 'sl': round(sl, 4), 'sr': round(sr, 4)}


RIBBONS = {
    'group': 'Group_32',
    'agency': 'Group_11', 'interlink': 'Group_12', 'logistics': 'Group_13',      # company sections
    'agencyT': 'Group_33', 'interlinkT': 'Group_34', 'logisticsT': 'Group_35',  # figure tiles
}


def data():
    # the hero frame is traced once (tools/hero_frame.py) and read from its JSON
    with open(os.path.join(os.path.dirname(os.path.abspath(__file__)), 'hero_frame.json')) as f:
        frame = json.load(f)
    # 'hero' (the first design's single band) stays for the static pages (build_home.py)
    return {'ribbons': {k: ribbon(v) for k, v in RIBBONS.items()}, 'hero': band('Vector'), 'frame': frame}


if __name__ == '__main__':
    d = data()
    for k, v in d['ribbons'].items():
        print(k, v['w'], len(v['lo']['x']), 'pts', min(v['up']['t']), max(v['lo']['b']))
    h = d['hero']
    print('hero', h['w'], h['fill'], len(h['x']), 'pts', 'slopes', h['sl'], h['sr'], 'y', min(h['t']), max(h['b']))
