# Extract exact brochure shapes from pdftocairo SVGs and sample them as y(x) edges on a shared grid.
import re, sys, json, math
import xml.etree.ElementTree as ET
SVGDIR = sys.argv[1]; OUT = sys.argv[2]
NS = '{http://www.w3.org/2000/svg}'
PW = 594.551  # page width in pt
_cache = {}
def load(p):
    if p in _cache: return _cache[p]
    s = open(f'{SVGDIR}/p{p}.svg').read()
    s = re.sub(r'xlink:href="data:[^"]*"', 'href="DATA"', s)
    root = ET.fromstring(s)
    clips, fills = {}, []
    def walk(e, glyph):
        tag = e.tag.replace(NS, ''); gid = e.get('id', '')
        if gid.startswith('glyph'): return
        if tag == 'clipPath':
            for c in e: clips[gid] = c.get('d')
            return
        if tag == 'path' and e.get('fill', '').startswith('rgb'): fills.append(e.get('d'))
        for c in e: walk(c, glyph)
    walk(root, False)
    _cache[p] = (clips, fills); return _cache[p]

def parse(d):
    toks = re.findall(r'[MLCZ]|-?\d+\.?\d*(?:e-?\d+)?', d)
    i = 0; cmd = None; subpaths = []; cur = []; pos = None; start = None
    while i < len(toks):
        t = toks[i]
        if t in 'MLCZ':
            cmd = t; i += 1
            if t == 'Z':
                if cur: subpaths.append(cur); cur = []
                pos = start
            continue
        if cmd == 'M':
            pos = (float(toks[i]), float(toks[i+1])); start = pos; i += 2
            if cur: subpaths.append(cur)
            cur = [('M', pos)]; cmd = 'L'
        elif cmd == 'L':
            p = (float(toks[i]), float(toks[i+1])); i += 2; cur.append(('L', p)); pos = p
        elif cmd == 'C':
            a = [float(x) for x in toks[i:i+6]]; i += 6
            cur.append(('C', (a[0], a[1]), (a[2], a[3]), (a[4], a[5]))); pos = (a[4], a[5])
    if cur: subpaths.append(cur)
    # biggest subpath only
    return max(subpaths, key=len)

def flatten(seg_list, steps=96):
    pts = []; vx = set(); pos = None
    for s in seg_list:
        if s[0] == 'M': pos = s[1]; pts.append(pos); vx.add(pos[0])
        elif s[0] == 'L': pos = s[1]; pts.append(pos); vx.add(pos[0])
        else:
            p0, p1, p2, p3 = pos, s[1], s[2], s[3]
            for k in range(1, steps + 1):
                t = k / steps; mt = 1 - t
                x = mt**3*p0[0] + 3*mt*mt*t*p1[0] + 3*mt*t*t*p2[0] + t**3*p3[0]
                y = mt**3*p0[1] + 3*mt*mt*t*p1[1] + 3*mt*t*t*p2[1] + t**3*p3[1]
                pts.append((x, y))
            pos = p3; vx.add(p3[0])
    if pts[0] != pts[-1]: pts.append(pts[0])
    return pts, vx

def scan(pts, x):
    ys = []
    for (x1, y1), (x2, y2) in zip(pts, pts[1:]):
        if x1 == x2: continue
        if min(x1, x2) <= x <= max(x1, x2):
            ys.append(y1 + (y2 - y1) * (x - x1) / (x2 - x1))
    ys.sort()
    out = []
    for y in ys:
        if not out or abs(y - out[-1]) > 0.05: out.append(y)
    return out

SHAPES = {}   # name -> (pts, kind)
VX = set()
def add(name, d, kind):
    pts, vx = flatten(parse(d))
    SHAPES[name] = (pts, kind)
    VX.update(x for x in vx if 0.5 < x < PW - 0.5)

R = {  # ribbons: page -> (bright clip, faint clip)
 'p3': (3, '3', '6'), 'p4': (4, '5', '2'), 'p7': (7, '2', '4'), 'p8': (8, '4', '2'),
 'p11': (11, '5', '7'), 'p12': (12, '5', '3'), 'p17': (17, '3', '5'), 'p18': (18, '4', '2'),
}
for key, (pg, hi, lo) in R.items():
    clips, _ = load(pg)
    add(key + 'hi', clips['clip-' + hi], 'band')
    add(key + 'lo', clips['clip-' + lo], 'band')

def fill_with(pg, needle):
    _, fills = load(pg); target = float(needle)
    best = None
    for d in fills:
        nums = [float(v) for v in re.findall(r'-?\d+\.?\d*', d)]
        if len(nums) > 8 and any(abs(v - target) < 0.15 for v in nums) and max(nums) > 800:
            if best is None or len(d) > len(best): best = d
    if best is None: raise KeyError((pg, needle))
    return best
# shaped colour blocks (top edge of the filled region)
add('b6', fill_with(6, '208.6'), 'top')
add('b10', fill_with(10, '253.2'), 'top')
add('b13', fill_with(13, '363'), 'top')
add('b16', fill_with(16, '246'), 'top')
add('b20', fill_with(20, '516.6'), 'top')
# image caps (top edge of the photo clip)
for pg in (5, 9, 14, 15, 19):
    clips, _ = load(pg); add(f'c{pg}', clips['clip-2'], 'top')
# hero bands
_, f2 = load(2)
add('tb', [d for d in f2 if d.strip().startswith('M 594.55') and '297.' in d][0], 'band')
add('bb', [d for d in f2 if d.strip().startswith('M 594.55') and '629.' in d][0], 'band')

grid = sorted(set([round(i * 2.5, 3) for i in range(int(PW / 2.5) + 1)] + [round(x, 3) for x in VX] + [PW]))
gx = [min(max(x, 0.02), PW - 0.02) for x in grid]

def r(v): return round(v, 1)
out = {'grid': [r(x) for x in grid], 'shapes': {}}
for name, (pts, kind) in SHAPES.items():
    tops, bots = [], []
    for x in gx:
        ys = scan(pts, x)
        if not ys: raise ValueError((name, x))
        tops.append(ys[0]); bots.append(ys[-1] if kind == 'band' else None)
    def slopes(arr):
        return (arr[1] - arr[0]) / (gx[1] - gx[0]), (arr[-1] - arr[-2]) / (gx[-1] - gx[-2])
    e = {'t': [r(y) for y in tops]}
    e['tsl'], e['tsr'] = [round(v, 4) for v in slopes(tops)]
    if kind == 'band':
        e['b'] = [r(y) for y in bots]
        e['bsl'], e['bsr'] = [round(v, 4) for v in slopes(bots)]
    out['shapes'][name] = e
json.dump(out, open(OUT, 'w'), separators=(',', ':'))
print(len(grid), 'grid pts;', {k: (min(v['t']), max(v.get('b', v['t']))) for k, v in out['shapes'].items()})
print({k: (v['tsl'], v['tsr'], v.get('bsl'), v.get('bsr')) for k, v in out['shapes'].items()})
