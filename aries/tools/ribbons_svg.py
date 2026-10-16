"""Read the identity ribbon SVGs (tools/ribbons/*.svg, one per company) into y(x) band data.

Each SVG holds two filled bands that share an edge: the lower one (gradient bright on the left)
and the upper one (bright on the right). Every band is flattened, scanned on a dense x grid into a
top and bottom edge, simplified with RDP, and centred vertically so all four companies share one
coordinate space (needed for the transitions between them).
"""
import os
import re
import xml.etree.ElementTree as ET

NS = '{http://www.w3.org/2000/svg}'
HERE = os.path.dirname(os.path.abspath(__file__))
TOL = 0.12  # in SVG units (spread width 1190.55); under 0.2 px at the largest scale used on the page


def tokens(d):
    return re.findall(r'[MmLlHhVvCcSsZz]|-?(?:\d+\.?\d*|\.\d+)(?:e-?\d+)?', d)


def flatten_path(d, steps=48):
    """Absolute polyline for a path with M L H V C S Z (absolute or relative)."""
    t = tokens(d)
    i, cmd, pts = 0, None, []
    x = y = sx = sy = 0.0
    lc = None  # last cubic control point for S
    num = lambda: float(t[i])
    while i < len(t):
        if re.match(r'[A-Za-z]', t[i]):
            cmd = t[i]; i += 1
            if cmd in 'Zz':
                pts.append((sx, sy)); x, y = sx, sy; lc = None
                continue
        rel = cmd.islower()
        c = cmd.upper()
        if c == 'M':
            nx, ny = float(t[i]), float(t[i + 1]); i += 2
            x, y = (x + nx, y + ny) if rel else (nx, ny)
            sx, sy = x, y; pts.append((x, y)); cmd = 'l' if rel else 'L'; lc = None
        elif c == 'L':
            nx, ny = float(t[i]), float(t[i + 1]); i += 2
            x, y = (x + nx, y + ny) if rel else (nx, ny); pts.append((x, y)); lc = None
        elif c == 'H':
            n = float(t[i]); i += 1; x = x + n if rel else n; pts.append((x, y)); lc = None
        elif c == 'V':
            n = float(t[i]); i += 1; y = y + n if rel else n; pts.append((x, y)); lc = None
        elif c in 'CS':
            if c == 'C':
                a = [float(v) for v in t[i:i + 6]]; i += 6
                if rel: a = [a[0] + x, a[1] + y, a[2] + x, a[3] + y, a[4] + x, a[5] + y]
                p1, p2, p3 = (a[0], a[1]), (a[2], a[3]), (a[4], a[5])
            else:
                a = [float(v) for v in t[i:i + 4]]; i += 4
                if rel: a = [a[0] + x, a[1] + y, a[2] + x, a[3] + y]
                p1 = (2 * x - lc[0], 2 * y - lc[1]) if lc else (x, y)
                p2, p3 = (a[0], a[1]), (a[2], a[3])
            p0 = (x, y)
            for k in range(1, steps + 1):
                u = k / steps; m = 1 - u
                pts.append((m ** 3 * p0[0] + 3 * m * m * u * p1[0] + 3 * m * u * u * p2[0] + u ** 3 * p3[0],
                            m ** 3 * p0[1] + 3 * m * m * u * p1[1] + 3 * m * u * u * p2[1] + u ** 3 * p3[1]))
            lc = p2; x, y = p3
        else:
            raise ValueError('unsupported command ' + cmd)
    if pts and pts[0] != pts[-1]:
        pts.append(pts[0])
    return pts


def scan(pts, x):
    ys = []
    for (x1, y1), (x2, y2) in zip(pts, pts[1:]):
        if x1 != x2 and min(x1, x2) <= x <= max(x1, x2):
            ys.append(y1 + (y2 - y1) * (x - x1) / (x2 - x1))
    return min(ys), max(ys)


def rdp_keep(xs, ys, tol):
    keep, stack = {0, len(xs) - 1}, [(0, len(xs) - 1)]
    while stack:
        a, b = stack.pop()
        if b <= a + 1: continue
        dx, dy = xs[b] - xs[a], ys[b] - ys[a]
        n = (dx * dx + dy * dy) ** 0.5 or 1.0
        far, idx = -1, -1
        for k in range(a + 1, b):
            dd = abs(dy * (xs[k] - xs[a]) - dx * (ys[k] - ys[a])) / n
            if dd > far: far, idx = dd, k
        if far > tol:
            keep.add(idx); stack += [(a, idx), (idx, b)]
    return keep


def read(name):
    root = ET.parse(os.path.join(HERE, 'ribbons', name + '.svg')).getroot()
    w = float(root.get('viewBox').split()[2])
    grads = {}
    for g in root.iter(NS + 'linearGradient'):
        grads[g.get('id')] = {
            'c': [float(g.get(k)) for k in ('x1', 'y1', 'x2', 'y2')],
            's': [[float(s.get('offset')), float(s.get('stop-opacity', 1))] for s in g.iter(NS + 'stop')],
        }
    css = ''.join(s.text or '' for s in root.iter(NS + 'style'))
    cls_grad = dict(re.findall(r'\.([\w-]+)\s*\{\s*fill:\s*url\(#([\w-]+)\)', css))
    bands = []
    for el in root.iter():
        tag = el.tag.replace(NS, '')
        if tag == 'path':
            pts = flatten_path(el.get('d'))
        elif tag == 'polygon':
            nums = [float(v) for v in re.findall(r'-?\d+\.?\d*', el.get('points'))]
            pts = list(zip(nums[0::2], nums[1::2])); pts.append(pts[0])
        else:
            continue
        bands.append((pts, grads[cls_grad[el.get('class')]]))
    # grid: dense, plus every vertex so corners stay sharp
    vx = {round(x, 3) for pts, _ in bands for x, _ in pts if 0.05 < x < w - 0.05}
    grid = sorted(set([round(k * 1.0, 3) for k in range(int(w) + 1)]) | vx | {w})
    gx = [min(max(x, 0.02), w - 0.02) for x in grid]
    out = []
    for pts, grad in bands:
        tb = [scan(pts, x) for x in gx]
        out.append(({'t': [a for a, _ in tb], 'b': [b for _, b in tb]}, grad, sum(a + b for a, b in tb) / len(tb)))
    out.sort(key=lambda o: -o[2])  # lower band first (larger y)
    (lo, glo, _), (up, gup, _) = out
    up['b'] = list(lo['t'])  # the two bands share one edge exactly: no seam, no overlap
    yc = (min(up['t']) + max(lo['b'])) / 2
    keep = sorted(rdp_keep(grid, lo['t'], TOL) | rdp_keep(grid, lo['b'], TOL) | rdp_keep(grid, up['t'], TOL) | rdp_keep(grid, up['b'], TOL))
    r = lambda v: round(v, 1)
    pick = lambda e: {'x': [r(grid[k]) for k in keep], 't': [r(e['t'][k] - yc) for k in keep], 'b': [r(e['b'][k] - yc) for k in keep]}
    shift = lambda g: {'c': [g['c'][0], round(g['c'][1] - yc, 2), g['c'][2], round(g['c'][3] - yc, 2)], 's': g['s']}
    return w, {'lo': pick(lo), 'up': pick(up), 'gl': shift(glo), 'gu': shift(gup)}


def spreads():
    res, w = {}, None
    for name in ('group', 'agency', 'interlink', 'logistics'):
        w, res[name] = read(name)
    return w, res


if __name__ == '__main__':
    w, s = spreads()
    for k, v in s.items():
        print(k, len(v['lo']['x']), 'pts', 'y', min(v['up']['t']), max(v['lo']['b']), 'shared edge gap', max(abs(a - b) for a, b in zip(v['lo']['t'], v['up']['b'])))
